@extends('layouts.auth')

@section('content')
<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }

    .login-page::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        top: -100px;
        left: -100px;
        animation: float 6s ease-in-out infinite;
    }

    .login-page::after {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        bottom: -50px;
        right: -50px;
        animation: float 8s ease-in-out infinite reverse;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(30px); }
    }

    .login-container {
        position: relative;
        z-index: 1;
        width: 100%;
        padding: 20px;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 450px;
        margin: 0 auto;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .login-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 30px;
        text-align: center;
        color: white;
    }

    .login-header h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .login-header p {
        margin: 10px 0 0 0;
        font-size: 14px;
        opacity: 0.9;
        font-weight: 300;
    }

    .login-body {
        padding: 40px 35px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .form-group input {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 15px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        color: #333;
        font-family: inherit;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .form-group input::placeholder {
        color: #999;
    }

    .form-group input.is-invalid {
        border-color: #dc3545;
        background-color: #fff;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        text-transform: uppercase;
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
    }

    .btn-login:active {
        transform: translateY(-1px);
    }

    .login-footer {
        text-align: center;
        padding: 20px 35px;
        background: #f8f9fa;
        border-top: 1px solid #e0e0e0;
        font-size: 13px;
        color: #666;
    }

    .login-footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .login-footer a:hover {
        color: #764ba2;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-card {
            max-width: 100%;
            margin: 20px;
        }

        .login-header {
            padding: 30px 20px;
        }

        .login-header h1 {
            font-size: 24px;
        }

        .login-body,
        .login-footer {
            padding: 30px 20px;
        }
    }
</style>

<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 {{ __('Inicio de Sesión') }}</h1>
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
