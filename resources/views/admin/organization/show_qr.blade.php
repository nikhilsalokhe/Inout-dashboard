@extends('layouts.admin')

@section('title', 'Location QR Code')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.organization.index', ['tab' => 'locations']) }}" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50 transition-all">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Location QR Code</h1>
            <p class="text-sm font-medium text-slate-500">View and print the attendance QR code for this location</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-8 lg:p-12">
        <div class="max-w-md mx-auto text-center space-y-8">
            <div class="space-y-2">
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">{{ $location->location_name }}</h2>
                <div class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wider">
                    <i class="bi bi-geo-alt-fill text-indigo-500"></i> Office Location
                </div>
            </div>

            <!-- Print Area Start -->
            <div id="printable-qr" class="bg-white border-2 border-slate-100 rounded-3xl p-8 shadow-sm">
                <div class="flex justify-center">
                    <img src="{{ $qrCodeBase64 }}" alt="QR Code" class="w-64 h-64 lg:w-80 lg:h-80" />
                </div>
                <div class="mt-6 text-slate-500 font-semibold text-sm">
                    Scan with Mobile App to Clock In/Out
                </div>
            </div>
            <!-- Print Area End -->

            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('admin.organization.locations.qr', $location->id) }}" download="qr_{{ $location->id }}.svg" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-slate-900 text-white font-extrabold text-sm hover:bg-slate-800 transition-colors shadow-sm">
                    <i class="bi bi-download"></i> Download SVG
                </a>
                <button onclick="printQR()" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-indigo-50 text-indigo-700 font-extrabold text-sm hover:bg-indigo-100 transition-colors shadow-sm border border-indigo-100">
                    <i class="bi bi-printer"></i> Print Code
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function printQR() {
    const printContent = document.getElementById('printable-qr').innerHTML;
    const locationName = "{{ addslashes($location->location_name) }}";
    
    const printWindow = window.open('', '_blank', 'width=800,height=800');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code - ${locationName}</title>
            <style>
                body {
                    font-family: 'Inter', -apple-system, sans-serif;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                    background: #fff;
                }
                .container {
                    text-align: center;
                    padding: 40px;
                    border: 4px solid #f1f5f9;
                    border-radius: 24px;
                    max-width: 500px;
                }
                h1 {
                    font-size: 32px;
                    color: #0f172a;
                    margin-bottom: 32px;
                }
                img {
                    width: 100%;
                    max-width: 400px;
                    height: auto;
                }
                .footer {
                    margin-top: 24px;
                    font-size: 18px;
                    color: #64748b;
                    font-weight: 600;
                }
                @media print {
                    .container { border: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>${locationName}</h1>
                ${printContent}
            </div>
            <script>
                window.onload = () => {
                    window.print();
                    setTimeout(() => window.close(), 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
@endsection
