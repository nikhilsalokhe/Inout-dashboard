@extends('layouts.admin')

@section('title', 'Salary & Structure Management')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Create salary templates, assign pay packages to employees, and track revisions.</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="toggleModal('assignSalaryModal')" class="px-5 py-3 bg-white hover:bg-slate-50 text-indigo-600 font-extrabold rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
            <i class="bi bi-person-plus text-sm"></i>
            <span class="text-sm">Assign Salary Package</span>
        </button>
        <button onclick="toggleModal('createStructureModal')" class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
            <i class="bi bi-plus-lg text-sm"></i>
            <span class="text-sm">Create Salary Structure</span>
        </button>
    </div>
</div>

<!-- Salary Structures Grid -->
<div class="mb-10">
    <div class="flex items-center gap-2 mb-6">
        <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
        <h3 class="font-bold text-slate-800 text-base">Salary Structures (Templates)</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($structures as $structure)
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col relative group">
                <!-- Status Badge -->
                <div class="absolute top-6 right-6">
                    @if($structure->status === 'active')
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[10px] font-extrabold border border-emerald-100 uppercase tracking-wider">Active</span>
                    @else
                        <span class="px-2.5 py-1 bg-slate-50 text-slate-400 rounded-lg text-[10px] font-extrabold border border-slate-100 uppercase tracking-wider">Inactive</span>
                    @endif
                </div>

                <div class="p-6 flex-1">
                    <!-- Icon & Name -->
                    <div class="flex items-center gap-4.5 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500 text-xl font-bold">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-base leading-tight mb-1">{{ $structure->structure_name }}</h4>
                            <span class="text-[10px] font-extrabold text-indigo-500 uppercase tracking-widest bg-indigo-50/50 border border-indigo-100/30 px-2 py-0.5 rounded-md">
                                Template ID: {{ $structure->id }}
                            </span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-3 border-t border-slate-100 pt-5 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Basic Salary</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $structure->basic_percentage }}% of Gross</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">HRA Portion</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $structure->hra_percentage }}% of Gross</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">DA Portion</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $structure->da_percentage }}% of Gross</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Travel Allowance</span>
                            <span class="text-slate-700 font-bold text-xs">Rs. {{ number_format($structure->travel_allowance, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">PF Contribution</span>
                            <span class="text-xs font-bold {{ $structure->pf_enabled ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $structure->pf_enabled ? 'Enabled (12% of Basic)' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">ESIC Contribution</span>
                            <span class="text-xs font-bold {{ $structure->esic_enabled ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $structure->esic_enabled ? 'Enabled (0.75% of Gross)' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Professional Tax</span>
                            <span class="text-slate-700 font-bold text-xs">Rs. {{ number_format($structure->professional_tax, 2) }}/mo</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Actions -->
                <div class="px-6 py-4.5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end gap-2 group-hover:bg-slate-50 transition-colors">
                    <button onclick="openEditStructureModal({{ json_encode($structure) }})" class="px-3.5 py-2 rounded-xl bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-100 text-slate-500 hover:text-indigo-600 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                        <i class="bi bi-pencil-square"></i>
                        <span>Configure Template</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl border border-slate-200/60 p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100 mx-auto mb-4">
                    <i class="bi bi-wallet2"></i>
                </div>
                <h5 class="font-bold text-slate-800 text-base mb-1">No Salary Structures Configured</h5>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4 leading-relaxed font-semibold">There are currently no salary structures configured. Please create a salary structure template to proceed with assignments.</p>
                <button onclick="toggleModal('createStructureModal')" class="px-4 py-2 bg-indigo-50 border border-indigo-100 text-indigo-600 text-xs font-bold rounded-xl hover:bg-indigo-100 transition-colors">
                    Add Salary Structure
                </button>
            </div>
        @endforelse
    </div>
</div>

<!-- Active Employee Salary Allocations List -->
<div>
    <div class="flex items-center gap-2 mb-6">
        <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
        <h3 class="font-bold text-slate-800 text-base">Employee Salary Allocations</h3>
    </div>

    <!-- Table Card container -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                    <tr>
                        <th class="px-8 py-5">Employee</th>
                        <th class="px-8 py-5">Salary Template</th>
                        <th class="px-8 py-5">Gross Salary</th>
                        <th class="px-8 py-5">Effective From</th>
                        <th class="px-8 py-5">Status</th>
                        <th class="px-8 py-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employeeSalaries as $salary)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-8 py-4.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-indigo-600">
                                        {{ strtoupper(substr($salary->employee->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-xs leading-tight mb-0.5">{{ $salary->employee->name }}</h4>
                                        <p class="text-[9px] text-slate-400 font-medium">Code: {{ $salary->employee->employee_code ?? '-' }} • Dept: {{ $salary->employee->department->department_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-4.5">
                                <span class="font-bold text-slate-700 text-xs">{{ $salary->salaryStructure->structure_name }}</span>
                            </td>
                            <td class="px-8 py-4.5 text-xs text-slate-800 font-bold">
                                Rs. {{ number_format($salary->gross_salary, 2) }}
                            </td>
                            <td class="px-8 py-4.5 text-xs text-slate-500 font-bold">
                                {{ $salary->effective_from->format('M d, Y') }}
                            </td>
                            <td class="px-8 py-4.5">
                                @if($salary->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[10px] font-extrabold bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-slate-400 text-[10px] font-extrabold bg-slate-50 border border-slate-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-8 py-4.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="openReviseModal({{ json_encode($salary) }})" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold transition-all flex items-center gap-1">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span>Revise</span>
                                    </button>
                                    <button onclick="viewRevisions({{ $salary->employee_id }})" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all flex items-center gap-1">
                                        <i class="bi bi-clock-history"></i>
                                        <span>History</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-lg border border-slate-100 mx-auto mb-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h6 class="font-bold text-slate-700 text-xs mb-0.5">No employee salary allocations assigned</h6>
                                <p class="text-[10px] text-slate-400 font-semibold">Assign employees to salary packages to start processing their monthly payroll.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employeeSalaries->hasPages())
            <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                {{ $employeeSalaries->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- 1. Create Structure Modal -->
<div id="createStructureModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('admin.salary.structure.store') }}" method="POST">
                @csrf
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Create Salary Structure Template</h3>
                </div>
                <div class="px-8 py-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Structure Name</label>
                        <input type="text" name="structure_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800" placeholder="e.g. Executive Corporate Template">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Basic (%)</label>
                            <input type="number" name="basic_percentage" step="0.01" required value="50.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">HRA (%)</label>
                            <input type="number" name="hra_percentage" step="0.01" required value="20.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">DA (%)</label>
                            <input type="number" name="da_percentage" step="0.01" required value="10.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Travel Allowance (Rs.)</label>
                            <input type="number" name="travel_allowance" step="1" required value="1600" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Professional Tax (Rs.)</label>
                            <input type="number" name="professional_tax" step="1" required value="200" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 pt-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="pf_enabled" id="pf_enabled" value="1" checked class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <label for="pf_enabled" class="text-slate-700 text-xs font-bold uppercase tracking-wide">Enable Provident Fund (PF) (12% of Basic)</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="esic_enabled" id="esic_enabled" value="1" checked class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <label for="esic_enabled" class="text-slate-700 text-xs font-bold uppercase tracking-wide">Enable ESIC Deduction (0.75% of Gross)</label>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('createStructureModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all">Create Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Edit Structure Modal -->
<div id="editStructureModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="editStructureForm" method="POST">
                @csrf
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Configure Salary Structure</h3>
                </div>
                <div class="px-8 py-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Structure Name</label>
                        <input type="text" name="structure_name" id="edit_structure_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Basic (%)</label>
                            <input type="number" name="basic_percentage" id="edit_basic_percentage" step="0.01" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">HRA (%)</label>
                            <input type="number" name="hra_percentage" id="edit_hra_percentage" step="0.01" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">DA (%)</label>
                            <input type="number" name="da_percentage" id="edit_da_percentage" step="0.01" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Travel Allowance (Rs.)</label>
                            <input type="number" name="travel_allowance" id="edit_travel_allowance" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Professional Tax (Rs.)</label>
                            <input type="number" name="professional_tax" id="edit_professional_tax" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Status</label>
                            <select name="status" id="edit_status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 pt-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="pf_enabled" id="edit_pf_enabled" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <label for="edit_pf_enabled" class="text-slate-700 text-xs font-bold uppercase tracking-wide">Enable Provident Fund (PF) (12% of Basic)</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="esic_enabled" id="edit_esic_enabled" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <label for="edit_esic_enabled" class="text-slate-700 text-xs font-bold uppercase tracking-wide">Enable ESIC Deduction (0.75% of Gross)</label>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('editStructureModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Assign Salary Modal -->
<div id="assignSalaryModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('admin.salary.assign') }}" method="POST">
                @csrf
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Assign Salary Package</h3>
                </div>
                <div class="px-8 py-6 space-y-4">
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Select Employee</label>
                        <select name="employee_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                            <option value="">-- Choose Employee --</option>
                            @foreach($employeesWithoutSalary as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} (Code: {{ $emp->employee_code ?? '-' }})</option>
                            @endforeach
                            @if(count($employeesWithoutSalary) == 0)
                                <option disabled>No employees without active salaries found</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Salary Structure Template</label>
                        <select name="salary_structure_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                            @foreach($structures as $structure)
                                @if($structure->status === 'active')
                                    <option value="{{ $structure->id }}">{{ $structure->structure_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Gross Salary (Rs. Per Month)</label>
                        <input type="number" name="gross_salary" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800" placeholder="e.g. 45000">
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Effective From</label>
                        <input type="date" name="effective_from" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                    </div>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('assignSalaryModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all">Assign Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Revise Salary Modal -->
<div id="reviseSalaryModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="reviseSalaryForm" method="POST">
                @csrf
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Revise Salary (Increment)</h3>
                </div>
                <div class="px-8 py-6 space-y-4">
                    <div>
                        <span class="text-slate-400 font-bold text-[10px] uppercase tracking-wide block mb-1">Employee</span>
                        <span id="revise_employee_name" class="font-extrabold text-slate-800 text-sm">N/A</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-slate-400 font-bold text-[10px] uppercase tracking-wide block mb-1">Current Gross Salary</span>
                            <span id="revise_current_gross" class="font-extrabold text-slate-800 text-sm">Rs. 0.00</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold text-[10px] uppercase tracking-wide block mb-1">Current Structure</span>
                            <span id="revise_current_structure" class="font-bold text-slate-800 text-sm">N/A</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">New Gross Salary (Rs.)</label>
                        <input type="number" name="new_gross_salary" id="revise_new_gross" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">New Salary Structure Template</label>
                        <select name="new_structure_id" id="revise_new_structure_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                            @foreach($structures as $structure)
                                @if($structure->status === 'active')
                                    <option value="{{ $structure->id }}">{{ $structure->structure_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Effective Date</label>
                        <input type="date" name="effective_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Remarks / Reason</label>
                        <textarea name="remarks" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800" placeholder="e.g. Annual Appraisal / Promotion"></textarea>
                    </div>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('reviseSalaryModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all">Save Revision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. Salary Revision History Modal -->
<div id="historyModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-base font-extrabold text-slate-900">Salary Revision Log History</h3>
                <button onclick="toggleModal('historyModal')" class="text-slate-400 hover:text-slate-600 font-extrabold text-lg">✕</button>
            </div>
            <div class="px-8 py-6 space-y-4 max-h-[60vh] overflow-y-auto">
                <div class="mb-4">
                    <span class="text-xs text-slate-400 font-bold block mb-1">Employee Profile</span>
                    <h4 id="history_employee_name" class="font-extrabold text-slate-800 text-sm">N/A</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider">
                                <th class="px-4 py-3">Effective Date</th>
                                <th class="px-4 py-3">Previous Gross</th>
                                <th class="px-4 py-3">New Gross</th>
                                <th class="px-4 py-3">Structure details</th>
                                <th class="px-4 py-3">Revised By</th>
                                <th class="px-4 py-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="history_table_body" class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
                <button type="button" onclick="toggleModal('historyModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    function openEditStructureModal(structure) {
        document.getElementById('editStructureForm').action = '/admin/salary/structure/' + structure.id + '/update';
        document.getElementById('edit_structure_name').value = structure.structure_name;
        document.getElementById('edit_basic_percentage').value = structure.basic_percentage;
        document.getElementById('edit_hra_percentage').value = structure.hra_percentage;
        document.getElementById('edit_da_percentage').value = structure.da_percentage;
        document.getElementById('edit_travel_allowance').value = structure.travel_allowance;
        document.getElementById('edit_professional_tax').value = structure.professional_tax;
        document.getElementById('edit_status').value = structure.status;
        document.getElementById('edit_pf_enabled').checked = !!structure.pf_enabled;
        document.getElementById('edit_esic_enabled').checked = !!structure.esic_enabled;
        
        toggleModal('editStructureModal');
    }

    function openReviseModal(salary) {
        document.getElementById('reviseSalaryForm').action = '/admin/salary/revise/' + salary.id;
        document.getElementById('revise_employee_name').innerText = salary.employee.name + ' (Code: ' + (salary.employee.employee_code || 'N/A') + ')';
        document.getElementById('revise_current_gross').innerText = 'Rs. ' + parseFloat(salary.gross_salary).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('revise_current_structure').innerText = salary.salary_structure.structure_name;
        document.getElementById('revise_new_gross').value = salary.gross_salary;
        document.getElementById('revise_new_structure_id').value = salary.salary_structure_id;
        
        toggleModal('reviseSalaryModal');
    }

    function viewRevisions(employeeId) {
        const body = document.getElementById('history_table_body');
        body.innerHTML = '<tr><td colspan="6" class="text-center py-4 font-semibold text-slate-400">Loading history...</td></tr>';
        toggleModal('historyModal');

        fetch('/admin/salary/revisions/' + employeeId)
            .then(res => res.json())
            .then(data => {
                document.getElementById('history_employee_name').innerText = data.employee.name + ' (Code: ' + (data.employee.employee_code || '-') + ')';
                body.innerHTML = '';
                
                if (data.revisions.length === 0) {
                    body.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-slate-400">No revisions logged for this employee.</td></tr>';
                    return;
                }

                data.revisions.forEach(rev => {
                    const row = document.createElement('tr');
                    const d = new Date(rev.effective_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
                    row.innerHTML = `
                        <td class="px-4 py-3 font-bold">${d}</td>
                        <td class="px-4 py-3 text-slate-500">Rs. ${parseFloat(rev.previous_gross_salary).toFixed(2)}</td>
                        <td class="px-4 py-3 text-emerald-600 font-extrabold">Rs. ${parseFloat(rev.new_gross_salary).toFixed(2)}</td>
                        <td class="px-4 py-3">${rev.new_structure?.structure_name || 'N/A'}</td>
                        <td class="px-4 py-3">${rev.revised_by?.name || 'System'}</td>
                        <td class="px-4 py-3 text-slate-400 font-medium">${rev.remarks || '-'}</td>
                    `;
                    body.appendChild(row);
                });
            })
            .catch(err => {
                body.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-rose-500">Error loading revision history.</td></tr>';
            });
    }
</script>
@endsection
