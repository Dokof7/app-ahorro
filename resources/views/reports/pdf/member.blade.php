<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Miembros</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #17a2b8; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Historial de Miembros</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Grupo</th>
                <th>Ingreso</th>
                <th class="text-right">Ahorros (Bs.)</th>
                <th class="text-right">Emergencia (Bs.)</th>
                <th class="text-right">Multas (Bs.)</th>
                <th>Préstamos</th>
                <th style="text-align:center">Membresía</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
            <tr>
                <td>{{ $member->full_name }}</td>
                <td>{{ $member->document_number ?? '-' }}</td>
                <td>{{ $member->group->name ?? '-' }}</td>
                <td>{{ $member->join_date }}</td>
                <td class="text-right">{{ number_format($member->contributions->sum('savings'), 2) }}</td>
                <td class="text-right">{{ number_format($member->contributions->sum('emergency_fund'), 2) }}</td>
                <td class="text-right">{{ number_format($member->fines->sum('amount'), 2) }}</td>
                <td style="text-align:center">{{ $member->loans->count() }}</td>
                <td style="text-align:center">
                    @if(($member->group->membership_fee ?? 0) > 0)
                        {{ $member->membership_paid ? 'Pagada' : 'Pendiente' }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $member->status === 'active' ? 'Activo' : 'Inactivo' }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
