@extends('layouts.app')

@section('content')
    <main id="home">
        <section class="hero">
            <div class="container">
                <div class="row align-items-start g-4">
                    <div class="col-md-6">
                        <div class="hero-copy">
                            <h1 class="hero-title">{{ \App\Models\Setting::get('hero_title', __('site.home.hero_title')) }}</h1>
                            <p class="hero-text">{{ \App\Models\Setting::get('hero_text', __('site.home.hero_text')) }}</p>
                            <div class="hero-actions">
                                <a href="{{ auth()->check() ? route('submit.start') : route('login') }}" class="btn-dark-custom">{{ __('site.home.submit_article') }}</a>
                                @guest
                                    <a href="{{ route('login') }}" class="btn-light-custom">{{ __('site.login') }}</a>
                                @else
                                    <a href="{{ route('profile.identity') }}" class="btn-light-custom">{{ __('site.profile') }}</a>
                                @endguest
                            </div>
                            <div class="stats d-flex">
                                <div class="stat-item flex-fill">
                                    <div class="stat-number">{{ $stats['articles'] }}+</div>
                                    <div class="stat-label">{{ __('site.home.stat_articles') }}</div>
                                </div>
                                <div class="stat-item flex-fill">
                                    <div class="stat-number">{{ $stats['issues'] }}+</div>
                                    <div class="stat-label">{{ __('site.home.stat_issues') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"><img class="hero-image" src="{{ asset('assets/images/hero/unec-building.png') }}" alt="UNEC"></div>
                </div>
            </div>
        </section>

        @if ($latest)
            <section class="featured" id="featured">
                <div class="container unec-journal-page">
                    <section class="unec-journal-top">
                        <div class="unec-journal-hero">
                            <img src="{{ asset('assets/images/journals/image.png') }}" alt="{{ __('site.journal_name') }}">
                        </div>
                        <div class="unec-journal-issues">
                            @foreach ($thumbs as $issue)
                                <a class="unec-journal-issue-link" href="{{ route('archive.show', $issue) }}">
                                    <article class="unec-journal-issue">
                                        <img class="unec-journal-issue__image" src="{{ $issue->cover_url }}" alt="{{ $issue->label }}">
                                        <div>
                                            <h2 class="unec-journal-issue__title">{{ $issue->label }}</h2>
                                            <p class="unec-journal-issue__text">{{ \Illuminate\Support\Str::limit(strip_tags((string) $issue->description), 110) }}</p>
                                        </div>
                                    </article>
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <section class="unec-journal-content">
                        <div class="unec-journal-main">
                            <div class="unec-journal-date">
                                <i class="bi bi-calendar3"></i>
                                <span>{{ $latest->published_at?->format('d.m.Y') }}</span>
                            </div>
                            <h1>{{ $latest->label }}: {{ __('site.journal_name') }}</h1>
                            <p class="unec-journal-description">{{ \Illuminate\Support\Str::limit(strip_tags((string) $latest->description), 900) }}</p>
                            <div class="unec-journal-actions">
                                @if ($latest->pdf_url)
                                    <a href="{{ $latest->pdf_url }}" class="unec-journal-pdf" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                @endif
                                @if ($latest->doi_url)
                                    <div class="doi-link">
                                        <span>DOI:</span>
                                        <a href="{{ $latest->doi_url }}" target="_blank" rel="noopener">{{ $latest->doi_url }}</a>
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <aside class="unec-journal-sidebar">
                            <div class="unec-journal-section-title">{{ __('site.articles') }}</div>
                            @foreach ($latest->articles as $article)
                                <article class="unec-journal-article">
                                    <a href="{{ route('articles.show', $article) }}" class="unec-journal-article__title">{{ $article->title }}</a>
                                    <div class="unec-journal-article__author">{{ $article->author_line }} ({{ __('site.author') }})</div>
                                    @if ($article->pdf_url)
                                        <a href="{{ $article->pdf_url }}" class="unec-journal-pdf" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    @endif
                                </article>
                            @endforeach
                        </aside>
                    </section>
                </div>
            </section>
        @endif

        <section class="archive" id="archive">
            <div class="container">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">{{ __('site.nav.archive') }}</h2>
                        <div class="section-subtitle">{{ __('site.home.archive_subtitle') }}</div>
                    </div>
                    <a class="view-more" href="{{ route('archive.index') }}">{{ __('site.view_more') }}</a>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">
                    @foreach ($archive as $issue)
                        <div class="col">
                            <article>
                                <img class="archive-card-img" src="{{ $issue->cover_url }}" alt="">
                                <div class="archive-card-title">{{ $issue->label }}</div>
                                <a class="archive-card-link" href="{{ route('archive.show', $issue) }}">{{ __('site.view_journal') }} →</a>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($infoPages->isNotEmpty())
            <section class="info-banner" id="about">
                <div class="container">
                    <div class="info-banner-inner">
                        <div class="journal-info">
                            <div class="container">
                                <section class="journal-notification">
                                    <div class="journal-tabs">
                                        @foreach ($infoPages as $page)
                                            <a href="{{ route('pages.show', ['information', $page->slug]) }}" class="journal-tab {{ $loop->first ? 'active' : '' }}">{{ $page->title }}</a>
                                        @endforeach
                                    </div>
                                    <div class="journal-content">
                                        <div class="journal-panel">
                                            {!! \App\Support\Html::clean($infoPages->first()->body) !!}
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="announcements" id="announcements">
            <div class="container">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">{{ __('site.nav.announcements') }}</h2>
                        <div class="section-subtitle">{{ __('site.home.announcements_subtitle') }}</div>
                    </div>
                    <a class="view-more" href="{{ route('announcements.index') }}">{{ __('site.view_more') }}</a>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                    @foreach ($announcements as $announcement)
                        <div class="col">@include('partials.announcement-card', ['announcement' => $announcement])</div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
