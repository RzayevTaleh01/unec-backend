@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.password'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <form class="profile-form" method="post" action="{{ route('profile.password.update') }}">
            @csrf
            @method('put')
            <p class="profile-password-intro">{{ __('site.profile_page.password_intro') }}</p>

            <label for="current-password">{{ __('site.profile_page.current_password') }}</label>
            <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
            @error('current_password')<div class="auth-error">{{ $message }}</div>@enderror

            <label for="new-password">{{ __('site.profile_page.new_password') }}</label>
            <input id="new-password" name="password" type="password" autocomplete="new-password" required>
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror

            <label for="repeat-password">{{ __('site.profile_page.repeat_password') }}</label>
            <input id="repeat-password" name="password_confirmation" type="password" autocomplete="new-password" required>
            @include('profile._footer')
        </form>
    </section>
@endsection
