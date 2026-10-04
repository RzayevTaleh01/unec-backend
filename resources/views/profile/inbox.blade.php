@extends('layouts.profile')

@section('title', __('site.inbox.title'))

@section('panel')
    <section class="profile-panel">
        <div class="page-head">
            <h2 class="profile-notification-heading">{{ __('site.inbox.title') }}</h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="post" action="{{ route('profile.inbox.read-all') }}">
                    @csrf
                    <button class="btn-compact" type="submit">{{ __('site.inbox.read_all') }}</button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <div class="list-item {{ $notification->read_at ? '' : 'is-unread' }}">
                <div class="list-item-main">
                    <span class="list-item-title">{{ $notification->data['message'] ?? '' }}</span>
                    <span class="list-item-meta">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
                </div>
                @if (! empty($notification->data['url']))
                    <a class="btn-compact" href="{{ $notification->data['url'] }}">{{ __('site.notify.open') }}</a>
                @endif
            </div>
        @empty
            <p class="list-empty">{{ __('site.inbox.empty') }}</p>
        @endforelse

        {{ $notifications->links('pagination::bootstrap-5') }}
    </section>
@endsection
