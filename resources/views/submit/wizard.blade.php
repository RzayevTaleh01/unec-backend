@extends('layouts.profile')

@section('title', __('site.wizard.title'))

@php
    $labels = [1 => __('site.wizard.steps.start'), 2 => __('site.wizard.steps.upload'), 3 => __('site.wizard.steps.metadata'), 4 => __('site.wizard.steps.contributors'), 5 => __('site.wizard.steps.review')];

    // Reopen on the first step that has a server-side validation error.
    $stepOfField = fn (string $key) => match (true) {
        str_starts_with($key, 'checklist') || $key === 'copyright' || $key === 'language' => 1,
        $key === 'manuscript' => 2,
        in_array($key, ['title', 'abstract', 'keywords'], true) => 3,
        str_starts_with($key, 'authors') || $key === 'primary' => 4,
        default => 5,
    };
    $errorStep = $errors->isEmpty() ? null : collect($errors->keys())->map($stepOfField)->min();

    $locale = $article && in_array($article->language, ['az', 'en', 'ru'], true) ? $article->language : 'az';
    $authors = collect(old('authors', $article?->authors?->map->only(['name', 'institution', 'email', 'is_primary'])->values()->all() ?? []));
    if ($authors->isEmpty()) {
        $me = auth()->user();
        $authors = collect([['name' => $me->name, 'institution' => $me->institution, 'email' => $me->email, 'is_primary' => true]]);
    }
    $authors->push(['name' => '', 'institution' => '', 'email' => '', 'is_primary' => false]); // spare blank row
    $primary = old('primary', $authors->search(fn ($row) => ! empty($row['is_primary'])) ?: 0);
    $hasFile = (bool) $article?->files?->isNotEmpty();
@endphp

