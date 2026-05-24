@extends('layouts.app')
@section('page_title', $member->full_name)
@section('page_actions')
    <a href="{{ route('members.edit', $member) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i>Editar</a>
@endsection
@section('main_content')

<div class="row">
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-piggy-bank"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Ahorrado</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_savings'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shield-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Fondo Emergencia</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_emergency'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-gavel"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Multas</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_fines'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Préstamos Activos</span>
                <span class="info-box-number">{{ $stats['pending_loans'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Datos del Miembro</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Grupo:</strong> {{ $member->group->name }}</p>
                <p><strong>Documento:</strong> {{ $member->document_number ?? '-' }}</p>
                <p><strong>Teléfono:</strong> {{ $member->phone ?? '-' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Dirección:</strong> {{ $member->address ?? '-' }}</p>
                <p><strong>Fecha de ingreso:</strong> {{ $member->join_date->format('d/m/Y') }}</p>
                <p><strong>Estado:</strong>
                    @if($member->status === 'active')<span class="badge bg-success">Activo</span>@else<span class="badge bg-danger">Inactivo</span>@endif
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Historial de Aportes</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Reunión</th><th>Ahorro</th><th>F. Emergencia</th><th>Multa</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($member->contributions as $c)
                <tr>
                    <td>N° {{ $c->meeting->meeting_number }} - {{ $c->meeting->month }}</td>
                    <td>Bs. {{ number_format($c->savings, 2) }}</td>
                    <td>Bs. {{ number_format($c->emergency_fund, 2) }}</td>
                    <td>Bs. {{ number_format($c->fine, 2) }}</td>
                    <td><strong>Bs. {{ number_format($c->total, 2) }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">Sin aportes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
