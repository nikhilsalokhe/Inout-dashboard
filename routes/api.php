<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\EmployeeSelfServiceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active.employee', 'employee'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register-fcm-token', [AuthController::class, 'registerFcmToken']);
    
    // Attendance
    Route::post('/register-face', [AttendanceController::class, 'registerFace']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance-history', [AttendanceController::class, 'history']);
    
    // Attendance Regularization
    Route::post('/regularization/apply', [AttendanceController::class, 'applyRegularization']);
    Route::get('/regularization/history', [AttendanceController::class, 'regularizationHistory']);
    
    // Face Resets
    Route::post('/face-reset-request', [AttendanceController::class, 'requestFaceReset']);
    Route::get('/face-reset-request/pending', [AttendanceController::class, 'checkPendingFaceReset']);

    // Org Hierarchy
    Route::get('/user/hierarchy', [AttendanceController::class, 'getHierarchy']);

    // Dashboard Status (Employee)
    Route::get('/dashboard-status', [AttendanceController::class, 'getDashboardStatus']);

    // Admin Dashboard Stats
    Route::get('/admin/dashboard-stats', [AttendanceController::class, 'getAdminDashboardStats']);

    // Leave Management (Mobile API)
    Route::get('/leaves/balances', [LeaveController::class, 'getBalances']);
    Route::get('/leaves/history', [LeaveController::class, 'getHistory']);
    Route::post('/leaves/apply', [LeaveController::class, 'apply']);
    Route::get('/leaves/pending-approvals', [LeaveController::class, 'getPendingApprovals']);
    Route::post('/leaves/{id}/action', [LeaveController::class, 'action']);
    Route::get('/leaves/policies', [LeaveController::class, 'getPolicies']);
    Route::get('/leaves/holidays', [LeaveController::class, 'getHolidays']);
    
    // Employee Self Service Mobile APIs
    Route::get('/ess/salary-history', [EmployeeSelfServiceController::class, 'apiSalaryHistory']);
    Route::get('/ess/payslip/{id}', [EmployeeSelfServiceController::class, 'apiPayslipDetails']);
    Route::get('/notifications', [EmployeeSelfServiceController::class, 'apiNotifications']);
    Route::get('/announcements', [EmployeeSelfServiceController::class, 'apiAnnouncements']);
    Route::post('/notifications/{id}/read', [EmployeeSelfServiceController::class, 'apiReadNotification']);
    Route::post('/ess/profile/update', [EmployeeSelfServiceController::class, 'apiUpdateProfile']);
    Route::get('/employee/profile', [EmployeeSelfServiceController::class, 'apiProfile']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
