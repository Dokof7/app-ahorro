<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Miembros Morosos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #B71C1C; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) td { background: #FFF8F8; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .tag-overdue { background: #B71C1C; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .tag-pending { background: #E65100; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 9px; }
        .totals td { font-weight: bold; background: #FFEBEE; border-top: 2px solid #B71C1C; }
        .alert-row td { background: #FFEBEE !important; }
    </style>
</head>
<body>
    <h1>Miembros Morosos</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    @if($rows->isEmpty())
    <p style="text-align:center; color: #2E7D32; font-weight:bold; margin-top:30px">
        ¡Sin miembros morosos! Todos al día.
    </p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:center">Multas Pendientes</th>
                <th style="text-align:right">Monto Multas</th>
                <th style="text-align:center">Préstamos Activos</th>
                <th style="text-align:right">Saldo Préstamos</th>
                <th style="text-align:center">Días Atraso</th>
            </tr>
        </thead>
        <tbody>
            @php $totalFines = 0; $totalLoans = 0; @endphp
            @foreach($rows as $i => $row)
            @php
                $totalFines += $row['fines_amount'];
                $totalLoans += $row['loans_balance'];
                $isAlert = $row['overdue_days'] > 60 || $row['loans_balance'] > 500;
            @endphp
            <tr class="{{ $isAlert ? 'alert-row' : '' }}">
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="center">
                    {{ $row['pending_fines']->count() }}
                    @if($row['pending_fines']->count() > 0)
                    <span class="tag-pending">pendiente</span>
                    @endif
                </td>
                <td class="num">Bs. {{ number_format($row['fines_amount'], 2) }}</td>
                <td class="center">
                    {{ $row['active_loans']->count() }}
                    @if($row['active_loans']->where('status', 'overdue')->count() > 0)
                    <span class="tag-overdue">vencido</span>
                    @endif
                </td>
                <td class="num">Bs. {{ number_format($row['loans_balance'], 2) }}</td>
                <td class="center">{{ $row['overdue_days'] > 0 ? $row['overdue_days'] . ' días' : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="4" style="text-align:right">TOTALES</td>
                <td class="num">Bs. {{ number_format($totalFines, 2) }}</td>
                <td></td>
                <td class="num">Bs. {{ number_format($totalLoans, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif
</body>
</html>
