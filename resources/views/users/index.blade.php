@extends('layouts.app')
@section('page_title', 'Usuarios')
@section('page_actions')
    <a href="{{ route('users.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nuevo Usuario</a>
@endsection
@section('main_content')
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-2"></i>Listado de Usuarios</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="usersTable">
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Grupos</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection
@push('js')
<script>
$('#usersTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("users.index") }}',
    columns: [
        { data: 'name' }, { data: 'email' }, { data: 'phone', defaultContent: '-' },
        { data: 'role_badge', orderable: false },
        { data: 'groups_count', className: 'text-center' },
        { data: 'status_badge', orderable: false },
        { data: 'actions', orderable: false, className: 'text-center' }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
</script>
@endpush
