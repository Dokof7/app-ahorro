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
</script>
@endpush
