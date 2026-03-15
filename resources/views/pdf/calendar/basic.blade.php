<!DOCTYPE html>
<html lang="es">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{{ $calendar->title }}</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: Helvetica, Arial, sans-serif;
    }
    body {
      font-size: 11px;
      color: #222;
      margin: 0;
      padding: 0;
    }
    @page {
      margin: 1.5cm 1.2cm 1.8cm 1.2cm;
    }

    /* ── Header ──────────────────────────────────────────────── */
    .doc-header {
      border-bottom: 3px solid #dcb244;
      padding-bottom: 8px;
      margin-bottom: 16px;
    }
    .doc-header h1 {
      margin: 0 0 2px;
      font-size: 20px;
      color: #dcb244;
      font-weight: bold;
    }
    .doc-header p {
      margin: 0;
      font-size: 9px;
      color: #888;
    }

    /* ── Section titles ───────────────────────────────────────── */
    .section-title {
      font-size: 13px;
      font-weight: bold;
      color: #dcb244;
      margin: 18px 0 8px;
      padding-bottom: 4px;
      border-bottom: 1px solid #f0d88a;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* ── Page break ───────────────────────────────────────────── */
    .page-break {
      page-break-before: always;
    }

    /* ── Calendar grid ────────────────────────────────────────── */
    .calendar-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    .calendar-table th {
      background: #dcb244;
      color: #fff;
      padding: 6px 8px;
      font-size: 10px;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .calendar-table td {
      vertical-align: top;
      padding: 5px 8px;
      border-bottom: 1px solid #f0f0f0;
    }
    .calendar-table tr:nth-child(even) td {
      background: #fafafa;
    }
    .meal-label {
      font-size: 8px;
      font-weight: bold;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 2px;
    }
    .recipe-name {
      font-size: 10px;
      color: #222;
      font-weight: bold;
    }
    .recipe-side {
      font-size: 9px;
      color: #666;
      margin-top: 2px;
    }
    .racion-badge {
      font-size: 8px;
      color: #dcb244;
      font-weight: bold;
    }
    .empty-cell {
      color: #ccc;
      font-size: 9px;
    }

    /* ── Nutrition table ──────────────────────────────────────── */
    .nutri-table {
      width: 100%;
      border-collapse: collapse;
    }
    .nutri-table th {
      background: #f7f7f7;
      font-size: 9px;
      color: #888;
      font-weight: bold;
      text-transform: uppercase;
      padding: 5px 8px;
      border: 1px solid #eee;
      text-align: left;
    }
    .nutri-table td {
      padding: 5px 8px;
      border: 1px solid #eee;
      font-size: 10px;
      vertical-align: middle;
    }
    .nutri-table tr:nth-child(even) td {
      background: #fdfdf5;
    }
    .cal-value {
      font-weight: bold;
      color: #dcb244;
      font-size: 12px;
    }

    /* ── Lista / Shopping list ────────────────────────────────── */
    .lista-category {
      margin-bottom: 12px;
    }
    .lista-cat-title {
      font-size: 10px;
      font-weight: bold;
      color: #dcb244;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 1px solid #f0d88a;
      padding-bottom: 3px;
      margin-bottom: 5px;
    }
    .lista-items {
      width: 100%;
      border-collapse: collapse;
    }
    .lista-items td {
      padding: 3px 4px;
      font-size: 10px;
      vertical-align: middle;
    }
    .lista-items tr:nth-child(even) td {
      background: #fafafa;
    }
    .checkbox-cell {
      width: 14px;
      text-align: center;
    }
    .item-taken {
      color: #bbb;
      text-decoration: line-through;
    }
    .item-amount {
      color: #888;
      font-size: 9px;
      width: 80px;
    }

    /* ── Footer ───────────────────────────────────────────────── */
    footer {
      position: fixed;
      bottom: -0.5cm;
      left: 0;
      right: 0;
      height: 1cm;
      font-size: 8px;
      color: #aaa;
      text-align: center;
      border-top: 1px solid #f0f0f0;
      padding-top: 4px;
    }
  </style>
</head>
<body>

<footer>
  {{ $calendar->title }} &nbsp;·&nbsp; {{ $user->name }}
  @if($user->bname) &nbsp;·&nbsp; {{ $user->bname }} @endif
  &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</footer>

{{-- ═══════════════════════════════════════════════════════════
     DOCUMENT HEADER
═══════════════════════════════════════════════════════════ --}}
<div class="doc-header">
  <h1>{{ $calendar->title }}</h1>
  <p>
    {{ $user->name }}
    @if($user->bname) · {{ $user->bname }} @endif
    &nbsp;·&nbsp; Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}
  </p>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 1 — CALENDAR GRID
═══════════════════════════════════════════════════════════ --}}
@if(in_array(1, $exportParams) && !empty($days))

