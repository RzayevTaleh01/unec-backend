@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.api_key'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <form class="profile-form" method="post" action="{{ route('profile.api-key.store') }}">
            @csrf
            <div class="profile-api-field">
                <label for="profile-api-key">{{ __('site.profile_page.tabs.api_key') }}</label>
                <input id="profile-api-key" value="{{ session('api_token') ?? ($hasToken ? '••••••••••••••••••••••••' : __('site.profile_page.api_none')) }}" readonly>
                @if (session('api_token'))
                    <small>{{ __('site.profile_page.api_copy_now') }}</small>
                @endif
                <small>{{ __('site.profile_page.api_warning') }}</small>
            </div>
            <p class="profile-privacy">{{ __('site.profile_page.privacy_text') }} <a href="{{ route('privacy') }}">{{ __('site.profile_page.privacy_statement') }}</a>.</p>
            <button class="profile-save profile-api-create" type="submit">{{ __('site.profile_page.api_create') }}</button>
        </form>
        @if ($hasToken)
            <form class="profile-form" method="post" action="{{ route('profile.api-key.destroy') }}" onsubmit="return confirm('{{ __('site.profile_page.api_confirm_delete') }}')">
                @csrf
                @method('delete')
                <button class="profile-save" type="submit">{{ __('site.profile_page.api_delete') }}</button>
            </form>
        @endif
    </section>
@endsection
