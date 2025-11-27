{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

{{-- Main Menu Items --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('Recetas') }}"><i class="la la-book nav-icon"></i> <span>Recetas</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('Clientes') }}"><i class="la la-user nav-icon"></i> <span>Clientes</span></a></li>

{{-- Catálogos Dropdown --}}
<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="la la-book nav-icon"></i> Catálogos</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('Nutrientes') }}"><i class="la la-book nav-icon"></i> <span>Nutrientes</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('nutrienttype') }}"><i class="la la-tag nav-icon"></i> <span>Tipo de Nutriente</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('Medidas') }}"><i class="la la-book nav-icon"></i> <span>Medida</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('TiposMedida') }}"><i class="la la-book nav-icon"></i> <span>Tipos de medidas</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('Ingredientes') }}"><i class="la la-book nav-icon"></i> <span>Ingredientes</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('Tags') }}"><i class="la la-book nav-icon"></i> <span>Tags</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('Categorias') }}"><i class="la la-book nav-icon"></i> <span>Categorías</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('FormasCompra') }}"><i class="la la-book nav-icon"></i> <span>Formas de compra</span></a></li>
    </ul>
</li>

{{-- Other Menu Items --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('plan') }}"><i class="la la-tag nav-icon"></i><span>Planes</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('terms-conditions') }}"><i class="la la-file-text-o nav-icon"></i><span>Términos y condiciones</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('templates') }}"><i class="la la-file-text-o nav-icon"></i><span>Plantilla</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('privacy-notice') }}"><i class="la la-file-text-o nav-icon"></i><span>Aviso de privacidad</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('equivalence') }}"><i class="la la-file-text-o nav-icon"></i><span>Equivalencias</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('comment') }}"><i class="la la-file-text-o nav-icon"></i><span>Comentarios</span></a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('youtube-channel') }}"><i class="la la-tag nav-icon"></i> <span>Canal de YT</span></a></li>