@section('panel')
    <section class="profile-panel wizard"
        data-wizard
        data-initial-step="{{ $errorStep ?? 1 }}"
        data-has-file="{{ $hasFile ? 1 : 0 }}"
        data-msg-checklist="{{ __('site.wizard.checklist_all') }}"
        data-msg-copyright="{{ __('site.wizard.err_copyright') }}"
        data-msg-file="{{ __('site.wizard.err_file') }}"
        data-msg-authors="{{ __('site.wizard.err_authors') }}"
        data-msg-abstract="{{ __('site.wizard.err_abstract') }}">

        <ol class="wizard-steps" aria-label="{{ __('site.wizard.title') }}">
            @foreach ($labels as $number => $label)
                <li data-wizard-item="{{ $number }}">
                    <button type="button" class="wizard-step-btn" data-wizard-goto="{{ $number }}"><span>{{ $number }}</span>{{ $label }}</button>
                </li>
            @endforeach
        </ol>

        @if ($drafts->isNotEmpty())
            <div class="wizard-drafts">
                <strong>{{ __('site.wizard.continue_draft') }}</strong>
                @foreach ($drafts as $draft)
                    <a class="auth-link" href="{{ route('submit.edit', $draft) }}">{{ $draft->title ?: __('site.wizard.untitled') }} · {{ $draft->created_at->format('d.m.Y') }}</a>
                @endforeach
            </div>
        @endif

        @error('submit')<div class="auth-alert auth-alert--error">{{ $message }}</div>@enderror

        <form id="wizard-form" class="profile-form" method="post" novalidate enctype="multipart/form-data"
            action="{{ $article ? route('submit.update', $article) : route('submit.create') }}">
            @csrf
            @if ($article) @method('put') @endif

            {{-- Step 1: start --}}
            <div class="wizard-step" data-wizard-step="1">
                <h2 class="profile-notification-heading">{{ $labels[1] }}</h2>
                <p class="profile-password-intro">{{ __('site.wizard.start_intro') }}</p>

                <label for="submission-language">{{ __('site.wizard.language') }}</label>
                <div class="profile-select-wrap">
                    <select id="submission-language" name="language" required>
                        @foreach (['az' => 'Azərbaycan', 'en' => 'English', 'ru' => 'Русский', 'tr' => 'Türkçe'] as $code => $name)
                            <option value="{{ $code }}" @selected(old('language', $article?->language ?? 'az') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </div>
                @error('language')<div class="auth-error">{{ $message }}</div>@enderror

                <fieldset class="profile-role-options" data-checklist>
                    <legend>{{ __('site.wizard.checklist_title') }}</legend>
                    @foreach ($checklist as $item)
                        <label><input type="checkbox" name="checklist[]" value="{{ $item }}" @checked(in_array($item, old('checklist', []), true))> {{ __('site.wizard.checklist.'.$item) }}</label>
                    @endforeach
                </fieldset>
                @error('checklist')<div class="auth-error">{{ $message }}</div>@enderror
                @error('checklist.*')<div class="auth-error">{{ $message }}</div>@enderror

                <label class="register-consent">
                    <input type="checkbox" name="copyright" value="1" @checked(old('copyright'))>
                    <span>{{ __('site.wizard.copyright') }} (<a href="{{ route('pages.show', ['submission', 'copyright']) }}" target="_blank" rel="noopener">{{ __('site.wizard.read') }}</a>)</span>
                </label>
                @error('copyright')<div class="auth-error">{{ $message }}</div>@enderror

                <div class="auth-error" role="alert" data-step-error></div>
            </div>

            {{-- Step 2: file --}}
            <div class="wizard-step" data-wizard-step="2" hidden>
                <h2 class="profile-notification-heading">{{ $labels[2] }}</h2>
                <p class="profile-password-intro">{{ __('site.wizard.upload_intro') }}</p>

                @if ($hasFile)
                    <div class="profile-notification-group">
                        <strong>{{ __('site.wizard.current_file') }}: {{ $article->files->first()->original_name }}</strong>
                        <span>{{ number_format($article->files->first()->size / 1024 / 1024, 2) }} MB</span>
                    </div>
                @endif

                <label for="manuscript">{{ $hasFile ? __('site.wizard.replace_file') : __('site.wizard.manuscript') }}</label>
                @include('partials.file-input', [
                    'id' => 'manuscript', 'name' => 'manuscript', 'accept' => '.doc,.docx,.rtf,.odt,.pdf',
                    'maxMb' => 20, 'hint' => __('site.wizard.file_hint'), 'required' => false, 'kind' => 'document',
                ])
                @error('manuscript')<div class="auth-error">{{ $message }}</div>@enderror
                <div class="auth-error" role="alert" data-step-error></div>
            </div>

            {{-- Step 3: details --}}
            <div class="wizard-step" data-wizard-step="3" hidden>
                <h2 class="profile-notification-heading">{{ $labels[3] }}</h2>

                <label for="meta-title">{{ __('site.wizard.article_title') }} *</label>
                <input id="meta-title" name="title" value="{{ old('title', $article?->getTranslation('title', $locale, false)) }}" maxlength="500" required>
                @error('title')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="meta-abstract">{{ __('site.article.abstract') }} * <small class="wizard-counter" data-abstract-counter></small></label>
                <textarea id="meta-abstract" name="abstract" rows="9" minlength="50" maxlength="5000" required>{{ old('abstract', $article?->getTranslation('abstract', $locale, false)) }}</textarea>
                @error('abstract')<div class="auth-error">{{ $message }}</div>@enderror

                <label for="meta-keywords">{{ __('site.article.keywords') }} *</label>
                <input id="meta-keywords" name="keywords" value="{{ old('keywords', $article?->getTranslation('keywords', $locale, false)) }}" maxlength="500" placeholder="{{ __('site.wizard.keywords_hint') }}" required>
                @error('keywords')<div class="auth-error">{{ $message }}</div>@enderror

                <div class="profile-required">{{ __('site.profile_page.required_note') }}</div>
                <div class="auth-error" role="alert" data-step-error></div>
            </div>

            {{-- Step 4: authors --}}
            <div class="wizard-step" data-wizard-step="4" hidden>
                <h2 class="profile-notification-heading">{{ $labels[4] }}</h2>
                <p class="profile-password-intro">{{ __('site.wizard.contributors_intro') }}</p>
                @error('authors')<div class="auth-error">{{ $message }}</div>@enderror
                @error('authors.*')<div class="auth-error">{{ $message }}</div>@enderror

                <div id="author-rows">
                    @foreach ($authors as $i => $row)
                        <fieldset class="wizard-author" data-author-row>
                            <div class="profile-field-grid">
                                <div>
                                    <label>{{ __('site.wizard.author_name') }}</label>
                                    <input name="authors[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" maxlength="255">
                                </div>
                                <div>
                                    <label>{{ __('site.auth.institution') }}</label>
                                    <input name="authors[{{ $i }}][institution]" value="{{ $row['institution'] ?? '' }}" maxlength="255">
                                </div>
                                <div>
                                    <label>Email</label>
                                    <input name="authors[{{ $i }}][email]" type="email" value="{{ $row['email'] ?? '' }}" maxlength="255">
                                </div>
                            </div>
                            <label class="wizard-primary"><input type="radio" name="primary" value="{{ $i }}" @checked((string) $primary === (string) $i)> {{ __('site.wizard.primary_author') }}</label>
                        </fieldset>
                    @endforeach
                </div>

                <button type="button" class="profile-journal-link" id="add-author"><span>+</span><strong>{{ __('site.wizard.add_author') }}</strong></button>
                <div class="auth-error" role="alert" data-step-error></div>
            </div>

            {{-- Step 5: confirm (filled in by JavaScript from the form) --}}
            <div class="wizard-step" data-wizard-step="5" hidden>
                <h2 class="profile-notification-heading">{{ $labels[5] }}</h2>
                <dl class="wizard-summary">
                    <dt>{{ __('site.wizard.language') }}</dt><dd data-summary="language"></dd>
                    <dt>{{ __('site.wizard.article_title') }}</dt><dd data-summary="title"></dd>
                    <dt>{{ __('site.article.abstract') }}</dt><dd data-summary="abstract"></dd>
                    <dt>{{ __('site.article.keywords') }}</dt><dd data-summary="keywords"></dd>
                    <dt>{{ __('site.wizard.manuscript') }}</dt><dd data-summary="file"></dd>
                    <dt>{{ __('site.authors') }}</dt><dd data-summary="authors"></dd>
                </dl>
                <p class="profile-hint">{{ __('site.wizard.final_note') }}</p>
            </div>

            <div class="wizard-actions">
                <button type="button" class="register-secondary" data-wizard-prev hidden>{{ __('site.back') }}</button>
                <button type="submit" class="wizard-draft-btn" name="mode" value="draft" data-wizard-draft>{{ __('site.wizard.save_draft') }}</button>
                <button type="button" class="profile-save" data-wizard-next>{{ __('site.next') }}</button>
                <button type="submit" class="profile-save" name="mode" value="submit" data-wizard-send hidden>{{ __('site.wizard.send') }}</button>
            </div>
        </form>

        @if ($article)
            <form id="delete-draft" method="post" action="{{ route('submit.destroy', $article) }}" onsubmit="return confirm('{{ __('site.wizard.confirm_delete') }}')" class="wizard-delete">
                @csrf
                @method('delete')
                <button class="auth-link" type="submit">{{ __('site.wizard.delete_draft') }}</button>
            </form>
        @endif
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/wizard.js') }}"></script>
@endpush
