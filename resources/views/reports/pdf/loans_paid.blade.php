<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Préstamos Pagados</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #28a745; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f1f1f1; }
    </style>
</head>
<body>
    <h1>Préstamos Pagados</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Grupo</th>
                <th>Entrega</th>
                <th>Vencimiento</th>
                <th class="text-right">Monto (Bs.)</th>
                <th class="text-right">Interés (%)</th>
                <th class="text-right">Total Retorno (Bs.)</th>
                <th>Pagos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $loan)
            <tr>
                <td>{{ $loan->member->full_name ?? '-' }}</td>
                <td>{{ $loan->group->name ?? '-' }}</td>
                <td>{{ $loan->delivery_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $loan->due_date?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-right">{{ number_format($loan->amount, 2) }}</td>
                <td class="text-right">{{ $loan->interest_rate }}%</td>
                <td class="text-right">{{ number_format($loan->total_to_return, 2) }}</td>
                <td>{{ $loan->payments->count() }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
        @if($loans->count())
        <tfoot>
            <tr>
                <td colspan="6" class="text-right">Total Retornado:</td>
                <td class="text-right">Bs. {{ number_format($loans->sum('total_to_return'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
