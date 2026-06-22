<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asistencia a Reuniones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        .summary-row { background: #E8F5E9; padding: 8px 12px; margin-bottom: 12px; border-left: 4px solid #2E7D32; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #2E7D32; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #F1F8E9; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .bar-bg { background: #ddd; border-radius: 3px; height: 10px; display:inline-block; width: 80px; vertical-align: middle; }
        .bar-fill-high { background: #2E7D32; border-radius: 3px; height: 10px; display: inline-block; }
        .bar-fill-mid  { background: #F9A825; border-radius: 3px; height: 10px; display: inline-block; }
        .bar-fill-low  { background: #B71C1C; border-radius: 3px; height: 10px; display: inline-block; }
        .pct-high { color: #2E7D32; font-weight: bold; }
        .pct-mid  { color: #F9A825; font-weight: bold; }
        .pct-low  { color: #B71C1C; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Asistencia a Reuniones</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    <div class="summary-row">
        <strong>Total de Reuniones Realizadas:</strong> {{ $total_meetings }} &nbsp;|&nbsp;
        <strong>Total de Miembros:</strong> {{ $member_rows->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Miembro</th>
                <th>Grupo</th>
                <th style="text-align:center">Asistencias</th>
                <th style="text-align:center">Ausencias</th>
                <th style="text-align:center">% Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member_rows as $i => $row)
            @php
                $pctClass   = $row['pct'] >= 80 ? 'pct-high' : ($row['pct'] >= 50 ? 'pct-mid' : 'pct-low');
                $barClass   = $row['pct'] >= 80 ? 'bar-fill-high' : ($row['pct'] >= 50 ? 'bar-fill-mid' : 'bar-fill-low');
                $barWidth   = round($row['pct'] * 0.8); // scale to 80px
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->group->name ?? '—' }}</td>
                <td class="center">{{ $row['attended'] }}</td>
                <td class="center">{{ $row['absent'] }}</td>
                <td class="center">
                    <span class="{{ $pctClass }}">{{ $row['pct'] }}%</span>
                    <div class="bar-bg"><div class="{{ $barClass }}" style="width:{{ $barWidth }}px"></div></div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
