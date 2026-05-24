<a href="{{ route('users.edit', $user) }}" class="btn btn-xs btn-warning" title="Editar">
    <i class="fas fa-edit"></i>
</a>
@if($user->id !== auth()->id())
<form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm" title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</form>
@endif
