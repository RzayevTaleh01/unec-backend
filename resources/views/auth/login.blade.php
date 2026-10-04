@extends('layouts.app')

@section('title', __('site.login'))
@section('body_class', 'login-page')

@section('content')
    <main class="auth-section" id="main-content">
        <section class="auth-card" aria-labelledby="login-title">
            <h2 id="login-title">{{ __('site.login') }}</h2>

            @if (session('status'))
                <div class="auth-alert auth-alert--success">{{ session('status') }}</div>
            @endif

            <form class="auth-form" method="post" action="{{ route('login.store') }}">
                @csrf
                <label for="login-email">{{ __('site.auth.login_label') }}</label>
                <input id="login-email" name="login" type="text" value="{{ old('login') }}" placeholder="{{ __('site.enter') }}" autocomplete="username" required>
                @error('login')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="login-password">{{ __('site.auth.password') }}</label>
                <input id="login-password" name="password" type="password" placeholder="{{ __('site.enter') }}" autocomplete="current-password" required>
                @error('password')<div class="auth-error">{{ $message }}</div>@enderror

                <div class="auth-options">
                    <label class="remember-me" for="remember-login">
                        <input id="remember-login" name="remember" value="1" type="checkbox">
                        <span>{{ __('site.auth.remember') }}</span>
                    </label>
                    <a class="auth-link" href="{{ route('password.request') }}">{{ __('site.auth.forgot') }}</a>
                </div>

                <button class="auth-submit" type="submit">{{ __('site.login') }}</button>
            </form>
            <p class="auth-footer">{{ __('site.auth.no_account') }} <a class="auth-link" href="{{ route('register.personal') }}">{{ __('site.auth.register_link') }}</a></p>
        </section>
    </main>
@endsection
