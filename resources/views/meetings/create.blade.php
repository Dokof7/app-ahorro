@extends('layouts.app')
@section('page_title', 'Nueva Reunión')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title">Nueva Reunión</h3></div>
            <form action="{{ route('meetings.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo <span class="text-danger">*</span></label>
                                <select name="group_id" class="form-control select2 @error('group_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id', $selectedGroup?->id) == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="status" class="form-control">
                                    <option value="open">Abierta</option>
                                    <option value="closed">Cerrada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de la Reunión <span class="text-danger">*</span></label>
                                <input type="date" name="meeting_date" class="form-control" value="{{ old('meeting_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mes <span class="text-danger">*</span></label>
                                <select name="month" class="form-control">
                                    @php $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; @endphp
                                    @foreach($meses as $mes)
                                        <option value="{{ $mes }}" {{ old('month', now()->translatedFormat('F')) == $mes ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observations" class="form-control" rows="3">{{ old('observations') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Al crear la reunión, se generarán automáticamente los registros de asistencia y aportes para todos los miembros activos del grupo.
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Crear Reunión</button>
                    <a href="{{ route('meetings.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
