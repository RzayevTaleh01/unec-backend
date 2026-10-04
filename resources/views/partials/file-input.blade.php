{{--
    Drag-and-drop file field with validation and live preview (see main.js, "FILE INPUTS").

    $name, $id, $accept (".pdf,.docx"), $maxMb, $hint, $required (bool),
    $kind: "document" (PDF is previewed inline) | "image" (thumbnail preview).
--}}
@php
    $kind = $kind ?? 'document';
    $maxMb = $maxMb ?? 20;
@endphp
<div class="file-drop"
    data-file-drop
    data-kind="{{ $kind }}"
    data-max-mb="{{ $maxMb }}"
    data-accept="{{ $accept }}"
    data-msg-type="{{ __('site.file.wrong_type', ['types' => $accept]) }}"
    data-msg-size="{{ __('site.file.too_big', ['max' => $maxMb]) }}"
    data-msg-preview="{{ __('site.file.no_preview') }}">
    <input class="file-drop__input" type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept }}" @required($required ?? false)>

    <label class="file-drop__zone" for="{{ $id }}">
        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
        <span class="file-drop__title">{{ __('site.file.drop') }} <u>{{ __('site.file.choose') }}</u></span>
        <span class="file-drop__hint">{{ $hint ?? '' }}</span>
    </label>

    <div class="file-drop__selected" hidden>
        <div class="file-drop__thumb" data-file-thumb></div>
        <div class="file-drop__meta">
            <strong data-file-name></strong>
            <span data-file-size></span>
            <span class="file-drop__note" data-file-note></span>
        </div>
        <div class="file-drop__actions">
            <button type="button" class="file-drop__btn" data-file-toggle-preview hidden>{{ __('site.file.preview') }}</button>
            <button type="button" class="file-drop__btn file-drop__btn--danger" data-file-clear>{{ __('site.file.remove') }}</button>
        </div>
    </div>

    <div class="file-drop__preview" data-file-preview hidden></div>
    <div class="file-drop__error" role="alert" data-file-error></div>
</div>
