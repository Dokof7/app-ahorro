<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aportes por Miembro</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #6A1B9A; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #F3E5F5; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .bar-bg { background: #e0e0e0; border-radius: 3px; height: 10px; display:inline-block; width:100px; vertical-align:middle; }
        .bar-fill { background: #6A1B9A; border-radius: 3px; height: 10px; display:inline-block; }
        .totals td { font-weight: bold; background: #EDE7F6; border-top: 2px solid #6A1B9A; }
        .pct-high { color: #2E7D32; font-weight: bold; }
        .pct-mid  { color: #E65100; }
        .pct-low  { color: #C62828; }
    </style>
</head>
<body>
    <h1>Aportes por Miembro</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }} — Total de reuniones: {{ $total_meetings }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:right">Total Aportado</th>
                <th style="text-align:center">Cuotas Pagadas</th>
                <th style="text-align:center">Cuotas Pendientes</th>
                <th style="text-align:center">% Cumplimiento</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @forelse($rows as $i => $row)
            @php
                $grandTotal += $row['total_saved'];
                $pctClass = $row['compliance'] >= 80 ? 'pct-high' : ($row['compliance'] >= 50 ? 'pct-mid' : 'pct-low');
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($row['total_saved'], 2) }}</td>
                <td class="center">{{ $row['paid_count'] }}</td>
                <td class="center">{{ $row['pending_count'] }}</td>
                <td class="center">
                    <span class="{{ $pctClass }}">{{ $row['compliance'] }}%</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:{{ $row['compliance'] }}px"></div></div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if($rows->count() > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="3">TOTAL</td>
                <td class="num">Bs. {{ number_format($grandTotal, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
