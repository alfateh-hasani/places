@php
    $connected_entity = new $field['model'];
    $connected_entity_key_name = $connected_entity->getKeyName();
    $field['multiple'] = $field['multiple'] ?? $crud->relationAllowsMultiple($field['relation_type']);
    $field['attribute'] = $field['attribute'] ?? $connected_entity->identifiableAttribute();
    $field['include_all_form_fields'] = $field['include_all_form_fields'] ?? true;
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
    // Note: isColumnNullable returns true if column is nullable in database, also true if column does not exist.

    $field['closeOnSelect'] ??= !$field['multiple'];

    // this field can be used as a pivot select for n-n relationships
    $field['is_pivot_select'] = $field['is_pivot_select'] ?? false;
    $field['allow_duplicate_pivots'] = $field['allow_duplicate_pivots'] ?? false;

    if (!isset($field['options'])) {
            $field['options'] = $connected_entity::all()->pluck($field['attribute'],$connected_entity_key_name);
        } else {
            $field['options'] = call_user_func($field['options'], $field['model']::query())->pluck($field['attribute'],$connected_entity_key_name);
    }

    // make sure the $field['value'] takes the proper value
    $current_value = old_empty_or_null($field['name'], []) ??  $field['value'] ?? $field['default'] ?? [];

    if (!empty($current_value) || is_int($current_value)) {
        switch (gettype($current_value)) {
            case 'array':
                $current_value = $connected_entity
                                    ->whereIn($connected_entity_key_name, $current_value)
                                    ->get()
                                    ->pluck($field['attribute'], $connected_entity_key_name);
                break;

            case 'object':
                if (is_subclass_of(get_class($current_value), 'Illuminate\Database\Eloquent\Model') ) {
                    $current_value = [$current_value->{$connected_entity_key_name} => $current_value->{$field['attribute']}];
                }else{
                    $current_value = $current_value
                                    ->pluck($field['attribute'], $connected_entity_key_name);
                }
            break;

            case 'NULL':
                $current_value = [];
            break;

            default:
                $value = $field['options']
                                ->where($connected_entity_key_name, $current_value)
                                ->pluck($field['attribute'], $connected_entity_key_name)->first();

                $current_value = $value ?? [$current_value => $field['options'][$current_value]];
        }
    }

    $current_value = !is_array($current_value) ? $current_value->toArray() : $current_value;
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    {{-- To make sure a value gets submitted even if the "select multiple" is empty, we need a hidden input --}}
    @if($field['multiple'])<input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />@endif
    <select
        style="width:100%"
        name="{{ $field['name'].($field['multiple']?'[]':'') }}"
        data-init-function="bpFieldInitRelationshipSelectElement"
        data-field-is-inline="{{var_export($inlineCreate ?? false)}}"
        data-column-nullable="{{ var_export($field['allows_null']) }}"
        data-placeholder="{{ $field['placeholder'] }}"
        data-field-multiple="{{var_export($field['multiple'])}}"
        data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
        data-is-pivot-select="{{var_export($field['is_pivot_select'])}}"
        data-allow-duplicate-pivots="{{ var_export($field['allow_duplicate_pivots']) }}"
        data-close-on-select="{{var_export($field['closeOnSelect'])}}"
        bp-field-main-input
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control'])

        @if($field['multiple'])
        multiple
        @endif
        >

        @if ($field['allows_null'] && !$field['multiple'])
            <option value="">-</option>
        @endif

        @if (count($field['options']))
            @foreach ($field['options'] as $key => $option)
            @php
                $selected = '';
                if(!empty($current_value)) {
                    if(in_array($key, array_keys($current_value))) {
                        $selected = 'selected';
                    }
                }
            @endphp
                    <option value="{{ $key }}" {{$selected}}>{{ $option }}</option>
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
    <style type="text/css">
        .select2-search__field {
            width: 100%!important;
        }
    </style>
@endpush

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
    {{-- include select2 js --}}
    @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js')
    @if (app()->getLocale() !== 'en')
        @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js')
    @endif

