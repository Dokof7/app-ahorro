<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Reuniones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ffc107; color: #333; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f1f1f1; }
    </style>
</head>
<body>
    <h1>Detalle de Reuniones</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['month'])) &mdash; Mes: {{ $filters['month'] }} @endif
        @if(!empty($filters['date_from'])) &mdash; Desde: {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }} @endif
        @if(!empty($filters['date_to'])) &mdash; Hasta: {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }} @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Grupo</th>
                <th>Fecha</th>
                <th>Mes</th>
                <th class="text-right">Ahorros (Bs.)</th>
                <th class="text-right">Emergencia (Bs.)</th>
                <th class="text-right">Multas (Bs.)</th>
                <th class="text-right">Total (Bs.)</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meetings as $meeting)
            <tr>
                <td>{{ $meeting->meeting_number }}</td>
                <td>{{ $meeting->group->name ?? '-' }}</td>
                <td>{{ $meeting->meeting_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $meeting->month }}</td>
                <td class="text-right">{{ number_format($meeting->contributions->sum('savings'), 2) }}</td>
                <td class="text-right">{{ number_format($meeting->contributions->sum('emergency_fund'), 2) }}</td>
                <td class="text-right">{{ number_format($meeting->contributions->sum('fine'), 2) }}</td>
                <td class="text-right">{{ number_format($meeting->contributions->sum('total'), 2) }}</td>
                <td>{{ $meeting->status === 'open' ? 'Abierta' : 'Cerrada' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
        @if($meetings->count())
        @php
            $totalSavings   = $meetings->sum(fn($m) => $m->contributions->sum('savings'));
            $totalEmergency = $meetings->sum(fn($m) => $m->contributions->sum('emergency_fund'));
            $totalFines     = $meetings->sum(fn($m) => $m->contributions->sum('fine'));
            $grandTotal     = $meetings->sum(fn($m) => $m->contributions->sum('total'));
        @endphp
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">TOTALES ({{ $meetings->count() }} reuniones):</td>
                <td class="text-right">Bs. {{ number_format($totalSavings, 2) }}</td>
                <td class="text-right">Bs. {{ number_format($totalEmergency, 2) }}</td>
                <td class="text-right">Bs. {{ number_format($totalFines, 2) }}</td>
                <td class="text-right">Bs. {{ number_format($grandTotal, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
