@can('canEdit')
<a href="{{ route('activities.edit', $activity) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
<form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
</form>
@endcan
