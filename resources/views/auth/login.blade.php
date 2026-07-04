@extends('layouts.auth')

@section('content')
<style>
    /* Filament-style login page */
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f9fafb;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .login-container {
        width: 100%;
        padding: 20px;
    }

    .login-card {
        background: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1),
                    0 0 0 1px rgba(3, 7, 18, 0.06);
        overflow: hidden;
        max-width: 420px;
        margin: 0 auto;
    }

    .login-header {
        padding: 2rem 2rem 1.5rem;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .login-header h1 {
        font-size: 1.375rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 0.25rem;
        letter-spacing: -0.01em;
    }

    .login-header p {
        margin: 0;
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 400;
    }

    .login-body {
        padding: 1.75rem 2rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
        font-size: 0.875rem;
    }

    .form-group input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        font-family: inherit;
        color: #374151;
        background-color: #ffffff;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        line-height: 1.5;
    }

    .form-group input:focus {
        outline: none;
        border-color: #f59e0b;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35);
    }

    .form-group input::placeholder {
        color: #9ca3af;
    }

    .form-group input.is-invalid {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
    }

    .invalid-feedback {
        display: block;
        color: #b91c1c;
        font-size: 0.8125rem;
        margin-top: 0.375rem;
        font-weight: 500;
    }

    .btn-login {
        width: 100%;
        padding: 0.625rem 1rem;
        background-color: #f59e0b;
        color: #ffffff;
        border: 1px solid #f59e0b;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        margin-top: 0.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        letter-spacing: 0.01em;
    }

    .btn-login:hover {
        background-color: #d97706;
        border-color: #d97706;
    }

    .btn-login:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35);
    }

    .btn-login:active {
        background-color: #b45309;
        border-color: #b45309;
    }

    .login-footer {
        text-align: center;
        padding: 1rem 2rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        font-size: 0.8125rem;
        color: #6b7280;
    }

    .login-footer a {
        color: #b45309;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.15s ease;
    }

    .login-footer a:hover {
        color: #f59e0b;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-card {
            max-width: 100%;
        }

        .login-header,
        .login-body,
        .login-footer {
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }
    }

    /* ==========================================================
       Dark mode — Filament v3 dark palette.
       Page background is also scoped under html.dark-mode so the
       FOUC-prevention head script covers it before body class sync.
       ========================================================== */
    html.dark-mode .login-page,
    body.dark-mode .login-page {
        background-color: #030712;
    }

    body.dark-mode .login-card {
        background: #111827;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4), 0 1px 2px -1px rgba(0, 0, 0, 0.4),
                    0 0 0 1px rgba(255, 255, 255, 0.10);
    }

    body.dark-mode .login-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    body.dark-mode .login-header h1 {
        color: #f9fafb;
    }

    body.dark-mode .login-header p {
        color: #9ca3af;
    }

    body.dark-mode .form-group label {
        color: #d1d5db;
    }

    body.dark-mode .form-group input {
        background-color: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.10);
        color: #e5e7eb;
    }

    body.dark-mode .form-group input::placeholder {
        color: #6b7280;
    }

    body.dark-mode .form-group input:focus {
        border-color: #f59e0b;
        background-color: rgba(255, 255, 255, 0.07);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35);
    }

    body.dark-mode .form-group input.is-invalid {
        border-color: #f87171;
        box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.15);
    }

    body.dark-mode .invalid-feedback {
        color: #f87171;
    }

    /* Amber button stays unchanged in dark mode (brand continuity) */

    body.dark-mode .login-footer {
        background: rgba(255, 255, 255, 0.03);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        color: #9ca3af;
    }

    body.dark-mode .login-footer a {
        color: #fbbf24;
    }

    body.dark-mode .login-footer a:hover {
        color: #f59e0b;
    }
</style>

<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>{{ __('Inicio de Sesión') }}</h1>
                <p>{{ __('Accede a tu cuenta') }}</p>
            </div>

            <div class="login-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email">{{ __('Correo Electrónico') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="@error('email') is-invalid @enderror"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="tu@correo.com"
                            required
                            autocomplete="email"
                            autofocus
                        >
                        @error('email')
                            <span class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">{{ __('Contraseña') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="@error('password') is-invalid @enderror"
                            name="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        @error('password')
                            <span class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <button type="submit" class="btn-login">
                        {{ __('Iniciar Sesión') }}
                    </button>
                </form>
            </div>

            @if (Route::has('password.request'))
                <div class="login-footer">
                    {{ __('¿Olvidaste tu contraseña?') }}
                    <a href="{{ route('password.request') }}">
                        {{ __('Recupérala aquí') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
