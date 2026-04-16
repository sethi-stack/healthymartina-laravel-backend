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
    .day-grid {
      width: 90%;
      margin: 0 auto;
      table-layout: fixed;
      border-collapse: collapse;
    }
    .day-card {
      vertical-align: top;
      width: 33.3333%;
      padding: 0 10px 20px 0;
    }
    .day-title {
      color: {{ auth()->user()->color }};
      font-size: 16px;
      font-weight: 700;
      margin: 0 0 8px;
      text-transform: uppercase;
    }
    .macro-line {
      font-size: 10px;
      margin-bottom: 8px;
      white-space: nowrap;
    }
    .macro-line .carb { color: #cc77ff; }
    .macro-line .protein { color: #51d95a; }
    .macro-line .fat { color: #d07cd4; }
    .nutri-list {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .nutri-split {
      width: 100%;
      table-layout: fixed;
    }
    .nutri-split td {
      vertical-align: top;
      width: 50%;
      padding-right: 8px;
    }
    .nutri-list td {
      padding: 4px 0;
      font-size: 10px;
      vertical-align: top;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .nutri-list td.label {
      width: 58%;
      padding-right: 6px;
    }
    .nutri-list td.value {
      width: 42%;
      text-align: right;
      white-space: nowrap;
    }
    .nutri-list .bold {
      font-weight: 700;
    }
    .nutri-col-wrap {
      width: 100%;
      table-layout: fixed;
      border-collapse: collapse;
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
    $nutriInfo = $nutri_info ?? [];
  @endphp

  @foreach($dayChunks as $chunk)
    <table class="day-grid" width="100%">
      <tr>
        @foreach($chunk as $dayKey)
          @php
            $dayRows = $nutriInfo[$dayKey] ?? [];
            $topRows = array_slice($dayRows, 0, 2);
            $restRows = array_slice($dayRows, 2);
          @endphp
          <td class="day-card" width="{{ 100 / max(count($chunk), 1) }}%">
            <div class="day-title">{{ $cLabels['days'][$dayKey] ?? $dayKey }}</div>
            <div class="macro-line">
              @if(!empty($topRows[0]))
                <span class="carb">Carbohidratos {{ number_format((float)($topRows[0][4] ?? 0), 2) }}%</span>
              @endif
              @if(!empty($topRows[1]))
                <span class="protein"> Proteína {{ number_format((float)($topRows[1][4] ?? 0), 2) }}%</span>
              @endif
            </div>
            @php
              $leftRows = array_slice($restRows, 0, (int) ceil(count($restRows) / 2));
              $rightRows = array_slice($restRows, (int) ceil(count($restRows) / 2));
            @endphp
            <table class="nutri-split">
              <tr>
                <td>
                  <table class="nutri-list">
                    @foreach($leftRows as $row)
                      <tr>
                        <td class="label @if(($loop->index % 2) == 0) bold @endif">{{ $row[1] ?? '' }}</td>
                        <td class="value">{{ isset($row[3]) ? number_format((float) $row[3], 2, '.', ',') : '' }} {{ $row[2] ?? '' }}</td>
                      </tr>
                    @endforeach
                  </table>
                </td>
                <td>
                  <table class="nutri-list">
                    @foreach($rightRows as $row)
                      <tr>
                        <td class="label @if(($loop->index % 2) == 0) bold @endif">{{ $row[1] ?? '' }}</td>
                        <td class="value">{{ isset($row[3]) ? number_format((float) $row[3], 2, '.', ',') : '' }} {{ $row[2] ?? '' }}</td>
                      </tr>
                    @endforeach
                  </table>
                </td>
              </tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>
  @endforeach
</body>
</html>
