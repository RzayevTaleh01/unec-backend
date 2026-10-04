@extends('layouts.app')

@section('title', __('site.auth.register_title'))

@section('content')
    <main class="register-section" id="main-content">
        <section class="auth-card register-card">
            <a class="back-link register-back" href="{{ route('register.personal') }}"><i class="bi bi-arrow-left"></i> {{ __('site.back') }}</a>
            <h2>{{ __('site.auth.register_title') }}</h2>
            <form class="auth-form register-form" method="post" action="{{ route('register.account.store') }}">
                @csrf
                <label for="register-email">Email</label>
                <input id="register-email" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('site.enter') }}" autocomplete="email" required>
                @error('email')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-username">{{ __('site.auth.username') }}</label>
                <input id="register-username" name="username" type="text" value="{{ old('username') }}" placeholder="{{ __('site.enter') }}" autocomplete="username" required>
                @error('username')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-password">{{ __('site.auth.password') }}</label>
                <input id="register-password" name="password" type="password" placeholder="{{ __('site.enter') }}" autocomplete="new-password" required>
                @error('password')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-password-confirm">{{ __('site.auth.password_confirm') }}</label>
                <input id="register-password-confirm" name="password_confirmation" type="password" placeholder="{{ __('site.enter') }}" autocomplete="new-password" required>

                <label class="register-consent" for="consent-privacy">
                    <input id="consent-privacy" name="consent_privacy" value="1" type="checkbox" required>
                    <span>{{ __('site.auth.consent_privacy') }} (<a href="{{ route('privacy') }}">{{ __('site.footer.privacy') }}</a>)</span>
                </label>
                @error('consent_privacy')<div class="auth-error">{{ $message }}</div>@enderror

                <label class="register-consent" for="consent-news">
                    <input id="consent-news" name="consent_news" value="1" type="checkbox" @checked(old('consent_news'))>
                    <span>{{ __('site.auth.consent_news') }}</span>
                </label>

                <label class="register-consent" for="consent-contact">
                    <input id="consent-contact" name="consent_reviewer_contact" value="1" type="checkbox" @checked(old('consent_reviewer_contact'))>
                    <span>{{ __('site.auth.consent_reviewer') }}</span>
                </label>

                <div class="register-actions">
                    <a class="register-secondary" href="{{ route('register.personal') }}">{{ __('site.back') }}</a>
                    <button class="auth-submit" type="submit">{{ __('site.auth.register_submit') }}</button>
                </div>
            </form>
            <p class="auth-footer">{{ __('site.auth.have_account') }} <a class="auth-link" href="{{ route('login') }}">{{ __('site.auth.login_link') }}</a></p>
        </section>
    </main>
@endsection
