@extends('layouts.profile')

@section('title', __('site.profile_page.submissions'))

@section('panel')
    <section class="profile-panel">
        <div class="submissions-head">
            <h2 class="profile-notification-heading">{{ __('site.profile_page.submissions') }}</h2>
            <a class="profile-save" href="{{ route('submit.start') }}">{{ __('site.wizard.new_submission') }}</a>
        </div>

        @forelse ($articles as $article)
            <div class="profile-notification-group submission-row">
                <strong>
                    @if ($article->isDraft())
                        <a href="{{ route('submit.edit', $article) }}">{{ $article->title ?: __('site.wizard.untitled') }}</a>
                    @else
                        <a href="{{ route('profile.submissions.show', $article) }}">{{ $article->title }}</a>
                    @endif
                </strong>
                <span>
                    <span class="status-badge status-{{ $article->status }}">{{ __('site.statuses.'.$article->status) }}</span>
                    · {{ ($article->submitted_at ?? $article->created_at)->format('d.m.Y') }}
                </span>
            </div>
        @empty
            <p>{{ __('site.profile_page.no_submissions') }}</p>
        @endforelse
    </section>
@endsection
