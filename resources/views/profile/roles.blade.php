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

            <button type="button" class="profile-journal-link" data-role-modal-open aria-haspopup="dialog" aria-controls="journals-modal"><span>+</span><strong>{{ __('site.profile_page.other_journals_link') }}</strong></button>

            <label for="profile-specialty">{{ __('site.profile_page.specialty') }}</label>
            <input id="profile-specialty" name="specialty" type="text" value="{{ old('specialty', $user->specialty) }}">
            @error('specialty')<div class="auth-error">{{ $message }}</div>@enderror
            @include('profile._footer', ['required' => true])
        </form>

        {{-- Other journals: same dark-header dialog as the original design. Own form (forms cannot nest). --}}
        <div class="profile-role-modal" id="journals-modal" data-role-modal @if ($errors->has('journals') || $errors->has('journals.*')) data-open @else hidden @endif>
            <div class="profile-role-modal-backdrop" data-role-modal-close></div>
            <section class="profile-role-dialog" role="dialog" aria-modal="true" aria-labelledby="role-modal-title">
                <header>
                    <h2 id="role-modal-title">{{ __('site.profile_page.other_journals') }}</h2>
                    <button type="button" aria-label="{{ __('site.close') }}" data-role-modal-close><i class="bi bi-x"></i></button>
                </header>

                <form method="post" action="{{ route('profile.roles.journals.update') }}">
                    @csrf
                    @method('put')
                    <div class="profile-journal-list">
                        @forelse ($journals as $journal)
                            @php $current = $memberships[$journal->id] ?? []; @endphp
                            <div class="profile-journal">
                                <strong>{{ $journal->name }}</strong>
                                @foreach (\App\Models\User::SELF_ROLES as $role)
                                    <label><input type="checkbox" name="journals[{{ $journal->id }}][]" value="{{ $role }}" @checked(in_array($role, $current, true))> {{ __('site.roles.'.$role) }}</label>
                                @endforeach
                            </div>
                        @empty
                            <p>{{ __('site.profile_page.no_journals') }}</p>
                        @endforelse
                        @error('journals.*.*')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>
                    <footer class="profile-role-footer">
                        <button type="button" class="register-secondary" data-role-modal-close>{{ __('site.cancel') }}</button>
                        <button class="profile-save" type="submit">{{ __('site.save') }}</button>
                    </footer>
                </form>
            </section>
        </div>
    </section>
@endsection
