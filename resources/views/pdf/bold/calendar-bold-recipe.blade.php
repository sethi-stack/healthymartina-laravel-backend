<?php
    $recipe_ingredients = [];
    $recipe_porcion = $porcion ?? '';
    $recipe_ingredients_data = $recipe_ingredients_data ?? [];
    $export_param = $export_param ?? [];
    $recipe_ingredients_a = $recipe_ingredients_data[$recipe->id] ?? [];
    foreach ($recipe_ingredients_a as $key => $value) {
        $recipe_ingredients[$key] = $value;
    }

    $nutritionals_info = json_decode(json_encode($nutritionals_info ?? []), false);
    $filter_info = [];
    foreach ($nutritionals_info as $key => $value) {
        if (($value->mostrar ?? 0) == 1) {
            $filter_info[] = $value->id;
        }
    }
    $nutrientes = $recipe->getInformacionNutrimental();
    $filteredNutri = [];
    foreach (($nutrientes['info'] ?? []) as $nutriente) {
        if (($nutriente['mostrar'] ?? 0) && in_array($nutriente['id'] ?? null, $filter_info)) {
            $filteredNutri[] = $nutriente;
        }
    }
    $nutriHalf = (int) ceil(count($filteredNutri) / 2);
    $nutriLeft = array_slice($filteredNutri, 0, $nutriHalf);
    $nutriRight = array_slice($filteredNutri, $nutriHalf);
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
      @page { margin: 35px 0 50px 0; }
      * {
        box-sizing: border-box;
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
      }
      body { font-size: 12px; }
      table .recipe_heading {
        margin: 0 0 10px;
        color: {{ auth()->user()->color }};
        font-size: 12px;
        text-align: left;
      }
      ol { padding: 0 0 0 15px; margin: 0; }
      ol li { margin: 5px 0; font-size: 12px; }
      dt { color: {{ auth()->user()->color }}; font-size: 11px; }
      dl { margin: 0 0 10px; font-size: 10px; }
      .recipe-detail-title {
        text-align: center;
        background: {{ auth()->user()->color }}14;
        padding: 50px 0 200px;
        margin-top: -35px !important;
      }
      .container { width: 80%; margin: 0 auto; }
      .recipe-detail-title h2 {
        color: {{ auth()->user()->color }};
        font-size: 16px;
        margin: 0;
      }
      .recipe-detail-image { margin: 0; }
      .recipe-detail-image img { max-width: 100%; }
      .col-7, .col-3 { display: inline-block; vertical-align: top; box-sizing: border-box; }
      footer {
        position: fixed;
        bottom: 0 !important;
        left: 1cm;
        right: 0;
        height: 35px;
        width: 100%;
        padding-top: 50px;
      }
      td, tr { font-size: 11px !important; }
      .smallText { font-size: 9px; color: #777; }
      .nutrition-wrap {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
      }
      .nutrition-col {
        width: 50%;
        vertical-align: top;
        padding-right: 10px;
      }
      .nutrition-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
      }
      .nutrition-table td {
        padding: 3px 0;
        font-size: 9px !important;
        vertical-align: top;
        word-break: break-word;
        overflow-wrap: anywhere;
      }
      .nutrition-label {
        width: 60%;
        padding-right: 6px;
      }
      .nutrition-value {
        width: 40%;
        text-align: right;
        white-space: nowrap;
      }
    </style>
  </head>
  <body>
    <footer>
      <table width="100%" valign="top">
        <tr>
          <td width="25%"><img width="25%" src="{{ auth()->user()->bimage }}" /></td>
          <td width="25%"></td>
          <td width="50%" style="background: {{ auth()->user()->color }}3f; padding: 25px 15px; font-size:10px;">
            <strong style="color: {{ auth()->user()->color }}">{{ auth()->user()->bname }}</strong>
            <a style="color: #000; text-decoration: none;" href="mailto:{{ auth()->user()->bemail }}">{{ auth()->user()->bemail }}</a>
          </td>
        </tr>
      </table>
    </footer>

    <div class="recipe-detail-outer">
      <table width="85%" style="margin: 0 auto;" valign="top">
        <caption>
          <div class="recipe-detail-image">
            <div class="recipe-detail-title">
              <h2>{{ $recipe->titulo }}</h2>
              <p>
                <strong>
                  @if($recipe_porcion)
                    {{ number_format($recipe_porcion, 0) }} {{ $recipe_porcion > 1 ? $recipe->getPorciones()['nombre_plural'] : $recipe->getPorciones()['nombre'] }}
                  @else
                    {{ number_format($recipe->getPorciones()['cantidad'], 0) }} {{ $recipe->getPorciones()['cantidad'] > 1 ? $recipe->getPorciones()['nombre_plural'] : $recipe->getPorciones()['nombre'] }}
                  @endif
                </strong>
                {{ $recipe->tiempo }} minutos
              </p>
            </div>
            <div class="img-height" style="max-height: 350px; max-width: 90%; margin:-150px auto 0; overflow: hidden">
              <img src="{{ $recipe->imagen_principal }}" alt="Image" />
            </div>
          </div>
        </caption>
      </table>

      <table width="85%" valign="top" style="margin: auto 30px 0px 60px">
        <tr>
          <td width="300px" valign="top">
            <table>
              <tr><th colspan="2"><h3 class="recipe_heading">INGREDIENTES</h3></th></tr>
              @foreach($recipe->getIngredientes() as $ingrediente)
              <tr>
                <td valign="top" class="medidaIngrediente">
                  <b>
                    @if($ingrediente['tipo_medida_id'] == 4)
                      al gusto
                    @else
                      @if(array_key_exists($ingrediente['ingred_uid'], $recipe_ingredients))
                        {!! $recipe_ingredients[$ingrediente['ingred_uid']] !!}
                      @else
                        {{ is_float($ingrediente['cantidad']) ? decToFraction($ingrediente['cantidad']) : $ingrediente['cantidad'] }}
                        {{ $ingrediente['cantidad'] > 1 ? mb_strtolower($ingrediente['medida_plural']) : mb_strtolower($ingrediente['medida']) }}
                      @endif
                    @endif
                  </b>
                </td>
                <td>{!! $ingrediente['ingrediente'] !!} {!! (isset($ingrediente['nota']) && $ingrediente['nota']!='' ? '<span class="smallText">'.$ingrediente['nota'].'</span>' : '') !!}</td>
              </tr>
              @endforeach
            </table>
          </td>
          <td valign="top">
            <table valign="top">
              <tr>
                <td valign="top">
                  <table valign="top">
                    <tr><th colspan="2"><h3 style="color: {{ auth()->user()->color }}; padding-left:0;" class="recipe_heading">INSTRUCCIONES</h3></th></tr>
                    @foreach($recipe->getInstrucciones() as $key => $instruccion)
                    <tr>
                      <td>
                        <span style="color: {{ auth()->user()->color }}"> {{ $key+1 }}. </span>
                        <span>{{ str_replace('◦','º',$instruccion) }}</span>
                      </td>
                    </tr>
                    @endforeach
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      @if(in_array(4, $export_param))
      <table width="85%" valign="top" style="margin: 0 auto 60px">
        <tr>
          <td width="300px" valign="top">
            <table width="300px" valign="top">
              <tr>
                <th style="text-align:left"><h3 class="recipe_heading">INFORMACION NUTRICIONAL</h3><strong style="text-align:left;font-size:8px !important; color: {{ auth()->user()->color }};">CANTIDADES POR PORCION</strong></th>
              </tr>
              <tr valign="top">
                <td colspan="2">
                  <table class="nutrition-wrap">
                    <tr valign="top">
                      <td class="nutrition-col">
                        <table class="nutrition-table">
                          @foreach($nutriLeft as $nutriente)
                            <tr>
                              <td class="nutrition-label">{{ \Illuminate\Support\Str::limit($nutriente['nombre'], 24) }}</td>
                              <td class="nutrition-value">{{ ($nutriente['cantidad'] > 0.01 ? number_format($nutriente['cantidad'], 2, '.', ',') : round($nutriente['cantidad'],3)) }} {{ $nutriente['unidad_medida'] }}</td>
                            </tr>
                          @endforeach
                        </table>
                      </td>
                      <td class="nutrition-col">
                        <table class="nutrition-table">
                          @foreach($nutriRight as $nutriente)
                            <tr>
                              <td class="nutrition-label">{{ \Illuminate\Support\Str::limit($nutriente['nombre'], 24) }}</td>
                              <td class="nutrition-value">{{ ($nutriente['cantidad'] > 0.01 ? number_format($nutriente['cantidad'], 2, '.', ',') : round($nutriente['cantidad'],3)) }} {{ $nutriente['unidad_medida'] }}</td>
                            </tr>
                          @endforeach
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
          <td valign="top">
            @if(in_array(3, $export_param))
            <table valign="top">
              <tr><th colspan="2"><h3 class="recipe_heading" style="padding-left:0;">TIPS</h3></th></tr>
              @for($i = 0; $i < count($recipe->getTips()); $i += 2)
                @if(($i + 2) <= count($recipe->getTips()))
                <tr>
                  <td>
                    <dt>{!! $recipe->getTips()[$i] !!}</dt>
                    <dl>{!! str_replace('◦','º',$recipe->getTips()[$i + 1]) !!}</dl>
                  </td>
                </tr>
                @endif
              @endfor
            </table>
            @endif
          </td>
        </tr>
      </table>
      @endif
    </div>
  </body>
</html>
