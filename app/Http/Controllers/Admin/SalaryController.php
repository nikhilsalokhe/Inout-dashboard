<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\EmployeeSalary;
use App\Models\SalaryRevision;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalaryController extends Controller
{
    /**
     * Display salary structures and active employee salaries.
     */
    public function index()
    {
        $structures = SalaryStructure::all();
        $employeeSalaries = EmployeeSalary::with(['employee.department', 'salaryStructure'])
            ->orderBy('id', 'desc')
            ->paginate(15);
        $employeesWithoutSalary = User::where('role', 'employee')
            ->where('status', 'active')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_salaries')
                    ->whereColumn('employee_salaries.employee_id', 'users.id')
                    ->where('employee_salaries.status', 'active');
            })
            ->get();

        return view('admin.salary.index', compact('structures', 'employeeSalaries', 'employeesWithoutSalary'));
    }

    /**
     * Store a new salary structure template.
     */
    public function storeStructure(Request $request)
    {
        $validated = $request->validate([
            'structure_name' => 'required|string|max:100',
            'basic_percentage' => 'required|numeric|between:0,100',
            'hra_percentage' => 'required|numeric|between:0,100',
            'da_percentage' => 'required|numeric|between:0,100',
            'travel_allowance' => 'required|numeric|min:0',
            'pf_enabled' => 'nullable|boolean',
            'esic_enabled' => 'nullable|boolean',
            'professional_tax' => 'required|numeric|min:0',
        ]);

        $validated['pf_enabled'] = $request->has('pf_enabled') ? 1 : 0;
        $validated['esic_enabled'] = $request->has('esic_enabled') ? 1 : 0;
        $validated['status'] = 'active';

        $structure = SalaryStructure::create($validated);

        // Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'salary',
            'action' => 'create_structure',
            'new_data' => $structure->toArray(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Salary Structure template created successfully.');
    }

    /**
     * Update an existing salary structure template.
     */
    public function updateStructure(Request $request, $id)
    {
        $structure = SalaryStructure::findOrFail($id);

        $validated = $request->validate([
            'structure_name' => 'required|string|max:100',
            'basic_percentage' => 'required|numeric|between:0,100',
            'hra_percentage' => 'required|numeric|between:0,100',
            'da_percentage' => 'required|numeric|between:0,100',
            'travel_allowance' => 'required|numeric|min:0',
            'pf_enabled' => 'nullable|boolean',
            'esic_enabled' => 'nullable|boolean',
            'professional_tax' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $oldData = $structure->toArray();
        $validated['pf_enabled'] = $request->has('pf_enabled') ? 1 : 0;
        $validated['esic_enabled'] = $request->has('esic_enabled') ? 1 : 0;

        $structure->update($validated);

        // Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'salary',
            'action' => 'update_structure',
            'old_data' => $oldData,
            'new_data' => $structure->toArray(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Salary Structure template updated successfully.');
    }

    /**
     * Assign salary structure to an employee.
     */
    public function assignSalary(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'salary_structure_id' => 'required|exists:salary_structures,id',
            'gross_salary' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Deactivate previous active salary if exists
            EmployeeSalary::where('employee_id', $validated['employee_id'])
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            $newSalary = EmployeeSalary::create([
                'employee_id' => $validated['employee_id'],
                'salary_structure_id' => $validated['salary_structure_id'],
                'gross_salary' => $validated['gross_salary'],
                'effective_from' => $validated['effective_from'],
                'status' => 'active',
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'module' => 'salary',
                'action' => 'assign_salary',
                'new_data' => $newSalary->toArray(),
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);

            // Create notification for employee
            \App\Models\Notification::create([
                'user_id' => $validated['employee_id'],
                'title' => 'Salary Structure Assigned',
                'description' => 'A new gross salary structure of Rs. ' . number_format($validated['gross_salary'], 2) . ' has been assigned to you, effective from ' . $validated['effective_from'],
                'type' => 'salary',
            ]);
        });

        return redirect()->back()->with('success', 'Salary structure assigned to employee successfully.');
    }

    /**
     * Revise gross salary of an employee.
     */
    public function reviseSalary(Request $request, $id)
    {
        $currentSalary = EmployeeSalary::findOrFail($id);

        $validated = $request->validate([
            'new_gross_salary' => 'required|numeric|min:0',
            'new_structure_id' => 'required|exists:salary_structures,id',
            'effective_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($currentSalary, $validated, $request) {
            // Log Revision History
            $revision = SalaryRevision::create([
                'employee_id' => $currentSalary->employee_id,
                'previous_gross_salary' => $currentSalary->gross_salary,
                'new_gross_salary' => $validated['new_gross_salary'],
                'previous_structure_id' => $currentSalary->salary_structure_id,
                'new_structure_id' => $validated['new_structure_id'],
                'revised_by' => auth()->id(),
                'effective_date' => $validated['effective_date'],
                'remarks' => $validated['remarks'],
            ]);

            // Deactivate current active salary
            $currentSalary->update(['status' => 'inactive']);

            // Create new active salary record
            $newSalary = EmployeeSalary::create([
                'employee_id' => $currentSalary->employee_id,
                'salary_structure_id' => $validated['new_structure_id'],
                'gross_salary' => $validated['new_gross_salary'],
                'effective_from' => $validated['effective_date'],
                'status' => 'active',
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'module' => 'salary',
                'action' => 'revise_salary',
                'old_data' => $currentSalary->toArray(),
                'new_data' => $newSalary->toArray(),
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);

            // Create notification for employee
            \App\Models\Notification::create([
                'user_id' => $currentSalary->employee_id,
                'title' => 'Salary Increment / Revision Approved',
                'description' => 'Your salary has been revised to Rs. ' . number_format($validated['new_gross_salary'], 2) . ' effective from ' . $validated['effective_date'],
                'type' => 'salary',
            ]);
        });

        return redirect()->back()->with('success', 'Salary revised successfully.');
    }

    /**
     * Show salary revision history.
     */
    public function revisions($employee_id)
    {
        $employee = User::findOrFail($employee_id);
        $revisions = SalaryRevision::where('employee_id', $employee_id)
            ->with(['previousStructure', 'newStructure', 'revisedBy'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'employee' => $employee,
            'revisions' => $revisions,
        ]);
    }
}
