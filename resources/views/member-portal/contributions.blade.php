@extends('layouts.app')
@section('page_title', 'Mis Aportes')
@section('breadcrumb')
    <li class="breadcrumb-item active">Mis Aportes</li>
@endsection

@section('main_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-coins mr-2"></i>Aportes de {{ $member->full_name }}</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Reunión</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                    <th>Ahorros</th>
                    <th>Fondo Emerg.</th>
                    <th>Multa</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contributions as $contribution)
                <tr>
                    <td>{{ $contribution->meeting->name ?? '-' }}</td>
                    <td>{{ $contribution->meeting->meeting_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $contribution->shares }}</td>
                    <td>S/ {{ number_format($contribution->savings, 2) }}</td>
                    <td>S/ {{ number_format($contribution->emergency_fund, 2) }}</td>
                    <td>S/ {{ number_format($contribution->fine, 2) }}</td>
                    <td><strong>S/ {{ number_format($contribution->total, 2) }}</strong></td>
                    <td>
                        @if($contribution->confirmed)
                            <span class="badge bg-success">Confirmado</span>
                        @else
                            <span class="badge bg-warning">Pendiente</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No hay aportes registrados.</td>
                </tr>
                @endforelse
            </tbody>
            @if($contributions->isNotEmpty())
            <tfoot>
                <tr class="font-weight-bold">
                    <td colspan="3">Total</td>
                    <td>S/ {{ number_format($contributions->sum('savings'), 2) }}</td>
                    <td>S/ {{ number_format($contributions->sum('emergency_fund'), 2) }}</td>
                    <td>S/ {{ number_format($contributions->sum('fine'), 2) }}</td>
                    <td>S/ {{ number_format($contributions->sum('total'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
