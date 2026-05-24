@extends('layouts.app')
@section('page_title', 'Préstamo #' . $loan->id)
@section('main_content')

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Datos del Préstamo</h3></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Miembro:</th><td>{{ $loan->member->full_name }}</td></tr>
                    <tr><th>Grupo:</th><td>{{ $loan->group->name }}</td></tr>
                    <tr><th>Reunión aprobada:</th><td>N° {{ $loan->meeting->meeting_number }}</td></tr>
                    <tr><th>Monto prestado:</th><td>Bs. {{ number_format($loan->amount, 2) }}</td></tr>
                    <tr><th>Tasa de interés:</th><td>{{ $loan->interest_rate }}%</td></tr>
                    <tr><th>Interés:</th><td>Bs. {{ number_format($loan->interest_amount, 2) }}</td></tr>
                    <tr><th>Total a devolver:</th><td><strong>Bs. {{ number_format($loan->total_to_return, 2) }}</strong></td></tr>
                    <tr><th>Total pagado:</th><td>Bs. {{ number_format($loan->amount_paid, 2) }}</td></tr>
                    <tr><th>Saldo restante:</th><td class="{{ $loan->balance > 0 ? 'text-danger' : 'text-success' }}"><strong>Bs. {{ number_format($loan->balance, 2) }}</strong></td></tr>
                    <tr><th>Fecha entrega:</th><td>{{ $loan->delivery_date->format('d/m/Y') }}</td></tr>
                    <tr><th>Fecha límite:</th><td>{{ $loan->due_date->format('d/m/Y') }} @if($loan->isOverdue())<span class="badge bg-danger ml-1">VENCIDO</span>@endif</td></tr>
                    <tr><th>Estado:</th><td>
                        @switch($loan->status)
                            @case('pending') <span class="badge bg-warning">Pendiente</span> @break
                            @case('paid') <span class="badge bg-success">Pagado</span> @break
                            @case('overdue') <span class="badge bg-danger">Vencido</span> @break
                        @endswitch
                    </td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Registrar Pago</h3></div>
            @if($loan->balance > 0)
            <form action="{{ route('loan-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Pago</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reunión (opcional)</label>
                                <select name="meeting_id" class="form-control select2">
                                    <option value="">Sin reunión específica</option>
                                    @foreach($loan->group->meetings as $m)
                                        <option value="{{ $m->id }}">N° {{ $m->meeting_number }} - {{ $m->meeting_date->format('d/m/Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monto Pagado (Bs.) <small class="text-muted">Saldo: {{ number_format($loan->balance, 2) }}</small></label>
                                <input type="number" step="0.01" min="0.01" max="{{ $loan->balance }}" name="amount_paid" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Interés Pagado (Bs.)</label>
                                <input type="number" step="0.01" min="0" name="interest_paid" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <input type="text" name="observations" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-money-bill mr-1"></i>Registrar Pago</button>
                </div>
            </form>
            @else
            <div class="card-body">
                <div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-1"></i>Préstamo completamente pagado.</div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Historial de Pagos</h3></div>
    <div class="card-body">
        @if($loan->payments->isEmpty())
            <p class="text-muted">No hay pagos registrados.</p>
        @else
        <table class="table table-bordered table-sm">
            <thead class="thead-dark"><tr><th>Fecha</th><th>Monto Pagado</th><th>Interés Pagado</th><th>Saldo Restante</th><th>Observaciones</th><th></th></tr></thead>
            <tbody>
                @foreach($loan->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>Bs. {{ number_format($payment->amount_paid, 2) }}</td>
                    <td>Bs. {{ number_format($payment->interest_paid, 2) }}</td>
                    <td>Bs. {{ number_format($payment->remaining_balance, 2) }}</td>
                    <td>{{ $payment->observations }}</td>
                    <td>
                        <form action="{{ route('loan-payments.destroy', $payment) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger btn-delete-confirm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
