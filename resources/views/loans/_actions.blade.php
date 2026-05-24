<a href="{{ route('loans.show', $loan) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
@if($loan->status !== 'paid')
<a href="{{ route('loans.show', $loan) }}" class="btn btn-xs btn-success" title="Registrar pago"><i class="fas fa-money-bill"></i></a>
@endif
@if($loan->isOverdue())
<span class="badge bg-danger ml-1">VENCIDO</span>
@endif
<form action="{{ route('loans.destroy', $loan) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
</form>
