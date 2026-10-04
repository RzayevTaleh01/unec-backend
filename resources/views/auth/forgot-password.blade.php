@extends('layouts.app')

@section('title', __('site.auth.reset_title'))
@section('body_class', 'login-page')

@section('content')
    <main class="auth-section" id="main-content">
        <section class="auth-card auth-card-forgot" aria-labelledby="forgot-title">
            <a class="back-link" href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> {{ __('site.back') }}</a>
            <h2 id="forgot-title">{{ __('site.auth.reset_title') }}</h2>
            <p class="auth-description">{{ __('site.auth.reset_description') }}</p>
            <form class="auth-form" method="post" action="{{ route('password.email') }}">
                @csrf
                <label for="reset-email">Email</label>
                <input id="reset-email" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('site.enter') }}" autocomplete="email" required>
                @error('email')<div class="auth-error">{{ $message }}</div>@enderror
                <button class="auth-submit" type="submit">{{ __('site.auth.reset_submit') }}</button>
            </form>
            <p class="auth-footer">{{ __('site.auth.no_account') }} <a class="auth-link" href="{{ route('register.personal') }}">{{ __('site.auth.register_link') }}</a></p>
        </section>
    </main>
@endsection
