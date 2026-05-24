@extends('layouts.app')
@section('page_title', 'Miembros')
@section('page_actions')
    <a href="{{ route('members.create') }}" class="btn btn-success"><i class="fas fa-user-plus mr-1"></i>Nuevo Miembro</a>
@endsection
@section('main_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-friends mr-2"></i>Listado de Miembros</h3>
        <div class="card-tools">
            <select id="filterGroup" class="form-control form-control-sm select2">
                <option value="">Todos los grupos</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="membersTable">
            <thead>
                <tr><th>Nombre</th><th>CI/Documento</th><th>Teléfono</th><th>Grupo</th><th>Ingreso</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
var table = $('#membersTable').DataTable({
    processing: true, serverSide: true,
    ajax: { url: '{{ route("members.index") }}', data: function(d) { d.group_id = $('#filterGroup').val(); } },
    columns: [
        { data: 'full_name' }, { data: 'document_number', defaultContent: '-' },
        { data: 'phone', defaultContent: '-' }, { data: 'group_name' },
        { data: 'join_date' }, { data: 'status_badge', orderable: false },
        { data: 'actions', orderable: false }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
$('#filterGroup').on('change', function() { table.ajax.reload(); });
</script>
@endpush
