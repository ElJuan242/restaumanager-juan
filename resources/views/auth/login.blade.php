@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="rm-login-icon">
        <i class="bi bi-shop"></i>
    </div>

    <div class="text-center mb-4">
        <div class="rm-login-brand">
            <span>Restau<span class="text-warning">Manager</span></span>
        </div>
        <p class="text-muted mb-0 small">Sistema de gestión de restaurante</p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" autocomplete="off">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Correo electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@restauranager.com"
                    required
                    autofocus
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label small" for="remember">Recordarme</label>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
        </button>
    </form>

    <hr class="my-4">

    <div class="card bg-light border-0 small">
        <div class="card-body py-2 px-3">
            <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i>Credenciales por defecto</div>
            <div class="text-muted">Admin: admin@restauranager.com / admin123</div>
            <div class="text-muted">User: mesero@restauranager.com / mesero123</div>
        </div>
    </div>
@endsection
