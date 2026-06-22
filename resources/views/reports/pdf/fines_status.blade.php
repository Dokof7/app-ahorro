<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Multas Cobradas vs Pendientes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        .pie-section { margin: 0 auto 20px; width: 80%; }
        .pie-bar { height: 30px; border-radius: 4px; overflow: hidden; display: flex; margin-bottom: 8px; }
        .pie-paid    { background: #2E7D32; display: inline-block; height: 30px; }
        .pie-pending { background: #B71C1C; display: inline-block; height: 30px; }
        .legend { margin-bottom: 16px; }
        .legend-item { display: inline-block; margin-right: 20px; }
        .legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .kpi-table td { padding: 10px 16px; border: 1px solid #ddd; text-align: center; }
        .kpi-label { font-size: 10px; color: #666; display: block; }
        .kpi-value { font-size: 18px; font-weight: bold; display: block; margin-top: 4px; }
        .kpi-paid    { color: #2E7D32; }
        .kpi-pending { color: #B71C1C; }
        .kpi-total   { color: #1565C0; }
        table.detail { width: 100%; border-collapse: collapse; }
        th { padding: 6px 8px; color: #fff; text-align: left; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #FAFAFA; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .th-paid    { background: #2E7D32; }
        .th-pending { background: #B71C1C; }
        .section-title { font-size: 12px; font-weight: bold; margin: 14px 0 6px; padding: 3px 8px; }
        .section-paid    { background: #E8F5E9; border-left: 4px solid #2E7D32; }
        .section-pending { background: #FFEBEE; border-left: 4px solid #B71C1C; }
    </style>
</head>
<body>
    <h1>Multas Cobradas vs Pendientes</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    @php
        $grandTotal  = $total_paid + $total_pending;
        $paidPct     = $grandTotal > 0 ? round(($total_paid    / $grandTotal) * 100, 1) : 0;
        $pendingPct  = $grandTotal > 0 ? round(($total_pending / $grandTotal) * 100, 1) : 0;
    @endphp

    {{-- KPI cards --}}
    <table class="kpi-table">
        <tr>
            <td style="background:#E8F5E9">
                <span class="kpi-label">Total Cobradas</span>
                <span class="kpi-value kpi-paid">Bs. {{ number_format($total_paid, 2) }}</span>
                <span class="kpi-label">{{ $paid->count() }} multas ({{ $paidPct }}%)</span>
            </td>
            <td style="background:#FFEBEE">
                <span class="kpi-label">Total Pendientes</span>
                <span class="kpi-value kpi-pending">Bs. {{ number_format($total_pending, 2) }}</span>
                <span class="kpi-label">{{ $pending->count() }} multas ({{ $pendingPct }}%)</span>
            </td>
            <td style="background:#E3F2FD">
                <span class="kpi-label">Total General</span>
                <span class="kpi-value kpi-total">Bs. {{ number_format($grandTotal, 2) }}</span>
                <span class="kpi-label">{{ $fines->count() }} multas</span>
            </td>
        </tr>
    </table>

    {{-- Visual bar --}}
    @if($grandTotal > 0)
    <div style="margin-bottom:16px">
        <div style="height:24px; border-radius:4px; overflow:hidden; display:flex">
            <div style="width:{{ $paidPct }}%; background:#2E7D32; height:24px; line-height:24px; text-align:center; color:#fff; font-size:10px; font-weight:bold">
                @if($paidPct > 8){{ $paidPct }}% Cobradas@endif
            </div>
            <div style="width:{{ $pendingPct }}%; background:#B71C1C; height:24px; line-height:24px; text-align:center; color:#fff; font-size:10px; font-weight:bold">
                @if($pendingPct > 8){{ $pendingPct }}% Pendientes@endif
            </div>
        </div>
    </div>
    @endif

    {{-- Paid detail --}}
    @if($paid->count() > 0)
    <div class="section-title section-paid">Multas Cobradas ({{ $paid->count() }})</div>
    <table class="detail">
        <thead><tr>
            <th class="th-paid">Fecha</th>
            <th class="th-paid">Miembro</th>
            <th class="th-paid">Motivo</th>
            <th class="th-paid" style="text-align:right">Monto</th>
            <th class="th-paid">Fecha Cobro</th>
        </tr></thead>
        <tbody>
            @foreach($paid as $fine)
            <tr>
                <td>{{ $fine->meeting?->meeting_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $fine->member->full_name ?? '—' }}</td>
                <td>{{ $fine->reason ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($fine->amount, 2) }}</td>
                <td>{{ $fine->paid_date?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Pending detail --}}
    @if($pending->count() > 0)
    <div class="section-title section-pending">Multas Pendientes ({{ $pending->count() }})</div>
    <table class="detail">
        <thead><tr>
            <th class="th-pending">Fecha</th>
            <th class="th-pending">Miembro</th>
            <th class="th-pending">Motivo</th>
            <th class="th-pending" style="text-align:right">Monto</th>
        </tr></thead>
        <tbody>
            @foreach($pending as $fine)
            <tr>
                <td>{{ $fine->meeting?->meeting_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $fine->member->full_name ?? '—' }}</td>
                <td>{{ $fine->reason ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($fine->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
