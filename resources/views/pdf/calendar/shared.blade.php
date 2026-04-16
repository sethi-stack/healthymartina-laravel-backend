<!DOCTYPE html>
<html lang="es">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{{ $calendar->title }}</title>
  @php
    $template = $template ?? 'classic';
    $themeConfig = [
      'classic' => [
        'accent' => '#dcb244',
        'accentSoft' => '#f8ebc5',
        'bg' => '#fffdf8',
        'headerBorder' => '#dcb244',
        'text' => '#222222',
        'muted' => '#777777',
      ],
      'modern' => [
        'accent' => '#5f8ea6',
        'accentSoft' => '#dfeaf0',
        'bg' => '#f7fbfd',
        'headerBorder' => '#5f8ea6',
        'text' => '#1f2b33',
        'muted' => '#66757f',
      ],
      'bold' => [
        'accent' => '#111111',
        'accentSoft' => '#ececec',
        'bg' => '#ffffff',
        'headerBorder' => '#111111',
        'text' => '#111111',
        'muted' => '#555555',
      ],
    ];
    $themeData = $themeConfig[$template] ?? $themeConfig['classic'];
    $placeholderImage = $placeholderImage ?? public_path('img/recetas/imagen-receta-principal.jpg');
    $user = $user ?? auth()->user();

    $labels = $labels ?? json_decode($calendar->labels, true) ?? [];
    $dayLabels = $labels['days'] ?? [
      'day_1' => 'Lunes',
      'day_2' => 'Martes',
      'day_3' => 'Miércoles',
      'day_4' => 'Jueves',
      'day_5' => 'Viernes',
      'day_6' => 'Sábado',
      'day_7' => 'Domingo',
    ];
    $mealLabels = $labels['meals'] ?? [
      'meal_1' => 'Desayuno',
      'meal_2' => 'Lunch',
      'meal_3' => 'Comida',
      'meal_4' => 'Snack',
      'meal_5' => 'Cena',
      'meal_6' => 'Otros',
    ];
    $mainSchedule = $mainSchedule ?? json_decode($calendar->main_schedule, true) ?? [];
    $sidesSchedule = $sidesSchedule ?? json_decode($calendar->sides_schedule, true) ?? [];
    $mainLeftovers = $mainLeftovers ?? json_decode($calendar->main_leftovers, true) ?? [];
    $sidesLeftovers = $sidesLeftovers ?? json_decode($calendar->sides_leftovers, true) ?? [];
    $mainRacion = $mainRacion ?? json_decode($calendar->main_racion, true) ?? [];
    $sidesRacion = $sidesRacion ?? json_decode($calendar->sides_racion, true) ?? [];
    $recipesMap = collect($recipes_list ?? $recipes ?? [])->keyBy('id');

    $visibleDayKeys = [];
    foreach ($dayLabels as $dayKey => $dayLabel) {
      foreach ($mealLabels as $mealKey => $mealLabel) {
        if (($mainSchedule[$dayKey][$mealKey] ?? null) || ($sidesSchedule[$dayKey][$mealKey] ?? null)) {
          $visibleDayKeys[] = $dayKey;
          break;
        }
      }
    }

    $visibleMealKeys = [];
    foreach ($mealLabels as $mealKey => $mealLabel) {
      foreach ($visibleDayKeys as $dayKey) {
        if (($mainSchedule[$dayKey][$mealKey] ?? null) || ($sidesSchedule[$dayKey][$mealKey] ?? null)) {
          $visibleMealKeys[] = $mealKey;
          break;
        }
      }
    }

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
  @endphp
  <style>
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    }

    body {
      margin: 0;
      padding: 0;
      color: {{ $themeData['text'] }};
      font-size: 9px;
      background: {{ $themeData['bg'] }};
    }

    @page {
      margin: 1.05cm 0.9cm 1.25cm 0.9cm;
    }

    .page-footer {
      position: fixed;
      left: 0;
      right: 0;
      bottom: -0.3cm;
      border-top: 1px solid #ececec;
      padding-top: 4px;
      text-align: center;
      font-size: 8px;
      color: #9a9a9a;
    }

    .doc-header {
      border-bottom: 3px solid {{ $themeData['headerBorder'] }};
      padding-bottom: 8px;
      margin-bottom: 14px;
    }

    .doc-header h1 {
      margin: 0 0 2px;
      font-size: 20px;
      color: {{ $themeData['accent'] }};
      font-weight: 700;
    }

    .doc-header p {
      margin: 0;
      font-size: 9px;
      color: {{ $themeData['muted'] }};
    }

    .brand-note {
      float: right;
      text-align: right;
      font-size: 8px;
      color: {{ $themeData['muted'] }};
      padding-top: 2px;
    }

    .section-title {
      font-size: 12px;
      font-weight: 700;
      color: {{ $themeData['accent'] }};
      margin: 14px 0 8px;
      padding-bottom: 4px;
      border-bottom: 1px solid {{ $themeData['accentSoft'] }};
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .page-section {
      break-before: page;
      page-break-before: always;
    }

    .page-section:first-of-type {
      break-before: auto;
      page-break-before: auto;
    }

    .calendar-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-bottom: 10px;
    }

    .calendar-table th,
    .calendar-table td {
      border: 1px solid #dcdcdc;
      vertical-align: top;
      overflow: hidden;
    }

    .calendar-table thead th {
      background: {{ $themeData['accentSoft'] }};
      color: {{ $themeData['text'] }};
      font-size: 9px;
      font-weight: 700;
      padding: 7px 5px;
      text-align: center;
    }

    .meal-head {
      width: 72px;
      background: {{ $themeData['accentSoft'] }};
      padding: 0;
      text-align: center;
      position: relative;
      height: 78px;
      overflow: hidden;
    }

    .meal-head span {
      position: absolute;
      top: 50%;
      left: 50%;
      display: block;
      transform: translate(-50%, -50%) rotate(-90deg);
      transform-origin: center center;
      white-space: nowrap;
      font-size: 9px;
      font-weight: 700;
      color: {{ $themeData['text'] }};
      line-height: 1;
      padding: 0;
      width: max-content;
    }

    .calendar-cell {
      padding: 5px 4px;
      min-height: 76px;
      overflow: hidden;
    }

    .recipe-block {
      display: flex;
      gap: 6px;
      align-items: flex-start;
      padding: 0 0 5px;
      margin-bottom: 5px;
      border-bottom: 1px solid #efefef;
      width: 100%;
      overflow: hidden;
    }

    .recipe-block:last-child {
      padding-bottom: 0;
      margin-bottom: 0;
      border-bottom: 0;
    }

    .recipe-thumb {
      width: 20px;
      height: 20px;
      border-radius: 4px;
      object-fit: cover;
      flex: 0 0 20px;
      background: #fafafa;
    }

    .recipe-copy {
      min-width: 0;
      flex: 1;
      overflow: hidden;
    }

    .recipe-title {
      font-size: 8px;
      line-height: 1.15;
      font-weight: 700;
      color: {{ $themeData['text'] }};
      word-break: break-word;
      overflow-wrap: anywhere;
      hyphens: auto;
      max-width: 100%;
    }

    .recipe-meta {
      display: block;
      margin-top: 2px;
      font-size: 6.5px;
      line-height: 1.1;
      color: {{ $themeData['muted'] }};
      max-width: 100%;
      overflow-wrap: anywhere;
    }

    .recipe-leftover .recipe-title,
    .recipe-leftover .recipe-meta {
      color: #999;
    }

    .recipe-leftover .recipe-title {
      text-decoration: line-through;
    }

    .empty-cell {
      color: #c7c7c7;
      font-size: 8px;
      text-align: center;
      padding-top: 24px;
    }

    .nutri-table {
      width: 100%;
      border-collapse: collapse;
    }

    .nutri-table th {
      background: #f7f7f7;
      font-size: 9px;
      color: #777;
      font-weight: 700;
      text-transform: uppercase;
      padding: 5px 8px;
      border: 1px solid #ededed;
      text-align: left;
    }

    .nutri-table td {
      padding: 5px 8px;
      border: 1px solid #ededed;
      font-size: 10px;
      vertical-align: middle;
    }

    .nutri-table tr:nth-child(even) td {
      background: #fcfcfc;
    }

    .cal-value {
      font-weight: 700;
      color: {{ $themeData['accent'] }};
      font-size: 12px;
    }

    .lista-grid {
      width: 100%;
      border-collapse: collapse;
    }

    .lista-grid td {
      width: 50%;
      vertical-align: top;
      padding: 0 6px 0 0;
    }

    .lista-category {
      margin-bottom: 11px;
    }

    .lista-cat-title {
      font-size: 10px;
      font-weight: 700;
      color: {{ $themeData['accent'] }};
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border-bottom: 1px solid {{ $themeData['accentSoft'] }};
      padding-bottom: 3px;
      margin-bottom: 5px;
    }

    .lista-items {
      width: 100%;
      border-collapse: collapse;
    }

    .lista-items td {
      padding: 3px 4px;
      font-size: 9px;
      vertical-align: middle;
    }

    .lista-items tr:nth-child(even) td {
      background: rgba(0,0,0,0.02);
    }

    .checkbox-cell {
      width: 14px;
      text-align: center;
      font-family: DejaVu Sans, sans-serif;
    }

    .item-taken {
      color: #bbb;
      text-decoration: line-through;
    }

    .item-amount {
      color: #777;
      font-size: 8px;
      width: 88px;
      text-align: right;
    }
  </style>
