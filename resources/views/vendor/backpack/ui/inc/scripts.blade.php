@basset('https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js')
@basset('https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js')
@basset('https://cdn.jsdelivr.net/npm/noty@3.2.0-beta-deprecated/lib/noty.min.js')
@basset('https://cdn.jsdelivr.net/npm/sweetalert@2.1.2/dist/sweetalert.min.js')

{{-- CSRF Token Setup for AJAX --}}
<script>
// Ajax calls should always have the CSRF token attached to them, otherwise they won't work
if (typeof $ !== 'undefined') {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}
</script>

@if (backpack_theme_config('scripts') && count(backpack_theme_config('scripts')))
    @foreach (backpack_theme_config('scripts') as $path)
        @if(is_array($path))
            @basset(...$path)
        @else
            @basset($path)
        @endif
    @endforeach
@endif

@if (backpack_theme_config('mix_scripts') && count(backpack_theme_config('mix_scripts')))
    @foreach (backpack_theme_config('mix_scripts') as $path => $manifest)
        <script type="text/javascript" src="{{ mix($path, $manifest) }}"></script>
    @endforeach
@endif

@if (backpack_theme_config('vite_scripts') && count(backpack_theme_config('vite_scripts')))
    @vite(backpack_theme_config('vite_scripts'))
@endif

<script>
// Project-level helper used by a few custom Backpack field views (attribute-driven validation).
// Guarded so it won't crash if included twice.
window.validateField = window.validateField || function ($field) {
    if (typeof jQuery === 'undefined') return true;
    var $el = ($field && $field.jquery) ? $field : jQuery($field);
    if (!$el || !$el.length) return true;

    var value = ($el.val() ?? '').toString().trim();
    var isRequired = $el.attr('validate_required') === 'true';
    var mustBeNumber = $el.attr('validate_number') === 'true';
    var minAttr = $el.attr('validate_min');
    var maxAttr = $el.attr('validate_max');
    var minValue = (minAttr !== undefined && minAttr !== null && minAttr !== '') ? parseFloat(minAttr) : null;
    var maxValue = (maxAttr !== undefined && maxAttr !== null && maxAttr !== '') ? parseFloat(maxAttr) : null;

    var valid = true;

    if (isRequired && value === '') valid = false;
    if (valid && mustBeNumber) {
        var numberValue = parseFloat(value);
        if (Number.isNaN(numberValue)) valid = false;
        if (valid && minValue !== null && numberValue < minValue) valid = false;
        if (valid && maxValue !== null && numberValue > maxValue) valid = false;
    }

    // Bootstrap validation styling (Backstrap/CoreUIv2 compatible)
    $el.toggleClass('is-invalid', !valid);
    $el.toggleClass('is-valid', valid);

    return valid;
};
</script>
