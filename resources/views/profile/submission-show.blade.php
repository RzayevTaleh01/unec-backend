@extends('layouts.profile')

@section('title', $article->title)

@section('panel')
    <section class="profile-panel">
        <a class="back-link" href="{{ route('profile.submissions') }}"><i class="bi bi-arrow-left"></i> {{ __('site.back') }}</a>

        <h2 class="profile-notification-heading">{{ $article->title }}</h2>
        <p><span class="status-badge status-{{ $article->status }}">{{ __('site.statuses.'.$article->status) }}</span>
            @if ($article->submitted_at) · {{ __('site.wizard.submitted_on') }} {{ $article->submitted_at->format('d.m.Y') }} @endif</p>

        <dl class="wizard-summary">
            <dt>{{ __('site.authors') }}</dt>
            <dd>{{ $article->author_line }}</dd>
            <dt>{{ __('site.article.keywords') }}</dt>
            <dd>{{ $article->keywords }}</dd>
            <dt>{{ __('site.article.abstract') }}</dt>
            <dd>{{ $article->abstract }}</dd>
            <dt>{{ __('site.wizard.files') }}</dt>
            <dd>
                @foreach ($article->files as $file)
                    <div><a class="auth-link" href="{{ route('files.download', $file) }}">{{ $file->original_name }}</a>
                        <small>({{ __('site.wizard.file_types.'.$file->type) }}, {{ $file->created_at->format('d.m.Y') }})</small></div>
                @endforeach
            </dd>
        </dl>

        @if ($article->decisions->isNotEmpty())
            <h3 class="profile-notification-heading">{{ __('site.wizard.decisions') }}</h3>
            @foreach ($article->decisions as $decision)
                <div class="profile-notification-group">
                    <strong>{{ __('site.decisions.'.$decision->decision) }}</strong>
                    <span>{{ $decision->created_at->format('d.m.Y') }}</span>
                    @if ($decision->comment)<p>{!! nl2br(e($decision->comment)) !!}</p>@endif
                </div>
            @endforeach
        @endif

        @if ($feedback->isNotEmpty())
            <h3 class="profile-notification-heading">{{ __('site.wizard.reviewer_comments') }}</h3>
            @foreach ($feedback as $i => $review)
                <div class="profile-notification-group">
                    <strong>{{ __('site.wizard.reviewer') }} {{ $i + 1 }}: {{ __('site.recommendations.'.$review->recommendation) }}</strong>
                    <p>{!! nl2br(e($review->comments_to_author)) !!}</p>
                </div>
            @endforeach
        @endif

        @if ($article->status === 'revisions')
            <h3 class="profile-notification-heading">{{ __('site.wizard.upload_revision') }}</h3>
            <form class="profile-form" method="post" action="{{ route('profile.submissions.revision', $article) }}" enctype="multipart/form-data">
                @csrf
                @include('partials.file-input', [
                    'id' => 'revision-file', 'name' => 'manuscript', 'accept' => '.doc,.docx,.rtf,.odt,.pdf',
                    'maxMb' => 20, 'hint' => __('site.wizard.file_hint'), 'required' => true, 'kind' => 'document',
                ])
                @error('manuscript')<div class="auth-error">{{ $message }}</div>@enderror
                <button class="profile-save" type="submit">{{ __('site.wizard.send_revision') }}</button>
            </form>
        @endif

        @if ($article->status === 'published')
            <p><a class="auth-link" href="{{ route('articles.show', $article) }}">{{ __('site.wizard.view_published') }}</a></p>
        @endif
    </section>
@endsection