@bassetBlock('backpack/pro/fields/relationship-select-field-'.app()->getLocale().'.js')
<script>
    // if nullable, make sure the Clear button uses the translated string
    document.styleSheets[0].addRule('.select2-selection__clear::after','content:  "{{ trans('backpack::crud.clear') }}";');


    /**
     *
     * This method gets called automatically by Backpack:
     *
     * @param  node element The jQuery-wrapped "select" element.
     * @return void
     */
    function bpFieldInitRelationshipSelectElement(element) {
        const $placeholder = element.attr('data-placeholder');
        const $multiple = element.attr('data-field-multiple')  == 'false' ? false : true;
        const $allows_null = (element.attr('data-column-nullable') == 'true') ? true : false;
        const $allowClear = $allows_null;
        const $isFieldInline = element.data('field-is-inline');
        const $isPivotSelect = element.data('is-pivot-select');
        const $allowDuplicatePivots = element.data('allow-duplicate-pivots');
        const $closeOnSelect = element.data('close-on-select');
        
        const changePivotOptionState = function(pivotSelector, enable = false, value = null) {
            let containerName = getPivotContainerName(pivotSelector);
            let pivotsContainer = pivotSelector.closest('div[data-repeatable-holder="'+containerName+'"]');
            let pivotValue = value ? value : pivotSelector.val();

            $(pivotsContainer).children().each(function(i,container) {
                $(container).find('select').each(function(i, el) {
                    if(typeof $(el).attr('data-is-pivot-select') !== 'undefined' && $(el).attr('data-is-pivot-select')) {
                        if(pivotValue) {
                            if(enable) {
                                $(el).find('option[value="'+pivotValue+'"]').prop('disabled',false);   
                            }else{
                                if($(el).val() !== pivotValue) {
                                    $(el).find('option[value="'+pivotValue+'"]').prop('disabled',true);
                                }
                            }
                        }
                    }
                });
            });
        };

        const getPivotContainerName = function(pivotSelector) {
            let containerName = pivotSelector.attr('data-repeatable-input-name');
            return containerName.indexOf('[') !== -1 ? containerName.substring(0, containerName.indexOf('[')) : containerName;
        }

        const disablePreviouslySelectedPivots = function(pivotSelector) {
            let selectedValues = [];
            let selectInputs = [];
            let containerName = getPivotContainerName(pivotSelector);
            let pivotsContainer = pivotSelector.closest('div[data-repeatable-holder="'+containerName+'"]');
            
            $(pivotsContainer).children().each(function(i,container) {
                $(container).find('select').each(function(i, el) {
                    if(typeof $(el).attr('data-is-pivot-select') !== 'undefined' && $(el).attr('data-is-pivot-select') != "false") {
                        selectInputs.push(el);
                        if($(el).val()) {
                            selectedValues.push($(el).val());
                        }
                    }
                });
            });

            selectInputs.forEach(function(input) {
                selectedValues.forEach(function(value) {
                    if(value !== $(input).val()) {
                        $(input).find('option[value="'+value+'"]').prop('disabled',true);
                    }
                });
            });
        };

        var $select2Settings = {
                theme: 'bootstrap',
                multiple: $multiple,
                placeholder: $placeholder,
                allowClear: $allowClear,
                dropdownParent: $isFieldInline ? $('#inline-create-dialog .modal-content') : $(document.body),
                closeOnSelect: $closeOnSelect
            };
        if (!$(element).hasClass("select2-hidden-accessible"))
        {
            $(element).select2($select2Settings);
            
            if($isPivotSelect && !$allowDuplicatePivots) {
                disablePreviouslySelectedPivots($(element));
            }
        }

        if($isPivotSelect && !$allowDuplicatePivots) {
            $(element).on('select2:selecting', function(e) {
                if($(this).val()) {
                    changePivotOptionState($(this)); 
                }
                return true;
            });

            $(element).on('select2:select', function(e) {
                changePivotOptionState($(this));
                return true;
            });

            $(element).on('select2:unselecting', function(e) {
                changePivotOptionState($(this), true, e.params.args.data.id); 
                return true;
            });

            $(element).on('CrudField:delete', function(e) {
                changePivotOptionState($(this), true);
                return true;
            });
        }

        $(element).on('CrudField:disable', function(e) {
            if($multiple) {
                let hiddenInput = element.siblings('input[type="hidden"]');
                if(hiddenInput.length) {
                    hiddenInput.prop('disabled',true);
                }
            }
            return true;
        });

        $(element).on('CrudField:enable', function(e) {
            if($multiple) {
                let hiddenInput = element.siblings('input[type="hidden"]');
                if(hiddenInput.length) {
                    hiddenInput.prop('disabled',false);
                }
            }
            return true;
        });

    }
</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
