@extends('layouts.app')
@section('page_title', 'Reportes')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reportes</li>
@endsection
@section('main_content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Generar Reporte</h3></div>
    <form action="{{ route('reports.generate') }}" method="POST" target="_blank">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tipo de Reporte <span class="text-danger">*</span></label>
                        <select name="report_type" id="report_type" class="form-control select2" required>
                            <option value="">-- Seleccionar tipo --</option>
                            <option value="group">Resumen de Grupos</option>
                            <option value="member">Historial de Miembros</option>
                            <option value="meeting">Detalle de Reuniones</option>
                            <option value="loans_pending">Préstamos Pendientes</option>
                            <option value="loans_paid">Préstamos Pagados</option>
                            <option value="monthly">Aportes Mensuales</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Formato <span class="text-danger">*</span></label>
                        <select name="format" class="form-control" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel (.xlsx)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Grupo</label>
                        <select name="group_id" class="form-control select2">
                            <option value="">-- Todos los grupos --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6 filter-member">
                    <div class="form-group">
                        <label>Miembro</label>
                        <select name="member_id" class="form-control select2">
                            <option value="">-- Todos --</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 filter-month">
                    <div class="form-group">
                        <label>Mes</label>
                        <select name="month" class="form-control">
                            <option value="">-- Todos los meses --</option>
                            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $mes)
                            <option value="{{ $mes }}">{{ $mes }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4 filter-date">
                    <div class="form-group">
                        <label>Fecha Desde</label>
                        <input type="date" name="date_from" class="form-control">
                    </div>
                </div>
                <div class="col-md-4 filter-date">
                    <div class="form-group">
                        <label>Fecha Hasta</label>
                        <input type="date" name="date_to" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-download mr-1"></i>Generar Reporte</button>
        </div>
    </form>
</div>
@endsection
@push('js')
<script>
$('#report_type').on('change', function() {
    const type = $(this).val();
    $('.filter-member').toggle(type === 'member');
    $('.filter-month').toggle(['meeting', 'monthly'].includes(type));
    $('.filter-date').toggle(type === 'meeting');
}).trigger('change');
</script>
@endpush
