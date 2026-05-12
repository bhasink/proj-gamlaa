<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · Gamlaa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('admin-ui/css/admin.css') }}">
</head>
<body>
    <main class="login">
        <div class="login__card">
            <div class="login__brand">
                <span class="login__brand-dot"></span> Gamlaa <span class="muted">/ Admin</span>
            </div>
            <h1 class="login__title">Welcome back.</h1>
            <p  class="login__sub">Sign in to manage inspirations and categories.</p>

            @if($errors->any())
                <div class="flash flash--error" style="margin-bottom:14px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                <div class="field">
                    <label class="field__label" for="email">Email</label>
                    <input id="email" name="email" type="text" class="input" autocomplete="username"
                           required autofocus value="{{ old('email') }}">
                </div>
                <div class="field">
                    <label class="field__label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn--primary">Sign in</button>
            </form>

        </div>
    </main>
</body>
</html>
