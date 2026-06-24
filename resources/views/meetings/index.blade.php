@extends('layouts.app')
@section('page_title', 'Reuniones')
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('meetings.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Nueva Reunión</a>
    @endcan
@endsection
@section('main_content')

@php
    $overdueLoans = \App\Models\Loan::whereHas('group', fn($q) => $q->where('user_id', auth()->id()))
        ->where('status', 'pending')->where('due_date', '<', now())->count();
@endphp
@if($overdueLoans > 0)
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <strong>Atención:</strong> Tienes <strong>{{ $overdueLoans }}</strong> préstamo(s) vencido(s).
    <a href="{{ route('loans.index') }}" class="alert-link">Ver préstamos vencidos</a>
</div>
@endif

<div class="row">
    {{-- Meetings table --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Listado de Reuniones</h3>
                <div class="card-tools d-flex">
                    <select id="filterGroup" class="form-control form-control-sm mr-1">
                        <option value="">Todos los grupos</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">Todos los estados</option>
                        <option value="open">Abierta</option>
                        <option value="closed">Cerrada</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped" id="meetingsTable">
                    <thead>
                        <tr><th>N° Reunión</th><th>Grupo</th><th>Fecha</th><th>Mes</th><th>Total Aportes</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Scheduled dates panel --}}
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Fechas Programadas</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="d-block">Grupo</label>
                    <select id="scheduleGroup" class="form-control form-control-sm">
                        <option value="">Seleccionar grupo...</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                @can('canEdit')
                <div class="form-group" id="addDateSection" style="display:none">
                    <label>Agregar fecha</label>
                    <div class="input-group input-group-sm">
                        <input type="date" id="newScheduledDate" class="form-control">
                        <input type="text" id="newScheduledNotes" class="form-control" placeholder="Notas (opcional)">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="btnAddDate" type="button">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endcan

                <div id="scheduledDatesList">
                    <p class="text-muted small">Seleccioná un grupo para ver las fechas.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
var table = $('#meetingsTable').DataTable({
    processing: true, serverSide: true,
    ajax: { url: '{{ route("meetings.index") }}', data: function(d) { d.group_id = $('#filterGroup').val(); d.status = $('#filterStatus').val(); } },
    columns: [
        { data: 'meeting_number', className: 'text-center' }, { data: 'group.name' },
        { data: 'meeting_date' }, { data: 'month' },
        { data: 'total_contributions', className: 'text-right' },
        { data: 'status_badge', orderable: false }, { data: 'actions', orderable: false }
    ],
    order: [[0, 'desc']],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});
$('#filterGroup, #filterStatus').on('change', function() { table.ajax.reload(); });

// Scheduled dates
const csrfToken = '{{ csrf_token() }}';
let currentGroupId = null;

$('#scheduleGroup').on('change', function() {
    currentGroupId = $(this).val();
    if (currentGroupId) {
        $('#addDateSection').show();
        loadScheduledDates(currentGroupId);
    } else {
        $('#addDateSection').hide();
        $('#scheduledDatesList').html('<p class="text-muted small">Seleccioná un grupo para ver las fechas.</p>');
    }
});

function loadScheduledDates(groupId) {
    $.get('{{ route("meeting-scheduled-dates.index") }}', { group_id: groupId }, function(dates) {
        if (!dates.length) {
            $('#scheduledDatesList').html('<p class="text-muted small">No hay fechas programadas.</p>');
            return;
        }
        let html = '<ul class="list-group list-group-flush">';
        dates.forEach(function(d) {
            const dateStr = new Date(d.scheduled_date + 'T00:00:00').toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const used = d.used ? '<span class="badge badge-secondary ml-1">Usada</span>' : '';
            html += `<li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                <span>${dateStr}${used}${d.notes ? '<br><small class="text-muted">' + d.notes + '</small>' : ''}</span>
                @can('canEdit')
                <button class="btn btn-xs btn-outline-danger btn-remove-date" data-id="${d.id}" title="Eliminar"><i class="fas fa-times"></i></button>
                @endcan
            </li>`;
        });
        html += '</ul>';
        $('#scheduledDatesList').html(html);
    });
}

$('#btnAddDate').on('click', function() {
    const date = $('#newScheduledDate').val();
    const notes = $('#newScheduledNotes').val();
    if (!date || !currentGroupId) return;

    $.ajax({
        url: '{{ route("meeting-scheduled-dates.store") }}',
        method: 'POST',
        data: { group_id: currentGroupId, scheduled_date: date, notes: notes, _token: csrfToken },
        success: function() {
            $('#newScheduledDate').val('');
            $('#newScheduledNotes').val('');
            loadScheduledDates(currentGroupId);
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Error al guardar la fecha.';
            alert(msg);
        }
    });
});

$(document).on('click', '.btn-remove-date', function() {
    const id = $(this).data('id');
    if (!confirm('¿Eliminar esta fecha programada?')) return;
    $.ajax({
        url: '/meeting-scheduled-dates/' + id,
        method: 'POST',
        data: { _method: 'DELETE', _token: csrfToken },
        success: function() { loadScheduledDates(currentGroupId); }
    });
});
</script>
@endpush
