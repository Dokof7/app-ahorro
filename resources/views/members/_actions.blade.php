<a href="{{ route('members.show', $member) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
<a href="{{ route('members.edit', $member) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
@if($member->pendingFines()->count() > 0)
<span class="badge bg-warning" title="Tiene multas pendientes"><i class="fas fa-exclamation-triangle"></i> {{ $member->pendingFines()->count() }}</span>
@endif
<form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
</form>
