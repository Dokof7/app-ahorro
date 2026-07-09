@extends('layouts.app')
@section('page_title', 'Actividades')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('activities.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nueva Actividad</a>
    @endcan
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Listado de Actividades</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="activitiesTable">
            <thead>
                <tr><th>Fecha</th><th>Actividad</th><th>Lugar</th><th>Grupo</th><th>Monto Recaudado</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#activitiesTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("activities.index") }}',
    columns: [
        { data: 'activity_date' }, { data: 'name' },
        { data: 'location', defaultContent: '-' }, { data: 'group.name' },
        { data: 'status_badge', orderable: false },
        { data: 'actions', orderable: false }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
