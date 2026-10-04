@extends('layouts.app')

@section('title', $article->title)

@section('content')
    @php
        $crumbs = [__('site.nav.archive') => route('archive.index')];
        if ($article->issue) {
            $crumbs[$article->issue->label] = route('archive.show', $article->issue);
        }
        $crumbs[__('site.articles')] = null;
        $date = $article->published_at ?? $article->issue?->published_at;
    @endphp
    @include('partials.breadcrumb', ['crumbs' => $crumbs])

    <section class="journal-hero">
        <div class="container archive-container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="journal-info">
                        @if ($article->issue)
                            <p><span class="gray-text">{{ __('site.article.issue') }}: </span> <span class="current">{{ $article->issue->label }}: {{ __('site.journal_name') }}</span></p>
                        @endif
                        <h1>{{ $article->title }}</h1>
                        @if ($date)
                            <div class="journal-date">
                                <strong>{{ $date->format('d') }}</strong>
                                <span>{{ mb_strtoupper(__('site.months.'.$date->month), 'UTF-8') }} {{ $date->year }}</span>
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
            <div class="article-layout">
                <div class="article-main-column">
                    <div class="journal-links">
                        @if ($article->pdf_url)
                            <a href="{{ $article->pdf_url }}" class="unec-journal-pdf" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                        @endif
                        @if ($article->doi_url)
                            <div class="doi-link">
                                <span>DOI:</span>
                                <a href="{{ $article->doi_url }}" target="_blank" rel="noopener">{{ $article->doi_url }}</a>
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                        @endif
                    </div>

                    <div class="article-info">
                        <p class="mb-2"><span class="gray-text">{{ __('site.author') }}: </span> <span>{{ $article->author_line }}</span></p>
                        @if ($article->keywords)
                            <p><span class="gray-text">{{ __('site.article.keywords') }}: </span> <span>{{ $article->keywords }}</span></p>
                        @endif
                    </div>

                    @if ($article->abstract)
                        <div class="journal-description">
                            <div class="archive-section-title">{{ __('site.article.abstract') }}</div>
                            <p>{{ $article->abstract }}</p>
                        </div>
                    @endif
                </div>

                <aside class="article-citation-panel">
                    <div class="citation-box">
                        <div class="citation-title">{{ __('site.article.how_to_cite') }}</div>
                        <div class="citation-meta">
                            <div class="citation-name">{{ $article->cite($style) }}</div>
                            @if ($article->doi_url)
                                <div class="doi-link">
                                    <span>DOI:</span>
                                    <a href="{{ $article->doi_url }}" target="_blank" rel="noopener">{{ $article->doi_url }}</a>
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </div>
                            @endif
                        </div>
                        <form method="get" class="citation-select-wrap">
                            <select class="citation-select" name="style" aria-label="{{ __('site.article.more_formats') }}" onchange="this.form.submit()">
                                @foreach (['apa' => 'APA', 'mla' => 'MLA', 'chicago' => 'Chicago'] as $key => $label)
                                    <option value="{{ $key }}" @selected($style === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </aside>
            </div>

            @if ($similar->isNotEmpty())
                <section class="unec-journal-sidebar">
                    <div class="unec-journal-section-title">{{ __('site.article.similar') }}</div>
                    @foreach ($similar as $item)
                        <article class="unec-journal-article">
                            <a href="{{ route('articles.show', $item) }}" class="unec-journal-article__title">{{ $item->title }}@if ($item->issue), {{ __('site.journal_name_upper') }}: {{ $item->issue->label }}@endif</a>
                            <div class="unec-journal-article__author">{{ $item->author_line }} ({{ __('site.author') }})</div>
                        </article>
                    @endforeach
                    <p class="similar-articles"><a href="{{ route('search', ['title' => \Illuminate\Support\Str::words($article->title, 3, '')]) }}">{{ __('site.article.similar_search') }}</a></p>
                </section>
            @endif
        </div>
    </main>
@endsection
