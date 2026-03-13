<!-- used for heading, separators, etc -->
@php $medidas = App\Models\Medida::all()->pluck('nombre', 'id')->toArray(); @endphp
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
	{!! $field['value'] !!}
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
<script>
    jQuery(document).ready(function($) {
				var medida = $("[name='medida_id']");
				var cantidad_resultado = $("[name='cantidad_resultado']");
				var active_resultado = $("[name='active_resultado']");
        var btn_insertar = $("#btn_agregar_resultado_receta");
        var tabla_resultados = $("#tabla_resultados");
				var input_array = $("input[name='array_resultados']");
        var resultados_array = [];
        var dataIndexActual = 0;

        cantidad_resultado.attr("validate_required","true");
        cantidad_resultado.attr("validate_number","true");
        cantidad_resultado.attr("validate_min","0.1");
        cantidad_resultado.attr("validate_max","26");

				if(input_array.val() != ''){//REGRESAR CON INPUTS
           var value = JSON.parse(input_array.val());
          //  console.log(value);

           for(var i = 0; i < value.length; i++){
                resultados_array.push({
                medida: value[i].medida,
                medida_nombre: value[i].medida_nombre,
								cantidad_resultado: value[i].cantidad_resultado,
                active:value[i].active
							});

              let row = "<tr data-index='"+ i +"' class='validation_form'>" +  
              "<td class='columnas-editables cantidad'>" + value[i].cantidad_resultado + "</td>" +
							"<td class='columnas-editables-selector medida' style='padding-left: 8px' data-medida_id='"+ value[i].medida +"'>" + value[i].medida_nombre + "</td>" +
							"<td class='columnas-editables-sino' style='padding-left: 8px' data-active_id='"+ value[i].active +"'>" + getActiveResultadoText(value[i].active) + "</td>" +
							"<td style='padding-left: 25px'><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_edit'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" +
							"<td style='padding-left: 25px'><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td>" +
              +"</tr>";
              dataIndexActual = i + 1;
              tabla_resultados.append(row);
           }
        }


        btn_insertar.click(function(){
          var fieldsValidated = true;
         if(medida[0].value == 3 || medida[0].value == 4 ){
            cantidad_resultado.attr("validate_max","48");
            fieldsValidated = true;
          }else{
            cantidad_resultado.attr("validate_max","26");
          }
          var fieldValidated = window.validateField(cantidad_resultado);
          if(fieldValidated == false) {
            fieldsValidated = false;
          }
          if(fieldsValidated) {
            if(medida[0].value && cantidad_resultado.val()){
              resultados_array.push({
                medida: medida[0].value,
                medida_nombre: $("[name='medida_id'] option:selected").html(),
                cantidad_resultado: cantidad_resultado.val(),
                active: getActiveResultadoValue(),
              });
              //console.log('resultados_array',resultados_array);

              $("input[name=array_resultados]").val(JSON.stringify(resultados_array));

              let row = "<tr data-index='"+ dataIndexActual +"' class='validation_form'>" +  
                "<td class='columnas-editables cantidad'>" + cantidad_resultado.val() + "</td>" + 
                "<td class='columnas-editables-selector medida' data-medida_id='"+$("[name='medida_id'] option:selected").val()+"'>" + $("[name='medida_id'] option:selected").html() + "</td>" +  
                "<td class='columnas-editables-sino' data-active_id='"+getActiveResultadoValue()+"'>" + getActiveResultadoText(getActiveResultadoValue()) + "</td>"  +
                "<td style='padding-left: 25px'><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_edit'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" +
                "<td style='padding-left: 25px'><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td></tr>";
              dataIndexActual++;
              tabla_resultados.append(row);
            }
          }
        });

        $("#tabla_resultados").on( "click", ".btn_trash", function(e) {
          e.preventDefault();
          //  console.log('resultados_array',resultados_array);
          resultados_array.splice($( this ).parents( "tr" ).index(), 1);
          $( this ).parents( "tr" ).remove();
          $("input[name=array_resultados]").val(JSON.stringify(resultados_array));

        });

        $("#tabla_resultados").on('click', '.btn_edit', function (e) {
          //console.log('resultados_array',resultados_array);
          if($(this).hasClass('modo-edicion')){
            var fieldsValidated = true;
            var medida_check = $(this).parents('.validation_form').find('.medida select').val();
            var cantidad_input = $(this).parents('.validation_form').find('.cantidad input');
            if (medida_check == 3 || medida_check == 4) {
              cantidad_input.attr("validate_max","48");
              fieldsValidated = true;
            }else{
              cantidad_input.attr("validate_max","26");
            }
            $(this).parents('.validation_form').find('input.modo-edicion, select.modo-edicion').each(function(){
              var fieldValidated = window.validateField($(this));
              if(fieldValidated == false) {
                fieldsValidated = false;
              }
            });
            if(fieldsValidated) {
              $(this).removeClass('modo-edicion');
              var order = $(this).parents('tr').data('index');
              // alert(order);
              var obj = null;
              // if(order){
                console.log('order',order);
                obj = resultados_array.indexOf(order);
              // }

              if(!obj){
                alert('Ocurrió un error y puede que la información no se haya modificado');
              }
              //convertir inputs y selector en td
              $(this).parents('tr').find('td.columnas-editables').each(function() {
                if($(this).hasClass("instruccion")){
                  resultados_array[order].instruccion = $(this).find('input').val();
                  $(this).html($(this).find('input').val());
                }

                // if($(this).hasClass("sin_conversion")){
                //   resultados_array[order].instruccion = $(this).val();
                // }

                if($(this).hasClass("cantidad")){
                  resultados_array[order].cantidad_resultado = $(this).find('input').val();
                  $(this).html($(this).find('input').val());
                }

                if($(this).hasClass("nota")){
                  resultados_array[order].nota = $(this).find('input').val();
                  $(this).html($(this).find('input').val());
                }

                // $(this).find("input").remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original

              });
              $(this).parents('tr').find('td.columnas-editables-selector').each(function() {
                $(this).find('.modo-edicion').each(function(){
                  let medida_nombre = $(this).find('.medida_nombre');
                  let medida_id = $(this).find('.medida_id');

                  medida_nombre = $(this).find('option:selected').text();
                  resultados_array[order].medida_nombre = medida_nombre;

                  medida_id = $(this).find('option:selected').val();
                  resultados_array[order].medida = medida_id; //index + 1 porque inicia en cero
                  $(this).parent().attr("data-medida_id",medida_id);

                  $(this).parent().text($(this).find('option:selected').text());
                  $(this).remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original
                });
              });

              $(this).parents('tr').find('td.columnas-editables-sino').each(function() {
                $(this).find('.modo-edicion').each(function(){
                  active_id = $(this).find('option:selected').val();
                  resultados_array[order].active = active_id; //index + 1 porque inicia en cero
                  $(this).parent().attr("data-active_id",active_id);

                  $(this).parent().text($(this).find('option:selected').text());
                  $(this).remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original
                });
              });

              // console.log('resultados_array',resultados_array);
              $("input[name='array_resultados']").val(JSON.stringify(resultados_array));
              console.log('resultados_array',resultados_array);
            }
          }
          else{
            $(this).addClass('modo-edicion');
            $(this).parents('tr').find('td.columnas-editables').each(function() {
              // alert($(this).text());
              var input = '';
              if($(this).hasClass('cantidad')) {
                input = $('<input class="modo-edicion" type="text" style="width: 100%;" value="'+ $(this).text() +'" validate_required="true" validate_number="true" validate_min="0.1" validate_max="26" />');
              } else {
                input = $('<input class="modo-edicion" type="text" style="width: 100%;" value="'+ $(this).text() +'"/>');
              }
              $(this).html(input);
            });
            $(this).parents('tr').find('td.columnas-editables-selector').each(function() {
              var medidas = '';
              var medida_id = $(this).data('medida_id');
              // console.log('medida_id', medida_id);

              @foreach($medidas as $key => $medida);
                if("{{ $key }}" == medida_id){
                  medidas += '<option value="{{ $key }}" selected> {{ $medida }} </option>';
                }
                else{
                  medidas += '<option value="{{ $key }}"> {{ $medida }} </option>';
                }
              @endforeach; 
              var select = $('<select class="modo-edicion" style="width: 100%;">' +  medidas  +'</select>');
              $(this).html(select);
            });

            $(this).parents('tr').find('td.columnas-editables-sino').each(function() {
              var active_id = $(this).attr("data-active_id");
              var medidas = '';
              medidas += '<option value="1" '+ (active_id == 1 ? 'selected' : '') + '> Si </option>';
              medidas += '<option value="0" '+ (active_id == 0 ? 'selected' : '') + '> No </option>';
              var select = $('<select class="modo-edicion" style="width: 100%;">' +  medidas  +'</select>');
              $(this).html(select);
            });
          }
          // console.log('resultados_array', resultados_array);
        });

        function getActiveResultadoValue(){
          if ($("[name='active_resultado']").is(":checked")){
            return 1;
          }
          else{
            return 0;
          }
        }
        function getActiveResultadoText(value){
          if(value){
            return 'Si';
          }
          else{
            return 'No';
          }
        }
    });
</script>
@endpush

{{-- End of Extra CSS and JS --}}
