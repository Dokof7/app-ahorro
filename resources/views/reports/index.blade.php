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
                            <optgroup label="Reportes Financieros">
                                <option value="financial_summary">1. Resumen General del Grupo</option>
                                <option value="savings_evolution">2. Evolución de Ahorros</option>
                                <option value="cash_statement">3. Estado de Caja</option>
                            </optgroup>
                            <optgroup label="Reportes de Miembros">
                                <option value="member_contributions">4. Aportes por Miembro</option>
                                <option value="member_ranking">5. Ranking de Ahorradores</option>
                                <option value="delinquent_members">6. Miembros Morosos</option>
                            </optgroup>
                            <optgroup label="Reportes de Préstamos">
                                <option value="loan_history">7. Historial de Préstamos</option>
                                <option value="active_loans">8. Préstamos Activos</option>
                                <option value="loan_recovery">9. Recuperación de Préstamos</option>
                                <option value="loan_profitability">10. Rentabilidad de Préstamos</option>
                            </optgroup>
                            <optgroup label="Reportes de Reuniones">
                                <option value="meeting_attendance">11. Asistencia a Reuniones</option>
                                <option value="member_participation">12. Participación de Miembros</option>
                            </optgroup>
                            <optgroup label="Reportes de Multas">
                                <option value="fines_generated">13. Multas Generadas</option>
                                <option value="fines_status">14. Multas Cobradas vs Pendientes</option>
                            </optgroup>
                            <optgroup label="Reportes Originales">
                                <option value="group">Resumen de Grupos</option>
                                <option value="member">Historial de Miembros</option>
                                <option value="meeting">Detalle de Reuniones</option>
                                <option value="loans_pending">Préstamos Pendientes</option>
                                <option value="loans_paid">Préstamos Pagados</option>
                                <option value="monthly">Aportes Mensuales</option>
                            </optgroup>
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
                        <select name="group_id" id="group_id" class="form-control select2">
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
                        <select name="member_id" id="member_id" class="form-control select2">
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
                <div class="col-md-4 filter-year">
                    <div class="form-group">
                        <label>Año</label>
                        <select name="year" class="form-control">
                            <option value="">-- Todos --</option>
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
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
const FILTER_MAP = {
    // show member filter
    member_filter: ['member', 'member_contributions', 'member_ranking', 'loan_history',
                    'member_participation', 'fines_generated'],
    // show month filter
    month_filter:  ['meeting', 'monthly', 'fines_generated'],
    // show year filter
    year_filter:   ['savings_evolution', 'cash_statement', 'loan_history',
                    'active_loans', 'loan_profitability', 'meeting_attendance',
                    'member_participation'],
    // show date range filter
    date_filter:   ['meeting'],
};

function loadMembers(groupId) {
    const $select = $('#member_id');
    $select.empty().append('<option value="">-- Todos --</option>');
    if (!groupId) return;
    $.getJSON('{{ route("reports.members", ":groupId") }}'.replace(':groupId', groupId), function(members) {
        members.forEach(function(m) {
            $select.append('<option value="' + m.id + '">' + m.full_name + '</option>');
        });
        if ($select.hasClass('select2-hidden-accessible')) $select.trigger('change');
    });
}

$('#report_type').on('change', function() {
    const type = $(this).val();

    $('.filter-member').toggle(FILTER_MAP.member_filter.includes(type));
    $('.filter-month').toggle(FILTER_MAP.month_filter.includes(type));
    $('.filter-year').toggle(FILTER_MAP.year_filter.includes(type));
    $('.filter-date').toggle(FILTER_MAP.date_filter.includes(type));

    if (!FILTER_MAP.date_filter.includes(type))  $('.filter-date input').val('');
    if (!FILTER_MAP.month_filter.includes(type)) $('[name="month"]').val('');
    if (!FILTER_MAP.year_filter.includes(type))  $('[name="year"]').val('');

    if (FILTER_MAP.member_filter.includes(type)) loadMembers($('#group_id').val());
}).trigger('change');

$('#group_id').on('change', function() {
    const type = $('#report_type').val();
    if (FILTER_MAP.member_filter.includes(type)) loadMembers($(this).val());
});
</script>
@endpush
