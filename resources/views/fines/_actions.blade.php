@can('canEdit')
@if($fine->status === 'pending')
<form action="{{ route('fines.mark-paid', $fine) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-xs btn-success" title="Marcar pagada"><i class="fas fa-check"></i></button>
</form>
@endif
<form action="{{ route('fines.destroy', $fine) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
</form>
@endcan
