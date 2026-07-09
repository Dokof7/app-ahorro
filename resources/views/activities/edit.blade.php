@extends('layouts.app')
@section('page_title', 'Editar Actividad')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Editar Actividad</h3></div>
            <form action="{{ route('activities.update', $activity) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="activity_date" class="form-control @error('activity_date') is-invalid @enderror" value="{{ old('activity_date', $activity->activity_date->format('Y-m-d')) }}" required>
                                @error('activity_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monto Recaudado (Bs.)</label>
                                <input type="number" step="0.01" min="0" name="amount_raised" class="form-control @error('amount_raised') is-invalid @enderror" value="{{ old('amount_raised', $activity->amount_raised) }}">
                                <small class="form-text text-muted">Dejar en blanco si la actividad aún no se realizó.</small>
                                @error('amount_raised')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Actividad <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $activity->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Lugar</label>
                                <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $activity->location) }}">
                                @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Notas</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $activity->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
                    <a href="{{ route('activities.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
