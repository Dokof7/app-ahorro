@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Inicio</li>
@endsection

@section('main_content')

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $stats['total_groups'] }}</h3><p>Total Grupos</p></div>
            <div class="icon"><i class="fas fa-users-cog"></i></div>
            @can('admin')
            <a href="{{ route('groups.index') }}" class="small-box-footer">Ver grupos <i class="fas fa-arrow-circle-right"></i></a>
            @else
            <span class="small-box-footer">&nbsp;</span>
            @endcan
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $stats['total_members'] }}</h3><p>Miembros Registrados</p></div>
            <div class="icon"><i class="fas fa-user-friends"></i></div>
            <a href="{{ route('members.index') }}" class="small-box-footer">Ver miembros <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $stats['total_meetings'] }}</h3><p>Reuniones Registradas</p></div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            <a href="{{ route('meetings.index') }}" class="small-box-footer">Ver reuniones <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $stats['loans_overdue'] }}</h3><p>Préstamos Vencidos</p></div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="{{ route('loans.index') }}" class="small-box-footer">Ver vencidos <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-piggy-bank"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Ahorrado</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_savings'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shield-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Fondo Emergencia</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_emergency'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-gavel"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Multas</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_fines'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Préstamos Pendientes</span>
                <span class="info-box-number">Bs. {{ number_format($stats['loans_pending'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Préstamos Pagados</span>
                <span class="info-box-number">Bs. {{ number_format($stats['loans_paid'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-university"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Gastos Bancarios</span>
                <span class="info-box-number">Bs. {{ number_format($stats['bank_expenses'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-purple elevation-1" style="background-color:#6f42c1!important"><i class="fas fa-id-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Membresías Cobradas</span>
                <span class="info-box-number">Bs. {{ number_format($stats['total_membership'], 2) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Ahorros y Fondo de Emergencia (últimas 4 reuniones)</h3></div>
            <div class="card-body"><canvas id="savingsChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Acciones por miembro (últimas 4 reuniones)</h3></div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-7">
                        <canvas id="sharesChart"></canvas>
                    </div>
                    <div class="col-5">
                        <ul id="sharesLegend" class="list-unstyled mb-0" style="font-size:0.78rem; max-height:220px; overflow-y:auto;"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Mis Grupos</h3>
                <div class="card-tools">
                    @can('admin')
                    <a href="{{ route('groups.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i>Nuevo Grupo
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr><th>Grupo</th><th>Miembros</th><th>Reuniones</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                        <tr>
                            <td><strong>{{ $group->name }}</strong><br><small class="text-muted">{{ $group->description }}</small></td>
                            <td><span class="badge bg-info">{{ $group->members->count() }}</span></td>
                            <td><span class="badge bg-primary">{{ $group->meetings->count() }}</span></td>
                            <td>
                                @if($group->status === 'active')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('groups.show', $group) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('meetings.create', ['group_id' => $group->id]) }}" class="btn btn-xs btn-success"><i class="fas fa-plus"></i> Reunión</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-users-cog fa-2x mb-2 d-block"></i>
                                No tienes grupos creados aún. <a href="{{ route('groups.create') }}">Crear primer grupo</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
const ctxSavings = document.getElementById('savingsChart').getContext('2d');
new Chart(ctxSavings, {
    type: 'line',
    data: {
        labels: {!! json_encode($chartData['labels']) !!},
        datasets: [
            { label: 'Ahorros (Bs.)', data: {!! json_encode($chartData['savings']) !!}, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', tension: 0.4, fill: true },
            { label: 'Fondo Emergencia (Bs.)', data: {!! json_encode($chartData['emergency']) !!}, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.1)', tension: 0.4, fill: true }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});

const sharesLabels = {!! json_encode($sharesChart['labels']) !!};
const sharesData   = {!! json_encode($sharesChart['data']) !!};

const palette = [
    '#f59e0b','#0ea5e9','#16a34a','#8b5cf6','#ec4899','#f97316',
    '#14b8a6','#ef4444','#6366f1','#64748b','#eab308','#06b6d4'
];

const sharesColors = sharesLabels.map((_, i) => palette[i % palette.length]);
const ctxShares = document.getElementById('sharesChart').getContext('2d');
new Chart(ctxShares, {
    type: 'doughnut',
    data: {
        labels: sharesLabels,
        datasets: [{ data: sharesData, backgroundColor: sharesColors }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        tooltips: {
            callbacks: {
                label: function(item, data) {
                    var label = data.labels[item.index] || '';
                    var value = data.datasets[0].data[item.index];
                    return ' ' + label + ': Bs. ' + parseFloat(value).toFixed(2);
                }
            }
        }
    }
});

const legend = document.getElementById('sharesLegend');
sharesLabels.forEach(function(name, i) {
    legend.innerHTML += `<li class="mb-1">
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${sharesColors[i]};margin-right:5px;"></span>
        ${name}
    </li>`;
});
</script>
@endpush
