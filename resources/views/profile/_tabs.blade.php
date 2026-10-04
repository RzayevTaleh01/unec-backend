@php
    $tabs = [
        'profile.identity' => ['identity', 'profile.identity'],
        'profile.contact' => ['contact', 'profile.contact'],
        'profile.roles' => ['roles', 'profile.roles*'],
        'profile.public' => ['public', 'profile.public'],
        'profile.password' => ['password', 'profile.password'],
        'profile.notifications' => ['notifications', 'profile.notifications'],
        'profile.api-key' => ['api_key', 'profile.api-key*'],
    ];
@endphp
<div class="profile-tabs" role="navigation" aria-label="{{ __('site.profile_page.sections') }}">
    @foreach ($tabs as $route => [$label, $pattern])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ __('site.profile_page.tabs.'.$label) }}</a>
    @endforeach
</div>
