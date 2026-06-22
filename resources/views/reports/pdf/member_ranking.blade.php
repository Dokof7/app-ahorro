<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ranking de Ahorradores</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        .section-title { font-size: 13px; font-weight: bold; margin: 16px 0 8px; padding: 4px 8px; border-radius: 3px; }
        .title-savings { background: #E8F5E9; color: #2E7D32; border-left: 4px solid #2E7D32; }
        .title-punctual { background: #E3F2FD; color: #1565C0; border-left: 4px solid #1565C0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { padding: 6px 8px; color: #fff; text-align: left; }
        .th-savings  { background: #2E7D32; }
        .th-punctual { background: #1565C0; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #FAFAFA; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .medal-1 { color: #F9A825; font-weight: bold; }
        .medal-2 { color: #90A4AE; font-weight: bold; }
        .medal-3 { color: #A1887F; font-weight: bold; }
        .medal-other { color: #555; }
    </style>
</head>
<body>
    <h1>Ranking de Ahorradores</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }} — Total reuniones: {{ $total_meetings }}</div>

    <div class="section-title title-savings">Top 10 — Mayor Ahorro Acumulado</div>
    <table>
        <thead>
            <tr>
                <th class="th-savings" style="width:40px">#</th>
                <th class="th-savings">Miembro</th>
                <th class="th-savings">Grupo</th>
                <th class="th-savings" style="text-align:right">Total Ahorrado</th>
                <th class="th-savings" style="text-align:center">Cuotas Pagadas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($top_savers as $i => $row)
            @php
                $medalClass = $i === 0 ? 'medal-1' : ($i === 1 ? 'medal-2' : ($i === 2 ? 'medal-3' : 'medal-other'));
            @endphp
            <tr>
                <td class="center {{ $medalClass }}">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="num">Bs. {{ number_format($row['total_saved'], 2) }}</td>
                <td class="center">{{ $row['paid'] }} / {{ $total_meetings }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title title-punctual">Top 10 — Mayor Puntualidad</div>
    <table>
        <thead>
            <tr>
                <th class="th-punctual" style="width:40px">#</th>
                <th class="th-punctual">Miembro</th>
                <th class="th-punctual">Grupo</th>
                <th class="th-punctual" style="text-align:center">Asistencias</th>
                <th class="th-punctual" style="text-align:right">% Puntualidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($top_punctual as $i => $row)
            @php
                $medalClass = $i === 0 ? 'medal-1' : ($i === 1 ? 'medal-2' : ($i === 2 ? 'medal-3' : 'medal-other'));
            @endphp
            <tr>
                <td class="center {{ $medalClass }}">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="center">{{ $row['attended'] }} / {{ $total_meetings }}</td>
                <td class="num">{{ $row['punctuality'] }}%</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
