<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
    @page { margin: 35px 40px 50px 40px; }
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
      font-size: 12px;
    }
    body {
      color: #606060;
      font-size: 12px;
    }
    .container {
      width: 90%;
      margin: 0 auto;
    }
    .column {
      width: 33%;
      padding: 5px;
      vertical-align: top;
    }
    .title {
      width: 100%;
      padding: 0 0 5px;
      color: {{ auth()->user()->color }};
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .ingredient {
      width: 100%;
      display: block;
      margin: 0 0 5px;
      word-wrap: break-word;
    }
    .ingredient h3 {
      display: inline-block;
      width: 25%;
      margin: 0;
      padding: 0 0 0 5px;
      font-size: 9px;
      vertical-align: middle;
    }
    .ingredient span {
      display: inline-block;
      padding-left: 5px;
      font-size: 9px;
      width: 130px;
    }
    footer {
      position: fixed;
      bottom: 0;
      left: 1cm;
      right: -5cm;
      height: 35px;
      width: 100%;
      padding-top: 50px;
    }
  </style>
</head>
<body>
  <footer style="bottom: -10px">
    <table width="100%">
      <tr>
        <td width="25%"><img width="25%" src="{{ auth()->user()->bimage }}" /></td>
        <td width="25%"></td>
        <td width="50%" style="background: {{ auth()->user()->color }}3f; padding: 20px 15px; font-size:10px;">
          <strong style="color: {{ auth()->user()->color }}">{{ auth()->user()->bname }}</strong>
          <a style="color: #000; text-decoration: none;" href="mailto:{{ auth()->user()->bemail }}">{{ auth()->user()->bemail }}</a>
        </td>
      </tr>
    </table>
  </footer>

  @php
    $categories = $categorias ?? [];
    $categoryChunks = array_chunk($categories, 3);
    $recipeIngredients = $recipe_ingredients ?? new stdClass();
  @endphp

  <div style="background: {{ auth()->user()->color }}3f; display:inline-block;width:100%; height: 100px;margin-top:-1cm"></div>
  <h2 style="font-size:16px;text-align:center;color: {{ auth()->user()->color }}">{{ $calendario->title }}</h2>
  <strong style="text-align:center; width: 100%; display: block">{{ auth()->user()->bname }}</strong>
  <h2 style="width: 90%; display: block; margin: 10px auto 10px;color: {{ auth()->user()->color }}"><span id="count" style="margin: 10px 0 0; display: inline-block;color: {{ auth()->user()->color }}">{{ $ingredients_count }} ingredientes</span></h2>

  @foreach($categoryChunks as $chunk)
    <table class="container">
      <tr>
        @foreach($chunk as $category)
          @php
            $ingredientResponse = getRelatedIngrediente($calendario->id, $category->id, 'pdf');
          @endphp
          <td class="column">
            <h2 class="title" style="font-family: 'Helvetica' !important;color: {{ auth()->user()->color }};">{{ ucwords($category->nombre) }}</h2>
            <div class="list">
              @foreach($ingredientResponse as $ingrediente)
                @php
                  $checkTaken = check_taken_ingredientes($category->id, $ingrediente['ingrediente_id'], 'receta', $calendario->id, 'pdf');
                @endphp
                @if($checkTaken == true)
                  <label class="ingredient">
                    <div style="font-family: DejaVu Sans, sans-serif;display:inline-block;color: {{ auth()->user()->color }}">▢</div>
                    <h3>
                      @if($ingrediente['tipo_medida_id'] == 4)
                        al gusto
                      @else
                        @if(property_exists($recipeIngredients, $ingrediente['ingred_uid']))
                          <?php $ingred_uid = $ingrediente['ingred_uid']; ?>
                          {!! $recipeIngredients->$ingred_uid !!}
                        @else
                          {{ is_float($ingrediente['cantidad']) ? decToFraction($ingrediente['cantidad']) : $ingrediente['cantidad'] }}
                          {{ $ingrediente['cantidad'] > 1 ? mb_strtolower($ingrediente['medida_plural']) : mb_strtolower($ingrediente['medida']) }}
                        @endif
                      @endif
                    </h3>
                    <span>{{ $ingrediente['ingrediente'] }}</span>
                  </label>
                @endif
              @endforeach
              @if(!empty($lista_ingredientes))
                @foreach($lista_ingredientes as $lista_ingrediente)
                  @if($lista_ingrediente->categoria == $category->id && check_taken_ingredientes($category->id, $lista_ingrediente->id, 'lista', $calendario->id, 'pdf') == true)
                    <label class="ingredient">
                      <div style="font-family: DejaVu Sans, sans-serif;display:inline-block;color: {{ auth()->user()->color }}">▢</div>
                      <h3>{{ $lista_ingrediente->cantidad }} {{ $lista_ingrediente->unidad_medida }}</h3>
                      <span>{{ $lista_ingrediente->nombre }}</span>
                    </label>
                  @endif
                @endforeach
              @endif
            </div>
          </td>
        @endforeach
      </tr>
    </table>
  @endforeach
</body>
</html>
