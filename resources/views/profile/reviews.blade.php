@extends('layouts.profile')

@section('title', __('site.profile_page.reviews'))

@section('panel')
    <section class="profile-panel">
        <h2 class="profile-notification-heading">{{ __('site.reviews.open') }}</h2>
        @forelse ($open as $review)
            <div class="profile-notification-group submission-row">
                <strong><a href="{{ route('profile.reviews.show', $review) }}">{{ $review->article->title }}</a></strong>
                <span>
                    <span class="status-badge status-{{ $review->status }}">{{ __('site.reviews.statuses.'.$review->status) }}</span>
                    @if ($review->due_at) · {{ __('site.reviews.due') }} {{ $review->due_at->format('d.m.Y') }} @endif
                    @if ($review->isOverdue()) · <span class="auth-error">{{ __('site.reviews.overdue') }}</span> @endif
                </span>
            </div>
        @empty
            <p>{{ __('site.profile_page.no_reviews') }}</p>
        @endforelse

        @if ($done->isNotEmpty())
            <h2 class="profile-notification-heading">{{ __('site.reviews.done') }}</h2>
            @foreach ($done as $review)
                <div class="profile-notification-group submission-row">
                    <strong><a href="{{ route('profile.reviews.show', $review) }}">{{ $review->article->title }}</a></strong>
                    <span class="status-badge status-{{ $review->status }}">{{ __('site.reviews.statuses.'.$review->status) }}</span>
                </div>
            @endforeach
        @endif
    </section>
@endsection
