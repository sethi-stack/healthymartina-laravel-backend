@php
  $options = $field['options'] ?? [];
@endphp

@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_start')
  <label class="form-label mb-2">{!! $field['label'] ?? 'Buscador de ingredientes' !!}</label>
  <div class="d-flex gap-2">
    <select id="ingrediente-searcher" class="form-control select2_from_array" style="width: 100%">
      <option value="">Busca un ingrediente</option>
      @foreach($options as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
      @endforeach
    </select>
    <a id="ingrediente-searcher-view" class="btn btn-light" target="_blank" title="Ver ingrediente" style="min-width: 48px; display:flex; align-items:center; justify-content:center;">
      <i class="la la-eye"></i>
    </a>
  </div>
@includeWhen(!isset($field['wrapper']) || $field['wrapper'] !== false, 'crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
  jQuery(function($) {
    var $select = $('#ingrediente-searcher');
    if (!$select.hasClass('select2-hidden-accessible')) {
      $select.select2({ theme: 'bootstrap' });
    }

    function updateLink() {
      var id = $select.val();
      var $link = $('#ingrediente-searcher-view');
      if (!id) {
        $link.attr('href', 'javascript:;').addClass('disabled');
        return;
      }
      $link.removeClass('disabled').attr('href', {!! json_encode(url(config('backpack.base.route_prefix', 'admin').'/Ingredientes')) !!} + '/' + id + '/edit');
    }

    $select.on('change', updateLink);
    updateLink();
  });
</script>
@endpush

