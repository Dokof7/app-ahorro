@extends('layouts.app')
@section('page_title', 'Editar Gasto Bancario')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Editar Gasto Bancario</h3></div>
            <form action="{{ route('bank-expenses.update', $bankExpense) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $bankExpense->expense_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monto (Bs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $bankExpense->amount) }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Concepto <span class="text-danger">*</span></label>
                                <input type="text" name="concept" class="form-control" value="{{ old('concept', $bankExpense->concept) }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observations" class="form-control" rows="2">{{ old('observations', $bankExpense->observations) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
                    <a href="{{ route('bank-expenses.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
