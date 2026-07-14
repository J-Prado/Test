<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Opción Yo')</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 4rem auto; padding: 0 1rem; }
        label { display: block; margin: .75rem 0 .25rem; font-weight: 600; }
        input { width: 100%; padding: .5rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .6rem 1rem; cursor: pointer; }
        .error { color: #b00020; margin-top: .5rem; }
    </style>
</head>
<body>
    <h1>@yield('title', 'Opción Yo')</h1>

    @if ($errors->any())
        <div class="error" data-testid="error">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</body>
</html>
