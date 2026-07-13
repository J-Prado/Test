@extends('layout')

@section('title', 'Mi panel')

@section('content')
    <p data-testid="welcome">Hola, {{ $user->name }} ({{ $user->email }})</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" data-testid="logout">Cerrar sesión</button>
    </form>
@endsection
