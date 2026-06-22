<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Préstamos Activos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4527A0; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #EDE7F6; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .tag-overdue { background: #B71C1C; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .tag-pending { background: #F57F17; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .tag-due-soon { background: #E65100; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .totals td { font-weight: bold; background: #D1C4E9; border-top: 2px solid #4527A0; }
        .row-overdue td { background: #FFEBEE !important; }
        .row-due-soon td { background: #FFF8E1 !important; }
    </style>
</head>
<body>
    <h1>Préstamos Activos</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:right">Capital Pendiente</th>
                <th style="text-align:right">Total Original</th>
                <th style="text-align:center">Cuotas Pagadas</th>
                <th>Próximo Vencimiento</th>
                <th style="text-align:center">Días para Vencer</th>
                <th style="text-align:center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $totalBalance = 0; @endphp
            @forelse($rows as $row)
            @php
                $loan = $row['loan'];
                $totalBalance += $loan->balance ?? 0;
                $daysRemaining = $row['days_to_due'];
                $isOverdue  = $loan->status === 'overdue';
                $isDueSoon  = !$isOverdue && $daysRemaining !== null && $daysRemaining <= 15 && $daysRemaining >= 0;
                $rowClass = $isOverdue ? 'row-overdue' : ($isDueSoon ? 'row-due-soon' : '');
            @endphp
            <tr class="{{ $rowClass }}">
                <td>{{ $loan->member->full_name ?? '—' }}</td>
                <td>{{ $loan->group->name ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($loan->balance ?? 0, 2) }}</td>
                <td class="num">Bs. {{ number_format($loan->total_to_return, 2) }}</td>
                <td class="center">{{ $row['installments_paid'] }}</td>
                <td class="center">{{ $loan->due_date?->format('d/m/Y') ?? '—' }}</td>
                <td class="center">
                    @if($daysRemaining === null)—
                    @elseif($isOverdue)<span class="tag-overdue">Vencido hace {{ abs($daysRemaining) }}d</span>
                    @elseif($isDueSoon)<span class="tag-due-soon">{{ $daysRemaining }}d</span>
                    @else{{ $daysRemaining }}d
                    @endif
                </td>
                <td class="center">
                    @if($isOverdue)<span class="tag-overdue">Vencido</span>
                    @else<span class="tag-pending">Activo</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center">No hay préstamos activos.</td></tr>
            @endforelse
        </tbody>
        @if($rows->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="2">TOTAL ({{ $rows->count() }} préstamos)</td>
                <td class="num">Bs. {{ number_format($totalBalance, 2) }}</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
