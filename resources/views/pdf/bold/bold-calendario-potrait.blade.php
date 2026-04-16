<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
    @page { margin: 35px 0 50px 0; }
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    }
    body { font-size: 12px; }
    footer {
      position: fixed;
      bottom: 40px;
      left: 1cm;
      right: 0;
      height: 25px;
      width: 100%;
    }
    .day-block {
      width: 100%;
      margin-bottom: 30px;
    }
    .day-title {
      color: {{ auth()->user()->color }};
      font-size: 16px;
      font-weight: 700;
      margin: 0 0 8px;
      text-transform: uppercase;
    }
    .recipe-outer {
      display: table;
      width: 100%;
      table-layout: auto;
      page-break-inside: avoid;
      margin-top: 1px;
      margin-bottom: 2px;
    }
    .recipe-img {
      display: table-cell;
      width: 30px;
      vertical-align: top;
    }
    .recipe-img img {
      width: 30px;
      height: 30px;
      object-fit: cover;
      display: block;
    }
    .recipe-right {
      display: table-cell;
      vertical-align: top;
      padding-left: 2px;
    }
    .recipe-right h4 {
      margin: 0;
      color: {{ auth()->user()->color }};
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      line-height: 1;
    }
    .recipe-right p {
      margin: 0;
      color: #000;
      font-size: 8px;
      line-height: 1.05;
      text-transform: uppercase;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
  </style>
</head>
<body>
  <footer>
    <table width="100%" valign="top">
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
    $dayChunks = array_chunk(array_keys($cLabels['days'] ?? []), 3);
    $mealLabels = $cLabels['meals'] ?? [];
    $recipesMap = collect($cRecipes ?? $recipes_list ?? [])->keyBy('id');
    $recipeImages = $recipe_images ?? [];
    $placeholderImage = $placeholderImage ?? public_path('img/recetas/imagen-receta-principal.jpg');
    $placeholderImageSrc = $placeholderImageSrc ?? $placeholderImage;
  @endphp

  <div style="width: 90%; margin: 0 auto;">
    @foreach($dayChunks as $chunk)
      <table width="100%" style="table-layout: fixed; margin-bottom: 12px;">
        <tr valign="top">
          @foreach($chunk as $dayKey)
            <td width="{{ 100 / max(count($chunk), 1) }}%" valign="top" style="padding-right: 8px;">
              <div class="day-block">
                <div class="day-title">{{ $cLabels['days'][$dayKey] ?? $dayKey }}</div>
                @foreach($mealLabels as $mealKey => $mealLabel)
                  @php
                    $mainId = $cMains[$dayKey][$mealKey] ?? null;
                    $sideId = $cSides[$dayKey][$mealKey] ?? null;
                    $mainRecipe = $mainId ? ($recipesMap[$mainId] ?? null) : null;
                    $sideRecipe = $sideId ? ($recipesMap[$sideId] ?? null) : null;
                    $recipeImageId = $mainRecipe->id ?? ($sideRecipe->id ?? null);
                    $mainRacion = $cMracion[$dayKey][$mealKey] ?? 1;
                    $sideRacion = $cSracion[$dayKey][$mealKey] ?? 1;
                  @endphp
                  @if($mainRecipe || $sideRecipe)
                    <div class="recipe-outer">
                      <div class="recipe-img">
                        <img src="{{ $recipeImages[$recipeImageId] ?? $placeholderImageSrc }}" alt="Recipe image">
                      </div>
                      <div class="recipe-right">
                        <h4>{{ $mealLabel }}</h4>
                        @if($mainRecipe)
                          <p><strong>{{ $mainRacion == 1 ? '' : $mainRacion }}</strong> {{ $mainRecipe->titulo }}
                            @if($sideRecipe)
                              , <strong>{{ $sideRacion == 1 ? '' : $sideRacion }}</strong> {{ $sideRecipe->titulo }}
                            @endif
                          </p>
                        @elseif($sideRecipe)
                          <p><strong>{{ $sideRacion == 1 ? '' : $sideRacion }}</strong> {{ $sideRecipe->titulo }}</p>
                        @endif
                      </div>
                    </div>
                  @endif
                @endforeach
              </div>
            </td>
          @endforeach
        </tr>
      </table>
    @endforeach
  </div>
</body>
</html>
