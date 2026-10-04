@extends('layouts.profile')

@section('title', __('site.profile_page.reviews'))

@section('panel')
    <section class="profile-panel">
        <h2 class="profile-notification-heading">{{ __('site.reviews.open') }}</h2>
        @forelse ($open as $review)
            <div class="list-item">
                <div class="list-item-main">
                    <span class="list-item-title"><a href="{{ route('profile.reviews.show', $review) }}">{{ $review->article->title }}</a></span>
                    <span class="list-item-meta">
                        @if ($review->due_at) <span>{{ __('site.reviews.due') }} {{ $review->due_at->format('d.m.Y') }}</span> @endif
                        @if ($review->isOverdue()) <span class="auth-error">{{ __('site.reviews.overdue') }}</span> @endif
                    </span>
                </div>
                <span class="status-badge status-{{ $review->status }}">{{ __('site.reviews.statuses.'.$review->status) }}</span>
            </div>
        @empty
            <p class="list-empty">{{ __('site.profile_page.no_reviews') }}</p>
        @endforelse

        @if ($done->isNotEmpty())
            <h2 class="profile-notification-heading">{{ __('site.reviews.done') }}</h2>
            @foreach ($done as $review)
                <div class="list-item">
                    <div class="list-item-main">
                        <span class="list-item-title"><a href="{{ route('profile.reviews.show', $review) }}">{{ $review->article->title }}</a></span>
                    </div>
                    <span class="status-badge status-{{ $review->status }}">{{ __('site.reviews.statuses.'.$review->status) }}</span>
                </div>
            @endforeach
        @endif
    </section>
@endsection
