{{-- Rich-text field: contenteditable body mirrored into a hidden input on submit (see main.js). --}}
<div class="profile-editor">
    <div class="profile-editor-toolbar" role="toolbar" aria-label="{{ $label }}">
        <button type="button" data-editor-command="bold"><strong>B</strong></button>
        <button type="button" data-editor-command="italic"><em>I</em></button>
        <button type="button" data-editor-command="underline"><u>U</u></button>
        <button type="button" data-editor-command="createLink">Link</button>
        <button type="button" data-editor-command="code">&lt;/&gt;</button>
    </div>
    <div id="{{ $id }}" class="profile-editor-body {{ $class ?? '' }}" contenteditable="true" role="textbox" aria-label="{{ $label }}">{!! \App\Support\Html::clean($value) !!}</div>
    <input type="hidden" name="{{ $name }}" data-editor-input>
</div>
