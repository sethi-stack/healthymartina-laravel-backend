@php
  $medidas = $field['options_medidas'] ?? [];
@endphp

@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
  <p class="text-muted">
    Si el ingrediente no tiene una instrucción o se requiere una instrucción vacía, colocar <code>NA</code>, para que no se visualice en el sitio web.
  </p>

  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Nota de Preparación</label>
      <input type="text" class="form-control" name="nota_preparacion_tmp" />
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="sin_conversion_tmp" />
        <label class="form-check-label" for="sin_conversion_tmp">Esta nota de preparación no tiene una conversión</label>
      </div>
    </div>
    <div class="col-md-2">
      <label class="form-label">Cantidad</label>
      <input type="number" step="0.0001" class="form-control" name="cantidad_tmp" />
    </div>
    <div class="col-md-2">
      <label class="form-label">Medida</label>
      <select class="form-control select2_from_array" name="medida_tmp">
        <option value="">-</option>
        @foreach($medidas as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Equivalencia en tipo de medida</label>
      <div class="d-flex gap-2">
        <input type="number" step="0.0001" class="form-control" name="equivalencia_gramos_tmp" placeholder="" />
        <button type="button" class="btn btn-light" id="btn_add_instruccion" style="min-width: 48px;">
          <i class="la la-plus"></i>
        </button>
      </div>
    </div>
  </div>

  <table class="table table-hover mt-3" id="tabla_instrucciones">
    <thead>
      <tr>
        <th style="width:60px;">#</th>
        <th>Nota de Preparación</th>
        <th style="width:110px;">Cantidad</th>
        <th style="width:180px;">Medida</th>
        <th>Equivalencia en tipo de medida</th>
        <th style="width:110px;"></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
  jQuery(function($) {
    var $inputArray = $("input[name='array_instrucciones']");
    var instrucciones = [];
    var idx = 0;

    function sync() {
      $inputArray.val(JSON.stringify(instrucciones));
    }

    function rowHtml(i, row) {
      return "<tr data-index='"+i+"'>"
        + "<td>"+(i+1)+"</td>"
        + "<td class='nota'>"+(row.nota_preparacion || "")+"</td>"
        + "<td class='cantidad'>"+(row.cantidad ?? "")+"</td>"
        + "<td class='medida' data-medida_id='"+(row.medida_id || "")+"'>"+(row.medida_nombre || "")+"</td>"
        + "<td class='equivalencia_gramos'>"+(row.equivalencia_gramos ?? "")+"</td>"
        + "<td class='text-end'>"
        +   "<button type='button' class='btn btn-warning btn-sm btn_edit'><i class='la la-pencil'></i></button> "
        +   "<button type='button' class='btn btn-warning btn-sm btn_delete'><i class='la la-trash'></i></button>"
        + "</td>"
        + "</tr>";
    }

    function renderAll() {
      var $tbody = $("#tabla_instrucciones tbody");
      $tbody.empty();
      instrucciones.forEach(function(r, i) {
        $tbody.append(rowHtml(i, r));
      });
    }

    // load existing
    if ($inputArray.val()) {
      try {
        instrucciones = JSON.parse($inputArray.val()) || [];
        idx = instrucciones.length;
        renderAll();
      } catch (e) {}
    }

    $("#btn_add_instruccion").on('click', function(e) {
      e.preventDefault();
      var nota = $("input[name='nota_preparacion_tmp']").val();
      var sinConv = $("#sin_conversion_tmp").is(':checked') ? 1 : 0;
      var cantidad = $("input[name='cantidad_tmp']").val();
      var medidaId = $("select[name='medida_tmp']").val();
      var medidaNombre = $("select[name='medida_tmp'] option:selected").text();
      var equivGramos = $("input[name='equivalencia_gramos_tmp']").val();

      instrucciones.push({
        nota_preparacion: nota,
        sin_conversion: sinConv,
        cantidad: cantidad,
        medida_id: medidaId,
        medida_nombre: (medidaId ? medidaNombre : ''),
        equivalencia_gramos: (sinConv ? null : equivGramos)
      });
      idx++;
      sync();
      renderAll();

      $("input[name='nota_preparacion_tmp']").val('');
      $("#sin_conversion_tmp").prop('checked', false);
      $("input[name='cantidad_tmp']").val('');
      $("select[name='medida_tmp']").val('').trigger('change');
      $("input[name='equivalencia_gramos_tmp']").val('');
    });

    $("#tabla_instrucciones").on('click', '.btn_delete', function() {
      var i = Number($(this).closest('tr').data('index'));
      instrucciones.splice(i, 1);
      sync();
      renderAll();
    });
  });
</script>
@endpush
