<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} · Gamlaa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('admin-ui/css/admin.css') }}">
</head>
<body>
    <div class="shell">
        @include('admin.partials.sidebar')
        <div class="main">
            <header class="topbar">
                <nav class="topbar__crumbs" aria-label="Breadcrumb">
                    @php $crumbs = $crumbs ?? [['label' => 'Admin', 'url' => route('admin.dashboard')]]; @endphp
                    @foreach($crumbs as $i => $c)
                        @if($i > 0)<span class="sep">/</span>@endif
                        @if(!empty($c['url']) && $i < count($crumbs) - 1)
                            <a href="{{ $c['url'] }}">{{ $c['label'] }}</a>
                        @else
                            <span class="here">{{ $c['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
                <div class="topbar__right">
                    <div class="topbar__user" title="{{ session('admin.email') }}">
                        <span class="topbar__avatar">{{ strtoupper(substr(config('admin.name'), 0, 2)) }}</span>
                        <span>{{ config('admin.name') }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="topbar__logout">Sign out</button>
                    </form>
                </div>
            </header>
            <main class="content">
                @include('admin.partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ versioned_asset('admin-ui/js/admin.js') }}"></script>
</body>
</html>
