@extends('layouts.app')
@section('page_title', 'Mis Préstamos')
@section('breadcrumb')
    <li class="breadcrumb-item active">Mis Préstamos</li>
@endsection

@section('main_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Préstamos de {{ $member->full_name }}</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Reunión</th>
                    <th>Monto</th>
                    <th>Interés</th>
                    <th>Total a Devolver</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                <tr>
                    <td>{{ $loan->meeting->name ?? '-' }}</td>
                    <td>S/ {{ number_format($loan->amount, 2) }}</td>
                    <td>{{ $loan->interest_rate }}%</td>
                    <td>S/ {{ number_format($loan->total_to_return, 2) }}</td>
                    <td>S/ {{ number_format($loan->amount_paid, 2) }}</td>
                    <td><strong>S/ {{ number_format($loan->balance, 2) }}</strong></td>
                    <td>{{ $loan->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        @php
                            $badges = [
                                'pending' => ['color' => 'warning', 'label' => 'Pendiente'],
                                'paid'    => ['color' => 'success', 'label' => 'Pagado'],
                                'overdue' => ['color' => 'danger',  'label' => 'Vencido'],
                            ];
                            $badge = $badges[$loan->status] ?? ['color' => 'secondary', 'label' => ucfirst($loan->status)];
                        @endphp
                        <span class="badge bg-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                    </td>
                </tr>
                @if($loan->payments->isNotEmpty())
                <tr class="bg-light">
                    <td colspan="8" class="py-1 px-4">
                        <small class="text-muted"><strong>Pagos registrados:</strong>
                            @foreach($loan->payments as $payment)
                                S/ {{ number_format($payment->amount, 2) }} ({{ $payment->payment_date?->format('d/m/Y') }})@if(!$loop->last), @endif
                            @endforeach
                        </small>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No hay préstamos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
