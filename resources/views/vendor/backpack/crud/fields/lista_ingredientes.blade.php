@php $medidas = App\Models\Medida::all()->pluck('nombre', 'id')->toArray(); @endphp
<!-- used for heading, separators, etc -->
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
	{!! $field['value'] !!}
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>

<script>
    jQuery(document).ready(function($) {
        var ingrediente = $("[name='ingrediente']");
				var medida = $("[name='medida']");
				var instruccion = $("[name='instruccion']");
				var cantidad = $("[name='cantidad']");
				var nota = $("[name='nota']");
        // var nota_tiempo = $("[name='nota_tiempo']")
        var btn_insertar = $("#btn_agregar_ingredientes_receta");
        var tabla_relaciones_ing = $("#tabla_relaciones_ing");
				var input_array = $("input[name='array_ingredientes']");
				var array_ingredientes = [];
        var orderActual = 0;

        cantidad.attr("validate_required","true");
        cantidad.attr("validate_number","true");
        cantidad.attr("validate_min","0.01");

        $("#tabla_relaciones_ing tbody").sortable({
          helper: fixHelperModified,
          itemSelector: 'tr',//SE UTILIZA TR PARA INDICAR TODA LA FILA
          update: function(event, ui) {
                  var productOrder = $(this).sortable('toArray');//REGRESA EL ARRAY REACOMODADO, UTILIZA LOS ID DE LOS TR, NO BORRAR LOS ID DE LOS TR EN DONDE SE AGREGA LA FILA
                  let tmpArrayIngredientes = [];

                  for(var i = 0; i < productOrder.length; i++){
                    var tmpIngrediente = array_ingredientes.find(o => o.order == productOrder[i]);
                    tmpArrayIngredientes.push(tmpIngrediente);
                  }

                  // console.log(tmpArrayIngredientes);
                  array_ingredientes = [];
                  array_ingredientes = tmpArrayIngredientes;
                  $("input[name=array_ingredientes]").val(JSON.stringify(array_ingredientes));//ACTUALIZA EL ARRAY QUE SERÁ ENVIADO EN EL REQUEST
               },
        }).disableSelection();

        // $("tbody").sortable({
        //   distance: 5,
        //   delay: 100,
        //   opacity: 0.6,
        //   cursor: 'move',
        //   stop: updateIndex
        // });

        var fixHelperModified = function(e, tr) {
          var $originals = tr.children();
          var $helper = tr.clone();
          $helper.children().each(function(index) {
            $(this).width($originals.eq(index).width());
            // console.log('entre2');
          });
          return $helper;
        };

				if(input_array.val() != ''){//REGRESAR CON INPUTS
           var value = JSON.parse(input_array.val());
          //  console.log(input_array.val());

           for(var i = 0; i < value.length; i++){
						 	// console.log('value[i] ' + value[i].cantidad);
						 		if(i == 0){//RELLENAR CANTIDAD DE COBERTURA CON LA OPCION
								 	cantidad.val(value[value.length - 1].cantidad);
								}

                array_ingredientes.push({
                  ingrediente: value[i].ingrediente,
                  medida: value[i].medida,
  								instruccion: value[i].instruccion ? value[i].instruccion : null,
  								cantidad: value[i].cantidad,
                  nota: value[i].nota,
                  es_ingrediente: value[i].es_ingrediente,
  								ingrediente_nombre: value[i].ingrediente_nombre,
  								instruccion_nombre: value[i].instruccion_nombre ? value[i].instruccion_nombre : '',
                  medida_nombre: value[i].medida_nombre,
                  order: i,
  							});

                // console.log(value);
              let row = "";
              if(value[i].es_ingrediente){
                row = "<tr id='"+ orderActual +"' data-index='"+ orderActual +"' class='validation_form'>" 
                          + "<td>" + (i + 1) + "</td>"
                          + "<td>" + value[i].ingrediente_nombre + "</td>"
  							          + "<td class='columnas-editables-instruccion instruccion' data-ingrediente='"+value[i].ingrediente+"' data-instruccion='"+value[i].instruccion+"'>" + (value[i].instruccion_nombre ? value[i].instruccion_nombre : ' ' ) + "</td>"
  							          + "<td class='columnas-editables cantidad'>" + value[i].cantidad  + "</td>" 
                          + "<td class='columnas-editables-selector medida' data-medida_nombre='"+ value[i].medida_nombre +"' data-medida_id='"+ value[i].medida +"' >"  + value[i].medida_nombre + "</td>"
  							          + "<td class='columnas-editables nota'>" + value[i].nota + "</td>" 
                          + "<td><button style='padding: 3px 6px;' type='button' class='btn-edit btn btn-warning dropdown-toggle'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" 
                          + "<td><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td>"
                        + "</tr>";
              }
              else{
                row = "<tr id='"+ orderActual +"' data-index='"+ orderActual +"' class='validation_form'>" 
                          + "<td>" + (i + 1) + "</td>"
                          + "<td>" + value[i].ingrediente_nombre + "</td>"
  							          + "<td>" + (value[i].instruccion_nombre ? value[i].instruccion_nombre : ' ' ) + "</td>"
  							          + "<td class='columnas-editables cantidad'>" + value[i].cantidad  + "</td>" 
                          + "<td class='columnas-editables-selector medida' data-medida_nombre='"+ value[i].medida_nombre +"' data-medida_id='"+ value[i].medida +"' >"  + value[i].medida_nombre + "</td>"
  							          + "<td class='columnas-editables nota'>" + value[i].nota + "</td>" 
                          + "<td><button style='padding: 3px 6px;' type='button' class='btn-edit btn btn-warning dropdown-toggle'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" 
                          + "<td><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td>"
                        + "</tr>";
              }


              tabla_relaciones_ing.append(row);
              orderActual++;
              // console.log($("[name='ingrediente']").html());
           }

           console.log(array_ingredientes);
        }
        // console.log(orderActual);

				//console.log(instruccion[0][0] ? instruccion[0][1].id_receta : 0);

        // alert('entre');

        btn_insertar.click(function(){
          var fieldsValidated = true;
          if(medida[0].value == 11){
            cantidad.attr("validate_min","0");
            fieldsValidated = true;
          }else if(medida[0].value == 3 || medida[0].value == 4){
            cantidad.attr("validate_min","0.001");
            fieldsValidated = true;
          }
          else{
            cantidad.attr("validate_min","0.01");
          }
          var fieldValidated = window.validateField(cantidad);
          
          if(fieldValidated == false) {
            fieldsValidated = false;
          }
          
          if(fieldsValidated) {
            var validoInsertar = false;
            if($("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true'){
              if(ingrediente[0].value && medida[0].value && instruccion[0].value && cantidad.val()){
                validoInsertar = true;
                array_ingredientes.push({
                  ingrediente: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ? ingrediente[0].value : $("[name='ingrediente'] option:selected").attr('receta_value'),
                  cantidad: cantidad.val(),
                  instruccion: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  instruccion[0].value : null,
                  medida: medida[0].value,
                  nota: nota.val(),
                  es_ingrediente: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true',
                  ingrediente_nombre: $("[name='ingrediente'] option:selected").html(),
                  instruccion_nombre: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  ($("[name='instruccion'] option:selected").html() ? $("[name='instruccion'] option:selected").html() : '') : '',
                  medida_nombre: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  $("[name='medida'] option:selected").html() : '',
                  order: orderActual
                });
              }
            }
            else {
              if(ingrediente[0].value && medida[0].value && cantidad.val()){
                validoInsertar = true;
                array_ingredientes.push({
                  ingrediente: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ? ingrediente[0].value : $("[name='ingrediente'] option:selected").attr('receta_value'),
                  cantidad: cantidad.val(),
                  instruccion: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  instruccion[0].value : null,
                  medida: medida[0].value,
                  nota: nota.val(),
                  es_ingrediente: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true',
                  ingrediente_nombre: $("[name='ingrediente'] option:selected").html(),
                  instruccion_nombre: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  ($("[name='instruccion'] option:selected").html() ? $("[name='instruccion'] option:selected").html() : '') : '',
                  medida_nombre: $("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ?  $("[name='medida'] option:selected").html() : '',
                  order: orderActual
                });
              }
            }

            if(validoInsertar){
              // console.log(array_ingredientes);
              $("input[name=array_ingredientes]").val(JSON.stringify(array_ingredientes));
              let row = "";
              if($("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true'){
                row = "<tr id='"+ orderActual +"' data-index='"+ orderActual +"' class='validation_form'>" 
                    + "<td class='index'>" + (orderActual + 1) + "</td>" 
                    + "<td>" + $("[name='ingrediente'] option:selected").html() + "</td>" 
                    + "<td class='columnas-editables-instruccion instruccion' data-ingrediente='" + ingrediente[0].value + "' data-instruccion='"+instruccion[0].value+"'>" + ($("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ? ($("[name='instruccion'] option:selected").html() ? $("[name='instruccion'] option:selected").html() : '') : '') + "</td>" 
                    + "<td class='columnas-editables cantidad'>" + cantidad.val() + "</td>" 
                    + "<td class='columnas-editables-selector medida' data-medida_nombre='"+ $("[name='medida'] option:selected").html() +"' data-medida_id='"+ $("[name='medida'] option:selected").val() +"' >" + $("[name='medida'] option:selected").html() +"</td>" 
                    + "<td class='columnas-editables nota'>" + nota.val() + "</td>" 
                    + "<td><button style='padding: 3px 6px;' type='button' class='btn-edit btn btn-warning dropdown-toggle'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" 
                    + "<td><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td>" 
                  + "</tr>";
              }
              else{
                row = "<tr id='"+ orderActual +"' data-index='"+ orderActual +"' class='validation_form'>" 
                    + "<td class='index'>" + (orderActual + 1) + "</td>" 
                    + "<td>" + $("[name='ingrediente'] option:selected").html() + "</td>" 
                    + "<td>" + ($("[name='ingrediente'] option:selected").attr('es_ingrediente') == 'true' ? ($("[name='instruccion'] option:selected").html() ? $("[name='instruccion'] option:selected").html() : '') : '') + "</td>" 
                    + "<td class='columnas-editables cantidad'>" + cantidad.val() + "</td>" 
                    + "<td class='columnas-editables-selector medida' data-medida_nombre='"+ $("[name='medida'] option:selected").html() +"' data-medida_id='"+ $("[name='medida'] option:selected").val() +"' >" + $("[name='medida'] option:selected").html() +"</td>" 
                    + "<td class='columnas-editables nota'>" + nota.val() + "</td>" 
                    + "<td><button style='padding: 3px 6px;' type='button' class='btn-edit btn btn-warning dropdown-toggle'><i style='font-size: 18px;' class='fa fa-pencil' aria-hidden='true'></i></button></td>" 
                    + "<td><button style='padding: 3px 6px;' type='button' class='btn btn-warning dropdown-toggle btn_trash'><i style='font-size: 18px;' class='fa fa-trash-o' aria-hidden='true'></i></button></td>" 
                  + "</tr>";
              }

              orderActual++;
              tabla_relaciones_ing.append(row);
            }
          }
        });

        $("#tabla_relaciones_ing").on('click', '.btn-edit', function () {
          if($(this).hasClass('modo-edicion')){
            var fieldsValidated = true;
            var medida_check = $(this).parents('.validation_form').find('.medida select').val();
            var cantidad_input = $(this).parents('.validation_form').find('.cantidad input');
            if (medida_check == 3 || medida_check == 4) {
              cantidad_input.attr("validate_min","0.001");
              fieldsValidated = true;
            }else if(medida_check == 11){
              cantidad_input.attr("validate_min","0");
            }
            else{
              cantidad_input.attr("validate_min","0.01");
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
                obj = array_ingredientes.find(x => x.order == order);
              // }

              if(!obj){
                alert('Ocurrió un error y puede que la información no se haya modificado');
              }
              //convertir inputs y selector en td
              $(this).parents('tr').find('td.columnas-editables').each(function() {
                if($(this).hasClass("instruccion")){
                  obj.instruccion = $(this).find('select').val();
                  $(this).html($(this).find('select').val());
                }

                // if($(this).hasClass("sin_conversion")){
                //   obj.instruccion = $(this).val();
                // }

                if($(this).hasClass("cantidad")){
                  obj.cantidad = $(this).find('input').val();
                  $(this).html($(this).find('input').val());
                }

                if($(this).hasClass("nota")){
                  obj.nota = $(this).find('input').val();
                  $(this).html($(this).find('input').val());
                }

                // $(this).find("input").remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original
              
              });
              $(this).parents('tr').find('td.columnas-editables-instruccion').each(function() {
                $(this).find('.modo-edicion').each(function(){

                  instruccion_nombre = $(this).find('option:selected').text();
                  obj.instruccion_nombre = instruccion_nombre;
                  
                  $(this).parent().data('medida_id', instruccion_nombre);

                  instruccion = $(this).find('option:selected').val();
                  obj.instruccion = instruccion; //index + 1 porque inicia en cero
                  $(this).parent().data('instruccion', instruccion);

                  $(this).parent().text($(this).find('option:selected').text());
                  $(this).remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original
                });
              });
              $(this).parents('tr').find('td.columnas-editables-selector').each(function() {
                $(this).find('.modo-edicion').each(function(){
                  let medida_nombre = $(this).find('.medida_nombre');
                  let medida_id = $(this).find('.medida_id');

                  medida_nombre = $(this).find('option:selected').text();
                  obj.medida_nombre = medida_nombre;
                  $(this).parent().data('medida_id', medida_nombre);

                  medida_id = $(this).find('option:selected').val();
                  obj.medida = medida_id; //index + 1 porque inicia en cero
                  $(this).parent().data('medida_id', medida_id);

                  $(this).parent().text($(this).find('option:selected').text());
                  $(this).remove();//remover el input que se generó dentro del td despues de modificar los atributos del td original
                });
              });
              // console.log(array_ingredientes);
              $("input[name=array_ingredientes]").val(JSON.stringify(array_ingredientes));
              //console.log(array_ingredientes);
            }
          }
          else{
            $(this).addClass('modo-edicion');
            $(this).parents('tr').find('td.columnas-editables').each(function() {
              var input = '';
              if($(this).hasClass('cantidad')) {
                input = $('<input class="modo-edicion" type="text" style="width: 100%;" value="'+ $(this).text() +'" validate_required="true" validate_number="true" validate_min="0.1" />');
              } else {
                input = $('<input class="modo-edicion" type="text" style="width: 100%;" value="'+ $(this).text() +'"/>');
              }
              $(this).html(input);
            });
            $(this).parents('tr').find('td.columnas-editables-selector').each(function() {
              var medidas = '';
              var medida_id = $(this).data('medida_id');
              var medida_nombre = $(this).data('medida_nombre');
              var instruccion_id = $(this).parents('tr').find('td.columnas-editables-instruccion').data('instruccion');
              var ingrediente_id = $(this).parents('tr').find('td.columnas-editables-instruccion').data('ingrediente');
              $(this).addClass('medida_ingre'+ingrediente_id+'_'+instruccion_id);
              var medidas = instruccion_change(ingrediente_id, instruccion_id,medida_id);
              // @foreach($medidas as $key => $medida);
              //   if("{{ $key }}" == medida_id){
              //     medidas += '<option value="{{ $key }}" selected> {{ $medida }} </option>';
              //   }
              //   else{
              //     medidas += '<option value="{{ $key }}"> {{ $medida }} </option>';
              //   }
              // @endforeach; 
              // var select = $('<select class="modo-edicion" style="width: 100%;">' +  medidas  +'</select>');
              // $(this).html(select);
            });

            $(this).parents('tr').find('td.columnas-editables-instruccion').each(function() {
              var instruccion_id = $(this).data('instruccion');
              var ingrediente_id = $(this).data('ingrediente');
              // console.log(this);
              // console.log(instruccion_id);
              // console.log(ingrediente_id);
              let esto = $(this);
              // console.log(medida_id, medida_nombre);
              //Ajax call
              $.ajax({
                  url: "/api/ingrediente/instruccion/" + ingrediente_id,
                  type: 'GET',
                  dataType: 'json',
                  success: function(data) {
                      // console.log(data);
                      // console.log(JSON.stringify(data));
                      let instrucciones = "";
                      for(x = 0; x< data.length; x++){
                        if(data[x].id == instruccion_id){
                          instrucciones += '<option value="'+ data[x].id +'" selected> '+ data[x].nombre +' </option>';
                        }
                        else{
                          instrucciones += '<option value="'+ data[x].id +'"> '+ data[x].nombre +' </option>';
                        }
                      }
                      var select = $('<select class="modo-edicion" style="width: 100%;">' +  instrucciones  +'</select>');
                      esto.html(select);
                  },
                  error: function(jqXHR, textStatus, errorThrown){
                      alert('Error: ' + textStatus + ' - ' + errorThrown);
                  }
              });

              $('.instruccion').on('change', function(){
                instruccion_change(ingrediente_id, instruccion_id);
              });
          
              
            });
          }
          function instruccion_change (ingrediente_id, instruccion_id,medida_id ='') {
            var medidas='';
              $.ajax({
                    url:'/admin/Recetas/MedidasPorInstruccion',
                    type: 'POST',
                    data: { ingrediente: ingrediente_id, instruccion: instruccion_id },
                    success: function(result){
                      if(result){
                       /// console.log(result);
                        $.each(result, function (i, item) {
                       // console.log(item);
                          if(medida_id && item.id == medida_id){
                            medidas += "<option value='" + result[i].id + "' selected> " + result[i].nombre + "</option>";
                          }
                          else{
                            medidas += "<option value='" + result[i].id + "'> " + result[i].nombre + "</option>";
                          }
                        });
                        var select = $('<select class="modo-edicion" style="width: 100%;">' +  medidas  +'</select>');
                        $('.medida_ingre'+ingrediente_id+'_'+instruccion_id+'').html(select);
                      }
                    },
                    error: function(result){
                      console.log('Error al encontrar una medida: ' + result);
                    }
                });
            }
          // console.log('array_ingredientes', array_ingredientes);
        });

        var eliminarRows;
        $("#tabla_relaciones_ing").on( "click", ".btn_trash", function(e) {
           e.preventDefault();
          //  console.log($( this ).parents( "tr" ), $( this ).parents( "tr" ).index());
           array_ingredientes.splice($( this ).parents( "tr" ).index(), 1);

           eliminarRows = $(this).parents("tr");

           eliminarRows.remove();
           // $( this ).parents( "tr" ).next().remove();

           $("input[name=array_ingredientes]").val(JSON.stringify(array_ingredientes));

          //  console.log('array_ingredientes', array_ingredientes);
        });
    });
</script>
@endpush

{{-- End of Extra CSS and JS --}}
