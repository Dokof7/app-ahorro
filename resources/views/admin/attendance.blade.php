@extends('layouts.app')
@section('page_title', 'Asistencia por Grupo')

@section('main_content')

<div class="card card-outline card-primary mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.attendance') }}" class="form-inline">
            <label class="mr-2 font-weight-bold">Grupo:</label>
            <select name="group_id" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">Seleccionar grupo...</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ $selectedGroup?->id == $g->id ? 'selected' : '' }}>
                        {{ $g->name }}
                    </option>
                @endforeach
            </select>
            @if($selectedGroup)
                <span class="badge badge-info px-3 py-2">
                    <i class="fas fa-calendar-alt mr-1"></i>{{ $meetings->count() }} reuniones registradas
                </span>
            @endif
        </form>
    </div>
</div>

@if($selectedGroup && $members->isNotEmpty())

{{-- Summary cards --}}
<div class="row mb-3">
    @php
        $totalMembers   = $members->count();
        $avgPct         = $members->avg('stats.attendance_pct');
        $perfectMembers = $members->filter(fn($m) => $m->stats->absent === 0)->count();
        $criticalMembers = $members->filter(fn($m) => $m->stats->absent >= 3)->count();
    @endphp
    <div class="col-md-3 col-6">
        <div class="info-box mb-2">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Miembros activos</span>
                <span class="info-box-number">{{ $totalMembers }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box mb-2">
            <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Asistencia promedio</span>
                <span class="info-box-number">{{ round($avgPct) }}%</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box mb-2">
            <span class="info-box-icon bg-info"><i class="fas fa-star"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Sin faltas</span>
                <span class="info-box-number">{{ $perfectMembers }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box mb-2">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Con 3+ faltas</span>
                <span class="info-box-number">{{ $criticalMembers }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Resumen por miembro --}}
<div class="card card-outline card-success mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Resumen de Asistencia — {{ $selectedGroup->name }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Miembro</th>
                        <th class="text-center text-success">Asistencias</th>
                        <th class="text-center text-warning">F. c/Permiso</th>
                        <th class="text-center text-danger">Faltas</th>
                        <th class="text-center">% Asistencia</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    @php $s = $member->stats; @endphp
                    <tr>
                        <td><strong>{{ $member->full_name }}</strong></td>
                        <td class="text-center">
                            <span class="badge badge-success px-2">{{ $s->attended }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-warning text-dark px-2">{{ $s->excused }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-danger px-2">{{ $s->absent }}</span>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height:18px; min-width:80px;">
                                <div class="progress-bar {{ $s->attendance_pct >= 80 ? 'bg-success' : ($s->attendance_pct >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                    style="width:{{ $s->attendance_pct }}%">
                                    {{ $s->attendance_pct }}%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($s->absent === 0)
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Perfecto</span>
                            @elseif($s->absent >= 3)
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Crítico</span>
                            @elseif($s->excused > 0 && $s->absent === 0)
                                <span class="badge badge-info"><i class="fas fa-user-clock mr-1"></i>c/Permisos</span>
                            @else
                                <span class="badge badge-warning text-dark"><i class="fas fa-exclamation mr-1"></i>Atención</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Tabla cruzada miembro × reunión --}}
@if($meetings->isNotEmpty())
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th mr-2"></i>Detalle por Reunión</h3>
        <div class="card-tools">
            <small class="text-muted">
                <span class="badge badge-success mr-1">✓</span> Asistió &nbsp;
                <span class="badge badge-warning text-dark mr-1">P</span> c/Permiso &nbsp;
                <span class="badge badge-danger mr-1">✗</span> Falta &nbsp;
                <span class="badge badge-secondary mr-1">—</span> Sin registro
            </small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="min-width: max-content;">
                <thead class="thead-dark">
                    <tr>
                        <th style="min-width:160px; position:sticky; left:0; z-index:2; background:#343a40;">Miembro</th>
                        @foreach($meetings as $meeting)
                        <th class="text-center" style="min-width:60px;" title="{{ $meeting->meeting_date->format('d/m/Y') }}">
                            #{{ $meeting->meeting_number }}<br>
                            <small style="font-weight:normal; font-size:0.7rem;">{{ $meeting->meeting_date->format('d/m') }}</small>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr>
                        <td style="position:sticky; left:0; z-index:1; background:#fff; font-weight:600;">
                            {{ $member->full_name }}
                        </td>
                        @foreach($meetings as $meeting)
                        @php $att = $member->stats->by_meeting->get($meeting->id); @endphp
                        <td class="text-center p-1">
                            @if(!$att)
                                <span class="badge badge-secondary">—</span>
                            @elseif($att->attended)
                                <span class="badge badge-success">✓</span>
                            @elseif($att->excused_absence)
                                <span class="badge badge-warning text-dark">P</span>
                            @else
                                <span class="badge badge-danger">✗</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@elseif($selectedGroup && $members->isEmpty())
<div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i>No hay miembros activos en este grupo.</div>
@else
<div class="alert alert-light border"><i class="fas fa-hand-point-up mr-1"></i>Seleccioná un grupo para ver la asistencia.</div>
@endif

@endsection
