<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aportes Mensuales</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #6f42c1; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f1f1f1; }
    </style>
</head>
<body>
    <h1>Aportes Mensuales</h1>
    <div class="subtitle">
        @if(!empty($filters['month']))Mes: {{ $filters['month'] }} — @endif
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Grupo</th>
                <th>Reunión</th>
                <th>Mes</th>
                <th class="text-right">Ahorros (Bs.)</th>
                <th class="text-right">Emergencia (Bs.)</th>
                <th class="text-right">Multa (Bs.)</th>
                <th class="text-right">Total (Bs.)</th>
                <th>Confirmado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contributions as $c)
            <tr>
                <td>{{ $c->member->full_name ?? '-' }}</td>
                <td>{{ $c->meeting->group->name ?? '-' }}</td>
                <td>#{{ $c->meeting->meeting_number ?? '-' }}</td>
                <td>{{ $c->meeting->month ?? '-' }}</td>
                <td class="text-right">{{ number_format($c->savings, 2) }}</td>
                <td class="text-right">{{ number_format($c->emergency_fund, 2) }}</td>
                <td class="text-right">{{ number_format($c->fine, 2) }}</td>
                <td class="text-right">{{ number_format($c->total, 2) }}</td>
                <td>{{ $c->confirmed ? 'Sí' : 'No' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
        @if($contributions->count())
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Totales:</td>
                <td class="text-right">Bs. {{ number_format($contributions->sum('savings'), 2) }}</td>
                <td class="text-right">Bs. {{ number_format($contributions->sum('emergency_fund'), 2) }}</td>
                <td class="text-right">Bs. {{ number_format($contributions->sum('fine'), 2) }}</td>
                <td class="text-right">Bs. {{ number_format($contributions->sum('total'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
