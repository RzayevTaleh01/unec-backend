@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.contact'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel profile-contact-panel">
        <form class="profile-form" method="post" action="{{ route('profile.contact.update') }}" data-editor-form>
            @csrf
            @method('put')
            <div class="profile-field-grid">
                <div>
                    <label for="profile-email">Email *</label>
                    <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="profile-phone">{{ __('site.profile_page.phone') }}</label>
                    <input id="profile-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}">
                    @error('phone')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="profile-organization">{{ __('site.auth.institution') }}</label>
                    <input id="profile-organization" name="institution" value="{{ old('institution', $user->institution) }}">
                    @error('institution')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="profile-contact-country">{{ __('site.auth.country') }} *</label>
                    <div class="profile-select-wrap">
                        <select id="profile-contact-country" name="country" required>
                            @foreach (['az', 'tr', 'other'] as $code)
                                <option value="{{ $code }}" @selected(old('country', $user->country) === $code)>{{ __('site.countries.'.$code) }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </div>
                    @error('country')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <label for="profile-signature">{{ __('site.profile_page.signature') }}</label>
            @include('profile._editor', ['id' => 'profile-signature', 'name' => 'signature', 'value' => old('signature', $user->signature), 'label' => __('site.profile_page.signature')])
            @error('signature')<div class="auth-error">{{ $message }}</div>@enderror

            <fieldset class="profile-languages">
                <legend>{{ __('site.profile_page.working_languages') }}</legend>
                @foreach (['az' => 'Azərbaycan', 'en' => 'English', 'ru' => 'Русский'] as $code => $name)
                    <label><input type="checkbox" name="working_languages[]" value="{{ $code }}" @checked(in_array($code, old('working_languages', $user->working_languages ?? []), true))> {{ $name }}</label>
                @endforeach
            </fieldset>
            @include('profile._footer', ['required' => true])
        </form>
    </section>
@endsection
