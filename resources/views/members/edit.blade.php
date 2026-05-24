@extends('layouts.app')
@section('page_title', 'Editar Miembro')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Editar: {{ $member->full_name }}</h3></div>
            <form action="{{ route('members.update', $member) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                    value="{{ old('full_name', $member->full_name) }}" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo</label>
                                <input type="text" class="form-control" value="{{ $member->group->name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CI / Documento</label>
                                <input type="text" name="document_number" class="form-control" value="{{ old('document_number', $member->document_number) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha de Ingreso <span class="text-danger">*</span></label>
                                <input type="date" name="join_date" class="form-control" value="{{ old('join_date', $member->join_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dirección</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address', $member->address) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ $member->status === 'active' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactive" {{ $member->status === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
                    <a href="{{ route('members.show', $member) }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
