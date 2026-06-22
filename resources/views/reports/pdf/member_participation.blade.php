<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Participación de Miembros</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #00838F; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #E0F7FA; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .totals td { font-weight: bold; background: #B2EBF2; border-top: 2px solid #00838F; }
    </style>
</head>
<body>
    <h1>Participación de Miembros</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }} — Total reuniones: {{ $total_meetings }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:center">Asistencias</th>
                <th style="text-align:right">% Asistencia</th>
                <th style="text-align:center">Aportes Realizados</th>
                <th style="text-align:right">Total Ahorrado</th>
                <th style="text-align:center">Préstamos Solicitados</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @forelse($rows as $i => $row)
            @php $grandTotal += $row['total_saved']; @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="center">{{ $row['attended'] }} / {{ $total_meetings }}</td>
                <td class="num">{{ $row['attendance_pct'] }}%</td>
                <td class="center">{{ $row['contributions'] }}</td>
                <td class="num">Bs. {{ number_format($row['total_saved'], 2) }}</td>
                <td class="center">{{ $row['loans_requested'] }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if($rows->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="6">TOTAL</td>
                <td class="num">Bs. {{ number_format($grandTotal, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
