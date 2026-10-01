{{-- select2 from array --}}
@php
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
    $field['value'] = old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '';
    $field['multiple'] = $field['allows_multiple'] ?? false;
    $field['placeholder'] = $field['placeholder'] ?? ($field['multiple'] ? trans('backpack::crud.select_entries') : trans('backpack::crud.select_entry'));
    $field['closeOnSelect'] = $field['closeOnSelect'] ?? !$field['multiple'];
    $field['sortable'] = $field['multiple'] ? ($field['sortable'] ?? false) : false;
@endphp
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    {{-- To make sure a value gets submitted even if the "select multiple" is empty, we need a hidden input --}}
    @if($field['multiple'])<input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />@endif
    <select
        name="{{ $field['name'] }}@if (isset($field['allows_multiple']) && $field['allows_multiple']==true)[]@endif"
        style="width: 100%"
        data-init-function="bpFieldInitSelect2FromArrayElement"
        data-field-is-inline="{{var_export($inlineCreate ?? false)}}"
        data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
        data-allows-null="{{var_export($field['allows_null'])}}"
        data-placeholder="{{$field['placeholder']}}"
        data-close-on-select="{{var_export($field['closeOnSelect'])}}"
        data-sortable="{{var_export($field['sortable'])}}"
        data-selected-order="{{ is_array($field['value']) ? json_encode($field['value']) : '[]' }}"
        bp-field-main-input
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_from_array'])
        @if ($field['multiple'])multiple @endif
        >

        @if ($field['allows_null'] && !$field['multiple'])
            <option value="">-</option>
        @endif

        @if (count($field['options']))
            @foreach ($field['options'] as $key => $value)
                @if($key == $field['value'] || (is_array($field['value']) && in_array($key, $field['value'])))
                    <option value="{{ $key }}" selected>{{ $value }}</option>
                @else
                    <option value="{{ $key }}">{{ $value }}</option>
                @endif
            @endforeach
        @endif
    </select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}


{{-- FIELD CSS - will be loaded in the after_styles section --}}
@push('crud_fields_styles')
    {{-- include select2 css --}}
    @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css')
    @basset('https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css')
@endpush

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
{{-- include select2 js --}}
@basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js')
@basset('https://cdn.jsdelivr.net/npm/jquery-ui@1.13.2/dist/jquery-ui.min.js')
@if (app()->getLocale() !== 'en')
    @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js')
@endif
@bassetBlock('backpack/pro/fields/select2-from-array-field.js')
<script>
    function bpFieldInitSelect2FromArrayElement(element) {

        function select2_renderSelections(select2) {
            const order      = select2.data('preserved-order') || [];
            const container = select2.next('.select2-container');
            const tags      = container.find('li.select2-selection__choice');
            const input     = tags.last().next();
            const select2Utils = $.fn.select2.amd.require('select2/utils');

            // Get the Select2 instance
            const select2Instance = select2Utils.GetData(select2[0], 'select2');

            var selectedOptions;
        
            select2Instance.dataAdapter.current(function(data) {
                selectedOptions = data;
            });

            // apply tag order
            order.forEach(val=> {
                let el = selectedOptions.find(obj=>obj.id == val);
                let elText = el.text || el.title;

                let tag = tags.filter(function() {
                    let tagTitle = $(this).attr('title') ?? $(this).text();
                    return tagTitle === elText;
                });
                input.before( tag );
            });
        }

        function selectionHandler(e) {
            const select2  = $(this);
            const val       = e.params.data.id;
            const order     = select2.data('preserved-order') || [];
            switch (e.type){
                case 'select2:select':      
                order[ order.length ] = val;
                break;
                case 'select2:unselect':
                let found_index = order.indexOf(val);
                if (found_index >= 0 )
                    order.splice(found_index,1);
                break;
            }
            select2.data('preserved-order', order); // store it for later
            
            // A promise might be better, but this is to avoid the race issue
            window.setTimeout(function(){
                select2_renderSelections(select2);
            },0);
        }

        if (!element.hasClass("select2-hidden-accessible"))
        {
            let $isFieldInline = element.data('field-is-inline');
            let $allowClear = element.data('allows-null');
            let $multiple = element.attr('multiple') ?? false;
            let $placeholder = element.attr('placeholder');
            let $closeOnSelect = element.data('close-on-select');
            let $sortable = element.data('sortable');
            let $orderedValue = element.data('selected-order');

            let select2 = element.select2({
                theme: "bootstrap",
                allowClear: $allowClear,
                multiple: $multiple,
                placeholder: $placeholder,
                dropdownParent: $isFieldInline ? $('#inline-create-dialog .modal-content') : $(document.body),
                closeOnSelect: $closeOnSelect
            });

            if($sortable) {
                let defaults = select2.select2('data');
                defaults.forEach(obj=>{
                    let order = select2.data('preserved-order') || [];
                    order[ order.length ] = obj.id;
                    select2.data('preserved-order', order)
                });

                select2.next().find('ul').sortable({
                    containment: 'parent', 
                    stop: function (event, ui) {
                        let order = [];
                        ui.item.parent().children('[title]').each(function () {
                            var title = $(this).attr('title');
                            $('option:contains(\'' + title + '\')', select2).filter(function(){
                                if ($(this).text() === title) {
                                    var original = $(this);
                                    order.push(original.val());
                                }
                            });
                        });
                        select2.data('preserved-order', order);
                    }
                });

                select2.val($orderedValue);
                select2.data('preserved-order', $orderedValue);  // must manually preserve the order
                select2.trigger('change');
                    
                // Render in preserved order
                select2_renderSelections(select2);

                select2.on('select2:select select2:unselect', selectionHandler);

                // before the form is submitted, reorder the <select> options so that they are sent to the server in the order they were selected
                select2.closest('form').submit(function() {
                    let order = select2.data('preserved-order') || [];
                    let options = select2.find('option');
                    options.sort((a,b)=>order.indexOf(a.value) - order.indexOf(b.value));
                    select2.html(options);

                });
            }
        }
    }
</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
