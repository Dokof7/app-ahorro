<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Theme FOUC prevention: apply saved dark mode before first paint.
         html-level class drives the background rule; the body class
         (used by component-level dark rules) is synced on DOMContentLoaded,
         which fires before first paint since CSS is render-blocking. -->
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

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- Inter (Filament theme) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Filament theme overrides -->
    <link rel="stylesheet" href="{{ asset('css/theme-filament.css') }}?v=3">

    <style>
        /* Theme toggle button (login page) */
        .tf-theme-toggle-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #6b7280;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .tf-theme-toggle-btn:hover {
            color: #111827;
            background-color: #f3f4f6;
        }
        body.dark-mode .tf-theme-toggle-btn {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.10);
            color: #9ca3af;
        }
        body.dark-mode .tf-theme-toggle-btn:hover {
            color: #f9fafb;
            background-color: #1f2937;
        }
    </style>

    @stack('css')
</head>
<body class="hold-transition login-page">
    <button type="button" class="tf-theme-toggle-btn" id="tf-theme-toggle"
            aria-label="Cambiar tema" title="Cambiar tema">
        <i class="fas fa-moon"></i>
    </button>

    <div class="container-fluid">
        @yield('content')
    </div>

    <!-- Dark/light theme toggle: persists explicit choice in localStorage -->
    <script>
    (function () {
        var btn = document.getElementById('tf-theme-toggle');
        if (!btn) return;
        var icon = btn.querySelector('i');

        function syncIcon() {
            var dark = document.body.classList.contains('dark-mode');
            icon.classList.toggle('fa-moon', !dark);
            icon.classList.toggle('fa-sun', dark);
        }

        document.addEventListener('DOMContentLoaded', syncIcon);
        syncIcon();

        btn.addEventListener('click', function () {
            var dark = document.body.classList.toggle('dark-mode');
            document.documentElement.classList.toggle('dark-mode', dark);
            try { localStorage.setItem('tf-theme', dark ? 'dark' : 'light'); } catch (e) {}
            syncIcon();
        });
    })();
    </script>

    <!-- jQuery -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <!-- AdminLTE JS -->
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    @stack('js')
</body>
</html>
