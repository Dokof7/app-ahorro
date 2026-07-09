@extends('adminlte::page')

@section('title', config('adminlte.title'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">@yield('page_title', 'Dashboard')</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center">
            @php $__gaUser = auth()->user(); @endphp
            @if($__gaUser && ($__gaUser->isAdmin() || $__gaUser->isAdminGrupo()))
                @if(session('active_group_id'))
                <span class="badge badge-success px-3 py-2 mr-2" style="font-size:0.85rem;">
                    <i class="fas fa-users-cog mr-1"></i>
                    {{ session('active_group_name') }}
                </span>
                @if($__gaUser->isAdmin() || $__gaUser->groups()->count() > 1)
                <form action="{{ route('group.selector.clear') }}" method="POST" class="d-inline mr-3">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-exchange-alt mr-1"></i>Cambiar grupo
                    </button>
                </form>
                @endif
                @endif
            @endif
            @yield('page_actions')
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">×</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-times-circle me-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">×</button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="close" data-dismiss="alert">×</button>
        </div>
    @endif
    @yield('main_content')
@stop

@section('footer')
    <div class="float-right d-none d-sm-inline">
        <b>Versión</b> 1.0.0
    </div>
    <strong>Copyright &copy; {{ date('Y') }} <a href="#">GrupoAhorro</a>.</strong>
    Todos los derechos reservados.
@stop

@push('css')
{{-- Theme FOUC prevention: apply saved dark mode before first paint.
     Level 1: class on <html> (available here in <head>) drives the
     html.dark-mode body background rule so no white flash occurs.
     Level 2: body class (required by all component-level dark rules)
     is synced on DOMContentLoaded, which fires before first paint
     because CSS is render-blocking. --}}
<script>
(function () {
    try {
        if (localStorage.getItem('tf-theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.addEventListener('DOMContentLoaded', function () {
                document.body.classList.add('dark-mode');
            });
        }
    } catch (e) { /* localStorage unavailable: stay in light mode */ }
})();
</script>
<style>
:root {
    --ga-green: #16a34a;
    --ga-green-dark: #15803d;
    --ga-blue: #0ea5e9;
}
.badge { font-size: 0.8rem; }
.table th { font-weight: 600; background-color: #f8f9fa; }
.card-header { font-weight: 600; }
.info-box { cursor: default; }
.status-open { color: var(--ga-green); }
.status-closed { color: #6c757d; }
</style>
@endpush

@push('js')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

$(document).on('click', '.btn-delete-confirm', function(e) {
    e.preventDefault();
    const form = $(this).closest('form');
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
});

$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    }
});

// Dark/light theme toggle (Filament theme).
// Injects a moon/sun nav item into the right navbar list; persists
// the explicit user choice in localStorage ('tf-theme'). Default: light.
(function () {
    var isDark = document.body.classList.contains('dark-mode')
        || document.documentElement.classList.contains('dark-mode');
    var $navList = $('.main-header .navbar-nav').last();
    if (!$navList.length) return;

    var $item = $(
        '<li class="nav-item">' +
        '<a class="nav-link" href="#" id="tf-theme-toggle" role="button" ' +
        'aria-label="Cambiar tema" title="Cambiar tema">' +
        '<i class="fas ' + (isDark ? 'fa-sun' : 'fa-moon') + '"></i>' +
        '</a></li>'
    );
    $navList.append($item);

    $item.on('click', '#tf-theme-toggle', function (e) {
        e.preventDefault();
        var dark = document.body.classList.toggle('dark-mode');
        document.documentElement.classList.toggle('dark-mode', dark);
        try { localStorage.setItem('tf-theme', dark ? 'dark' : 'light'); } catch (err) {}
        $(this).find('i')
            .toggleClass('fa-moon', !dark)
            .toggleClass('fa-sun', dark);
    });
})();
</script>
@endpush

@yield('extra_js')
