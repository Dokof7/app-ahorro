<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de Caja</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #37474F; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #FAFAFA; }
        td.num { text-align: right; }
        .section-header td { background: #ECEFF1; font-weight: bold; color: #37474F; }
        .totals td { font-weight: bold; background: #E8F5E9; border-top: 2px solid #2E7D32; }
        .income { color: #2E7D32; }
        .expense { color: #C62828; }
        .balance { color: #1565C0; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Estado de Caja</h1>
    <div class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['year'])) — Año {{ $filters['year'] }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Reunión</th>
                <th>Fecha</th>
                <th style="text-align:right">Saldo Anterior</th>
                <th style="text-align:right">+ Ahorros</th>
                <th style="text-align:right">+ Intereses</th>
                <th style="text-align:right">+ Multas</th>
                <th style="text-align:right">Total Ingresos</th>
                <th style="text-align:right">- Préstamos</th>
                <th style="text-align:right">- Gastos Banc.</th>
                <th style="text-align:right">Total Egresos</th>
                <th style="text-align:right">Saldo Final</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalIncome = 0; $grandTotalExpense = 0; @endphp
            @forelse($rows as $row)
            @php
                $grandTotalIncome  += $row['total_income'];
                $grandTotalExpense += $row['total_expenses'];
            @endphp
            <tr>
                <td>N° {{ $row['meeting']->meeting_number }}</td>
                <td>{{ $row['meeting']->meeting_date?->format('d/m/Y') }}</td>
                <td class="num">Bs. {{ number_format($row['previous'], 2) }}</td>
                <td class="num income">{{ number_format($row['income_savings'], 2) }}</td>
                <td class="num income">{{ number_format($row['income_interest'], 2) }}</td>
                <td class="num income">{{ number_format($row['income_fines'], 2) }}</td>
                <td class="num income" style="font-weight:bold">Bs. {{ number_format($row['total_income'], 2) }}</td>
                <td class="num expense">{{ number_format($row['loans_out'], 2) }}</td>
                <td class="num expense">{{ number_format($row['bank_expenses'], 2) }}</td>
                <td class="num expense" style="font-weight:bold">Bs. {{ number_format($row['total_expenses'], 2) }}</td>
                <td class="num balance">Bs. {{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="11" style="text-align:center">Sin datos disponibles.</td></tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr class="totals">
                <td colspan="6" style="text-align:right">TOTALES</td>
                <td class="num">Bs. {{ number_format($grandTotalIncome, 2) }}</td>
                <td colspan="2"></td>
                <td class="num">Bs. {{ number_format($grandTotalExpense, 2) }}</td>
                <td class="num balance">Bs. {{ number_format($grandTotalIncome - $grandTotalExpense, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
