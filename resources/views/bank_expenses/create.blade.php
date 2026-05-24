@extends('layouts.app')
@section('page_title', 'Nuevo Gasto Bancario')
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-university mr-2"></i>Registrar Gasto Bancario</h3></div>
            <form action="{{ route('bank-expenses.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grupo <span class="text-danger">*</span></label>
                                <select name="group_id" id="groupSelect" class="form-control select2 @error('group_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar grupo...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reunión <span class="text-danger">*</span></label>
                                <select name="meeting_id" id="meetingSelect" class="form-control select2 @error('meeting_id') is-invalid @enderror" required>
                                    <option value="">{{ $selectedMeeting ? 'Reunión N° ' . $selectedMeeting->meeting_number : 'Seleccionar grupo primero...' }}</option>
                                    @if($selectedMeeting)
                                        <option value="{{ $selectedMeeting->id }}" selected>N° {{ $selectedMeeting->meeting_number }} - {{ $selectedMeeting->meeting_date->format('d/m/Y') }}</option>
                                    @endif
                                </select>
                                @error('meeting_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', date('Y-m-d')) }}" required>
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
                                <label>Concepto <span class="text-danger">*</span></label>
                                <input type="text" name="concept" class="form-control @error('concept') is-invalid @enderror" value="{{ old('concept') }}" required>
                                @error('concept')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar</button>
                    <a href="{{ route('bank-expenses.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
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
    $.get('/api/groups/' + groupId + '/meetings', function(data) {
        let opts = '<option value="">Seleccionar reunión...</option>';
        data.forEach(m => opts += `<option value="${m.id}">N° ${m.meeting_number} - ${m.meeting_date}</option>`);
        $('#meetingSelect').html(opts);
    });
});
</script>
@endpush
