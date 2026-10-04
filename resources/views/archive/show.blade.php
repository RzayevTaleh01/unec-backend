@extends('layouts.app')

@section('title', $issue->label)

@section('content')
    @include('partials.breadcrumb', ['crumbs' => [__('site.nav.archive') => route('archive.index'), $issue->label => null]])

    <section class="journal-hero">
        <div class="container archive-container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="journal-info">
                        <h1>{{ \Illuminate\Support\Str::limit(strip_tags((string) $issue->description) ?: $issue->label, 260) }}</h1>
                        @if ($issue->published_at)
                            <div class="journal-date">
                                <strong>{{ $issue->published_at->format('d') }}</strong>
                                <span>{{ mb_strtoupper(__('site.months.'.$issue->published_at->month), 'UTF-8') }} {{ $issue->published_at->year }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="journal-cover"><img src="{{ asset('assets/images/journals/hero.jpg') }}" alt="{{ __('site.journal_name') }}"></div>
                </div>
            </div>
        </div>
    </section>

    <main class="archive-main">
        <div class="container archive-container">
            <div class="journal-links">
                @if ($issue->pdf_url)
                    <a href="{{ $issue->pdf_url }}" class="unec-journal-pdf" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                @endif
                @if ($issue->doi_url)
                    <div class="doi-link">
                        <span>DOI:</span>
                        <a href="{{ $issue->doi_url }}" target="_blank" rel="noopener">{{ $issue->doi_url }}</a>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </div>
                @endif
            </div>

            <div class="journal-description"><p>{{ strip_tags((string) $issue->description) }}</p></div>

            <section class="unec-journal-sidebar">
                <div class="unec-journal-section-title">{{ __('site.articles') }}</div>
                @forelse ($issue->articles as $article)
                    <article class="unec-journal-article">
                        <a href="{{ route('articles.show', $article) }}" class="unec-journal-article__title">{{ $article->title }}</a>
                        <div class="unec-journal-article__author">{{ $article->author_line }} ({{ __('site.author') }})</div>
                        @if ($article->pdf_url)
                            <a href="{{ $article->pdf_url }}" class="unec-journal-pdf" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                        @endif
                    </article>
                @empty
                    <p>{{ __('site.archive.no_articles') }}</p>
                @endforelse
            </section>
        </div>
    </main>
@endsection
