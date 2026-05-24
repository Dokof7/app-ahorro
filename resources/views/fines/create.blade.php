@extends('layouts.app')
@section('page_title', 'Nueva Multa')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Registrar Multa</h3></div>
            <form action="{{ route('fines.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo</label>
                                <select id="groupSelect" class="form-control select2">
                                    <option value="">Seleccionar grupo...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monto (Bs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Motivo <span class="text-danger">*</span></label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" required>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Registrar Multa</button>
                    <a href="{{ route('fines.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
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
    $.get('/api/groups/' + groupId + '/members', function(data) {
        let opts = '<option value="">Seleccionar miembro...</option>';
        data.forEach(m => opts += `<option value="${m.id}">${m.full_name}</option>`);
        $('#memberSelect').html(opts);
    });
    $.get('/api/groups/' + groupId + '/meetings', function(data) {
        let opts = '<option value="">Seleccionar reunión...</option>';
        data.forEach(m => opts += `<option value="${m.id}">N° ${m.meeting_number} - ${m.meeting_date}</option>`);
        $('#meetingSelect').html(opts);
    });
});
</script>
@endpush
