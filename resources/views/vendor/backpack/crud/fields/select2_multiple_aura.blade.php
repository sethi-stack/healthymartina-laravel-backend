<!-- select2 multiple -->
@php
    if (!isset($field['options'])) {
        $options = $field['model']::all();
    } else {
        $options = call_user_func($field['options'], $field['model']::query());
    }
    $multiple = isset($field['multiple']) && $field['multiple']===false ? '': 'multiple';
@endphp

@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <select

        name="{{ $field['name'] }}[]"
        style="width: 100%"
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_multiple'])
        {{$multiple}}>

        @if (isset($field['allows_null']) && $field['allows_null']==true)
            <option value="">-</option>
        @endif

        @if (isset($field['model']))
            @foreach ($options as $option)
                @if( (old(square_brackets_to_dots($field["name"])) && in_array($option->getKey(), old($field["name"]))) || (is_null(old(square_brackets_to_dots($field["name"]))) && isset($field['value']) && in_array($option->getKey(), $field['value']->pluck($option->getKeyName(), $option->getKeyName())->toArray())))
                    <option value="{{ $option->getKey() }}" selected>{{ $option->{$field['attribute']} }}</option>
                @else
                    <option value="{{ $option->getKey() }}">{{ $option->{$field['attribute']} }}</option>
                @endif
            @endforeach
        @endif
    </select>

    @if(isset($field['select_all']) && $field['select_all'])
        <a class="btn btn-xs btn-default select_all" style="margin-top: 5px;"><i class="fa fa-check-square-o"></i> {{ trans('backpack::crud.select_all') }}</a>
        <a class="btn btn-xs btn-default clear" style="margin-top: 5px;"><i class="fa fa-times"></i> {{ trans('backpack::crud.clear') }}</a>
    @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')


{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        <!-- include select2 css-->
        <link href="{{ asset('vendor/adminlte/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    
        <style>
            .select2-results__options {
                display: flex;
                justify-content: space-evenly;
                flex-wrap: wrap;
            }
            .select2-results__options li {
                width: fit-content;
                margin: 5px;
                border: 1px solid;
                border-radius: 5px;
            }
            .select2-container--bootstrap .select2-results__option[aria-selected=true] {
                background-color: #edeaea !important;
                color: #262626;
            }
        </style>    
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <!-- include select2 js-->
        <script src="{{ asset('vendor/adminlte/bower_components/select2/dist/js/select2.min.js') }}"></script>
        <script>
            jQuery(document).ready(function($) {
                // trigger select2 for each untriggered select2_multiple box

                $('.select2_multiple').each(function (i, obj) {
                    if (!$(obj).hasClass("select2-hidden-accessible"))
                    {
                        var $obj = $(obj).select2({
                            theme: "bootstrap",
                            closeOnSelect: false,
                        });

                        var options = [];
                        @if (count($options))
                            @foreach ($options as $option)
                                options.push({{ $option->getKey() }});
                            @endforeach
                        @endif

                        @if(isset($field['select_all']) && $field['select_all'])
                            $(obj).parent().find('.clear').on("click", function () {
                                $obj.val([]).trigger("change");
                            });
                            $(obj).parent().find('.select_all').on("click", function () {
                                $obj.val(options).trigger("change");
                            });
                        @endif
                    }
                });
            });
        </script>
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
