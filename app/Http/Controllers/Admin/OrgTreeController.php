<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;

class OrgTreeController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $locations = Location::where('status', 'active')->orderBy('location_name')->get();

        // 1. Fetch filtered list of employees
        $query = User::with(['department', 'location', 'position', 'reportingManager']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allEmployees = $query->get();

        // 2. Build recursive tree representation
        // Root nodes are employees who either have no manager OR whose manager is not in the filtered results
        $employeeIds = $allEmployees->pluck('id')->toArray();
        
        $roots = $allEmployees->filter(function ($emp) use ($employeeIds) {
            return is_null($emp->reporting_manager_id) || !in_array($emp->reporting_manager_id, $employeeIds);
        });

        // Parse employee tree recursively
        $tree = $this->buildTree($roots, $allEmployees);

        return view('admin.employees.tree', compact('tree', 'departments', 'locations'));
    }

    /**
     * Recursively build hierarchy map of employees.
     */
    private function buildTree($nodes, $allEmployees)
    {
        $tree = [];
        
        foreach ($nodes as $node) {
            $children = $allEmployees->filter(function ($emp) use ($node) {
                return $emp->reporting_manager_id === $node->id;
            });

            $tree[] = [
                'id' => $node->id,
                'name' => $node->name,
                'email' => $node->email,
                'code' => $node->employee_code,
                'role' => $node->role,
                'avatar' => strtoupper(substr($node->name, 0, 1)),
                'department' => $node->department ? $node->department->department_name : 'No Department',
                'position' => $node->position ? $node->position->position_name : 'No Position',
                'location' => $node->location ? $node->location->location_name : 'No Location',
                'children' => $this->buildTree($children, $allEmployees),
            ];
        }

        return $tree;
    }
}
