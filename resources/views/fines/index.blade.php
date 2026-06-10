@extends('layouts.app')
@section('page_title', 'Multas')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('fines.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nueva Multa</a>
    @endcan
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Listado de Multas</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="finesTable">
            <thead>
                <tr><th>Miembro</th><th>Grupo</th><th>Reunión</th><th>Monto</th><th>Motivo</th><th>Estado</th><th>Fecha Pago</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#finesTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("fines.index") }}',
    columns: [
        { data: 'member_name' }, { data: 'group_name' },
        { data: 'meeting.meeting_number', defaultContent: '-' },
        { data: 'amount' }, { data: 'reason' },
        { data: 'status_badge', orderable: false },
        { data: 'paid_date', defaultContent: '-' },
        { data: 'actions', orderable: false }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
