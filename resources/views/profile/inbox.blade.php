@extends('layouts.profile')

@section('title', __('site.inbox.title'))

@section('panel')
    <section class="profile-panel">
        <div class="submissions-head">
            <h2 class="profile-notification-heading">{{ __('site.inbox.title') }}</h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="post" action="{{ route('profile.inbox.read-all') }}">
                    @csrf
                    <button class="register-secondary" type="submit">{{ __('site.inbox.read_all') }}</button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <div class="profile-notification-group {{ $notification->read_at ? '' : 'is-unread' }}">
                <strong>{{ $notification->data['message'] ?? '' }}</strong>
                <span>{{ $notification->created_at->format('d.m.Y H:i') }}
                    @if (! empty($notification->data['url'])) · <a class="auth-link" href="{{ $notification->data['url'] }}">{{ __('site.notify.open') }}</a> @endif</span>
            </div>
        @empty
            <p>{{ __('site.inbox.empty') }}</p>
        @endforelse

        {{ $notifications->links('pagination::bootstrap-5') }}
    </section>
@endsection
