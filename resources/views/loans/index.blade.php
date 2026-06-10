@extends('layouts.app')
@section('page_title', 'Préstamos')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('loans.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nuevo Préstamo</a>
    @endcan
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Listado de Préstamos</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="loansTable">
            <thead>
                <tr><th>Miembro</th><th>Grupo</th><th>Monto</th><th>Interés</th><th>Saldo</th><th>Vencimiento</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#loansTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("loans.index") }}',
    columns: [
        { data: 'member.full_name' }, { data: 'group.name' },
        { data: 'amount' }, { data: 'interest_rate' }, { data: 'balance' }, { data: 'due_date' },
        { data: 'status_badge', orderable: false }, { data: 'actions', orderable: false }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
