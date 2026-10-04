@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.roles'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <form class="profile-form" method="post" action="{{ route('profile.roles.update') }}">
            @csrf
            @method('put')
            <fieldset class="profile-role-options">
                <legend>{{ __('site.profile_page.tabs.roles') }}</legend>
                @foreach (\App\Models\User::SELF_ROLES as $role)
                    <label><input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, old('roles', $user->roles ?? []), true))> {{ __('site.roles.'.$role) }}</label>
                @endforeach
            </fieldset>
            @error('roles')<div class="auth-error">{{ $message }}</div>@enderror
            @error('roles.*')<div class="auth-error">{{ $message }}</div>@enderror

            {{-- Staff roles are read-only here: only an administrator can grant them. --}}
            <div class="role-staff">
                <strong>{{ __('site.profile_page.staff_roles') }}</strong>
                @forelse ($user->staffRoles() as $role)
                    <span class="status-badge status-accepted">{{ __('site.roles.'.$role) }}</span>
                @empty
                    <span class="role-staff__none">{{ __('site.profile_page.no_staff_roles') }}</span>
                @endforelse
                <p class="profile-hint">{{ __('site.profile_page.staff_roles_note') }}</p>
            </div>

            <label class="register-consent" for="reviewer-volunteer">
                <input id="reviewer-volunteer" type="checkbox" name="reviewer_volunteer" value="1" @checked(old('reviewer_volunteer', $user->consent_reviewer_contact))>
                <span>{{ __('site.profile_page.reviewer_volunteer') }}</span>
            </label>

            <a class="profile-journal-link" href="{{ route('profile.roles.journals') }}"><span>+</span><strong>{{ __('site.profile_page.other_journals_link') }}</strong></a>

            <label for="profile-specialty">{{ __('site.profile_page.specialty') }}</label>
            <input id="profile-specialty" name="specialty" type="text" value="{{ old('specialty', $user->specialty) }}">
            @error('specialty')<div class="auth-error">{{ $message }}</div>@enderror
            @include('profile._footer', ['required' => true])
        </form>
    </section>
@endsection
