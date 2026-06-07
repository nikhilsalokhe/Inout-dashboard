<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\Shift;
use App\Models\SalaryStructure;
use App\Models\ShiftAssignment;
use App\Models\EmployeeSalary;
use App\Models\EmployeeContract;
use App\Models\SalaryRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Eager load relations to optimize queries
        $query = User::with(['department', 'location', 'position', 'reportingManager'])
            ->where('role', 'employee');

        // Apply filters
        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->employee_type);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        $employees = $query->latest()->paginate(10);
            
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $locations = Location::where('status', 'active')->orderBy('location_name')->get();
        $positions = Position::where('status', 'active')->orderBy('position_name')->get();
        
        // Reporting managers can be any user in the system (including admins/managers)
        $managers = User::orderBy('name')->get();

        // Active Shifts and Salary Structures
        $shifts = Shift::where('status', 'active')->orderBy('shift_name')->get();
        $salaryStructures = SalaryStructure::where('status', 'active')->orderBy('structure_name')->get();

        return view('admin.employees.create', compact('departments', 'locations', 'positions', 'managers', 'shifts', 'salaryStructures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'employee_code' => 'required|string|max:50|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'position_id' => 'nullable|exists:positions,id',
            'reporting_manager_id' => 'nullable|exists:users,id',
            'employee_type' => 'required|string|in:permanent,contract,temporary,trainee',
            'joining_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after_or_equal:joining_date',
            'contract_start_date' => 'required_if:employee_type,contract|nullable|date',
            'contract_end_date' => 'required_if:employee_type,contract|nullable|date|after_or_equal:contract_start_date',
            'permitted_locations' => 'nullable|array',
            'permitted_locations.*' => 'exists:locations,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'salary_structure_id' => 'nullable|exists:salary_structures,id',
            'gross_salary' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $employee = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
                'role' => 'employee',
                'employee_type' => $request->employee_type,
                'employment_status' => 'active',
                'joining_date' => $request->joining_date,
                'probation_end_date' => $request->employee_type === 'permanent' ? $request->probation_end_date : null,
                'contract_start_date' => $request->employee_type === 'contract' ? $request->contract_start_date : null,
                'contract_end_date' => $request->employee_type === 'contract' ? $request->contract_end_date : null,
                'employee_code' => $request->employee_code,
                'department_id' => $request->department_id,
                'location_id' => $request->location_id,
                'position_id' => $request->position_id,
                'reporting_manager_id' => $request->reporting_manager_id,
                'status' => 'active',
            ]);

            if ($request->has('permitted_locations')) {
                $employee->permittedLocations()->sync($request->permitted_locations);
            }

            // If employee is under contract, create EmployeeContract record
            if ($employee->employee_type === 'contract') {
                EmployeeContract::create([
                    'employee_id' => $employee->id,
                    'contract_start_date' => $request->contract_start_date,
                    'contract_end_date' => $request->contract_end_date,
                    'renewal_option' => $request->boolean('contract_renewal_option'),
                    'contract_status' => 'active',
                ]);
            }

            // Shift assignment
            if ($request->filled('shift_id')) {
                ShiftAssignment::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $request->shift_id,
                    'effective_from' => $request->joining_date,
                ]);
            }

            // Salary Structure allocation
            if ($request->filled('salary_structure_id') && $request->filled('gross_salary')) {
                EmployeeSalary::create([
                    'employee_id' => $employee->id,
                    'salary_structure_id' => $request->salary_structure_id,
                    'gross_salary' => $request->gross_salary,
                    'effective_from' => $request->joining_date,
                    'status' => 'active',
                ]);
            }
        });

        return redirect()->route('admin.employees.index')->with('success', 'Employee added successfully.');
    }

    public function edit($id)
    {
        $employee = User::with(['permittedLocations', 'employeeSalary', 'contracts' => function($q) {
            $q->where('contract_status', 'active');
        }])->findOrFail($id);
        
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $locations = Location::where('status', 'active')->orderBy('location_name')->get();
        $positions = Position::where('status', 'active')->orderBy('position_name')->get();
        
        // Reporting managers list excluding the employee themselves to prevent circular references
        $managers = User::where('id', '!=', $id)->orderBy('name')->get();

        // Shifts and Salary Structures
        $shifts = Shift::where('status', 'active')->orderBy('shift_name')->get();
        $salaryStructures = SalaryStructure::where('status', 'active')->orderBy('structure_name')->get();

        // Get currently assigned active shift
        $activeShift = ShiftAssignment::where('employee_id', $id)
            ->whereNull('effective_to')
            ->first();

        return view('admin.employees.edit', compact('employee', 'departments', 'locations', 'positions', 'managers', 'shifts', 'salaryStructures', 'activeShift'));
    }

    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'mobile' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'employee_code' => 'required|string|max:50|unique:users,employee_code,' . $id,
            'department_id' => 'nullable|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'position_id' => 'nullable|exists:positions,id',
            'reporting_manager_id' => 'nullable|exists:users,id|different:id',
            'employee_type' => 'required|string|in:permanent,contract,temporary,trainee',
            'joining_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after_or_equal:joining_date',
            'contract_start_date' => 'required_if:employee_type,contract|nullable|date',
            'contract_end_date' => 'required_if:employee_type,contract|nullable|date|after_or_equal:contract_start_date',
            'status' => 'required|string|in:active,inactive',
            'permitted_locations' => 'nullable|array',
            'permitted_locations.*' => 'exists:locations,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'salary_structure_id' => 'nullable|exists:salary_structures,id',
            'gross_salary' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $employee) {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'employee_code' => $request->employee_code,
                'department_id' => $request->department_id,
                'location_id' => $request->location_id,
                'position_id' => $request->position_id,
                'reporting_manager_id' => $request->reporting_manager_id,
                'employee_type' => $request->employee_type,
                'joining_date' => $request->joining_date,
                'probation_end_date' => $request->employee_type === 'permanent' ? $request->probation_end_date : null,
                'contract_start_date' => $request->employee_type === 'contract' ? $request->contract_start_date : null,
                'contract_end_date' => $request->employee_type === 'contract' ? $request->contract_end_date : null,
                'status' => $request->status,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->has('reset_device_binding') && $request->reset_device_binding == 1) {
                $data['device_id'] = null;
            }

            $employee->update($data);

            $permittedLocations = $request->input('permitted_locations', []);
            $employee->permittedLocations()->sync($permittedLocations);

            // Handle contract updates
            if ($employee->employee_type === 'contract') {
                EmployeeContract::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'contract_status' => 'active'
                    ],
                    [
                        'contract_start_date' => $request->contract_start_date,
                        'contract_end_date' => $request->contract_end_date,
                        'renewal_option' => $request->boolean('contract_renewal_option'),
                    ]
                );
            } else {
                // If converted away from contract, terminate active contract
                EmployeeContract::where('employee_id', $employee->id)
                    ->where('contract_status', 'active')
                    ->update(['contract_status' => 'terminated']);
            }

            // Shift assignment change check
            if ($request->filled('shift_id')) {
                $currentShift = ShiftAssignment::where('employee_id', $employee->id)
                    ->whereNull('effective_to')
                    ->first();
                
                if (!$currentShift || $currentShift->shift_id != $request->shift_id) {
                    if ($currentShift) {
                        $currentShift->update(['effective_to' => now()->subDay()->toDateString()]);
                    }
                    ShiftAssignment::create([
                        'employee_id' => $employee->id,
                        'shift_id' => $request->shift_id,
                        'effective_from' => now()->toDateString(),
                    ]);
                }
            }

            // Salary structure assignment change check
            if ($request->filled('salary_structure_id') && $request->filled('gross_salary')) {
                $currentSalary = EmployeeSalary::where('employee_id', $employee->id)
                    ->where('status', 'active')
                    ->first();
                    
                if (!$currentSalary || $currentSalary->salary_structure_id != $request->salary_structure_id || $currentSalary->gross_salary != $request->gross_salary) {
                    if ($currentSalary) {
                        $currentSalary->update(['status' => 'inactive']);
                        
                        SalaryRevision::create([
                            'employee_id' => $employee->id,
                            'previous_gross_salary' => $currentSalary->gross_salary,
                            'new_gross_salary' => $request->gross_salary,
                            'previous_structure_id' => $currentSalary->salary_structure_id,
                            'new_structure_id' => $request->salary_structure_id,
                            'revised_by' => auth()->id(),
                            'effective_date' => now()->toDateString(),
                            'remarks' => 'Updated via Employee Profile edit',
                        ]);
                    }
                    
                    EmployeeSalary::create([
                        'employee_id' => $employee->id,
                        'salary_structure_id' => $request->salary_structure_id,
                        'gross_salary' => $request->gross_salary,
                        'effective_from' => now()->toDateString(),
                        'status' => 'active',
                    ]);
                }
            }
        });

        return redirect()->route('admin.employees.index')->with('success', 'Employee credentials and organizational profile updated successfully.');
    }

    public function destroy($id)
    {
        // For backwards compatibility but recommend exit management
        $employee = User::findOrFail($id);
        $employee->delete();

        return redirect()->route('admin.employees.index')->with('success', 'Employee deleted successfully.');
    }
}
