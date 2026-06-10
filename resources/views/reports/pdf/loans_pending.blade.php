<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Préstamos Pendientes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #dc3545; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f1f1f1; }
    </style>
</head>
<body>
    <h1>Préstamos Pendientes</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Grupo</th>
                <th>Reunión</th>
                <th>Entrega</th>
                <th>Vencimiento</th>
                <th class="text-right">Monto (Bs.)</th>
                <th class="text-right">Interés (%)</th>
                <th class="text-right">Saldo (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $loan)
            <tr>
                <td>{{ $loan->member->full_name ?? '-' }}</td>
                <td>{{ $loan->group->name ?? '-' }}</td>
                <td>#{{ $loan->meeting->meeting_number ?? '-' }}</td>
                <td>{{ $loan->delivery_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $loan->due_date?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-right">{{ number_format($loan->amount, 2) }}</td>
                <td class="text-right">{{ $loan->interest_rate }}%</td>
                <td class="text-right">{{ number_format($loan->balance, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
        @if($loans->count())
        <tfoot>
            <tr>
                <td colspan="7" class="text-right">Total Saldo:</td>
                <td class="text-right">Bs. {{ number_format($loans->sum('balance'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
