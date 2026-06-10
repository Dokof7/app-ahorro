@extends('layouts.app')
@section('page_title', 'Grupo: ' . $group->name)
@section('page_actions')
    @can('canEdit')
    <a href="{{ route('groups.edit', $group) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i>Editar</a>
    <a href="{{ route('meetings.create', ['group_id' => $group->id]) }}" class="btn btn-success ml-1"><i class="fas fa-plus mr-1"></i>Nueva Reunión</a>
    @endcan
@endsection
@section('main_content')

<div class="row">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Miembros</span>
                <span class="info-box-number">{{ $stats['total_members'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-calendar"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Reuniones</span>
                <span class="info-box-number">{{ $stats['total_meetings'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Préstamos Pendientes</span>
                <span class="info-box-number">{{ $stats['pending_loans'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Información del Grupo</h3></div>
    <div class="card-body">
        <p>{{ $group->description }}</p>
        <p><strong>Inicio:</strong> {{ $group->start_date->format('d/m/Y') }} &nbsp;
           <strong>Estado:</strong>
           @if($group->status === 'active')<span class="badge bg-success">Activo</span>@else<span class="badge bg-danger">Inactivo</span>@endif
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Miembros</h3>
        <div class="card-tools">
            <a href="{{ route('members.create') }}?group_id={{ $group->id }}" class="btn btn-sm btn-success"><i class="fas fa-user-plus mr-1"></i>Agregar</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead><tr><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Ingreso</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse($group->members as $member)
                <tr>
                    <td><a href="{{ route('members.show', $member) }}">{{ $member->full_name }}</a></td>
                    <td>{{ $member->document_number ?? '-' }}</td>
                    <td>{{ $member->phone ?? '-' }}</td>
                    <td>{{ $member->join_date->format('d/m/Y') }}</td>
                    <td>@if($member->status === 'active')<span class="badge bg-success">Activo</span>@else<span class="badge bg-danger">Inactivo</span>@endif</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No hay miembros registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
