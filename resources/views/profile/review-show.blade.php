@extends('layouts.profile')

@section('title', $review->article->title)

@section('panel')
    @php($article = $review->article)
    <section class="profile-panel">
        <a class="back-link" href="{{ route('profile.reviews') }}"><i class="bi bi-arrow-left"></i> {{ __('site.back') }}</a>

        <h2 class="profile-notification-heading">{{ $article->title }}</h2>
        <p><span class="status-badge status-{{ $review->status }}">{{ __('site.reviews.statuses.'.$review->status) }}</span>
            @if ($review->due_at) · {{ __('site.reviews.due') }} {{ $review->due_at->format('d.m.Y') }} @endif</p>
        <p class="profile-hint">{{ __('site.reviews.blind_notice') }}</p>

        {{-- Authors are deliberately not shown: reviews are blind. --}}
        <dl class="wizard-summary">
            <dt>{{ __('site.article.keywords') }}</dt>
            <dd>{{ $article->keywords }}</dd>
            <dt>{{ __('site.article.abstract') }}</dt>
            <dd>{{ $article->abstract }}</dd>
            @if (in_array($review->status, ['accepted', 'completed'], true))
                <dt>{{ __('site.wizard.manuscript') }}</dt>
                <dd>
                    @foreach ($article->files as $file)
                        <div><a class="auth-link" href="{{ route('files.download', $file) }}">{{ __('site.reviews.download') }} #{{ $loop->iteration }} (.{{ $file->extension }})</a></div>
                    @endforeach
                </dd>
            @endif
        </dl>

        @if ($review->status === 'pending')
            <h3 class="profile-notification-heading">{{ __('site.reviews.invitation') }}</h3>
            @error('answer')<div class="auth-error">{{ $message }}</div>@enderror
            <form class="wizard-actions" method="post" action="{{ route('profile.reviews.respond', $review) }}">
                @csrf
                <button class="register-secondary" name="answer" value="decline" type="submit">{{ __('site.reviews.decline') }}</button>
                <button class="profile-save" name="answer" value="accept" type="submit">{{ __('site.reviews.accept') }}</button>
            </form>
        @elseif ($review->status === 'accepted')
            <h3 class="profile-notification-heading">{{ __('site.reviews.write') }}</h3>
            @if (in_array($article->status, ['submitted', 'in_review'], true))
                <form class="profile-form" method="post" action="{{ route('profile.reviews.submit', $review) }}">
                    @csrf
                    <label for="recommendation">{{ __('site.reviews.recommendation') }} *</label>
                    <div class="profile-select-wrap">
                        <select id="recommendation" name="recommendation" required>
                            <option value="" disabled @selected(! old('recommendation'))>{{ __('site.enter') }}</option>
                            @foreach (\App\Models\Review::RECOMMENDATIONS as $value)
                                <option value="{{ $value }}" @selected(old('recommendation') === $value)>{{ __('site.recommendations.'.$value) }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </div>
                    @error('recommendation')<div class="auth-error">{{ $message }}</div>@enderror

                    <label for="comments_to_author">{{ __('site.reviews.to_author') }} *</label>
                    <textarea id="comments_to_author" name="comments_to_author" rows="9" minlength="20" required>{{ old('comments_to_author') }}</textarea>
                    @error('comments_to_author')<div class="auth-error">{{ $message }}</div>@enderror

                    <label for="comments_to_editor">{{ __('site.reviews.to_editor') }}</label>
                    <textarea id="comments_to_editor" name="comments_to_editor" rows="4">{{ old('comments_to_editor') }}</textarea>
                    @error('comments_to_editor')<div class="auth-error">{{ $message }}</div>@enderror

                    <button class="profile-save" type="submit">{{ __('site.reviews.submit') }}</button>
                </form>
            @else
                <p>{{ __('site.workflow.review_closed') }}</p>
            @endif
        @elseif ($review->status === 'completed')
            <h3 class="profile-notification-heading">{{ __('site.reviews.your_review') }}</h3>
            <p><strong>{{ __('site.recommendations.'.$review->recommendation) }}</strong></p>
            <p>{!! nl2br(e($review->comments_to_author)) !!}</p>
        @endif
    </section>
@endsection
