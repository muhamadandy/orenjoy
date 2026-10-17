<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#cc4a08">
    <title>@yield('title', 'Menu') · Orenjoy</title>

    <!-- Font & ikon (sama seperti template) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Raleway:wght@600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <!-- CSS template, lalu tema Orenjoy (urutan ini penting) -->
    <link href="{{ asset('assets/customer/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/customer/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/customer/css/orenjoy.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <a class="oj-skip" href="#main">Lewati ke konten</a>

    <header class="oj-header">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{ route('menu') }}" class="oj-brand">Oren<span>joy</span></a>

            <div class="d-flex align-items-center gap-2">
                @if (session('table_number'))
                    <span class="oj-table-pill">Meja {{ session('table_number') }}</span>
                @endif
                @yield('header_actions')
            </div>
        </div>
    </header>

    <main id="main" class="oj-main">
        @yield('content')
    </main>
    

    @stack('scripts')
</body>
</html>
