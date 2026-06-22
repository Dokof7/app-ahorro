<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recuperación de Préstamos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; font-size: 10px; }
        .group-block { margin-bottom: 20px; }
        .group-name { font-size: 12px; font-weight: bold; color: #01579B; margin-bottom: 8px; border-bottom: 2px solid #01579B; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 10px; border-bottom: 1px solid #ddd; }
        .label-cell { color: #555; width: 40%; }
        .value-cell { font-weight: bold; text-align: right; width: 30%; }
        .pct-cell { text-align: right; width: 30%; }
        .row-loaned   { background: #E3F2FD; }
        .row-recovered{ background: #E8F5E9; }
        .row-pending  { background: #FFEBEE; }
        .bar-row td { padding: 4px 10px; }
        .bar-container { height: 16px; background: #FFCDD2; border-radius: 4px; position: relative; }
        .bar-recovered { height: 16px; background: #4CAF50; border-radius: 4px; }
        .bar-label { position: absolute; top: 0; right: 4px; font-size: 9px; line-height: 16px; color: #fff; font-weight: bold; }
        .grand-total { background: #E1F5FE; border-top: 2px solid #01579B; }
        .grand-total td { font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Recuperación de Préstamos</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    @php $gtLoaned = 0; $gtRecovered = 0; $gtPending = 0; @endphp

    @forelse($totals as $item)
    @php
        $gtLoaned    += $item['loaned'];
        $gtRecovered += $item['recovered'];
        $gtPending   += $item['pending'];
        $recoveryPct = $item['loaned'] > 0 ? round(($item['recovered'] / $item['loaned']) * 100, 1) : 0;
        $barWidth    = $recoveryPct;
    @endphp
    <div class="group-block">
        <div class="group-name">{{ $item['group']->name }}</div>
        <table>
            <tr class="row-loaned">
                <td class="label-cell">Total Prestado</td>
                <td class="value-cell">Bs. {{ number_format($item['loaned'], 2) }}</td>
                <td class="pct-cell">100%</td>
            </tr>
            <tr class="row-recovered">
                <td class="label-cell">Total Recuperado</td>
                <td class="value-cell">Bs. {{ number_format($item['recovered'], 2) }}</td>
                <td class="pct-cell">{{ $recoveryPct }}%</td>
            </tr>
            <tr class="row-pending">
                <td class="label-cell">Pendiente de Cobro</td>
                <td class="value-cell">Bs. {{ number_format($item['pending'], 2) }}</td>
                <td class="pct-cell">{{ round(100 - $recoveryPct, 1) }}%</td>
            </tr>
            <tr class="bar-row">
                <td colspan="3">
                    <div class="bar-container">
                        <div class="bar-recovered" style="width:{{ $barWidth }}%">
                            @if($recoveryPct > 10)
                            <span class="bar-label">{{ $recoveryPct }}% recuperado</span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @empty
    <p style="text-align:center">Sin datos disponibles.</p>
    @endforelse

    @if(count($totals) > 1)
    @php $gtPct = $gtLoaned > 0 ? round(($gtRecovered / $gtLoaned) * 100, 1) : 0; @endphp
    <table style="margin-top:10px">
        <tr class="grand-total">
            <td>TOTAL GLOBAL</td>
            <td style="text-align:right">Prestado: Bs. {{ number_format($gtLoaned, 2) }}</td>
            <td style="text-align:right">Recuperado: Bs. {{ number_format($gtRecovered, 2) }}</td>
            <td style="text-align:right">Pendiente: Bs. {{ number_format($gtPending, 2) }}</td>
            <td style="text-align:right">{{ $gtPct }}%</td>
        </tr>
    </table>
    @endif
</body>
</html>
