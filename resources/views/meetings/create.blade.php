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
                        <div class="col-md-9">
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
                                <input type="date" id="meeting_date" name="meeting_date" class="form-control"
                                    value="{{ old('meeting_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mes</label>
                                <input type="text" id="month_display" class="form-control" readonly
                                    style="background:#f4f6f9; cursor:default;">
                                <input type="hidden" id="month" name="month" value="">
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

@push('js')
<script>
const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function updateMonth(dateVal) {
    if (!dateVal) return;
    const month = meses[new Date(dateVal + 'T00:00:00').getMonth()];
    document.getElementById('month_display').value = month;
    document.getElementById('month').value = month;
}

const dateInput = document.getElementById('meeting_date');
dateInput.addEventListener('change', function() { updateMonth(this.value); });
updateMonth(dateInput.value);
</script>
@endpush
