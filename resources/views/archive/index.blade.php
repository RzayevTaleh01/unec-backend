@extends('layouts.app')

@section('title', __('site.nav.archive'))

@section('content')
    <main class="archive-main">
        <div class="container archive-container">
            <nav class="archive-breadcrumb">
                <a href="{{ route('home') }}">{{ __('site.nav.home') }}</a>
                <span>/</span>
                <span class="current-page">{{ __('site.nav.archive') }}</span>
            </nav>

            <div class="archive-heading">
                <h1>{{ __('site.nav.archive') }}</h1>
                <p>{{ __('site.home.archive_subtitle') }}</p>
            </div>

            @if ($issues->isNotEmpty())
                <div class="row archive-row g-0">
                    @foreach ($issues as $issue)
                        <div class="col-6 col-md-4 col-lg-2 archive-col">
                            <article class="archive-card">
                                <div class="journal-image"><img src="{{ $issue->cover_url }}" alt="{{ $issue->label }}"></div>
                                <h3>{{ $issue->label }}</h3>
                                <a href="{{ route('archive.show', $issue) }}" class="journal-link">{{ __('site.view_journal') }} <i class="bi bi-arrow-right"></i></a>
                            </article>
                        </div>
                    @endforeach
                </div>

                @if ($issues->hasMorePages())
                    <div class="archive-more">
                        <a class="more-btn" href="{{ $issues->nextPageUrl() }}">{{ __('site.view_more') }}</a>
                    </div>
                @endif
            @else
                @include('partials.empty', ['image' => 'archive-empty.png', 'message' => __('site.archive.empty')])
            @endif
        </div>
    </main>
@endsection
