<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rentabilidad de Préstamos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 10px; font-size: 10px; }
        .kpi-row { display: table; width: 100%; margin-bottom: 16px; }
        .kpi-box { display: table-cell; text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .kpi-label { font-size: 10px; color: #666; }
        .kpi-value { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .kpi-charged  { color: #1565C0; }
        .kpi-collected { color: #2E7D32; }
        .kpi-pending  { color: #E65100; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #00695C; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #E0F2F1; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .totals td { font-weight: bold; background: #B2DFDB; border-top: 2px solid #00695C; }
    </style>
</head>
<body>
    <h1>Rentabilidad de Préstamos</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    @php
        $pendingInterest = $total_interest_charged - $total_interest_collected;
    @endphp

    <table style="width:100%; border-collapse:collapse; margin-bottom:16px">
        <tr>
            <td style="padding:10px; text-align:center; background:#E3F2FD; border:1px solid #90CAF9; border-radius:4px">
                <div style="font-size:10px; color:#666">Intereses Generados</div>
                <div style="font-size:16px; font-weight:bold; color:#1565C0">Bs. {{ number_format($total_interest_charged, 2) }}</div>
            </td>
            <td style="padding:10px; text-align:center; background:#E8F5E9; border:1px solid #A5D6A7; border-radius:4px">
                <div style="font-size:10px; color:#666">Intereses Cobrados</div>
                <div style="font-size:16px; font-weight:bold; color:#2E7D32">Bs. {{ number_format($total_interest_collected, 2) }}</div>
            </td>
            <td style="padding:10px; text-align:center; background:#FFF3E0; border:1px solid #FFCC80; border-radius:4px">
                <div style="font-size:10px; color:#666">Intereses Pendientes</div>
                <div style="font-size:16px; font-weight:bold; color:#E65100">Bs. {{ number_format($pendingInterest, 2) }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:right">Monto</th>
                <th style="text-align:center">Tasa %</th>
                <th style="text-align:right">Interés Generado</th>
                <th style="text-align:right">Interés Cobrado</th>
                <th style="text-align:center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCharged = 0; $totalCollected = 0; @endphp
            @forelse($loans as $loan)
            @php
                $interestCollected = $loan->payments->sum('interest_paid');
                $totalCharged    += $loan->interest_amount;
                $totalCollected  += $interestCollected;
                $statusLabel = match($loan->status) {
                    'paid'    => 'Pagado',
                    'overdue' => 'Vencido',
                    default   => 'Pendiente',
                };
                $statusColor = match($loan->status) {
                    'paid'    => '#2E7D32',
                    'overdue' => '#B71C1C',
                    default   => '#E65100',
                };
            @endphp
            <tr>
                <td>{{ $loan->delivery_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $loan->member->full_name ?? '—' }}</td>
                <td>{{ $loan->group->name ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($loan->amount, 2) }}</td>
                <td class="center">{{ $loan->interest_rate }}%</td>
                <td class="num">Bs. {{ number_format($loan->interest_amount, 2) }}</td>
                <td class="num">Bs. {{ number_format($interestCollected, 2) }}</td>
                <td class="center" style="color:{{ $statusColor }}; font-weight:bold">{{ $statusLabel }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if($loans->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="5">TOTALES ({{ $loans->count() }} préstamos)</td>
                <td class="num">Bs. {{ number_format($totalCharged, 2) }}</td>
                <td class="num">Bs. {{ number_format($totalCollected, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
