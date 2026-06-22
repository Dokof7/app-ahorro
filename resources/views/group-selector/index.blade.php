@extends('adminlte::page')

@section('title', 'Seleccionar Grupo')

@section('content_header')
    <h1>Seleccionar Grupo de Ahorro</h1>
@stop

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header text-center">
                <h3 class="card-title">
                    <i class="fas fa-users-cog mr-2"></i>
                    ¿Con qué grupo desea trabajar?
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Buscar grupo</label>
                    <input type="text" id="groupSearch" class="form-control" placeholder="Escribí el nombre del grupo..." autocomplete="off">
                </div>

                <div id="groupResults" class="list-group mt-2" style="max-height: 350px; overflow-y: auto;"></div>

                <div id="noResults" class="text-center text-muted py-3" style="display:none;">
                    <i class="fas fa-search fa-2x mb-2 d-block"></i>
                    No se encontraron grupos activos.
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
let searchTimeout;

function loadGroups(query) {
    $.get('{{ route("group.selector.search") }}', { q: query }, function(groups) {
        const container = $('#groupResults');
        const noResults = $('#noResults');
        container.empty();

        if (groups.length === 0) {
            noResults.show();
            return;
        }

        noResults.hide();
        groups.forEach(function(group) {
            container.append(
                `<a href="#" class="list-group-item list-group-item-action select-group" data-id="${group.id}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users-cog text-success mr-3 fa-lg"></i>
                        <div>
                            <strong>${group.name}</strong>
                            ${group.description ? `<br><small class="text-muted">${group.description}</small>` : ''}
                        </div>
                        <i class="fas fa-chevron-right ml-auto text-muted"></i>
                    </div>
                </a>`
            );
        });
    });
}

// Load all active groups on page load
loadGroups('');

$('#groupSearch').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadGroups($(this).val()), 300);
});

$(document).on('click', '.select-group', function(e) {
    e.preventDefault();
    const groupId = $(this).data('id');

    $.post('{{ route("group.selector.select") }}', {
        group_id: groupId,
        _token: '{{ csrf_token() }}'
    }, function() {
        window.location.href = '{{ route("dashboard") }}';
    });
});
</script>
@endpush
