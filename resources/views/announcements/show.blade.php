@extends('layouts.app')

@section('title', $announcement->title)

@section('content')
    <main class="ann-detail-section">
        <div class="ann-detail-hero">
            <img src="{{ $announcement->image_url ?? asset('assets/images/announcements/announcement-3.png') }}" alt="">
        </div>

        <article class="ann-detail-card">
            <a href="{{ route('announcements.index') }}" class="ann-back-link"><i class="bi bi-chevron-left"></i> {{ __('site.announcements.back') }}</a>
            <h1>{{ $announcement->title }}</h1>
            <div class="ann-detail-meta">
                <span><i class="bi bi-calendar3"></i> {{ $announcement->published_at->format('d.m.Y') }}</span>
                <span><i class="bi bi-eye"></i> {{ $announcement->views_count }}</span>
            </div>
            <div class="ann-detail-copy">{!! \App\Support\Html::clean($announcement->body) !!}</div>
        </article>
    </main>
@endsection
