@php

  $recetas = $field['options_recetas'];


@endphp
<!-- select2 from array -->
<style>
  .select2-container--bootstrap .select2-dropdown {
    z-index: 100000;
  }
</style>

@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')

    <a data-fancybox data-options='{"src": "#modal-agregar-receta", "touch": false, "smallBtn" : false}' href="javascript:;" style="margin-top: 23px" id="btn_agregar_receta_tip" type="button" class="btn btn-default">
        <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
    </a>


    <div id="modal-agregar-receta" style="display: none; width: 900px">
      <h4 style="margin-bottom: 15px;">Agregar receta a tu tip</h4>

      <select
        name="receta-a-tip"
        style="width: 100%"
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_from_array'])
        @if (isset($field['allows_multiple']) && $field['allows_multiple']==true)multiple @endif
        >

        @if (count($field['options_recetas']))
            @foreach ($field['options_recetas'] as $key => $value)
                @if((old(square_brackets_to_dots($field['name'])) && (
                        $key == old(square_brackets_to_dots($field['name'])) ||
                        (is_array(old(square_brackets_to_dots($field['name']))) &&
                        in_array($key, old(square_brackets_to_dots($field['name'])))))) ||
                        (null === old(square_brackets_to_dots($field['name'])) &&
                            ((isset($field['value']) && (
                                        $key == $field['value'] || (
                                                is_array($field['value']) &&
                                                in_array($key, $field['value'])
                                                )
                                        )) ||
                                (isset($field['default']) &&
                                ($key == $field['default'] || (
                                                is_array($field['default']) &&
                                                in_array($key, $field['default'])
                                            )
                                        )
                                ))
                        ))
                    <option es_ingrediente="false" value="{{ $key }}" selected>{{ $value }}</option>
                @else
                    <option es_ingrediente="false" value="{{ $key }}">{{ $value }}</option>
                @endif
            @endforeach
        @endif

      </select>

      <button data-fancybox-close id="button-agregar-receta-tip" class="btn btn-success" style="margin-top: 10px">Agregar a tips</button>

      {{-- HINT --}}
      @if (isset($field['hint']))
          <p class="help-block">{!! $field['hint'] !!}</p>
      @endif
      
    </div>



    <!-- <label>{!! $field['label'] !!}</label>
    <select
        name="{{ $field['name'] }}@if (isset($field['allows_multiple']) && $field['allows_multiple']==true)[]@endif"
        style="width: 100%"
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_from_array'])
        @if (isset($field['allows_multiple']) && $field['allows_multiple']==true)multiple @endif
        >

        <!-- @if (isset($field['allows_null']) && $field['allows_null']==true)
            <option value="">-</option>
        @endif

      

    </select> -->



@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
    <!-- include select2 css-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    <link href="{{ asset('vendor/adminlte/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
    <!-- include select2 js-->
    <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/caret/1.3.7/jquery.caret.min.js"></script>
    <script src="{{ asset('vendor/adminlte/bower_components/select2/dist/js/select2.min.js') }}"></script>
    <script>
        jQuery(document).ready(function($) {
            // trigger select2 for each untriggered select2 box
            $('.select2_from_array').each(function (i, obj) {
                if (!$(obj).hasClass("select2-hidden-accessible"))
                {
                    $(obj).select2({
                        theme: "bootstrap"
                    });
                }
            });

            // var ingrediente = $('[name="{{ $field["name"] }}"]');
            // //var medida = $('[name="medida"]');
            // var instruccion = $('[name="instruccion"]');

            var receta;
            var textareaTips = $('textarea[name="tips"]');
            var textBefore;
            var textAfter;
            var nowText;
            var cursosPosition = 0;

            textareaTips.on('mouseup keydown', function() {// Posición del cursor dentro del textarea
              cursorPosition = $(this).caret();
            });

            $('#button-agregar-receta-tip').click(function() {
              receta = $('[name="receta-a-tip"]').val();
              nowText = textareaTips.val();
              textBefore = nowText.substring(0, cursorPosition);
              textAfter = nowText.substring(cursorPosition, nowText.length);

              textareaTips.val(textBefore + "receta[" + receta + "]" + textAfter);
            });
        });
    </script>
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
