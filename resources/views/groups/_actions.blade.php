<a href="{{ route('groups.show', $group) }}" class="btn btn-xs btn-info" title="Ver"><i class="fas fa-eye"></i></a>
<a href="{{ route('groups.edit', $group) }}" class="btn btn-xs btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
<a href="{{ route('meetings.create') }}?group_id={{ $group->id }}" class="btn btn-xs btn-success" title="Nueva reunión"><i class="fas fa-plus"></i></a>
<form action="{{ route('groups.destroy', $group) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm" title="Eliminar"><i class="fas fa-trash"></i></button>
</form>
