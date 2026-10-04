@extends('layouts.app')

@section('title', __('site.search'))

@section('content')
    <main class="search-main">
        <div class="container">
            <div class="search-heading"><h1>{{ __('site.search') }}</h1></div>

            <div class="search-section">
                <form class="search-filter-form" id="searchForm" method="get" action="{{ route('search') }}">
                    <div class="search-field search-field-text">
                        <input id="searchTitle" name="title" type="text" value="{{ $filters['title'] }}" placeholder="{{ __('site.search_page.article_title') }}" aria-label="{{ __('site.search_page.article_title') }}">
                        <button type="button" class="clear-btn" aria-label="{{ __('site.clear') }}"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="search-field search-field-text">
                        <input id="searchAuthor" name="author" type="text" value="{{ $filters['author'] }}" placeholder="{{ __('site.author') }}" aria-label="{{ __('site.author') }}">
                        <button type="button" class="clear-btn" aria-label="{{ __('site.clear') }}"><i class="bi bi-pen"></i></button>
                    </div>

                    <div class="date-picker-wrapper">
                        <input type="hidden" name="from" id="searchFrom" value="{{ $filters['from'] }}">
                        <input type="hidden" name="to" id="searchTo" value="{{ $filters['to'] }}">
                        <div class="search-field search-field-date" id="datePickerTrigger" role="button" tabindex="0" aria-label="{{ __('site.search_page.date_range') }}"
                            data-initial-start="{{ $filters['from'] }}" data-initial-end="{{ $filters['to'] }}"
                            data-months="{{ json_encode(array_values(trans('site.months')), JSON_UNESCAPED_UNICODE) }}">
                            <span id="dateRangeLabel">{{ __('site.search_page.date_range') }}</span>
                            <button type="button" class="field-icon"><i class="bi bi-calendar3"></i></button>
                        </div>

                        <div class="date-picker-popover" id="datePickerPopover" aria-label="{{ __('site.search_page.pick_date') }}">
                            <div class="date-picker-header">
                                <button type="button" class="date-nav" data-nav="prev" aria-label="‹"><i class="bi bi-chevron-left"></i></button>
                                <div class="date-picker-selectors">
                                    <label class="date-picker-select-wrap">
                                        <select id="dateMonthSelect" class="date-picker-select"></select>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </label>
                                    <label class="date-picker-select-wrap date-picker-year-wrap">
                                        <select id="dateYearSelect" class="date-picker-select"></select>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </label>
                                </div>
                                <button type="button" class="date-nav" data-nav="next" aria-label="›"><i class="bi bi-chevron-right"></i></button>
                            </div>
                            <div class="date-range-preview">
                                <span id="startDatePreview"></span>
                                <span class="range-divider">—</span>
                                <span id="endDatePreview"></span>
                            </div>
                            <div class="date-picker-grid" id="dateGrid"></div>
                            <div class="date-picker-actions">
                                <button type="button" class="date-action cancel" id="cancelDate">{{ __('site.cancel') }}</button>
                                <button type="button" class="date-action apply" id="applyDate">{{ __('site.apply') }}</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="search-submit-btn"><span>{{ __('site.search_page.submit') }}</span> <i class="bi bi-search"></i></button>
                </form>
            </div>

            <div class="search-results">
                <div class="search-results-header">
                    <div class="search-results-title-wrap">
                        <h2>{{ $searched ? __('site.search_page.results') : __('site.search_page.latest') }}</h2>
                        <p>{{ __('site.home.archive_subtitle') }}</p>
                    </div>
                    @if ($articles->hasMorePages())
                        <a class="search-more-btn" href="{{ $articles->nextPageUrl() }}">{{ __('site.more') }} <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>

                @foreach ($articles as $article)
                    <div class="search-result-card">
                        <a href="{{ route('articles.show', $article) }}" class="search-result-title">
                            {{ $article->title }}@if ($article->issue), {{ __('site.journal_name_upper') }}: {{ $article->issue->label }}@endif
                        </a>
                        <div class="search-result-meta">
                            <span class="search-author">{{ $article->author_line }}</span>
                            <span class="search-date"><i class="bi bi-calendar3"></i> {{ ($article->published_at ?? $article->issue?->published_at)?->format('d.m.Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($articles->isEmpty())
            @include('partials.empty', ['image' => 'search-empty.png', 'message' => __('site.search_page.empty')])
        @endif
    </main>
@endsection
