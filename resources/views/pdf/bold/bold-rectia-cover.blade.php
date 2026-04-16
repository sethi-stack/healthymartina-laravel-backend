<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
    @page { margin: 0; }
    * {
      box-sizing: border-box;
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    }
    body { margin: 0; }
    .cover {
      position: relative;
      min-height: 100vh;
      padding: 42px 40px 36px;
      background: #fff;
    }
    .cover::before {
      content: '';
      position: absolute;
      inset: 0;
      background: {{ auth()->user()->color }}14;
      z-index: 0;
    }
    .cover-content {
      position: relative;
      z-index: 1;
    }
    .title {
      margin: 0 0 8px;
      font-size: 26px;
      font-weight: 800;
      color: {{ auth()->user()->color }};
      text-transform: uppercase;
    }
    .subtitle {
      margin: 0 0 18px;
      color: #666;
      font-size: 11px;
    }
    .image-wrap {
      margin: 20px 0 18px;
      height: 620px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fafafa;
    }
    .image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 11px;
      color: #555;
    }
    .badge {
      padding: 6px 10px;
      border-radius: 999px;
      background: {{ auth()->user()->color }}22;
      color: {{ auth()->user()->color }};
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-size: 9px;
    }
  </style>
</head>
<body>
  <div class="cover">
    <div class="cover-content">
      <h1 class="title">{{ $recipe->titulo ?? $calendario->title }}</h1>
      <p class="subtitle">{{ $calendario->title }}</p>
      <div class="image-wrap">
        <img src="{{ $receta_cover_img_src }}" alt="{{ $recipe->titulo ?? $calendario->title }}">
      </div>
      <div class="meta">
        <span class="badge">Bold theme</span>
        <span>{{ auth()->user()->bname ?? auth()->user()->name ?? '' }}</span>
      </div>
    </div>
  </div>
</body>
</html>
