@extends('layout')

@section('title', 'Iniciar sesión')

@section('content')
    <form method="POST" action="{{ route('login') }}" data-testid="login-form">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" data-testid="email">

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" data-testid="password">

        <button type="submit" data-testid="submit">Ingresar</button>
    </form>

    <p><a href="{{ route('register') }}">Crear cuenta</a></p>
@endsection
