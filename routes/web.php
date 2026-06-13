<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\AdminFaceResetController;
use App\Http\Controllers\Admin\OrgTreeController;
use App\Http\Controllers\Admin\AdminShiftController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Admin\AdminOrgController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\HRAnalyticsController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\AdminOvertimePolicyController;
use App\Http\Controllers\Admin\AdminOvertimeAssignmentController;
use App\Http\Controllers\Admin\AdminOvertimeDashboardController;
use App\Http\Controllers\Admin\AdminOvertimeRequestController;
use App\Http\Controllers\EmployeeSelfServiceController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Protected Admin Routes
Route::middleware(['auth', 'active.employee', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('admin.dashboard');
    
    // Salary Management
    Route::get('/salary', [SalaryController::class, 'index'])->name('admin.salary.index');
    Route::post('/salary/structure', [SalaryController::class, 'storeStructure'])->name('admin.salary.structure.store');
    Route::post('/salary/structure/{id}/update', [SalaryController::class, 'updateStructure'])->name('admin.salary.structure.update');
    Route::post('/salary/assign', [SalaryController::class, 'assignSalary'])->name('admin.salary.assign');
    Route::post('/salary/revise/{id}', [SalaryController::class, 'reviseSalary'])->name('admin.salary.revise');
    Route::get('/salary/revisions/{employee_id}', [SalaryController::class, 'revisions'])->name('admin.salary.revisions');

    // Payroll Management
    Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('admin.payroll.generate');
    Route::post('/payroll/{id}/update', [PayrollController::class, 'update'])->name('admin.payroll.update');
    Route::post('/payroll/{id}/transition', [PayrollController::class, 'transition'])->name('admin.payroll.transition');
    Route::post('/payroll/bulk-transition', [PayrollController::class, 'bulkTransition'])->name('admin.payroll.bulk-transition');
    Route::get('/payroll/reports', [PayrollController::class, 'reports'])->name('admin.payroll.reports');
    Route::get('/payroll/reports/export/{type}/{format}', [PayrollController::class, 'exportReport'])->name('admin.payroll.reports.export');
    Route::get('/payroll/{id}', [PayrollController::class, 'show'])->name('admin.payroll.show');

    // HR Analytics
    Route::get('/analytics', [HRAnalyticsController::class, 'dashboard'])->name('admin.analytics.dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/attendance-board', [\App\Http\Controllers\Admin\AttendanceBoardController::class, 'index'])->name('admin.attendance.board');
    Route::post('/attendance/manual-update', [\App\Http\Controllers\Admin\AttendanceBoardController::class, 'manualUpdate'])->name('admin.attendance.manual-update');
    Route::get('/reports/export/{format}', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/reports/calendar-events', [ReportController::class, 'calendarEvents'])->name('admin.reports.calendar-events');

    // Employee Management
    Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('admin.employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
    Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit');
    Route::post('/employees/{id}/update', [EmployeeController::class, 'update'])->name('admin.employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

    // Exit / Termination Management
    Route::get('/terminations', [\App\Http\Controllers\Admin\TerminationController::class, 'index'])->name('admin.terminations.index');
    Route::get('/terminations/create/{employee_id}', [\App\Http\Controllers\Admin\TerminationController::class, 'create'])->name('admin.terminations.create');
    Route::post('/terminations', [\App\Http\Controllers\Admin\TerminationController::class, 'store'])->name('admin.terminations.store');
    Route::get('/terminations/reports', [\App\Http\Controllers\Admin\TerminationController::class, 'reports'])->name('admin.terminations.reports');
    Route::get('/terminations/{id}', [\App\Http\Controllers\Admin\TerminationController::class, 'show'])->name('admin.terminations.show');
    Route::post('/terminations/{id}/update', [\App\Http\Controllers\Admin\TerminationController::class, 'update'])->name('admin.terminations.update');
    Route::get('/terminations/{id}/print', [\App\Http\Controllers\Admin\TerminationController::class, 'generateExitSummary'])->name('admin.terminations.print');

    // Organization Management (Departments, Designations, Locations)
    Route::get('/organization', [AdminOrgController::class, 'index'])->name('admin.organization.index');
    Route::post('/organization/departments', [AdminOrgController::class, 'storeDepartment'])->name('admin.organization.departments.store');
    Route::post('/organization/departments/{id}/update', [AdminOrgController::class, 'updateDepartment'])->name('admin.organization.departments.update');
    Route::delete('/organization/departments/{id}', [AdminOrgController::class, 'destroyDepartment'])->name('admin.organization.departments.destroy');
    Route::post('/organization/positions', [AdminOrgController::class, 'storePosition'])->name('admin.organization.positions.store');
    Route::post('/organization/positions/{id}/update', [AdminOrgController::class, 'updatePosition'])->name('admin.organization.positions.update');
    Route::delete('/organization/positions/{id}', [AdminOrgController::class, 'destroyPosition'])->name('admin.organization.positions.destroy');
    Route::post('/organization/locations', [AdminOrgController::class, 'storeLocation'])->name('admin.organization.locations.store');
    Route::post('/organization/locations/{id}/update', [AdminOrgController::class, 'updateLocation'])->name('admin.organization.locations.update');
    Route::delete('/organization/locations/{id}', [AdminOrgController::class, 'destroyLocation'])->name('admin.organization.locations.destroy');
    Route::get('/organization/locations/{id}/show-qr', [AdminOrgController::class, 'showQr'])->name('admin.organization.locations.show-qr');
    Route::get('/organization/locations/{id}/qr', [AdminOrgController::class, 'downloadQr'])->name('admin.organization.locations.qr');

    // Organization Hierarchy Tree
    Route::get('/org-tree', [OrgTreeController::class, 'index'])->name('admin.org-tree');

    // Shift Management
    Route::get('/shifts', [AdminShiftController::class, 'index'])->name('admin.shifts.index');
    Route::get('/shifts/create', [AdminShiftController::class, 'create'])->name('admin.shifts.create');
    Route::post('/shifts', [AdminShiftController::class, 'store'])->name('admin.shifts.store');
    Route::get('/shifts/{id}/edit', [AdminShiftController::class, 'edit'])->name('admin.shifts.edit');
    Route::post('/shifts/{id}/update', [AdminShiftController::class, 'update'])->name('admin.shifts.update');
    Route::post('/shifts/{id}/deactivate', [AdminShiftController::class, 'deactivate'])->name('admin.shifts.deactivate');
    Route::get('/shifts/assign', [AdminShiftController::class, 'assignView'])->name('admin.shifts.assign');
    Route::post('/shifts/assign', [AdminShiftController::class, 'assignStore'])->name('admin.shifts.assign.store');

    // Face Resets Management
    Route::get('/face-resets', [AdminFaceResetController::class, 'index'])->name('admin.face-resets.index');
    Route::get('/face-resets/{id}', [AdminFaceResetController::class, 'show'])->name('admin.face-resets.show');
    Route::post('/face-resets/{id}/approve', [AdminFaceResetController::class, 'approve'])->name('admin.face-resets.approve');
    Route::post('/face-resets/{id}/reject', [AdminFaceResetController::class, 'reject'])->name('admin.face-resets.reject');

    // Overtime Management
    Route::get('/overtime/policies', [AdminOvertimePolicyController::class, 'index'])->name('admin.overtime.policies.index');
    Route::get('/overtime/policies/create', [AdminOvertimePolicyController::class, 'create'])->name('admin.overtime.policies.create');
    Route::post('/overtime/policies', [AdminOvertimePolicyController::class, 'store'])->name('admin.overtime.policies.store');
    Route::get('/overtime/policies/{policy}/edit', [AdminOvertimePolicyController::class, 'edit'])->name('admin.overtime.policies.edit');
    Route::post('/overtime/policies/{policy}/update', [AdminOvertimePolicyController::class, 'update'])->name('admin.overtime.policies.update');
    Route::delete('/overtime/policies/{policy}', [AdminOvertimePolicyController::class, 'destroy'])->name('admin.overtime.policies.destroy');

    Route::get('/overtime/assignments', [AdminOvertimeAssignmentController::class, 'index'])->name('admin.overtime.assignments.index');
    Route::post('/overtime/assignments', [AdminOvertimeAssignmentController::class, 'store'])->name('admin.overtime.assignments.store');
    Route::delete('/overtime/assignments/{assignment}', [AdminOvertimeAssignmentController::class, 'destroy'])->name('admin.overtime.assignments.destroy');

    // Overtime Dashboard & Approvals
    Route::get('/overtime/dashboard', [AdminOvertimeDashboardController::class, 'index'])->name('admin.overtime.dashboard');
    Route::get('/overtime/requests', [AdminOvertimeRequestController::class, 'index'])->name('admin.overtime.requests.index');
    Route::post('/overtime/requests/{record}/manager-approve', [AdminOvertimeRequestController::class, 'managerApprove'])->name('admin.overtime.requests.manager-approve');
    Route::post('/overtime/requests/{record}/hr-approve', [AdminOvertimeRequestController::class, 'hrApprove'])->name('admin.overtime.requests.hr-approve');
    Route::post('/overtime/requests/{record}/reject', [AdminOvertimeRequestController::class, 'reject'])->name('admin.overtime.requests.reject');
    Route::post('/overtime/requests/bulk-approve', [AdminOvertimeRequestController::class, 'bulkApprove'])->name('admin.overtime.requests.bulk-approve');

    // Settings Management
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');

    // Audit Logs
    Route::get('/audit-logs', [AdminSettingController::class, 'auditLogsIndex'])->name('admin.audit-logs.index');

    // Attendance Regularizations
    Route::get('/regularizations', [\App\Http\Controllers\Admin\AttendanceRegularizationController::class, 'index'])->name('admin.regularizations.index');
    Route::post('/regularizations', [\App\Http\Controllers\Admin\AttendanceRegularizationController::class, 'store'])->name('admin.regularizations.store');
    Route::post('/regularizations/{id}/update', [\App\Http\Controllers\Admin\AttendanceRegularizationController::class, 'update'])->name('admin.regularizations.update');
    Route::post('/regularizations/{id}/action', [\App\Http\Controllers\Admin\AttendanceRegularizationController::class, 'action'])->name('admin.regularizations.action');

    // Leave Management
    Route::get('/leaves/policies', [AdminLeaveController::class, 'policiesIndex'])->name('admin.leaves.policies');
    Route::post('/leaves/policies', [AdminLeaveController::class, 'policiesStore'])->name('admin.leaves.policies.store');
    Route::post('/leaves/policies/{id}/update', [AdminLeaveController::class, 'policiesUpdate'])->name('admin.leaves.policies.update');
    Route::get('/leaves/balances', [AdminLeaveController::class, 'balancesIndex'])->name('admin.leaves.balances');
    Route::get('/leaves/applications', [AdminLeaveController::class, 'applicationsIndex'])->name('admin.leaves.applications');
    Route::post('/leaves/applications/{id}/approve', [AdminLeaveController::class, 'approveApplication'])->name('admin.leaves.applications.approve');
    Route::post('/leaves/applications/{id}/reject', [AdminLeaveController::class, 'rejectApplication'])->name('admin.leaves.applications.reject');

    // Holiday Management
    Route::get('/holidays', [HolidayController::class, 'index'])->name('admin.holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('admin.holidays.store');
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->name('admin.holidays.destroy');

    // Announcements Management
    Route::get('/announcements', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'index'])->name('admin.announcements.index');
    Route::get('/announcements/create', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'create'])->name('admin.announcements.create');
    Route::post('/announcements', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::delete('/announcements/{id}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
});

// Protected Employee Self Service Routes
Route::middleware(['auth', 'active.employee', 'employee'])->prefix('employee')->group(function () {
    Route::get('/dashboard', [EmployeeSelfServiceController::class, 'dashboard'])->name('employee.dashboard');
    Route::get('/payslips', [EmployeeSelfServiceController::class, 'payslips'])->name('employee.payslips');
    Route::get('/payslips/{id}', [EmployeeSelfServiceController::class, 'payslip'])->name('employee.payslip.show');
    Route::get('/leaves', [EmployeeSelfServiceController::class, 'leaves'])->name('employee.leaves');
    Route::post('/leaves/apply', [EmployeeSelfServiceController::class, 'applyLeave'])->name('employee.leaves.apply');
});
