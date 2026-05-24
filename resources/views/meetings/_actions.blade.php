<a href="{{ route('meetings.show', $meeting) }}" class="btn btn-xs btn-info" title="Ver detalle"><i class="fas fa-eye"></i></a>
@if($meeting->isOpen())
<a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-xs btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
<form action="{{ route('meetings.close', $meeting) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-xs btn-secondary" title="Cerrar reunión" onclick="return confirm('¿Cerrar esta reunión?')"><i class="fas fa-lock"></i></button>
</form>
@else
<span class="btn btn-xs btn-secondary disabled" title="Reunión cerrada"><i class="fas fa-lock"></i></span>
@endif
<form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
</form>
