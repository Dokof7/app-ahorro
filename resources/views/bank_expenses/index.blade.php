@extends('layouts.app')
@section('page_title', 'Gastos Bancarios')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('bank-expenses.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nuevo Gasto</a>
    @endcan
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-university mr-2"></i>Listado de Gastos Bancarios</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="bankExpensesTable">
            <thead>
                <tr><th>Grupo</th><th>Reunión</th><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#bankExpensesTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("bank-expenses.index") }}',
    columns: [
        { data: 'group.name' }, { data: 'meeting.meeting_number', defaultContent: '-' },
        { data: 'expense_date' }, { data: 'concept' }, { data: 'amount' },
        { data: 'actions', orderable: false }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
