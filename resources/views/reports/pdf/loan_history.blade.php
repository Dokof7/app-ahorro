<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historial de Préstamos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #E65100; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #FFF3E0; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .status-paid    { background: #E8F5E9; color: #2E7D32; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .status-pending { background: #FFF9C4; color: #F57F17; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .status-overdue { background: #FFEBEE; color: #B71C1C; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .totals td { font-weight: bold; background: #FBE9E7; border-top: 2px solid #E65100; }
    </style>
</head>
<body>
    <h1>Historial de Préstamos</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:right">Monto</th>
                <th style="text-align:right">Interés (%)</th>
                <th style="text-align:right">Total a Retornar</th>
                <th style="text-align:right">Pagado</th>
                <th style="text-align:right">Saldo</th>
                <th style="text-align:center">Estado</th>
                <th>Vencimiento</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; $totalPaid = 0; $totalBalance = 0; @endphp
            @forelse($loans as $loan)
            @php
                $totalAmount  += $loan->amount;
                $totalPaid    += $loan->amount_paid ?? 0;
                $totalBalance += $loan->balance ?? 0;
                $statusClass = match($loan->status) {
                    'paid'    => 'status-paid',
                    'overdue' => 'status-overdue',
                    default   => 'status-pending',
                };
                $statusLabel = match($loan->status) {
                    'paid'    => 'Pagado',
                    'overdue' => 'Vencido',
                    default   => 'Pendiente',
                };
            @endphp
            <tr>
                <td>{{ $loan->delivery_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $loan->member->full_name ?? '—' }}</td>
                <td>{{ $loan->group->name ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($loan->amount, 2) }}</td>
                <td class="center">{{ $loan->interest_rate }}%</td>
                <td class="num">Bs. {{ number_format($loan->total_to_return, 2) }}</td>
                <td class="num">Bs. {{ number_format($loan->amount_paid ?? 0, 2) }}</td>
                <td class="num">Bs. {{ number_format($loan->balance ?? 0, 2) }}</td>
                <td class="center"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td class="center">{{ $loan->due_date?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if($loans->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="3">TOTALES ({{ $loans->count() }} préstamos)</td>
                <td class="num">Bs. {{ number_format($totalAmount, 2) }}</td>
                <td></td>
                <td></td>
                <td class="num">Bs. {{ number_format($totalPaid, 2) }}</td>
                <td class="num">Bs. {{ number_format($totalBalance, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
