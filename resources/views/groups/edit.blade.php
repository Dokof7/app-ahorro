@extends('layouts.app')
@section('page_title', 'Editar Grupo')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Editar: {{ $group->name }}</h3></div>
            <form action="{{ route('groups.update', $group) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre del Grupo <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $group->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha de Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', $group->start_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $group->description) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ $group->status === 'active' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactive" {{ $group->status === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valor por Acción (Bs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="share_value"
                                    class="form-control @error('share_value') is-invalid @enderror"
                                    value="{{ old('share_value', $group->share_value) }}" required>
                                <small class="text-muted">Costo en Bs. de cada acción de ahorro</small>
                                @error('share_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Acciones por Defecto</label>
                                <input type="number" min="1" max="25" name="default_shares"
                                    class="form-control @error('default_shares') is-invalid @enderror"
                                    value="{{ old('default_shares', $group->default_shares) }}">
                                <small class="text-muted">Acciones sugeridas al registrar aportes (1–25)</small>
                                @error('default_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cuota Fondo Emergencia (Bs.)</label>
                                <input type="number" step="0.01" min="0" name="default_emergency" class="form-control"
                                    value="{{ old('default_emergency', $group->default_emergency) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cuota de Membresía (Bs.)</label>
                                <input type="number" step="0.01" min="0" name="membership_fee"
                                    class="form-control @error('membership_fee') is-invalid @enderror"
                                    value="{{ old('membership_fee', $group->membership_fee) }}">
                                <small class="text-muted">Pago único por miembro durante el ciclo.</small>
                                @error('membership_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Registro <span class="text-danger">*</span></label>
                                @if($hasMeetings)
                                <select class="form-control" disabled>
                                    <option value="full" {{ $group->registration_mode === 'full' ? 'selected' : '' }}>Completo — detalle por miembro</option>
                                    <option value="partial" {{ $group->registration_mode === 'partial' ? 'selected' : '' }}>Parcial — solo totales de la reunión</option>
                                </select>
                                <small class="text-muted">No se puede cambiar el tipo de registro porque el grupo ya tiene reuniones registradas.</small>
                                @else
                                <select name="registration_mode" class="form-control @error('registration_mode') is-invalid @enderror">
                                    <option value="full" {{ old('registration_mode', $group->registration_mode) === 'full' ? 'selected' : '' }}>Completo — detalle por miembro</option>
                                    <option value="partial" {{ old('registration_mode', $group->registration_mode) === 'partial' ? 'selected' : '' }}>Parcial — solo totales de la reunión</option>
                                </select>
                                <small class="text-muted">En el registro parcial, el líder del grupo solo carga los totales de la reunión, sin detalle individual por miembro.</small>
                                @error('registration_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar Grupo</button>
                    <a href="{{ route('groups.show', $group) }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
