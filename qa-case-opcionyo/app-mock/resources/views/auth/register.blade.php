@extends('layout')

@section('title', 'Crear cuenta')

@section('content')
    <form method="POST" action="{{ route('register') }}" data-testid="register-form">
        @csrf
        <label for="name">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" data-testid="name">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" data-testid="email">

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" data-testid="password">

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" data-testid="password_confirmation">

        <button type="submit" data-testid="submit">Registrarme</button>
    </form>

    <p><a href="{{ route('login') }}">Ya tengo cuenta</a></p>
@endsection
