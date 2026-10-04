@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.notifications'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel profile-notifications-panel">
        <form class="profile-form" method="post" action="{{ route('profile.notifications.update') }}">
            @csrf
            @method('put')
            <p class="profile-notification-intro">{{ __('site.profile_page.notifications_intro') }}</p>

            @foreach (\App\Models\NotificationSetting::GROUPS as $group => $types)
                <h2 class="profile-notification-heading">{{ __('site.notification_groups.'.$group) }}</h2>
                @foreach ($types as $type)
                    @php $setting = $settings[$type] ?? null; @endphp
                    <div class="profile-notification-group">
                        <strong>{{ __('site.notification_types.'.$type) }}</strong>
                        <label><input type="checkbox" name="in_app[{{ $type }}]" value="1" @checked($setting?->in_app ?? true)> {{ __('site.profile_page.notify_in_app') }}</label>
                        <label><input type="checkbox" name="email_off[{{ $type }}]" value="1" @checked(! ($setting?->email ?? true))> {{ __('site.profile_page.notify_no_email') }}</label>
                    </div>
                @endforeach
            @endforeach
            @include('profile._footer')
        </form>
    </section>
@endsection
