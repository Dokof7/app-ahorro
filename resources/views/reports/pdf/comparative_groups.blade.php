<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comparativo de Grupos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #6A1B9A; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #F3E5F5; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .pct-high { color: #2E7D32; font-weight: bold; }
        .pct-mid  { color: #F9A825; font-weight: bold; }
        .pct-low  { color: #B71C1C; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Comparativo de Grupos</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
        @if(!empty($filters['date_from'])) — Desde {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }} @endif
        @if(!empty($filters['date_to'])) — Hasta {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Grupo</th>
                <th style="text-align:center">Miembros Activos</th>
                <th style="text-align:right">Total Ahorrado</th>
                <th style="text-align:right">F. Emergencia</th>
                <th style="text-align:right">Multas Cobradas</th>
                <th style="text-align:right">Préstamos Otorgados</th>
                <th style="text-align:right">Préstamos Recuperados</th>
                <th style="text-align:right">Saldo Pendiente</th>
                <th style="text-align:right">Intereses Cobrados</th>
                <th style="text-align:center">% Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php
                $pctClass = $row['attendance_rate'] >= 80 ? 'pct-high' : ($row['attendance_rate'] >= 50 ? 'pct-mid' : 'pct-low');
            @endphp
            <tr>
                <td>{{ $row['group']->name }}</td>
                <td class="center">{{ $row['active_members'] }}</td>
                <td class="num">Bs. {{ number_format($row['total_savings'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_emergency'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_fines'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_loans_out'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_loans_recov'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_loans_bal'], 2) }}</td>
                <td class="num">Bs. {{ number_format($row['total_interest'], 2) }}</td>
                <td class="center"><span class="{{ $pctClass }}">{{ $row['attendance_rate'] }}%</span></td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
