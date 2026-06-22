<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen General del Grupo</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        .group-block { margin-bottom: 24px; border: 1px solid #ddd; border-radius: 4px; padding: 12px; }
        .group-name { font-size: 13px; font-weight: bold; color: #1565C0; border-bottom: 2px solid #1565C0; padding-bottom: 4px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .kpi-table td { padding: 6px 10px; border-bottom: 1px solid #eee; }
        .kpi-table td:first-child { color: #555; width: 60%; }
        .kpi-table td:last-child { font-weight: bold; text-align: right; }
        .kpi-table tr:nth-child(even) td { background: #f5f9ff; }
        .highlight { background: #E8F5E9 !important; }
        .highlight td { color: #2E7D32 !important; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Resumen General del Grupo</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    @forelse($summary as $item)
    <div class="group-block">
        <div class="group-name">{{ $item['group']->name }}</div>
        <table class="kpi-table">
            <tr><td>Total Ahorrado</td><td>Bs. {{ number_format($item['total_savings'], 2) }}</td></tr>
            <tr><td>Fondo de Emergencia Acumulado</td><td>Bs. {{ number_format($item['total_emergency'], 2) }}</td></tr>
            <tr><td>Total de Multas Cobradas</td><td>Bs. {{ number_format($item['total_fines'], 2) }}</td></tr>
            <tr><td>Total de Préstamos Otorgados</td><td>Bs. {{ number_format($item['total_loans_out'], 2) }}</td></tr>
            <tr><td>Total Recuperado de Préstamos</td><td>Bs. {{ number_format($item['total_loans_recov'], 2) }}</td></tr>
            <tr><td>Intereses Generados</td><td>Bs. {{ number_format($item['total_interest'], 2) }}</td></tr>
            <tr><td>Gastos Bancarios Acumulados</td><td>Bs. {{ number_format($item['total_bank_expenses'], 2) }}</td></tr>
            <tr class="highlight"><td>Saldo Disponible en Caja</td><td>Bs. {{ number_format($item['available_balance'], 2) }}</td></tr>
        </table>
    </div>
    @empty
    <p style="text-align:center">Sin datos disponibles.</p>
    @endforelse
</body>
</html>
