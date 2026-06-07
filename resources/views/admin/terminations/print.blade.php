<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Summary Sheet - {{ $termination->employee->name ?? 'Employee' }}</title>
    <!-- Include Bootstrap Icons for aesthetics -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #334155;
            background-color: #ffffff;
            margin: 0;
            padding: 40px;
            font-size: 14px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-b: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .grid-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .section-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            background-color: #f8fafc;
        }
        .section-box h3 {
            margin: 0 0 15px 0;
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .detail-row:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            color: #64748b;
            font-weight: 600;
        }
        .detail-value {
            color: #0f172a;
            font-weight: 700;
        }
        .reason-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .reason-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .reason-text {
            color: #334155;
            font-size: 12px;
            background-color: #fafafa;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            border-radius: 8px;
            margin: 0;
            font-style: italic;
        }
        .checklist-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .checklist-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
        }
        .checklist-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 6px;
        }
        .status-badge.completed { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .status-badge.success { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-badge.warning { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-bottom: 1px solid #0f172a;
            margin-bottom: 10px;
            height: 40px;
        }
        .sig-label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .print-btn {
            background-color: #6366f1;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background-color: #4f46e5;
        }
        @media print {
            .print-btn {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">
        <i class="bi bi-printer-fill"></i> Click to Print Summary Sheet
    </button>

    <div class="header">
        <h1>InOut HRMS Corporate Exit Summary</h1>
        <p>Official Record of Employment Offboarding & Final Settlement</p>
    </div>

    <div class="grid-details">
        <!-- Employee Profile -->
        <div class="section-box">
            <h3>Employee Profile</h3>
            <div class="detail-row">
                <span class="detail-label">Legal Name:</span>
                <span class="detail-value">{{ $termination->employee->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Employee Code:</span>
                <span class="detail-value">{{ $termination->employee->employee_code ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Department:</span>
                <span class="detail-value">{{ $termination->employee->department->department_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Designation:</span>
                <span class="detail-value">{{ $termination->employee->position->position_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Primary Office:</span>
                <span class="detail-value">{{ $termination->employee->location->location_name ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Offboarding Timings -->
        <div class="section-box">
            <h3>Offboarding Timeline & Settlement</h3>
            <div class="detail-row">
                <span class="detail-label">Termination Type:</span>
                <span class="detail-value" style="text-transform: uppercase;">{{ str_replace('_', ' ', $termination->termination_type) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Joining Date:</span>
                <span class="detail-value">{{ $termination->employee->joining_date ? $termination->employee->joining_date->format('d M Y') : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Last Working Date:</span>
                <span class="detail-value">{{ $termination->last_working_date->format('d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Final month salary:</span>
                <span class="detail-value">Rs. {{ number_format($termination->pending_salary, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Leave Encashment:</span>
                <span class="detail-value">Rs. {{ number_format($termination->leave_encashment, 2) }}</span>
            </div>
            <div class="detail-row" style="border-t: 1px dashed #cbd5e1; padding-top: 6px; margin-top: 6px;">
                <span class="detail-label" style="color: #0f172a; font-weight: 800;">Total Settlement:</span>
                <span class="detail-value" style="color: #4f46e5; font-size: 13px;">Rs. {{ number_format($termination->pending_salary + $termination->leave_encashment, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="reason-box">
        <h3>Reason for Departure / Offboarding Remarks</h3>
        <p class="reason-text">
            "{{ $termination->termination_reason }}"
        </p>
    </div>

    <!-- Checklist Status table -->
    <table class="checklist-table">
        <thead>
            <tr>
                <th>Offboarding Process Item</th>
                <th>Status Verified</th>
                <th>Notes / Action Remarks</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Asset Recovery & Return Check</td>
                <td>
                    @if($termination->asset_return_status === 'completed')
                        <span class="status-badge success">All Assets Returned</span>
                    @elseif($termination->asset_return_status === 'partial')
                        <span class="status-badge warning">Partially Recovered</span>
                    @else
                        <span class="status-badge warning" style="color:#ef4444; background-color:#fef2f2; border: 1px solid #fecaca;">Dues Pending</span>
                    @endif
                </td>
                <td>Verified recovery of credentials, corporate hardware, electronic tags, and locker access.</td>
            </tr>
            <tr>
                <td>Exit Interview & Employee Feedback</td>
                <td>
                    @if($termination->exit_interview_status === 'completed')
                        <span class="status-badge success">Interview Completed</span>
                    @elseif($termination->exit_interview_status === 'skipped')
                        <span class="status-badge completed">Interview Skipped</span>
                    @elseif($termination->exit_interview_status === 'scheduled')
                        <span class="status-badge warning">Interview Scheduled</span>
                    @else
                        <span class="status-badge warning">Pending Interview</span>
                    @endif
                </td>
                <td>
                    {{ $termination->exit_interview_notes ? Str::limit($termination->exit_interview_notes, 120) : 'No interview feedback logs submitted.' }}
                </td>
            </tr>
            <tr>
                <td>Final settlement & Payout</td>
                <td>
                    @if($termination->final_settlement_status === 'paid')
                        <span class="status-badge success">Settlement Paid</span>
                    @elseif($termination->final_settlement_status === 'processed')
                        <span class="status-badge success" style="background-color: #f0fdf4; color:#166534; border:1px solid #bbf7d0;">Processed</span>
                    @else
                        <span class="status-badge warning">Pending Payout</span>
                    @endif
                </td>
                <td>{{ $termination->remarks ? Str::limit($termination->remarks, 120) : 'Calculations checked and verified.' }}</td>
            </tr>
            <tr>
                <td>System Deactivation & Account Block</td>
                <td>
                    @if($termination->exit_status === 'completed')
                        <span class="status-badge success">Blocked & Deactivated</span>
                    @else
                        <span class="status-badge warning">Pending System Block</span>
                    @endif
                </td>
                <td>Tokens cleared, device bindings reset, geofencing entries updated.</td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">Employee Signature</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">Processed By (Admin)</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">HR Director / Executive</div>
        </div>
    </div>

</body>
</html>
