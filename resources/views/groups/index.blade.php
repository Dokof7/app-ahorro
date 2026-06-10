@extends('layouts.app')
@section('page_title', 'Grupos de Ahorro')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('groups.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nuevo Grupo</a>
    @endcan
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Listado de Grupos</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="groupsTable">
            <thead>
                <tr><th>Nombre</th><th>Descripción</th><th>Inicio</th><th>Miembros</th><th>Reuniones</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#groupsTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("groups.index") }}',
    columns: [
        { data: 'name' }, { data: 'description', defaultContent: '-' }, { data: 'start_date' },
        { data: 'members_count', className: 'text-center' }, { data: 'meetings_count', className: 'text-center' },
        { data: 'status_badge', orderable: false }, { data: 'actions', orderable: false, className: 'text-center' }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
