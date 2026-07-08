@extends('layouts.app')
@section('page_title', 'Nuevo Miembro')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Registrar Miembro</h3></div>
            <form action="{{ route('members.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo <span class="text-danger">*</span></label>
                                <select name="group_id" class="form-control select2 @error('group_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ (old('group_id', $selectedGroup->id ?? '') == $group->id) ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                    value="{{ old('full_name') }}" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CI / Documento</label>
                                <input type="text" name="document_number" class="form-control" value="{{ old('document_number') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha de Ingreso <span class="text-danger">*</span></label>
                                <input type="date" name="join_date" class="form-control" value="{{ old('join_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dirección</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ciclo <span class="text-danger">*</span></label>
                                <input type="number" name="cycle" class="form-control @error('cycle') is-invalid @enderror"
                                    value="{{ old('cycle', 1) }}" min="1" max="99" required>
                                <small class="form-text text-muted">Número de ciclo en el que participa el miembro.</small>
                                @error('cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                        <div class="col-md-12">
                            <hr>
                            <h6 class="text-muted"><i class="fas fa-user-circle mr-1"></i>Cuenta de usuario (opcional)</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Vincular usuario existente</label>
                                <select name="user_id" id="user_id"
                                    class="form-control @error('user_id') is-invalid @enderror"
                                    style="width:100%">
                                    <option value="">Sin vincular</option>
                                </select>
                                <small class="form-text text-muted">Buscá por nombre o email. Solo muestra usuarios sin miembro asignado.</small>
                                @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar Miembro</button>
                    <a href="{{ route('members.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
$('#user_id').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: 'Buscar usuario por nombre o email...',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
        url: '{{ route("members.search-users") }}',
        dataType: 'json',
        delay: 300,
        data: function(params) { return { q: params.term }; },
        processResults: function(data) { return { results: data.results }; },
        cache: true
    }
});
</script>
@endpush
