@extends('layouts.admin')

@section('title', 'HR Analytics & Productivity Dashboard')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Real-time charts monitoring corporate budget distributions, employee attendance trends, and leave balances.</p>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('admin.analytics.dashboard') }}" class="flex items-center gap-2">
            <select name="year" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none">
                @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-xl border border-indigo-150 transition-all">Reload Analytics</button>
        </form>
    </div>
</div>

<!-- Summaries Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-500/20 flex flex-col justify-between h-40">
        <div>
            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white text-lg mb-4">
                <i class="bi bi-bank"></i>
            </div>
            <span class="text-[10px] font-bold text-indigo-100 uppercase tracking-widest block mb-0.5">Annual Payroll Budget Disbursed</span>
            <span class="text-2xl font-extrabold">Rs. {{ number_format($totalAnnualSpend, 2) }}</span>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex flex-col justify-between h-40">
        <div>
            <div class="w-10 h-10 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-lg mb-4">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-0.5">Average Attendance Rate (Current Month)</span>
            <span class="text-2xl font-extrabold text-slate-800">{{ $averageAttendanceRate }}%</span>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex flex-col justify-between h-40">
        <div>
            <div class="w-10 h-10 bg-purple-50 border border-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-lg mb-4">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-0.5">Active Departments Tracking</span>
            <span class="text-2xl font-extrabold text-slate-800">{{ count($deptNames) }}</span>
        </div>
    </div>
</div>

<!-- Charts Canvas Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Chart 1: Payroll spend trend -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm">Monthly Payroll Expense Trend ({{ $year }})</h3>
        </div>
        <div class="h-72">
            <canvas id="payrollTrendChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Department breakdown -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm">Department Salary Budget Allocation</h3>
        </div>
        <div class="h-72 flex items-center justify-center">
            <div class="w-64 h-64">
                <canvas id="deptAllocationChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Attendance trend -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm">Attendance History Logs (Last 6 Months)</h3>
        </div>
        <div class="h-72">
            <canvas id="attendanceTrendChart"></canvas>
        </div>
    </div>

    <!-- Chart 4: Leave breakdown -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm">Leave Utilization Breakdown (Total Days)</h3>
        </div>
        <div class="h-72">
            <canvas id="leaveUtilizationChart"></canvas>
        </div>
    </div>

</div>

<!-- Chart Script Configurations -->
<script>
    // 1. Payroll Trend
    const ctx1 = document.getElementById('payrollTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Salary Disbursed (Rs.)',
                data: @json($payrollTrendData),
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: '#6366f1',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#6366f1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { borderDash: [5, 5] }, ticks: { font: { family: 'Plus Jakarta Sans', weight: 'bold' } } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Department Allocation
    const ctx2 = document.getElementById('deptAllocationChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: @json($deptNames),
            datasets: [{
                data: @json($deptSpends),
                backgroundColor: [
                    '#6366f1', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#3b82f6', '#14b8a6'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } } }
            }
        }
    });

    // 3. Attendance Trends
    const ctx3 = document.getElementById('attendanceTrendChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: @json($attendanceTrendLabels),
            datasets: [
                {
                    label: 'Present/Late',
                    data: @json($presentCounts),
                    backgroundColor: '#10b981',
                    borderRadius: 6
                },
                {
                    label: 'Late Check-ins',
                    data: @json($lateCounts),
                    backgroundColor: '#f59e0b',
                    borderRadius: 6
                },
                {
                    label: 'Absent',
                    data: @json($absentCounts),
                    backgroundColor: '#ef4444',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { borderDash: [5, 5] }, stacked: false },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } } }
            }
        }
    });

    // 4. Leave Utilization
    const ctx4 = document.getElementById('leaveUtilizationChart').getContext('2d');
    new Chart(ctx4, {
        type: 'bar',
        indexAxis: 'y',
        data: {
            labels: @json($leaveLabels),
            datasets: [{
                label: 'Days Utilized',
                data: @json($leaveDays),
                backgroundColor: 'rgba(139, 92, 246, 0.7)',
                borderColor: '#8b5cf6',
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { borderDash: [5, 5] } },
                y: { grid: { display: false } }
            }
        }
    });
</script>
@endsection
