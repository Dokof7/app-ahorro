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
        .section-header td { background: #1565C0 !important; color: #fff !important; font-weight: bold; font-size: 10px; letter-spacing: 0.5px; }
        .subtotal td { background: #E3F2FD !important; color: #0D47A1 !important; font-weight: bold; border-top: 1px solid #90CAF9; }
        .grand-total td { background: #2E7D32 !important; color: #fff !important; font-size: 12px; font-weight: bold; }
        .highlight { background: #E8F5E9 !important; }
        .highlight td { color: #2E7D32 !important; font-size: 12px; }
        .consolidated-block { border: 2px solid #2E7D32; }
        .consolidated-name { color: #2E7D32; border-bottom-color: #2E7D32; }
    </style>
</head>
<body>
    <h1>Resumen General del Grupo</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    @forelse($summary as $item)
    <div class="group-block">
        <div class="group-name">{{ $item['group']->name }}</div>
        <table class="kpi-table">
            <tr class="section-header"><td colspan="2">INGRESOS</td></tr>
            <tr><td>Total Ahorrado</td><td>Bs. {{ number_format($item['total_savings'], 2) }}</td></tr>
            <tr><td>Fondo de Emergencia Acumulado</td><td>Bs. {{ number_format($item['total_emergency'], 2) }}</td></tr>
            <tr><td>Total de Multas Cobradas</td><td>Bs. {{ number_format($item['total_fines'], 2) }}</td></tr>
            <tr><td>Total Recuperado de Préstamos</td><td>Bs. {{ number_format($item['total_loans_recov'], 2) }}</td></tr>
            <tr><td>Intereses Generados</td><td>Bs. {{ number_format($item['total_interest'], 2) }}</td></tr>
            <tr class="subtotal"><td>Subtotal Ingresos</td><td>Bs. {{ number_format($item['subtotal_income'], 2) }}</td></tr>

            <tr class="section-header"><td colspan="2">EGRESOS</td></tr>
            <tr><td>Total de Préstamos Otorgados</td><td>Bs. {{ number_format($item['total_loans_out'], 2) }}</td></tr>
            <tr><td>Gastos Bancarios Acumulados</td><td>Bs. {{ number_format($item['total_bank_expenses'], 2) }}</td></tr>
            <tr class="subtotal"><td>Subtotal Egresos</td><td>Bs. {{ number_format($item['subtotal_outflow'], 2) }}</td></tr>

            <tr class="grand-total"><td>TOTAL GENERAL (Ingresos − Egresos)</td><td>Bs. {{ number_format($item['grand_total'], 2) }}</td></tr>
            <tr class="highlight"><td>Saldo Disponible en Caja</td><td>Bs. {{ number_format($item['available_balance'], 2) }}</td></tr>
        </table>
    </div>
    @empty
    <p style="text-align:center">Sin datos disponibles.</p>
    @endforelse

    @if(!empty($consolidated))
    <div class="group-block consolidated-block">
        <div class="group-name consolidated-name">TOTAL CONSOLIDADO ({{ count($summary) }} grupos)</div>
        <table class="kpi-table">
            <tr class="subtotal"><td>Subtotal Ingresos</td><td>Bs. {{ number_format($consolidated['subtotal_income'], 2) }}</td></tr>
            <tr class="subtotal"><td>Subtotal Egresos</td><td>Bs. {{ number_format($consolidated['subtotal_outflow'], 2) }}</td></tr>
            <tr class="grand-total"><td>TOTAL GENERAL (Ingresos − Egresos)</td><td>Bs. {{ number_format($consolidated['grand_total'], 2) }}</td></tr>
            <tr class="highlight"><td>Saldo Disponible en Caja (todos los grupos)</td><td>Bs. {{ number_format($consolidated['available_balance'], 2) }}</td></tr>
        </table>
    </div>
    @endif
</body>
</html>
