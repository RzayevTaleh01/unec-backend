@extends('layouts.app')

@section('title', __('site.auth.register_title'))

@section('content')
    <main class="register-section" id="main-content">
        <section class="auth-card register-card" aria-labelledby="register-title">
            <h2 id="register-title">{{ __('site.auth.register_title') }}</h2>
            <form class="auth-form register-form" method="post" action="{{ route('register.personal.store') }}">
                @csrf
                <label for="register-first-name">{{ __('site.auth.first_name') }}</label>
                <input id="register-first-name" name="first_name" type="text" value="{{ old('first_name', $old['first_name'] ?? '') }}" placeholder="{{ __('site.enter') }}" required>
                @error('first_name')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-last-name">{{ __('site.auth.last_name') }}</label>
                <input id="register-last-name" name="last_name" type="text" value="{{ old('last_name', $old['last_name'] ?? '') }}" placeholder="{{ __('site.enter') }}" required>
                @error('last_name')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-institution">{{ __('site.auth.institution') }}</label>
                <input id="register-institution" name="institution" type="text" value="{{ old('institution', $old['institution'] ?? '') }}" placeholder="{{ __('site.enter') }}" required>
                @error('institution')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="register-country">{{ __('site.auth.country') }}</label>
                <div class="register-select-wrap">
                    <select id="register-country" name="country" required>
                        <option value="" disabled @selected(! old('country', $old['country'] ?? null))>{{ __('site.enter') }}</option>
                        @foreach (['az', 'tr', 'other'] as $code)
                            <option value="{{ $code }}" @selected(old('country', $old['country'] ?? null) === $code)>{{ __('site.countries.'.$code) }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </div>
                @error('country')<div class="auth-error">{{ $message }}</div>@enderror

                <button class="auth-submit register-next" type="submit">{{ __('site.next') }}</button>
            </form>
            <p class="auth-footer">{{ __('site.auth.have_account') }} <a class="auth-link" href="{{ route('login') }}">{{ __('site.auth.login_link') }}</a></p>
        </section>
    </main>
@endsection
