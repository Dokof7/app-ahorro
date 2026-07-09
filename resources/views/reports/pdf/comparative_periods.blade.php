<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comparativo de Períodos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1565C0; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f5f9ff; }
        td.num { text-align: right; }
        td.center { text-align: center; }
    </style>
</head>
<body>
    <h1>Comparativo de Períodos</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Período</th>
                <th style="text-align:right">Ahorros</th>
                <th style="text-align:right">Multas Cobradas</th>
                <th style="text-align:right">Préstamos Otorgados</th>
                <th style="text-align:right">Pagos de Préstamos</th>
                <th style="text-align:center">% Asistencia</th>
                <th style="text-align:right">Variación Ahorros</th>
            </tr>
        </thead>
        <tbody>
            @forelse($periods as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="num">Bs. {{ number_format($row['savings'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['fines'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['loans_out'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['loan_payments'], 2) }}</td>
                <td class="center">{{ $row['attendance_rate'] }}%</td>
                <td class="num" style="color: {{ ($row['savings_delta'] ?? 0) >= 0 ? '#2E7D32' : '#C62828' }}">
                    {{ $row['savings_delta'] === null ? '—' : ($row['savings_delta'] >= 0 ? '+' : '') . $row['savings_delta'] . '%' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
