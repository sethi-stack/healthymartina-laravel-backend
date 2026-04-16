<!DOCTYPE html>
<html lang="es">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{{ $recipe->titulo }}</title>
  @php
    $template = $template ?? 'classic';
    $themeConfig = [
      'classic' => [
        'accent' => '#dcb244',
        'accentSoft' => '#f8ebc5',
        'bg' => '#fffdf8',
        'text' => '#222222',
        'muted' => '#777777',
      ],
      'modern' => [
        'accent' => '#5f8ea6',
        'accentSoft' => '#dfeaf0',
        'bg' => '#f7fbfd',
        'text' => '#1f2b33',
        'muted' => '#66757f',
      ],
      'bold' => [
        'accent' => '#111111',
        'accentSoft' => '#ececec',
        'bg' => '#ffffff',
        'text' => '#111111',
        'muted' => '#555555',
      ],
    ];
    $themeData = $themeConfig[$template] ?? $themeConfig['classic'];
    $placeholderImage = $placeholderImage ?? public_path('img/recetas/imagen-receta-principal.jpg');

    $normalizeImage = function ($src) use ($placeholderImage) {
      if (empty($src)) {
        return $placeholderImage;
      }
      if (preg_match('/^https?:\/\//i', $src)) {
        return $src;
      }
      $local = ltrim($src, '/');
      $candidates = [
        public_path($local),
        public_path('storage/' . $local),
      ];
      foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
          return $candidate;
        }
      }
      return $placeholderImage;
    };

    $recipeImage = $normalizeImage($recipe->imagen_principal ?? null);
    $portionValue = $portion ?? $recipe->getPorciones()['cantidad'] ?? 1;
    $portionName = $portionValue > 1
      ? ($recipe->getPorciones()['nombre_plural'] ?? 'Porciones')
      : ($recipe->getPorciones()['nombre'] ?? 'Porción');
    $ingredients = $ingredients ?? $recipe->getIngredientes();
    $nutrition = $nutrition ?? [];
    $instructions = method_exists($recipe, 'getInstrucciones') ? $recipe->getInstrucciones() : [];
  @endphp
  <style>
    @page {
      margin: 12mm 12mm 14mm 12mm;
    }
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    }
    body {
      margin: 0;
      color: {{ $themeData['text'] }};
      background: {{ $themeData['bg'] }};
      font-size: 10px;
      line-height: 1.4;
    }
    .page {
      position: relative;
      min-height: 100%;
    }
    .header {
      border-bottom: 3px solid {{ $themeData['accent'] }};
      padding-bottom: 8px;
      margin-bottom: 14px;
    }
    .eyebrow {
      margin: 0 0 4px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      font-size: 9px;
      color: {{ $themeData['accent'] }};
      font-weight: 700;
    }
    h1 {
      margin: 0;
      font-size: 22px;
      line-height: 1.1;
    }
    .meta {
      margin-top: 6px;
      color: {{ $themeData['muted'] }};
      font-size: 9px;
    }
    .hero {
      display: table;
      width: 100%;
      margin: 14px 0 16px;
    }
    .hero-image,
    .hero-copy {
      display: table-cell;
      vertical-align: top;
    }
    .hero-image {
      width: 38%;
      padding-right: 14px;
    }
    .hero-image img {
      width: 100%;
      height: 250px;
      object-fit: cover;
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.08);
      background: #fafafa;
    }
    .hero-copy {
      width: 62%;
    }
    .section {
      margin-top: 10px;
      break-inside: avoid;
    }
    .section-title {
      margin: 0 0 8px;
      font-size: 12px;
      font-weight: 700;
      color: {{ $themeData['accent'] }};
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 1px solid {{ $themeData['accentSoft'] }};
      padding-bottom: 4px;
    }
    .ingredients {
      margin: 0;
      padding-left: 18px;
    }
    .ingredients li {
      margin: 0 0 4px;
      break-inside: avoid;
    }
    .ingredient-line {
      display: flex;
      gap: 8px;
      justify-content: space-between;
    }
    .ingredient-name {
      flex: 1;
      min-width: 0;
    }
    .ingredient-qty {
      flex: 0 0 auto;
      color: {{ $themeData['muted'] }};
      white-space: nowrap;
    }
    .instructions {
      margin: 0;
      padding-left: 18px;
    }
    .instructions li {
      margin: 0 0 6px;
      break-inside: avoid;
    }
    .nutrition-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9px;
    }
    .nutrition-table th,
    .nutrition-table td {
      padding: 5px 0;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      text-align: left;
      vertical-align: top;
    }
    .nutrition-table th {
      color: {{ $themeData['accent'] }};
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-size: 8px;
    }
    .nutrition-table td:last-child,
    .nutrition-table th:last-child {
      text-align: right;
      white-space: nowrap;
    }
    .notes {
      margin-top: 10px;
      color: {{ $themeData['muted'] }};
      font-size: 9px;
    }
    .badge {
      display: inline-block;
      margin-top: 6px;
      padding: 4px 8px;
      border-radius: 999px;
      background: {{ $themeData['accentSoft'] }};
      color: {{ $themeData['accent'] }};
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div class="eyebrow">{{ $calendar->title }}</div>
      <h1>{{ $recipe->titulo }}</h1>
      <div class="meta">{{ auth()->user()->bname ?? auth()->user()->name ?? '' }} · {{ $portionValue }} {{ $portionName }}</div>
    </div>

    <div class="hero">
      <div class="hero-image">
        <img src="{{ $recipeImage }}" alt="{{ $recipe->titulo }}">
        <div class="badge">{{ ucfirst($template) }} theme</div>
      </div>
      <div class="hero-copy">
        <div class="section">
          <div class="section-title">Ingredientes</div>
          <ul class="ingredients">
            @foreach($ingredients as $ingredient)
              <li>
                <div class="ingredient-line">
                  <span class="ingredient-name">{!! $ingredient['ingrediente'] ?? 'Ingrediente' !!}</span>
                  <span class="ingredient-qty">
                    @if(isset($ingredient['cantidad']) && $ingredient['cantidad'] !== '')
                      {{ $ingredient['cantidad'] }}
                      @if(!empty($ingredient['medida']))
                        {{ $ingredient['medida'] }}
                      @endif
                    @endif
                  </span>
                </div>
              </li>
            @endforeach
          </ul>
        </div>

        @if(!empty($instructions))
          <div class="section">
            <div class="section-title">Preparación</div>
            <ol class="instructions">
              @foreach($instructions as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>
          </div>
        @endif

        @if(!empty($nutrition['info']))
          <div class="section">
            <div class="section-title">Información nutricional</div>
            <table class="nutrition-table">
              <thead>
                <tr>
                  <th>Nutriente</th>
                  <th>Cantidad</th>
                </tr>
              </thead>
              <tbody>
                @foreach($nutrition['info'] as $nutrient)
                  @if(!empty($nutrient['mostrar']))
                    <tr>
                      <td>{{ $nutrient['nombre'] ?? 'Nutriente' }}</td>
                      <td>
                        {{ isset($nutrient['cantidad']) ? number_format((float) $nutrient['cantidad'], 2, '.', '') : '0.00' }}
                        {{ $nutrient['unidad_medida'] ?? '' }}
                      </td>
                    </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="notes">
      {{ $recipe->tips ?? '' }}
    </div>
  </div>
</body>
</html>
