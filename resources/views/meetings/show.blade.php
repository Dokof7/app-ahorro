@extends('layouts.app')

@section('page_title', 'Reunión N° ' . $meeting->meeting_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('meetings.index') }}">Reuniones</a></li>
    <li class="breadcrumb-item active">Reunión #{{ $meeting->meeting_number }}</li>
@endsection

@section('page_actions')
    @if($meeting->isOpen())
        <form action="{{ route('meetings.close', $meeting) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('¿Cerrar esta reunión? No se podrá editar después.')">
                <i class="fas fa-lock mr-1"></i>Cerrar Reunión
            </button>
        </form>
        <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-secondary ml-1"><i class="fas fa-edit mr-1"></i>Editar</a>
    @else
        <span class="badge badge-secondary badge-lg p-2 mr-2"><i class="fas fa-lock mr-1"></i>Reunión Cerrada</span>
        @can('update', $meeting)
        <form action="{{ route('meetings.reopen', $meeting) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-lock-open mr-1"></i>Reabrir</button>
        </form>
        @endcan
    @endif
    <a href="{{ route('reports.index') }}" class="btn btn-info ml-1"><i class="fas fa-file-pdf mr-1"></i>Imprimir</a>
@endsection

@section('main_content')

<div class="row mb-3">
    <div class="col-md-8">
        <div class="card card-outline card-{{ $meeting->isOpen() ? 'success' : 'secondary' }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-3"><strong>Grupo:</strong></div><div class="col-sm-9">{{ $meeting->group->name }}</div>
                    <div class="col-sm-3"><strong>Reunión N°:</strong></div><div class="col-sm-9">{{ $meeting->meeting_number }}</div>
                    <div class="col-sm-3"><strong>Fecha:</strong></div><div class="col-sm-9">{{ $meeting->meeting_date->format('d/m/Y') }}</div>
                    <div class="col-sm-3"><strong>Mes:</strong></div><div class="col-sm-9">{{ $meeting->month }}</div>
                    <div class="col-sm-3"><strong>Estado:</strong></div>
                    <div class="col-sm-9">
                        @if($meeting->isOpen())<span class="badge bg-success">Abierta</span>@else<span class="badge bg-secondary">Cerrada</span>@endif
                    </div>
                    @if($meeting->observations)
                    <div class="col-sm-3"><strong>Observaciones:</strong></div><div class="col-sm-9">{{ $meeting->observations }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <h6 class="mb-2 text-white"><i class="fas fa-calculator mr-1"></i>Resumen de la Reunión</h6>
                <div class="d-flex justify-content-between mb-1"><span>Total Ahorros:</span><strong>Bs. {{ number_format($meeting->total_savings, 2) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Fondo Emergencia:</span><strong>Bs. {{ number_format($meeting->total_emergency, 2) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Multas:</span><strong>Bs. {{ number_format($meeting->total_fines, 2) }}</strong></div>
                <hr class="bg-white my-1">
                <div class="d-flex justify-content-between"><span><strong>Total Recaudado:</strong></span><strong>Bs. {{ number_format($meeting->total_savings + $meeting->total_emergency + $meeting->total_fines, 2) }}</strong></div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs" id="meetingTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#attendance"><i class="fas fa-clipboard-check mr-1"></i>Asistencia</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#contributions"><i class="fas fa-coins mr-1"></i>Aportes</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#summary"><i class="fas fa-chart-bar mr-1"></i>Resumen General</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#loans"><i class="fas fa-hand-holding-usd mr-1"></i>Préstamos</a></li>
</ul>

<div class="tab-content card card-body rounded-0">

    <div class="tab-pane fade" id="contributions">
        <h5 class="mb-3">Control de Aportes por Miembro</h5>
        @if($meeting->isOpen())
        <form action="{{ route('meetings.contributions.bulk', $meeting) }}" method="POST" id="contributionsForm">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr><th>N°</th><th>Nombre del Miembro</th><th>Acciones (×Bs.25)</th><th>Ahorro (Bs.)</th><th>Fondo Emergencia (Bs.)</th><th>Multa (Bs.)</th><th>Total (Bs.)</th><th class="text-center">Confirmado</th><th>Observaciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->contributions as $i => $contribution)
                        <input type="hidden" name="contributions[{{ $i }}][id]" value="{{ $contribution->id }}">
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $contribution->member->full_name }}</strong></td>
                            <td>
                                <input type="number" min="0" max="25" name="contributions[{{ $i }}][shares]"
                                    value="{{ $contribution->shares ?? 1 }}"
                                    class="form-control form-control-sm contribution-input" style="width:75px"
                                    data-row="{{ $i }}" data-field="shares">
                            </td>
                            <td><strong class="row-savings" id="savings-{{ $i }}">{{ number_format($contribution->savings, 2) }}</strong></td>
                            <td><input type="number" step="0.01" min="0" name="contributions[{{ $i }}][emergency_fund]" value="{{ $contribution->emergency_fund }}" class="form-control form-control-sm contribution-input" data-row="{{ $i }}" data-field="emergency_fund"></td>
                            <td><input type="number" step="0.01" min="0" name="contributions[{{ $i }}][fine]" value="{{ $contribution->fine }}" class="form-control form-control-sm contribution-input" data-row="{{ $i }}" data-field="fine"></td>
                            <td><strong class="row-total" id="total-{{ $i }}">{{ number_format($contribution->total, 2) }}</strong></td>
                            <td class="text-center"><input type="checkbox" name="contributions[{{ $i }}][confirmed]" value="1" class="form-check-input" {{ $contribution->confirmed ? 'checked' : '' }}></td>
                            <td><input type="text" name="contributions[{{ $i }}][observations]" value="{{ $contribution->observations }}" class="form-control form-control-sm" placeholder="Observaciones..."></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-success font-weight-bold">
                            <td colspan="2">TOTALES</td>
                            <td id="footer-shares"></td>
                            <td id="footer-savings">{{ number_format($meeting->total_savings, 2) }}</td>
                            <td id="footer-emergency">{{ number_format($meeting->total_emergency, 2) }}</td>
                            <td id="footer-fines">{{ number_format($meeting->total_fines, 2) }}</td>
                            <td id="footer-total">{{ number_format($meeting->total_savings + $meeting->total_emergency + $meeting->total_fines, 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="submit" class="btn btn-success mt-2"><i class="fas fa-save mr-1"></i>Guardar Aportes</button>
        </form>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark"><tr><th>N°</th><th>Nombre del Miembro</th><th>Acciones</th><th>Ahorro (Bs.)</th><th>Fondo Emergencia</th><th>Multa</th><th>Total</th><th>Confirmado</th></tr></thead>
                <tbody>
                    @foreach($meeting->contributions as $i => $contribution)
                    <tr>
                        <td>{{ $i + 1 }}</td><td>{{ $contribution->member->full_name }}</td>
                        <td class="text-center"><span class="badge bg-primary">{{ $contribution->shares ?? 1 }} acc.</span></td>
                        <td>Bs. {{ number_format($contribution->savings, 2) }}</td>
                        <td>Bs. {{ number_format($contribution->emergency_fund, 2) }}</td>
                        <td>Bs. {{ number_format($contribution->fine, 2) }}</td>
                        <td><strong>Bs. {{ number_format($contribution->total, 2) }}</strong></td>
                        <td>@if($contribution->confirmed)<span class="badge bg-success">✓ Confirmado</span>@else<span class="badge bg-secondary">Pendiente</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="tab-pane fade show active" id="attendance">
        <h5 class="mb-1">Registro de Asistencia</h5>
        @if($meeting->isOpen())
        <form action="{{ route('meetings.attendance.update', $meeting) }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark"><tr><th>N°</th><th>Nombre</th><th class="text-center">Asistió</th><th class="text-center">Falta c/Perm.</th><th class="text-center">Pagó Ahorro</th><th class="text-center">Pagó Emergencia</th><th class="text-center">Tiene Multa</th><th>Observaciones</th></tr></thead>
                    <tbody>
                        @foreach($meeting->attendances as $i => $att)
                        <input type="hidden" name="attendances[{{ $i }}][id]" value="{{ $att->id }}">
                        <tr>
                            <td>{{ $i + 1 }}</td><td><strong>{{ $att->member->full_name }}</strong></td>
                            <td class="text-center"><input type="checkbox" name="attendances[{{ $i }}][attended]" value="1" {{ $att->attended ? 'checked' : '' }} class="att-attended" data-row="{{ $i }}"></td>
                            <td class="text-center"><input type="checkbox" name="attendances[{{ $i }}][excused_absence]" value="1" {{ $att->excused_absence ? 'checked' : '' }} class="att-excused" data-row="{{ $i }}"></td>
                            <td class="text-center"><input type="checkbox" name="attendances[{{ $i }}][paid_savings]" value="1" {{ $att->paid_savings ? 'checked' : '' }}></td>
                            <td class="text-center"><input type="checkbox" name="attendances[{{ $i }}][paid_emergency]" value="1" {{ $att->paid_emergency ? 'checked' : '' }}></td>
                            <td class="text-center"><input type="checkbox" name="attendances[{{ $i }}][has_fine]" value="1" {{ $att->has_fine ? 'checked' : '' }}></td>
                            <td><input type="text" name="attendances[{{ $i }}][observations]" value="{{ $att->observations }}" class="form-control form-control-sm" placeholder="Obs..."></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success mt-2"><i class="fas fa-save mr-1"></i>Guardar Asistencia</button>
        </form>
        @else
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-dark"><tr><th>N°</th><th>Nombre</th><th class="text-center">Estado</th><th class="text-center">Pagó Ahorro</th><th class="text-center">Pagó Emergencia</th><th class="text-center">Multa</th><th>Observaciones</th></tr></thead>
                <tbody>
                    @foreach($meeting->attendances as $i => $att)
                    <tr>
                        <td>{{ $i + 1 }}</td><td>{{ $att->member->full_name }}</td>
                        <td class="text-center">
                            @if($att->attended)
                                <span class="badge bg-success">Asistió</span>
                            @elseif($att->excused_absence)
                                <span class="badge bg-warning text-dark">Falta c/Permiso</span>
                            @else
                                <span class="badge bg-danger">Falta</span>
                            @endif
                        </td>
                        <td class="text-center">{!! $att->paid_savings ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>' !!}</td>
                        <td class="text-center">{!! $att->paid_emergency ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>' !!}</td>
                        <td class="text-center">{!! $att->has_fine ? '<span class="text-warning">⚠</span>' : '<span class="text-success">-</span>' !!}</td>
                        <td>{{ $att->observations }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="tab-pane fade" id="summary">

        @php
            $attendedCount   = $meeting->attendances->where('attended', true)->count();
            $totalMembers    = $meeting->attendances->count();
            $totalShares     = $meeting->contributions->sum('shares');
            $totalSavings    = $meeting->contributions->sum('savings');
            $totalEmergency  = $meeting->contributions->sum('emergency_fund');
            $totalFines      = $meeting->contributions->sum('fine');
            $totalRecaudado  = $totalSavings + $totalEmergency + $totalFines;
            $shareValue      = $meeting->group->share_value ?? 10;
        @endphp

        {{-- Tarjetas resumen --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Asistencia</span>
                        <span class="info-box-number">{{ $attendedCount }} / {{ $totalMembers }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-piggy-bank"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Ahorros</span>
                        <span class="info-box-number">Bs. {{ number_format($totalSavings, 2) }}</span>
                        <span class="progress-description">{{ $totalShares }} acciones × Bs. {{ number_format($shareValue, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shield-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Fondo Emergencia</span>
                        <span class="info-box-number">Bs. {{ number_format($totalEmergency, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-gavel"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Multas</span>
                        <span class="info-box-number">Bs. {{ number_format($totalFines, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle por miembro --}}
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i>Detalle de Aportes por Miembro</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>N°</th>
                                <th>Miembro</th>
                                <th class="text-center">Asistió</th>
                                <th class="text-center">Acciones</th>
                                <th class="text-right">Ahorro (Bs.)</th>
                                <th class="text-right">F. Emergencia (Bs.)</th>
                                <th class="text-right">Multa (Bs.)</th>
                                <th class="text-right">Total (Bs.)</th>
                                <th class="text-center">Confirmado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meeting->contributions as $i => $c)
                            @php
                                $att = $meeting->attendances->firstWhere('member_id', $c->member_id);
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $c->member->full_name }}</strong></td>
                                <td class="text-center">
                                    @if($att && $att->attended)
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Asistió</span>
                                    @elseif($att && $att->excused_absence)
                                        <span class="badge bg-warning text-dark"><i class="fas fa-user-clock"></i> c/Permiso</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times"></i> Falta</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $c->shares ?? 1 }}</span>
                                </td>
                                <td class="text-right">{{ number_format($c->savings, 2) }}</td>
                                <td class="text-right">{{ number_format($c->emergency_fund, 2) }}</td>
                                <td class="text-right">{{ number_format($c->fine, 2) }}</td>
                                <td class="text-right"><strong>{{ number_format($c->total, 2) }}</strong></td>
                                <td class="text-center">
                                    @if($c->confirmed)
                                        <span class="badge bg-success">✓</span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success font-weight-bold">
                                <td colspan="3" class="text-right">SUBTOTALES:</td>
                                <td class="text-center">{{ $totalShares }} acc.</td>
                                <td class="text-right">Bs. {{ number_format($totalSavings, 2) }}</td>
                                <td class="text-right">Bs. {{ number_format($totalEmergency, 2) }}</td>
                                <td class="text-right">Bs. {{ number_format($totalFines, 2) }}</td>
                                <td class="text-right">Bs. {{ number_format($totalRecaudado, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Total general --}}
        <div class="card bg-dark text-white mb-3">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-md-3 border-right">
                        <div class="text-muted small">Ahorros</div>
                        <div class="h5 mb-0">Bs. {{ number_format($totalSavings, 2) }}</div>
                    </div>
                    <div class="col-md-3 border-right">
                        <div class="text-muted small">Fondo Emergencia</div>
                        <div class="h5 mb-0">Bs. {{ number_format($totalEmergency, 2) }}</div>
                    </div>
                    <div class="col-md-3 border-right">
                        <div class="text-muted small">Multas</div>
                        <div class="h5 mb-0">Bs. {{ number_format($totalFines, 2) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-warning small font-weight-bold">TOTAL RECAUDADO</div>
                        <div class="h4 mb-0 text-warning">Bs. {{ number_format($totalRecaudado, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumen de fondos acumulados --}}
        @if($meeting->summary)
        <div class="card card-outline card-info mb-3">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Fondos Acumulados del Grupo</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td>Total reunión anterior:</td><td class="text-right"><strong>Bs. {{ number_format($meeting->summary->previous_total, 2) }}</strong></td></tr>
                            <tr><td>+ Ingresos/Ahorros:</td><td class="text-right text-success"><strong>Bs. {{ number_format($meeting->summary->income_savings, 2) }}</strong></td></tr>
                            <tr><td>− Egresos por Préstamos:</td><td class="text-right text-danger"><strong>Bs. {{ number_format($meeting->summary->loan_outflow, 2) }}</strong></td></tr>
                            <tr><td>− Gastos Bancarios:</td><td class="text-right text-danger"><strong>Bs. {{ number_format($meeting->summary->bank_expenses_total, 2) }}</strong></td></tr>
                            <tr class="border-top"><td><strong>= Total Fondos Grupo:</strong></td><td class="text-right"><strong class="text-success">Bs. {{ number_format($meeting->summary->total_group_funds, 2) }}</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td>Fondo de Emergencia:</td><td class="text-right"><strong>Bs. {{ number_format($meeting->summary->total_emergency_funds, 2) }}</strong></td></tr>
                            <tr><td>Ingreso por Multas:</td><td class="text-right"><strong>Bs. {{ number_format($meeting->summary->income_fines, 2) }}</strong></td></tr>
                            <tr><td>Ingreso por Intereses:</td><td class="text-right"><strong>Bs. {{ number_format($meeting->summary->income_interest, 2) }}</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Gastos bancarios --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-university mr-2"></i>Gastos Bancarios</h3>
                <div class="card-tools">
                    @if($meeting->isOpen())
                    <a href="{{ route('bank-expenses.create') }}?meeting_id={{ $meeting->id }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-plus mr-1"></i>Agregar
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if($meeting->bankExpenses->isNotEmpty())
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light"><tr><th>Concepto</th><th>Fecha</th><th class="text-right">Monto (Bs.)</th><th>Observaciones</th></tr></thead>
                    <tbody>
                        @foreach($meeting->bankExpenses as $expense)
                        <tr>
                            <td>{{ $expense->concept }}</td>
                            <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td class="text-right">{{ number_format($expense->amount, 2) }}</td>
                            <td>{{ $expense->observations ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-warning font-weight-bold">
                            <td colspan="2" class="text-right">Total Gastos:</td>
                            <td class="text-right">Bs. {{ number_format($meeting->bankExpenses->sum('amount'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <div class="p-3 text-muted">No hay gastos bancarios registrados.</div>
                @endif
            </div>
        </div>

    </div>

    <div class="tab-pane fade" id="loans">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Préstamos aprobados en esta Reunión</h5>
            @if($meeting->isOpen())
            <a href="{{ route('loans.create') }}?meeting_id={{ $meeting->id }}" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i>Nuevo Préstamo</a>
            @endif
        </div>
        @if($meeting->loans->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-dark"><tr><th>Miembro</th><th>Monto</th><th>Interés</th><th>Total a Devolver</th><th>Vencimiento</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach($meeting->loans as $loan)
                    <tr class="{{ $loan->isOverdue() ? 'table-danger' : '' }}">
                        <td>{{ $loan->member->full_name }}</td>
                        <td>Bs. {{ number_format($loan->amount, 2) }}</td>
                        <td>{{ $loan->interest_rate }}%</td>
                        <td>Bs. {{ number_format($loan->total_to_return, 2) }}</td>
                        <td>{{ $loan->due_date->format('d/m/Y') }} @if($loan->isOverdue())<span class="badge bg-danger ml-1">Vencido</span>@endif</td>
                        <td>
                            @switch($loan->status)
                                @case('pending') <span class="badge bg-warning">Pendiente</span> @break
                                @case('paid') <span class="badge bg-success">Pagado</span> @break
                                @case('overdue') <span class="badge bg-danger">Vencido</span> @break
                            @endswitch
                        </td>
                        <td><a href="{{ route('loans.show', $loan) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted">No hay préstamos registrados en esta reunión.</p>
        @endif
    </div>

</div>
@endsection

@push('js')
<script>
// Asistió y Falta c/Permiso son mutuamente excluyentes
$(document).on('change', '.att-attended', function() {
    if ($(this).is(':checked')) {
        const row = $(this).data('row');
        $(`.att-excused[data-row="${row}"]`).prop('checked', false);
    }
});
$(document).on('change', '.att-excused', function() {
    if ($(this).is(':checked')) {
        const row = $(this).data('row');
        $(`.att-attended[data-row="${row}"]`).prop('checked', false);
    }
});

const SHARE_VALUE = {{ $meeting->group->share_value ?? 10 }};

$(document).on('input', '.contribution-input', function() {
    const row = $(this).data('row');
    const shares   = parseInt($(`input[name="contributions[${row}][shares]"]`).val()) || 1;
    const savings  = shares * SHARE_VALUE;
    const emergency = parseFloat($(`input[name="contributions[${row}][emergency_fund]"]`).val()) || 0;
    const fine      = parseFloat($(`input[name="contributions[${row}][fine]"]`).val()) || 0;

    $(`#savings-${row}`).text(savings.toFixed(2));
    $(`#total-${row}`).text((savings + emergency + fine).toFixed(2));

    let totalShares = 0, totalSavings = 0, totalEmergency = 0, totalFines = 0;
    $('input[name*="[shares]"]').each(function() { totalShares += parseInt($(this).val()) || 0; });
    $('input[name*="[emergency_fund]"]').each(function() { totalEmergency += parseFloat($(this).val()) || 0; });
    $('input[name*="[fine]"]').each(function() { totalFines += parseFloat($(this).val()) || 0; });
    totalSavings = totalShares * SHARE_VALUE;

    $('#footer-shares').text(totalShares + ' acc.');
    $('#footer-savings').text(totalSavings.toFixed(2));
    $('#footer-emergency').text(totalEmergency.toFixed(2));
    $('#footer-fines').text(totalFines.toFixed(2));
    $('#footer-total').text((totalSavings + totalEmergency + totalFines).toFixed(2));
});
</script>
@endpush
