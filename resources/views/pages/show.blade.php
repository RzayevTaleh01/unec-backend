@extends('layouts.app')

@section('title', $page->title)

@section('content')
    @include('partials.breadcrumb', ['crumbs' => [$section->title => null]])

    <main class="content-section" id="{{ $section->key }}">
        <div class="container">
            <h1 class="content-heading">{{ $section->title }}</h1>
            <div class="content-layout">
                <nav class="content-nav" aria-label="{{ $section->title }}">
                    @foreach ($section->pages as $item)
                        <a href="{{ route('pages.show', [$section->key, $item->slug]) }}" class="{{ $item->is($page) ? 'active' : '' }}" @if ($item->is($page)) aria-current="page" @endif>{{ $item->title }}</a>
                    @endforeach
                </nav>

                <article class="content-body">
                    <section class="tab-panel active">
                        {!! \App\Support\Html::clean($page->body) !!}
                    </section>
                </article>
            </div>
        </div>
    </main>
@endsection