<div class="section-title">Calendario Semanal</div>

@php
  // Group days into rows of 3 for portrait layout (3 cols per row)
  $dayChunks = array_chunk(array_values($days), 3, true);
  $dayKeys   = array_keys($days);
  $chunks    = [];
  $chunk     = [];
  $i         = 0;
  foreach ($days as $dKey => $dData) {
    $chunk[$dKey] = $dData;
    $i++;
    if ($i % 3 === 0) {
      $chunks[] = $chunk;
      $chunk = [];
    }
  }
  if (!empty($chunk)) $chunks[] = $chunk;
@endphp

@foreach($chunks as $chunkIndex => $dayGroup)
  @if($chunkIndex > 0)
    <div style="margin-top: 14px;"></div>
  @endif

  <table class="calendar-table">
    <thead>
      <tr>
        <th width="22%">Comida</th>
        @foreach($dayGroup as $dayKey => $dayData)
          <th>{{ $dayData['label'] }}</th>
        @endforeach
        @for($pad = count($dayGroup); $pad < 3; $pad++)
          <th></th>
        @endfor
      </tr>
    </thead>
    <tbody>
      @php
        // Collect all meal keys used across this chunk of days
        $allMealKeys = [];
        foreach ($dayGroup as $dData) {
          foreach (array_keys($dData['meals']) as $mk) {
            $allMealKeys[$mk] = true;
          }
        }
        $mealLabels = [];
        foreach (array_keys($allMealKeys) as $mk) {
          foreach ($dayGroup as $dData) {
            if (isset($dData['meals'][$mk])) {
              $mealLabels[$mk] = $dData['meals'][$mk]['label'];
              break;
            }
          }
        }
      @endphp

      @foreach($mealLabels as $mealKey => $mealLabel)
        <tr>
          <td>
            <div class="meal-label">{{ $mealLabel }}</div>
          </td>
          @foreach($dayGroup as $dayKey => $dayData)
            <td>
              @if(isset($dayData['meals'][$mealKey]) && $dayData['meals'][$mealKey]['main'])
                @php $meal = $dayData['meals'][$mealKey]; @endphp
                <div class="recipe-name">
                  @if($meal['racion'] > 1)
                    <span class="racion-badge">{{ $meal['racion'] }}x </span>
                  @endif
                  {{ $meal['main']->titulo }}
                </div>
                @if($meal['side'])
                  <div class="recipe-side">
                    @if($meal['sRacion'] > 1)
                      <span class="racion-badge">{{ $meal['sRacion'] }}x </span>
                    @endif
                    + {{ $meal['side']->titulo }}
                  </div>
                @endif
              @else
                <span class="empty-cell">—</span>
              @endif
            </td>
          @endforeach
          @for($pad = count($dayGroup); $pad < 3; $pad++)
            <td></td>
          @endfor
        </tr>
      @endforeach
    </tbody>
  </table>
@endforeach

@endif {{-- end calendar section --}}


{{-- ═══════════════════════════════════════════════════════════
     SECTION 2 — NUTRITION (calories per day)
═══════════════════════════════════════════════════════════ --}}
@if(in_array(4, $exportParams) && !empty($nutritionByDay))

<div class="{{ in_array(1, $exportParams) ? 'page-break' : '' }}"></div>
<div class="section-title">Información Nutricional</div>
<p style="font-size:9px; color:#999; margin: 0 0 8px;">
  Estimado de calorías por día basado en los valores de cada receta.
</p>

