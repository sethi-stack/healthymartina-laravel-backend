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
        <th style="width:140px;">Sin conversion</th>
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
    var enableFdcSinConversionAutofill = false;
    var legacySinConversionDefaults = {
      cantidad: '1',
      medida_id: '7',
      equivalencia_gramos: '1'
    };
    var $inputArray = $("input[name='array_instrucciones']");
    var $notaInput = $("input[name='nota_preparacion_tmp']");
    var $sinConversionInput = $("#sin_conversion_tmp");
    var $cantidadInput = $("input[name='cantidad_tmp']");
    var $medidaInput = $("select[name='medida_tmp']");
    var $equivalenciaInput = $("input[name='equivalencia_gramos_tmp']");
    var $submitButton = $("#btn_add_instruccion");
    var $fdcRawInput = $("input[name='fdc_raw']").first();
    var instrucciones = [];
    var currentFdcDefaults = null;

    function sync() {
      $inputArray.val(JSON.stringify(instrucciones));
    }

    function getSinConversionText(value) {
      return Number(value) === 1 ? 'Si' : 'No';
    }

    function normalizeNumber(value) {
      return value === null || value === undefined || value === '' ? '' : value;
    }

    function normalizeUnitLabel(value) {
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '');
    }

    function resolveEquivalentWeight(quantity, unit) {
      var normalizedUnit = normalizeUnitLabel(unit);
      if (['g', 'gr', 'gm', 'gram', 'grams', 'gramo', 'gramos', 'ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'].includes(normalizedUnit)) {
        return quantity;
      }
      return null;
    }

    function findMatchingMedida(unit) {
      var normalizedUnit = normalizeUnitLabel(unit);
      if (!normalizedUnit) return { id: '', name: '' };

      var aliases = [
        [normalizedUnit],
        ['g', 'gr', 'gm', 'gram', 'grams', 'gramo', 'gramos'],
        ['ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'],
        ['kg', 'kilo', 'kilos', 'kilogram', 'kilograms', 'kilogramo', 'kilogramos'],
        ['oz', 'ounce', 'ounces', 'onza', 'onzas'],
        ['lb', 'lbs', 'pound', 'pounds', 'libra', 'libras'],
        ['tz', 'cup', 'cups', 'taza', 'tazas'],
        ['cda', 'cdas', 'tbsp', 'tablespoon', 'tablespoons', 'cucharada', 'cucharadas'],
        ['cdta', 'cdtas', 'tsp', 'teaspoon', 'teaspoons', 'cucharadita', 'cucharaditas'],
        ['pieza', 'piezas', 'unidad', 'unidades', 'piece', 'pieces', 'unit', 'units']
      ];

      var wantedAliases = aliases.find(function(group) {
        return group.includes(normalizedUnit);
      }) || aliases[0];

      var match = null;
      $medidaInput.find('option').each(function() {
        var optionValue = $(this).attr('value');
        var optionText = $(this).text().trim();
        if (!optionValue) return;
        if (wantedAliases.includes(normalizeUnitLabel(optionText))) {
          match = { id: optionValue, name: optionText };
          return false;
        }
      });

      return match || { id: '', name: '' };
    }

    function deriveFdcInstructionDefaults(food) {
      if (!food) return null;

      var quantity = null;
      var unit = '';
      var equivalence = null;

      if (food.servingSize && Number(food.servingSize) > 0 && food.servingSizeUnit) {
        quantity = Number(food.servingSize);
        unit = String(food.servingSizeUnit);
        equivalence = resolveEquivalentWeight(quantity, unit);
      }

      if ((!quantity || !unit) && Array.isArray(food.foodPortions)) {
        for (var i = 0; i < food.foodPortions.length; i++) {
          var portion = food.foodPortions[i] || {};
          var amount = Number(portion.amount || 0);
          var portionMeasureUnit = portion.measureUnit || {};
          var portionUnit = portionMeasureUnit.abbreviation || portionMeasureUnit.name || '';
          var gramWeight = Number(portion.gramWeight || 0);

          if (amount > 0 && portionUnit) {
            quantity = amount;
            unit = String(portionUnit);
            equivalence = gramWeight > 0 ? gramWeight : null;
            break;
          }

          if (!quantity && gramWeight > 0) {
            quantity = gramWeight;
            unit = 'g';
            equivalence = gramWeight;
            break;
          }
        }
      }

      if (!quantity) return null;

      if (!unit) unit = 'g';
      if (equivalence === null) {
        equivalence = resolveEquivalentWeight(quantity, unit);
      }

      var medida = findMatchingMedida(unit);

      return {
        nota_preparacion: 'NA',
        cantidad: quantity,
        medida_id: medida.id,
        medida_nombre: medida.name || unit,
        equivalencia_gramos: equivalence
      };
    }

    function applySinConversionUi() {
      var isChecked = $sinConversionInput.is(':checked');
      var useFdcDefaults = enableFdcSinConversionAutofill && isChecked && currentFdcDefaults;
      var useLegacyDefaults = isChecked && !useFdcDefaults;

      $cantidadInput.prop('disabled', isChecked);
      $medidaInput.prop('disabled', isChecked);
      $equivalenciaInput.prop('disabled', isChecked);
      $notaInput.prop('disabled', Boolean(useFdcDefaults));

      if (useFdcDefaults) {
        $notaInput.val(currentFdcDefaults.nota_preparacion || 'NA');
        $cantidadInput.val(normalizeNumber(currentFdcDefaults.cantidad));
        $medidaInput.val(currentFdcDefaults.medida_id || '').trigger('change');
        $equivalenciaInput.val(normalizeNumber(currentFdcDefaults.equivalencia_gramos));
      } else if (useLegacyDefaults) {
        $notaInput.prop('disabled', false);
        if (!$notaInput.val()) {
          $notaInput.val('NA');
        }
        $cantidadInput.val(legacySinConversionDefaults.cantidad);
        $medidaInput.val(legacySinConversionDefaults.medida_id).trigger('change');
        $equivalenciaInput.val(legacySinConversionDefaults.equivalencia_gramos);
      } else {
        if (!isChecked) {
          $notaInput.prop('disabled', false);
          $equivalenciaInput.prop('disabled', false);
        }
      }
    }

    function clearForm() {
      $notaInput.val('');
      $sinConversionInput.prop('checked', false);
      $cantidadInput.val('');
      $medidaInput.val('').trigger('change');
      $equivalenciaInput.val('').prop('disabled', false);
      $notaInput.prop('disabled', false);
      $submitButton.html('<i class="la la-plus"></i>');
    }

    function rowHtml(i, row) {
      return "<tr data-index='"+i+"'>"
        + "<td>"+(i+1)+"</td>"
        + "<td class='columnas-editables nota'>"+(row.nota_preparacion || "")+"</td>"
        + "<td class='columnas-editables cantidad'>"+(row.cantidad ?? "")+"</td>"
        + "<td class='columnas-editables-selector medida' data-medida_id='"+(row.medida_id || "")+"'>"+(row.medida_nombre || "")+"</td>"
        + "<td class='columnas-editables-sino sin_conversion' data-sin_conversion='"+(row.sin_conversion || 0)+"'>"+getSinConversionText(row.sin_conversion || 0)+"</td>"
        + "<td class='columnas-editables equivalencia_gramos'>"+(row.equivalencia_gramos ?? "")+"</td>"
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
        renderAll();
      } catch (e) {}
    }

    $sinConversionInput.on('change', function() {
      applySinConversionUi();
    });

    $submitButton.on('click', function(e) {
      e.preventDefault();
      var useFdcDefaults = enableFdcSinConversionAutofill && $sinConversionInput.is(':checked') && currentFdcDefaults;
      var nota = useFdcDefaults ? (currentFdcDefaults.nota_preparacion || 'NA') : $notaInput.val();
      var sinConv = $sinConversionInput.is(':checked') ? 1 : 0;
      var cantidad = sinConv ? legacySinConversionDefaults.cantidad : (useFdcDefaults ? currentFdcDefaults.cantidad : $cantidadInput.val());
      var medidaId = sinConv ? legacySinConversionDefaults.medida_id : (useFdcDefaults ? currentFdcDefaults.medida_id : $medidaInput.val());
      var medidaNombre = sinConv
        ? ($medidaInput.find("option[value='" + legacySinConversionDefaults.medida_id + "']").text() || '')
        : (useFdcDefaults ? currentFdcDefaults.medida_nombre : $medidaInput.find('option:selected').text());
      var equivGramos = sinConv ? legacySinConversionDefaults.equivalencia_gramos : (useFdcDefaults ? currentFdcDefaults.equivalencia_gramos : $equivalenciaInput.val());
      var row = {
        nota_preparacion: nota || (sinConv ? 'NA' : ''),
        sin_conversion: sinConv,
        cantidad: cantidad,
        medida_id: medidaId,
        medida_nombre: (medidaId ? medidaNombre : (medidaNombre || '')),
        equivalencia_gramos: equivGramos
      };

      instrucciones.push(row);
      sync();
      renderAll();
      clearForm();
    });

    $(document).on('hm:fdc-food-selected', function(_, food) {
      currentFdcDefaults = deriveFdcInstructionDefaults(food);
      applySinConversionUi();
    });

    if ($fdcRawInput.length && $fdcRawInput.val()) {
      try {
        currentFdcDefaults = deriveFdcInstructionDefaults(JSON.parse($fdcRawInput.val()));
      } catch (e) {
        currentFdcDefaults = null;
      }
    }

    applySinConversionUi();

    $("#tabla_instrucciones").on('click', '.btn_edit', function() {
      var $button = $(this);
      var $row = $button.closest('tr');
      var rowIndex = Number($row.data('index'));

      if ($button.hasClass('modo-edicion')) {
        var nota = $row.find('.nota input').val();
        var sinConversion = Number($row.find('.sin_conversion select').val() || 0);
        var cantidad = sinConversion === 1 ? legacySinConversionDefaults.cantidad : $row.find('.cantidad input').val();
        var medidaId = sinConversion === 1 ? legacySinConversionDefaults.medida_id : $row.find('.medida select').val();
        var medidaNombre = sinConversion === 1
          ? ($row.find(".medida select option[value='" + legacySinConversionDefaults.medida_id + "']").text() || '')
          : $row.find('.medida select option:selected').text();
        var equivalenciaGramos = sinConversion === 1 ? legacySinConversionDefaults.equivalencia_gramos : $row.find('.equivalencia_gramos input').val();

        instrucciones[rowIndex] = {
          nota_preparacion: nota || (sinConversion === 1 ? 'NA' : ''),
          cantidad: cantidad,
          medida_id: medidaId,
          medida_nombre: medidaId ? medidaNombre : '',
          sin_conversion: sinConversion,
          equivalencia_gramos: equivalenciaGramos
        };

        sync();
        renderAll();
        return;
      }

      $button.addClass('modo-edicion');

      var current = instrucciones[rowIndex];
      if (!current) {
        return;
      }

      $row.find('td.columnas-editables').each(function() {
        var $cell = $(this);

        if ($cell.hasClass('cantidad') || $cell.hasClass('equivalencia_gramos')) {
          var value = $cell.hasClass('cantidad') ? normalizeNumber(current.cantidad) : normalizeNumber(current.equivalencia_gramos);
          $cell.html('<input class="modo-edicion form-control" type="number" step="0.0001" value="'+ value +'" style="width: 100%;" />');
          return;
        }

        if ($cell.hasClass('nota')) {
          $cell.html('<input class="modo-edicion form-control" type="text" value="'+ $('<div>').text(current.nota_preparacion || '').html() +'" style="width: 100%;" />');
        }
      });

      var medidas = '<option value="">-</option>';
      @foreach($medidas as $id => $name)
        medidas += '<option value="{{ $id }}">{{ $name }}</option>';
      @endforeach

      $row.find('.medida').html('<select class="modo-edicion form-control" style="width: 100%;">' + medidas + '</select>');
      $row.find('.medida select').val(current.medida_id || '');

      var sinConversionOptions = ''
        + '<option value="1">Si</option>'
        + '<option value="0">No</option>';
      $row.find('.sin_conversion').html('<select class="modo-edicion form-control" style="width: 100%;">' + sinConversionOptions + '</select>');
      $row.find('.sin_conversion select').val(String(Number(current.sin_conversion || 0)));

      if (Number(current.sin_conversion || 0) === 1) {
        $row.find('.cantidad input').prop('disabled', true).val(legacySinConversionDefaults.cantidad);
        $row.find('.medida select').prop('disabled', true).val(legacySinConversionDefaults.medida_id);
        $row.find('.equivalencia_gramos input').prop('disabled', true).val(legacySinConversionDefaults.equivalencia_gramos);
      }
    });

    $("#tabla_instrucciones").on('click', '.btn_delete', function() {
      var i = Number($(this).closest('tr').data('index'));
      instrucciones.splice(i, 1);
      sync();
      renderAll();
    });

    $("#tabla_instrucciones").on('change', '.sin_conversion select', function() {
      var disabled = Number($(this).val() || 0) === 1;
      var $row = $(this).closest('tr');
      var $cantidadInputRow = $row.find('.cantidad input');
      var $medidaInputRow = $row.find('.medida select');
      var $equivalenciaInputRow = $row.find('.equivalencia_gramos input');
      $cantidadInputRow.prop('disabled', disabled);
      $medidaInputRow.prop('disabled', disabled);
      $equivalenciaInputRow.prop('disabled', disabled);
      if (disabled) {
        $cantidadInputRow.val(legacySinConversionDefaults.cantidad);
        $medidaInputRow.val(legacySinConversionDefaults.medida_id);
        $equivalenciaInputRow.val(legacySinConversionDefaults.equivalencia_gramos);
      }
    });
  });
</script>
@endpush
