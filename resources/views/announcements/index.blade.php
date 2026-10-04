@extends('layouts.app')

@section('title', __('site.nav.announcements'))

@section('content')
    <main class="announcement-section" id="announcements">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">{{ __('site.nav.announcements') }}</h2>
                    <div class="section-subtitle">{{ __('site.home.announcements_subtitle') }}</div>
                </div>
            </div>

            @if ($announcements->isNotEmpty())
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                    @foreach ($announcements as $announcement)
                        <div class="col">@include('partials.announcement-card', ['announcement' => $announcement])</div>
                    @endforeach
                </div>
                @if ($announcements->hasMorePages())
                    <a class="view-more btn-announcements" href="{{ $announcements->nextPageUrl() }}">{{ __('site.view_more') }}</a>
                @endif
            @else
                @include('partials.empty', ['image' => 'archive-empty.png', 'message' => __('site.announcements.empty')])
            @endif
        </div>
    </main>
@endsection
