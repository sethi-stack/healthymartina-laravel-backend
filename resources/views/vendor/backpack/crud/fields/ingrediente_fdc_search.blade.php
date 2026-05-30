@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
  <label class="form-label mb-2">FDC API (buscar por nombre)</label>
  <div class="d-flex gap-2 align-items-start">
    <select id="fdc-food-search" class="form-control" style="width: 100%"></select>
    <button type="button" id="fdc-apply" class="btn btn-primary" style="min-width: 120px;">Usar ID</button>
  </div>
  <small class="text-muted d-block mt-2">Selecciona un alimento del buscador para copiar su FDC ID al campo "FDC API".</small>
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
  jQuery(function($) {
    var $select = $('#fdc-food-search');
    var $usdaInput = $('input[name="usda"]').first();

    $select.select2({
      theme: "bootstrap",
      placeholder: "Busca por nombre en FDC…",
      minimumInputLength: 2,
      ajax: {
        url: {!! json_encode(url(config('backpack.base.route_prefix', 'admin').'/getFDCData')) !!},
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          if (data && data.error) {
            console.warn('FDC search error:', data.error);
          }
          return { results: data.results || [] };
        },
        cache: true
      }
    });

    $('#fdc-apply').on('click', function(e) {
      e.preventDefault();
      var selected = $select.select2('data')[0];
      if (!selected || !selected.id) return;
      $usdaInput.val(selected.id).trigger('change');
    });
  });
</script>
@endpush
