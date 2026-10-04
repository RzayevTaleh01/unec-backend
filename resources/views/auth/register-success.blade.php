@extends('layouts.app')

@section('title', __('site.auth.register_done'))

@section('content')
    <main class="register-section" id="main-content"></main>

    <div class="auth-modal register-modal">
        <a class="auth-modal__backdrop" href="{{ route('login') }}" aria-label="{{ __('site.close') }}"></a>
        <section class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="register-success-title">
            <a class="auth-modal__close" href="{{ route('login') }}" aria-label="{{ __('site.close') }}"><i class="bi bi-x"></i></a>
            <div class="auth-modal__icon" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
            <h2 id="register-success-title">{{ __('site.auth.register_done') }}</h2>
            <p>{{ __('site.auth.register_thanks') }}</p>
            <a class="auth-submit auth-modal__submit" href="{{ route('login') }}">{{ __('site.auth.discover') }}</a>
        </section>
    </div>
@endsection