</head>
<body>
<div class="page-footer">
  {{ $calendar->title }} &nbsp;·&nbsp; {{ $user->name }}
  @if(!empty($user->bname)) &nbsp;·&nbsp; {{ $user->bname }} @endif
  &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>

<div class="doc-header">
  <div class="brand-note">
    <div>Healthy Martina</div>
    <div>{{ ucfirst($template) }} theme</div>
  </div>
  <h1>{{ $calendar->title }}</h1>
  <p>
    {{ $user->name }}
    @if(!empty($user->bname)) · {{ $user->bname }} @endif
    &nbsp;·&nbsp; Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}
  </p>
</div>

@if(in_array(1, $exportParams) && !empty($visibleDayKeys) && !empty($visibleMealKeys))
<div class="page-section">
  <div class="section-title">Calendario Semanal</div>
  <table class="calendar-table">
    <thead>
      <tr>
        <th width="72"></th>
        @foreach($visibleDayKeys as $dayKey)
          <th>{{ $dayLabels[$dayKey] ?? $dayKey }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($visibleMealKeys as $mealKey)
        <tr>
          <th class="meal-head"><span>{{ $mealLabels[$mealKey] ?? $mealKey }}</span></th>
          @foreach($visibleDayKeys as $dayKey)
            @php
              $mainId = $mainSchedule[$dayKey][$mealKey] ?? null;
              $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
              $mainRecipe = $mainId ? ($recipesMap[$mainId] ?? null) : null;
              $sideRecipe = $sideId ? ($recipesMap[$sideId] ?? null) : null;
              $mainLeftover = !empty($mainLeftovers[$dayKey][$mealKey] ?? false);
              $sideLeftover = !empty($sidesLeftovers[$dayKey][$mealKey] ?? false);
            @endphp
            <td>
              <div class="calendar-cell">
                @if($mainRecipe || $sideRecipe)
                  @if($mainRecipe)
                    @php
                      $mainImage = $normalizeImage($mainRecipe->imagen_principal ?? null);
                    @endphp
                    <div class="recipe-block {{ $mainLeftover ? 'recipe-leftover' : '' }}">
                      <img class="recipe-thumb" src="{{ $mainImage }}" alt="{{ $mainRecipe->titulo }}">
                      <div class="recipe-copy">
                        <div class="recipe-title">{{ $mainRecipe->titulo }}</div>
                        <span class="recipe-meta">Principal @if(!empty($mainRacion[$dayKey][$mealKey])) · x{{ $mainRacion[$dayKey][$mealKey] }} @endif</span>
                      </div>
                    </div>
                  @endif

                  @if($sideRecipe)
                    @php
                      $sideImage = $normalizeImage($sideRecipe->imagen_principal ?? null);
                    @endphp
                    <div class="recipe-block {{ $sideLeftover ? 'recipe-leftover' : '' }}">
                      <img class="recipe-thumb" src="{{ $sideImage }}" alt="{{ $sideRecipe->titulo }}">
                      <div class="recipe-copy">
                        <div class="recipe-title">{{ $sideRecipe->titulo }}</div>
                        <span class="recipe-meta">Acompa&ntilde;ante @if(!empty($sidesRacion[$dayKey][$mealKey])) · x{{ $sidesRacion[$dayKey][$mealKey] }} @endif</span>
                      </div>
                    </div>
                  @endif
                @else
                  <div class="empty-cell">&nbsp;</div>
                @endif
              </div>
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
  </div>
@endif

@if(in_array(4, $exportParams) && !empty($nutritionByDay))
  <div class="page-section">
  <div class="section-title">Información Nutricional</div>
  <table class="nutri-table">
    <thead>
      <tr>
        <th>Día</th>
        <th>Calorías</th>
      </tr>
    </thead>
    <tbody>
      @foreach($nutritionByDay as $row)
        <tr>
          <td>{{ $row['label'] ?? '' }}</td>
          <td class="cal-value">{{ $row['calories'] ?? 0 }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  </div>
@endif

@if(in_array(2, $exportParams) && !empty($listaData['categories']))
  <div class="page-section">
  <div class="section-title">Lista de Compras</div>
  <table class="lista-grid">
    <tr>
      @php
        $allCats = $listaData['categories'];
        $half = (int) ceil(count($allCats) / 2);
        $leftCats = array_slice($allCats, 0, $half);
        $rightCats = array_slice($allCats, $half);
      @endphp
      <td>
        @foreach($leftCats as $cat)
          <div class="lista-category">
            <div class="lista-cat-title">{{ $cat['name'] }}</div>
            <table class="lista-items">
              @foreach($cat['items'] as $item)
                @php
                  $isTaken = !empty($item['ingrediente_id']) && in_array($item['ingrediente_id'], $listaData['taken_ids'] ?? []);
                @endphp
                <tr>
                  <td class="checkbox-cell">
                    @if($isTaken)
                      &#9745;
                    @else
                      &#9744;
                    @endif
                  </td>
                  <td class="{{ $isTaken ? 'item-taken' : '' }}">{{ $item['nombre'] ?? $item['ingrediente'] ?? 'Ingrediente' }}</td>
                  <td class="item-amount {{ $isTaken ? 'item-taken' : '' }}">
                    @if(!empty($item['cantidad']))
                      {{ $item['cantidad'] }}@if(!empty($item['unidad'])) {{ $item['unidad'] }}@endif
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>
          </div>
        @endforeach
      </td>
      <td>
        @foreach($rightCats as $cat)
          <div class="lista-category">
            <div class="lista-cat-title">{{ $cat['name'] }}</div>
            <table class="lista-items">
              @foreach($cat['items'] as $item)
                @php
                  $isTaken = !empty($item['ingrediente_id']) && in_array($item['ingrediente_id'], $listaData['taken_ids'] ?? []);
                @endphp
                <tr>
                  <td class="checkbox-cell">
                    @if($isTaken)
                      &#9745;
                    @else
                      &#9744;
                    @endif
                  </td>
                  <td class="{{ $isTaken ? 'item-taken' : '' }}">{{ $item['nombre'] ?? $item['ingrediente'] ?? 'Ingrediente' }}</td>
                  <td class="item-amount {{ $isTaken ? 'item-taken' : '' }}">
                    @if(!empty($item['cantidad']))
                      {{ $item['cantidad'] }}@if(!empty($item['unidad'])) {{ $item['unidad'] }}@endif
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>
          </div>
        @endforeach
      </td>
    </tr>
  </table>
  </div>
@endif
</body>
</html>
