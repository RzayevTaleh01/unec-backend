@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.identity'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <div class="profile-panel-intro"><strong>{{ __('site.auth.username') }}</strong><span>{{ $user->username }}</span></div>
        <hr>
        <form class="profile-form" method="post" action="{{ route('profile.identity.update') }}">
            @csrf
            @method('put')
            <div class="profile-field-grid">
                <div>
                    <label for="profile-first-name">{{ __('site.auth.first_name') }} *</label>
                    <input id="profile-first-name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                    @error('first_name')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="profile-last-name">{{ __('site.auth.last_name') }}</label>
                    <input id="profile-last-name" name="last_name" value="{{ old('last_name', $user->last_name) }}">
                    @error('last_name')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <p class="profile-hint">{{ __('site.profile_page.full_name_hint') }}</p>
            <label for="profile-preferred-name">{{ __('site.profile_page.publish_name') }}</label>
            <input id="profile-preferred-name" name="publish_name" value="{{ old('publish_name', $user->publish_name) }}">
            @error('publish_name')<div class="auth-error">{{ $message }}</div>@enderror
            @include('profile._footer', ['required' => true])
        </form>
    </section>
@endsection
