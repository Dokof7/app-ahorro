<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Multas Generadas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 12px; font-size: 10px; }
        .section-title { font-size: 12px; font-weight: bold; margin: 14px 0 6px; padding: 3px 8px; border-left: 4px solid #F57F17; background: #FFF8E1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #F57F17; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #FFF8E1; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .status-paid    { background: #E8F5E9; color: #2E7D32; padding: 1px 6px; border-radius: 10px; }
        .status-pending { background: #FFEBEE; color: #B71C1C; padding: 1px 6px; border-radius: 10px; }
        .totals td { font-weight: bold; background: #FFF3E0; border-top: 2px solid #F57F17; }
        .summary-table td { padding: 5px 10px; border-bottom: 1px solid #eee; }
        .summary-table td:first-child { width: 50%; }
        .summary-table td:last-child { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    <h1>Multas Generadas</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    {{-- Summary by month --}}
    <div class="section-title">Por Mes</div>
    <table class="summary-table">
        <thead><tr><th>Mes</th><th style="text-align:center">Cantidad</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
            @forelse($by_month as $month => $monthFines)
            <tr>
                <td>{{ $month }}</td>
                <td class="center">{{ $monthFines->count() }}</td>
                <td class="num">Bs. {{ number_format($monthFines->sum('amount'), 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail --}}
    <div class="section-title">Detalle de Multas</div>
    <table>
        <thead>
            <tr>
                <th>Fecha Reunión</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th>Motivo</th>
                <th style="text-align:right">Monto</th>
                <th style="text-align:center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($fines as $fine)
            @php $total += $fine->amount; @endphp
            <tr>
                <td>{{ $fine->meeting?->meeting_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $fine->member->full_name ?? '—' }}</td>
                <td>{{ $fine->member->group->name ?? '—' }}</td>
                <td>{{ $fine->reason ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($fine->amount, 2) }}</td>
                <td class="center">
                    <span class="{{ $fine->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                        {{ $fine->status === 'paid' ? 'Cobrada' : 'Pendiente' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if($fines->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="4">TOTAL ({{ $fines->count() }} multas)</td>
                <td class="num">Bs. {{ number_format($total, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
