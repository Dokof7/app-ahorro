@extends('layouts.app')
@section('page_title', 'Editar Reunión N° ' . $meeting->meeting_number)
@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Editar Reunión</h3></div>
            <form action="{{ route('meetings.update', $meeting) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de la Reunión <span class="text-danger">*</span></label>
                                <input type="date" name="meeting_date" class="form-control"
                                    value="{{ old('meeting_date', $meeting->meeting_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Mes <span class="text-danger">*</span></label>
                                <select name="month" class="form-control">
                                    @php $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; @endphp
                                    @foreach($meses as $mes)
                                        <option value="{{ $mes }}" {{ old('month', $meeting->month) == $mes ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="status" class="form-control">
                                    <option value="open" {{ $meeting->status === 'open' ? 'selected' : '' }}>Abierta</option>
                                    <option value="closed" {{ $meeting->status === 'closed' ? 'selected' : '' }}>Cerrada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observations" class="form-control" rows="3">{{ old('observations', $meeting->observations) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
                    <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
