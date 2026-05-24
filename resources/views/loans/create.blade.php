@extends('layouts.app')
@section('page_title', 'Nuevo Préstamo')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Registrar Préstamo</h3></div>
            <form action="{{ route('loans.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo <span class="text-danger">*</span></label>
                                <select name="group_id" id="groupSelect" class="form-control select2 @error('group_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Miembro <span class="text-danger">*</span></label>
                                <select name="member_id" id="memberSelect" class="form-control select2 @error('member_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo primero...</option>
                                </select>
                                @error('member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reunión <span class="text-danger">*</span></label>
                                <select name="meeting_id" id="meetingSelect" class="form-control select2 @error('meeting_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo primero...</option>
                                </select>
                                @error('meeting_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Monto (Bs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tasa de Interés (%)</label>
                                <input type="number" step="0.01" min="0" name="interest_rate" class="form-control" value="{{ old('interest_rate', 0) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Entrega <span class="text-danger">*</span></label>
                                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha Límite de Pago <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observations" class="form-control" rows="2">{{ old('observations') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Registrar Préstamo</button>
                    <a href="{{ route('loans.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
$('#groupSelect').on('change', function() {
    const groupId = $(this).val();
    if (!groupId) return;
    $.get('/loans/members/' + groupId, function(data) {
        let opts = '<option value="">Seleccionar miembro...</option>';
        data.forEach(m => opts += `<option value="${m.id}">${m.full_name}</option>`);
        $('#memberSelect').html(opts);
    });
    $.get('/loans/meetings/' + groupId, function(data) {
        let opts = '<option value="">Seleccionar reunión...</option>';
        data.forEach(m => opts += `<option value="${m.id}">N° ${m.meeting_number} - ${m.meeting_date}</option>`);
        $('#meetingSelect').html(opts);
    });
});
</script>
@endpush
