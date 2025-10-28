{{-- resources/views/vendor/backpack/crud/fields/quill.blade.php --}}
@php
    // Quill needs a unique id
    $editorId = 'quill_' . \Illuminate\Support\Str::random(8);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    {{-- Hidden textarea that stores the actual HTML --}}
    <textarea
        name="{{ $field['name'] }}"
        id="{{ $editorId }}_input"
        bp-field-main-input
        class="d-none"
    >{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}</textarea>

    {{-- Quill visual editor --}}
    <div id="{{ $editorId }}" style="height:300px; border:1px solid #ccc; border-radius:6px;"></div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @basset('https://cdn.quilljs.com/1.3.6/quill.snow.css')
@endpush

@push('crud_fields_scripts')
    @basset('https://cdn.quilljs.com/1.3.6/quill.min.js')
    @bassetBlock('backpack/pro/fields/quill-editor.js')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const editorId = "{{ $editorId }}";
            const textarea = document.getElementById(editorId + "_input");
            const container = document.getElementById(editorId);

            const toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video', 'formula'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'list': 'check' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean']
            ];

            const quill = new Quill(container, {
                modules: { toolbar: toolbarOptions },
                theme: 'snow',
                placeholder: 'Start typing here...'
            });

            // Set initial value
            quill.root.innerHTML = textarea.value;

            // Update textarea when content changes
            quill.on('text-change', function() {
                textarea.value = quill.root.innerHTML;
                textarea.dispatchEvent(new Event('change'));
            });

            // Optional custom link/image prompts
            const toolbar = quill.getModule('toolbar');
            toolbar.addHandler('link', function(value) {
                if (value) {
                    const href = prompt('Enter the URL:');
                    if (href) this.quill.format('link', href);
                } else {
                    this.quill.format('link', false);
                }
            });

            toolbar.addHandler('image', function(value) {
                if (value) {
                    const src = prompt('Enter image URL:');
                    if (src) this.quill.format('image', src);
                } else {
                    this.quill.format('image', false);
                }
            });
        });
    </script>
    @endBassetBlock
@endpush
