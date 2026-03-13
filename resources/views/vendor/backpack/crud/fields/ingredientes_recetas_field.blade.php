@php

  $ingredientes = $field['options_ingredientes'];
  $recetas = $field['options_recetas'];

//  dd($ingredientes, $recetas);

@endphp
<!-- select2 from array -->

@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    <select
        name="{{ $field['name'] }}@if (isset($field['allows_multiple']) && $field['allows_multiple']==true)[]@endif"
        style="width: 100%"
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_from_array'])
        @if (isset($field['allows_multiple']) && $field['allows_multiple']==true)multiple @endif
        >

        @if (isset($field['allows_null']) && $field['allows_null']==true)
            <option value="">-</option>
        @endif

        @if (count($field['options_ingredientes']))
            @foreach ($field['options_ingredientes'] as $key => $value)
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
                    <option es_ingrediente="true" value="{{ $key }}" selected>{{ $value }}</option>
                @else
                    <option es_ingrediente="true" value="{{ $key }}">{{ $value }}</option>
                @endif
            @endforeach
        @endif

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
                    <option es_ingrediente="false" receta_value="{{ $key }}" selected>{{ $value }}</option>
                @else
                    <option es_ingrediente="false" receta_value="{{ $key }}">{{ $value }}</option>
                @endif
            @endforeach
        @endif

    </select>

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
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
    <!-- include select2 js-->
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

            var ingrediente = $('[name="{{ $field["name"] }}"]');
            var medida = $('[name="medida"]');
            var instruccion = $('[name="instruccion"]');

            ingrediente.on('change', function(){
              if(ingrediente.val()){
                if($('[name="{{ $field["name"] }}"] option:selected').attr('es_ingrediente') == 'true'){//ES INGREDIENTE
                  instruccion.prop('disabled', false);
                  $.ajax({
                    url:'/admin/Recetas/ingrediente-medida/' + ingrediente[0].value,
                    type: 'POST',
                    success: function(result){
                      if(result){
                        // console.log(result);
                        instruccion[0].options.length = 0;
                        for (var j = 0; j < result.length; j++) {
                          // console.log(result[j]);
                          if(result[j] != null)
                            instruccion.append("<option value='" + result[j].id + "'>" + result[j].nombre + "</option>");
                          else{
                            alert('Existe un error en el ingrediente');
                          }
                        }
                        instruccion.trigger('change');
                      }
                    },
                    error: function(result){
                      console.log('Error al encontrar un ingrediente: ' + result);
                    }
                  });
                }
                else{//ES UNA SUBRECETA
                  instruccion[0].options.length = 0;
                  instruccion.prop('disabled', true);
                  $.ajax({//VOLVER A CARGAR TODAS LAS MEDIDAS
                    url:'/admin/Recetas/receta-medida/' + $('[name="{{ $field["name"] }}"] option:selected').attr('receta_value'),
                    type: 'POST',
                    success: function(result){
                      if(result){
                        // console.log(result);
                        medida[0].options.length = 0;
                        for (var i = 0; i < result.length; i++) {
                          medida.append("<option value='" + result[i].id + "'>" + result[i].nombre + "</option>");
                        }
                      }
                    },
                    error: function(result){
                      console.log('Error al encontrar la receta' + result);
                    }
                  });
                }
              }
            });

            instruccion.on('change', function(){
              // alert('entre');
                $.ajax({
                    url:'/admin/Recetas/MedidasPorInstruccion',
                    type: 'POST',
                    data: { ingrediente: ingrediente[0].value, instruccion: instruccion[0].value },
                    success: function(result){
                      if(result){
                        console.log(result);
                        medida[0].options.length = 0;
                        for (var j = 0; j < result.length; j++) {
                          if(result[j] != null){
                            medida.append("<option value='" + result[j].id + "'>" + result[j].nombre + "</option>");
                          }
                          else{
                            alert('Existe un error en las instrucciones del ingrediente');
                          }
                        }
                      }
                    },
                    error: function(result){
                      console.log('Error al encontrar una medida: ' + result);
                    }
                });
            });
        });
    </script>
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
