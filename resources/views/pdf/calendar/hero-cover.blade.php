<!DOCTYPE html>
<html lang="es">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{{ $recipe->titulo ?? $calendar->title }}</title>
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
    $portionLabel = $recipe->getPorciones()['nombre'] ?? 'Porciones';
    $portionValue = $portion ?? $recipe->getPorciones()['cantidad'] ?? 1;
  @endphp
  <style>
    @page { margin: 0; }
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    }
    body {
      margin: 0;
      background: {{ $themeData['bg'] }};
      color: {{ $themeData['text'] }};
    }
    .cover {
      min-height: 100vh;
      padding: 40px 42px 36px;
      position: relative;
    }
    .cover::before {
      content: '';
      position: absolute;
      inset: 20px;
      border: 1px solid rgba(0,0,0,0.04);
      pointer-events: none;
    }
    .eyebrow {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: {{ $themeData['accent'] }};
      font-weight: 700;
      margin-bottom: 8px;
    }
    .title {
      margin: 0 0 10px;
      font-size: 28px;
      line-height: 1.05;
      font-weight: 800;
      color: {{ $themeData['text'] }};
      max-width: 70%;
    }
    .subtitle {
      margin: 0 0 18px;
      font-size: 11px;
      color: {{ $themeData['muted'] }};
    }
    .image-wrap {
      margin: 22px 0 18px;
      border-radius: 18px;
      overflow: hidden;
      border: 1px solid rgba(0,0,0,0.06);
      background: #fafafa;
      height: 620px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .meta {
      display: flex;
      gap: 16px;
      align-items: center;
      justify-content: space-between;
      margin-top: 12px;
      font-size: 11px;
      color: {{ $themeData['muted'] }};
    }
    .badge {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      background: {{ $themeData['accentSoft'] }};
      color: {{ $themeData['accent'] }};
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-size: 9px;
    }
  </style>
</head>
<body>
  <div class="cover">
    <div class="eyebrow">Receta destacada</div>
    <h1 class="title">{{ $recipe->titulo }}</h1>
    <p class="subtitle">{{ $calendar->title }} · {{ $portionValue }} {{ $portionLabel }}</p>
    <div class="image-wrap">
      <img src="{{ $recipeImage }}" alt="{{ $recipe->titulo }}">
    </div>
    <div class="meta">
      <span class="badge">{{ ucfirst($template) }} theme</span>
      <span>{{ auth()->user()->bname ?? auth()->user()->name ?? '' }}</span>
    </div>
  </div>
</body>
</html>
