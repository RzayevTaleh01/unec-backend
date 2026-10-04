@extends('layouts.app')

@section('title', __('site.auth.reset_title'))
@section('body_class', 'login-page')

@section('content')
    <main class="auth-section" id="main-content">
        <section class="auth-card auth-card-forgot">
            <h2>{{ __('site.auth.new_password') }}</h2>
            <form class="auth-form" method="post" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label for="reset-email">Email</label>
                <input id="reset-email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                @error('email')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="reset-password">{{ __('site.auth.password') }}</label>
                <input id="reset-password" name="password" type="password" autocomplete="new-password" required>
                @error('password')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="reset-password-confirm">{{ __('site.auth.password_confirm') }}</label>
                <input id="reset-password-confirm" name="password_confirmation" type="password" autocomplete="new-password" required>

                <button class="auth-submit" type="submit">{{ __('site.save') }}</button>
            </form>
        </section>
    </main>
@endsection