<table class="nutri-table">
  <thead>
    <tr>
      <th>Día</th>
      <th>Calorías estimadas</th>
      <th>Recetas del día</th>
    </tr>
  </thead>
  <tbody>
    @foreach($nutritionByDay as $dayKey => $dayNutri)
      <tr>
        <td>{{ $dayNutri['label'] }}</td>
        <td><span class="cal-value">{{ number_format($dayNutri['calories']) }}</span> kcal</td>
        <td style="font-size:9px; color:#666;">
          @php
            $dayRecipes = [];
            $mainSch = json_decode($calendar->main_schedule, true) ?? [];
            $sidesSch = json_decode($calendar->sides_schedule, true) ?? [];
            foreach (($mainSch[$dayKey] ?? []) as $mealKey => $rid) {
              if ($rid && isset($recipes[$rid])) $dayRecipes[] = $recipes[$rid]->titulo;
            }
            foreach (($sidesSch[$dayKey] ?? []) as $mealKey => $rid) {
              if ($rid && isset($recipes[$rid])) $dayRecipes[] = $recipes[$rid]->titulo;
            }
            $dayRecipes = array_unique($dayRecipes);
          @endphp
          {{ implode(', ', $dayRecipes) ?: '—' }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

@endif {{-- end nutrition section --}}


{{-- ═══════════════════════════════════════════════════════════
     SECTION 3 — LISTA / SHOPPING LIST
═══════════════════════════════════════════════════════════ --}}
@if(in_array(2, $exportParams) && !empty($listaData))

@php $hasItems = !empty($listaData['categories']); @endphp

@if($hasItems)
<div class="page-break"></div>
<div class="section-title">Lista de Compras</div>

@php
  // Two-column layout: split categories into two halves
  $allCats  = $listaData['categories'];
  $half     = (int) ceil(count($allCats) / 2);
  $leftCats = array_slice($allCats, 0, $half);
  $rightCats = array_slice($allCats, $half);
@endphp

<table width="100%" style="border-collapse: collapse;">
  <tr>
    {{-- Left column --}}
    <td width="49%" style="vertical-align: top; padding-right: 8px;">
      @foreach($leftCats as $cat)
        <div class="lista-category">
          <div class="lista-cat-title">{{ $cat['name'] }}</div>
          <table class="lista-items" width="100%">
            @foreach($cat['items'] as $item)
              @php
                $isTaken = isset($item['ingrediente_id'])
                  && in_array($item['ingrediente_id'], $listaData['taken_ids']);
              @endphp
              <tr>
                <td class="checkbox-cell">
                  @if($isTaken)
                    &#9745;
                  @else
                    &#9744;
                  @endif
                </td>
                <td class="{{ $isTaken ? 'item-taken' : '' }}">
                  {{ $item['nombre'] ?? ($item['name'] ?? '') }}
                </td>
                <td class="item-amount {{ $isTaken ? 'item-taken' : '' }}">
                  @if(!empty($item['cantidad']))
                    {{ $item['cantidad'] }}
                    @if(!empty($item['unidad'])) {{ $item['unidad'] }} @endif
                  @endif
                </td>
              </tr>
            @endforeach
          </table>
        </div>
      @endforeach
    </td>

    <td width="2%" style="border-right: 1px solid #f0f0f0;"></td>

    {{-- Right column --}}
    <td width="49%" style="vertical-align: top; padding-left: 8px;">
      @foreach($rightCats as $cat)
        <div class="lista-category">
          <div class="lista-cat-title">{{ $cat['name'] }}</div>
          <table class="lista-items" width="100%">
            @foreach($cat['items'] as $item)
              @php
                $isTaken = isset($item['ingrediente_id'])
                  && in_array($item['ingrediente_id'], $listaData['taken_ids']);
              @endphp
              <tr>
                <td class="checkbox-cell">
                  @if($isTaken)
                    &#9745;
                  @else
                    &#9744;
                  @endif
                </td>
                <td class="{{ $isTaken ? 'item-taken' : '' }}">
                  {{ $item['nombre'] ?? ($item['name'] ?? '') }}
                </td>
                <td class="item-amount {{ $isTaken ? 'item-taken' : '' }}">
                  @if(!empty($item['cantidad']))
                    {{ $item['cantidad'] }}
                    @if(!empty($item['unidad'])) {{ $item['unidad'] }} @endif
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
@endif

@endif {{-- end lista section --}}

</body>
</html>
