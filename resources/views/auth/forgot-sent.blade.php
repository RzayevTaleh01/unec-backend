@extends('layouts.app')

@section('title', __('site.auth.email_sent'))
@section('body_class', 'login-page')

@section('content')
    <main class="auth-section" id="main-content"></main>

    <div class="auth-modal">
        <a class="auth-modal__backdrop" href="{{ route('login') }}" aria-label="{{ __('site.close') }}"></a>
        <section class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="email-sent-title">
            <a class="auth-modal__close" href="{{ route('login') }}" aria-label="{{ __('site.close') }}"><i class="bi bi-x"></i></a>
            <div class="auth-modal__icon" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
            <h2 id="email-sent-title">{{ __('site.auth.email_sent') }}</h2>
            <p>{{ __('site.auth.email_sent_text') }}</p>
            <a class="auth-submit auth-modal__submit" href="{{ route('login') }}">{{ __('site.login') }}</a>
        </section>
    </div>
@endsection
