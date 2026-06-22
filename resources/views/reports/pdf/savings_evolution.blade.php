<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Evolución de Ahorros</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1565C0; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f5f9ff; }
        td.num { text-align: right; }
        .bar-cell { width: 200px; padding: 4px 8px; }
        .bar-bg { background: #e0e0e0; border-radius: 3px; height: 12px; }
        .bar-fill { background: #1565C0; border-radius: 3px; height: 12px; }
        .totals td { font-weight: bold; background: #E3F2FD; border-top: 2px solid #1565C0; }
    </style>
</head>
<body>
    <h1>Evolución de Ahorros</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    @php
        $maxSavings = collect($monthly_data)->max('savings') ?: 1;
        $totalSavings = collect($monthly_data)->sum('savings');
        $lastAccumulated = collect($monthly_data)->last()['accumulated'] ?? 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th style="text-align:right">Ahorros del Mes</th>
                <th style="text-align:right">Acumulado</th>
                <th>Progreso</th>
                <th style="text-align:right">Variación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthly_data as $i => $row)
            @php
                $prev = $i > 0 ? $monthly_data[$i - 1]['savings'] : 0;
                $variation = $prev > 0 ? round((($row['savings'] - $prev) / $prev) * 100, 1) : 0;
                $barWidth = round(($row['savings'] / $maxSavings) * 100);
            @endphp
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="num">Bs. {{ number_format($row['savings'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['accumulated'], 2) }}</td>
                <td class="bar-cell">
                    <div class="bar-bg"><div class="bar-fill" style="width:{{ $barWidth }}%"></div></div>
                </td>
                <td class="num" style="color: {{ $variation >= 0 ? '#2E7D32' : '#C62828' }}">
                    {{ $i > 0 ? ($variation >= 0 ? '+' : '') . $variation . '%' : '—' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if(count($monthly_data) > 0)
        <tfoot>
            <tr class="totals">
                <td>TOTAL</td>
                <td class="num">Bs. {{ number_format($totalSavings, 2) }}</td>
                <td class="num">Bs. {{ number_format($lastAccumulated, 2) }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
