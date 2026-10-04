{{-- Shared bottom of profile forms. $required: show the required-fields note. --}}
<p class="profile-privacy">{{ __('site.profile_page.privacy_text') }} <a href="{{ route('privacy') }}">{{ __('site.profile_page.privacy_statement') }}</a>.</p>
@if ($required ?? false)
    <div class="profile-required">{{ __('site.profile_page.required_note') }}</div>
@endif
<button class="profile-save" type="submit">{{ __('site.save') }}</button>
