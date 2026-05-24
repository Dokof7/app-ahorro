@extends('layouts.app')
@section('page_title', 'Crear Nuevo Grupo')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Nuevo Grupo de Ahorro</h3></div>
            <form action="{{ route('groups.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre del Grupo <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required placeholder="Ej: Grupo de Ahorro Familiar">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha de Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', date('Y-m-d')) }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="status" class="form-control">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valor por Acción (Bs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="share_value"
                                    class="form-control @error('share_value') is-invalid @enderror"
                                    value="{{ old('share_value', 10) }}" required>
                                <small class="text-muted">Costo en Bs. de cada acción de ahorro</small>
                                @error('share_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Acciones por Defecto</label>
                                <input type="number" min="1" max="25" name="default_shares"
                                    class="form-control @error('default_shares') is-invalid @enderror"
                                    value="{{ old('default_shares') }}" placeholder="Opcional">
                                <small class="text-muted">Acciones sugeridas al registrar aportes (1–25). Dejar vacío para iniciar sin monto.</small>
                                @error('default_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cuota Fondo Emergencia (Bs.)</label>
                                <input type="number" step="0.01" min="0" name="default_emergency"
                                    class="form-control" value="{{ old('default_emergency', 0) }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar Grupo</button>
                    <a href="{{ route('groups.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
