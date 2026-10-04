@extends('layouts.app')

@section('title', $page->title)

@section('content')
    @include('partials.breadcrumb', ['crumbs' => [$page->title => null]])

    <main class="content-section">
        <div class="container">
            <h1 class="content-heading">{{ $page->title }}</h1>
            <div class="content-layout">
                <article class="content-body">
                    <section class="tab-panel active">
                        {!! \App\Support\Html::clean($page->body) !!}
                    </section>
                </article>
            </div>
        </div>
    </main>
@endsection
