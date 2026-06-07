<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'employee_type',
        'employment_status',
        'joining_date',
        'probation_end_date',
        'contract_start_date',
        'contract_end_date',
        'employee_code',
        'department_id',
        'location_id',
        'position_id',
        'reporting_manager_id',
        'face_image',
        'face_encoding',
        'status',
        'device_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'face_encoding' => 'array',
        'joining_date' => 'date',
        'probation_end_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the department of the employee.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the location of the employee.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the position/designation of the employee.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the reporting manager of the employee.
     */
    public function reportingManager()
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    /**
     * Get the subordinates reporting to this user.
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'reporting_manager_id');
    }

    /**
     * Get the face reset requests submitted by the user.
     */
    public function faceResetRequests()
    {
        return $this->hasMany(FaceResetRequest::class, 'employee_id');
    }

    /**
     * Get the face resets approved by the user (as an admin).
     */
    public function approvedFaceResets()
    {
        return $this->hasMany(FaceResetRequest::class, 'approved_by');
    }

    /**
     * Get all shift assignments for the employee.
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class, 'employee_id');
    }

    /**
     * Get leave balances for the employee.
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
    }

    /**
     * Get leave applications for the employee.
     */
    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'employee_id');
    }

    /**
     * Get leaves approved by this manager.
     */
    public function approvedLeaves()
    {
        return $this->hasMany(LeaveApplication::class, 'approved_by');
    }

    /**
     * Get permitted check-in locations for the employee.
     */
    public function permittedLocations()
    {
        return $this->belongsToMany(Location::class, 'employee_geo_locations', 'employee_id', 'geo_location_id')->withTimestamps();
    }

    /**
     * Get the active salary structure and gross salary for the employee.
     */
    public function employeeSalary()
    {
        return $this->hasOne(EmployeeSalary::class, 'employee_id')->where('status', 'active');
    }

    /**
     * Get all salary records for the employee.
     */
    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    /**
     * Get salary revisions for the employee.
     */
    public function salaryRevisions()
    {
        return $this->hasMany(SalaryRevision::class, 'employee_id');
    }

    /**
     * Get all payroll entries for the employee.
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    /**
     * Get notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Get attendance regularizations for the employee.
     */
    public function attendanceRegularizations()
    {
        return $this->hasMany(AttendanceRegularization::class, 'employee_id');
    }

    /**
     * Get the termination details of the employee.
     */
    public function termination()
    {
        return $this->hasOne(EmployeeTermination::class, 'employee_id');
    }

    /**
     * Get all contracts for the employee.
     */
    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class, 'employee_id');
    }

    /**
     * Get employee terminations processed by this admin.
     */
    public function terminatedBy()
    {
        return $this->hasMany(EmployeeTermination::class, 'terminated_by');
    }

    /**
     * Helper check: Role is Admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Helper check: Role is Employee.
     */
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    /**
     * Helper check: Employment is Active.
     */
    public function isActive()
    {
        return $this->employment_status === 'active';
    }

    /**
     * Helper check: Employee is Terminated/Resigned.
     */
    public function isTerminated()
    {
        return in_array($this->employment_status, ['terminated', 'resigned', 'absconded', 'retired', 'contract_completed']);
    }

    /**
     * Helper check: Employee is on notice period.
     */
    public function isOnNoticePeriod()
    {
        return $this->employment_status === 'notice_period';
    }

    /**
     * Scope to active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNotIn('employment_status', ['terminated', 'resigned', 'absconded', 'retired', 'contract_completed']);
    }

    /**
     * Scope by employee type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('employee_type', $type);
    }

    /**
     * Scope to terminated employees.
     */
    public function scopeTerminated($query)
    {
        return $query->whereIn('employment_status', ['terminated', 'resigned', 'absconded', 'retired', 'contract_completed']);
    }
}
