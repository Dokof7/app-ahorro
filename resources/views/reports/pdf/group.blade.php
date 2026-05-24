<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Grupos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #28a745; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .badge-active { color: #28a745; font-weight: bold; }
        .badge-inactive { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Reporte de Grupos de Ahorro</h1>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Inicio</th>
                <th>Miembros</th>
                <th>Reuniones</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
            <tr>
                <td>{{ $group->name }}</td>
                <td>{{ $group->description ?? '-' }}</td>
                <td>{{ $group->start_date }}</td>
                <td>{{ $group->members->count() }}</td>
                <td>{{ $group->meetings->count() }}</td>
                <td class="{{ $group->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                    {{ $group->status === 'active' ? 'Activo' : 'Inactivo' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
