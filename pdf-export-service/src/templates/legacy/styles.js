function sanitizeCssColor(value, fallback) {
  const raw = String(value || '').trim();
  // allow hex and common functional notations; strip anything else
  const safe = raw.replace(/[^#(),.%\s0-9a-zA-Z-]/g, '');
  if (!safe) return fallback;
  return safe;
}

function sanitizeUrl(value) {
  const raw = String(value || '').trim();
  if (!raw) return '';
  // Avoid breaking out of CSS url()
  return raw.replace(/["'()\\\s]/g, '');
}

function renderStyles(model = {}) {
  const brandColor = sanitizeCssColor(model?.cover?.brandColor, '#36544e');
  const brandLogo = sanitizeUrl(model?.cover?.brandLogo);

  return `
  <style>
  :root{
    --hm-brand-color:${brandColor};
    --hm-brand-color-deep: color-mix(in srgb, var(--hm-brand-color) 45%, #1f2937);
    --hm-brand-color-soft: color-mix(in srgb, var(--hm-brand-color) 25%, transparent);
    --hm-brand-logo-url:${brandLogo ? `url(${brandLogo})` : 'none'};
  }
  @page { size: A4 portrait; margin: 0; }
  *{box-sizing:border-box;font-family:DejaVu Sans,Helvetica,Arial,sans-serif}
  html,body{margin:0;padding:0;background:#fff;color:#111}
  .pdf-page{position:relative;width:210mm;min-height:297mm;padding:12mm 12mm 20mm;page-break-after:always;break-after:page;background:#fff;overflow:hidden}
  .pdf-page:last-child{page-break-after:auto;break-after:auto}
  .section-break{break-before:page;page-break-before:always}
  .section-break:first-child{break-before:auto;page-break-before:auto}
  .recipe-avoid-break{break-inside:avoid;page-break-inside:avoid}
  .doc-header{border-bottom:3px solid #111;padding-bottom:8px;margin-bottom:14px}
  .doc-header h1{margin:0 0 2px;font-size:20px;color:#111;font-weight:700}
  .brand-note{float:right;text-align:right;font-size:8px;color:#555;padding-top:2px}
  .doc-header--recipe{border-bottom:0;margin-bottom:12px;text-align:center}
  .doc-header--recipe h1{margin:0;font-size:18pt;letter-spacing:.02em;text-transform:uppercase;color:var(--hm-brand-color)}
  .recipe-subtitle{margin-top:6px;font-size:11pt;color:#000}
  .section-title{font-size:12px;font-weight:700;color:#111;margin:14px 0 8px;padding-bottom:4px;border-bottom:1px solid #ececec;text-transform:uppercase;letter-spacing:.04em}
  .weekly-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px 22px}
  .day-card{break-inside:avoid;page-break-inside:avoid}
  .day-title{margin:0 0 8px;font-size:16px;line-height:1;font-weight:800;color:#36544e;text-transform:uppercase}
  .meal-row{display:flex;align-items:flex-start;gap:8px;margin-bottom:10px}
  .meal-images{width:62px;display:flex;flex-direction:column;gap:2px;flex:0 0 62px}
  .meal-images img{width:62px;height:36px;object-fit:cover;display:block}
  .meal-images img.item-taken{filter:grayscale(100%);opacity:.55}
  .meal-image-fallback{width:62px;height:36px;background:#fafafa;border:1px solid #eee}
  .meal-copy{min-width:0;flex:1}
  .meal-name{font-size:11px;font-weight:800;color:#36544e;text-transform:uppercase;margin-bottom:2px}
  .meal-desc{font-size:8px;line-height:1.3;color:#111}
  .item-taken{color:#bbb;text-decoration:line-through}
  .nutrition-reference-page{background:#fff}
  .nutrition-day-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px 18px;padding-right:6mm}
  .nutrition-day-card{break-inside:avoid;page-break-inside:avoid;margin-bottom:12px}
  .nutrition-day-title{margin:0 0 6px;font-size:12pt;font-weight:700;color:#36544e;line-height:1}
  .nutrition-macros-line{display:flex;gap:4px 8px;flex-wrap:wrap;margin-bottom:6px;font-size:6.6pt;font-weight:700;line-height:1.12}
  .macro-carb,.macro-protein,.macro-fat{white-space:nowrap}
  .macro-carb{color:#b279eb}.macro-protein{color:#3afe72}.macro-fat{color:#e79ccd}
  .nutrition-rows{font-size:6.8pt;line-height:1.22;color:#000}
  .nutrition-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:3px}
  .nutrition-cell{font-weight:400}
  .nutrition-bold{font-weight:700}
  .lista-grid{width:100%;border-collapse:collapse}.lista-grid td{width:50%;vertical-align:top;padding:0 8px 0 0}
  .lista-category{margin-bottom:11px;break-inside:avoid;page-break-inside:avoid}.lista-cat-title{font-size:10px;font-weight:700;color:#111;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #ececec;padding-bottom:3px;margin-bottom:5px}
  .lista-items{width:100%}
  .lista-item-row{display:flex;align-items:flex-start;gap:4px;margin:0 0 3px;font-size:9px;line-height:1.18}
  .checkbox-cell{display:inline-block;width:12px;min-width:12px;text-align:center}
  .item-amount{display:inline-block;color:#555;font-size:8px;font-weight:700;min-width:46px;white-space:nowrap}
  .item-name{display:inline-block;flex:1 1 auto;text-align:left}
  .page-cover{
    padding:0;
    background:#efefef;
    position:relative;
  }
  .page-cover::before{
    content:'';
    position:absolute;
    left:0;
    top:0;
    width:100%;
    height:50%;
    background:var(--hm-brand-color-soft);
    pointer-events:none;
    z-index:0;
  }
  .page-cover > *{position:relative;z-index:1}
  .cover-photo{
    height:74vh;
    padding:34px 26px 0;
    background:transparent;
  }
  .cover-photo img{width:100%;height:100%;object-fit:cover;display:block}
  .cover-footer{
    height:26vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    padding-top:18px;
    background:transparent;
  }
  .cover-logo{
    order:5;
    margin-top:8px;
    height:9mm;
    max-width:30mm;
    object-fit:contain;
    display:block;
  }
  .cover-title{
    font-size:11.5pt;
    line-height:1.1;
    letter-spacing:.06em;
    margin:0 0 7px;
    text-transform:uppercase;
    font-weight:700;
    color:#1f5d6d;
    text-align:center;
  }
  .cover-rule{
    width:280px;
    max-width:70%;
    height:2px;
    background:#444;
    margin:0 0 8px;
  }
  .cover-brand{
    font-size:10.5pt;
    line-height:1.1;
    font-weight:700;
    color:#1f5d6d;
    margin-bottom:5px;
  }
  .cover-email{
    font-size:10.5pt;
    line-height:1.1;
    font-weight:800;
    color:#101010;
    margin-bottom:0;
  }
  .recipe-top-image{width:100%;height:357pt;overflow:hidden;margin:0 0 12px;position:relative}
  /* Background overlay treatment (legacy-style) — full-page band behind the image page only. */
  .recipe-page-primary{position:relative}
  .recipe-page-primary::before{
    content:'';
    position:absolute;
    left:0;
    top:0;
    width:100%;
    height:357pt;
    background:var(--hm-brand-color-soft);
    pointer-events:none;
    z-index:0;
  }
  .recipe-page-primary > *{position:relative;z-index:1}
  .recipe-top-image img{width:100%;height:100%;object-fit:cover;display:block}
  .recipe-content-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;align-items:start}
  .recipe-content-grid-top{margin-bottom:20mm}
  .recipe-content-grid-bottom{margin-bottom:20mm}
  .recipe-grid-card{break-inside:avoid;page-break-inside:avoid;min-height:120px}
  .recipe-grid-card .section-title{margin-top:0}
  .recipe-grid-card p{margin:0;font-size:9px;line-height:1.35}
  .tip-block{margin:0 0 10px}
  .tip-title{margin:0 0 2px;font-size:10px;line-height:1.2;font-weight:700;color:#36544e}
  .tip-desc{margin:0;font-size:9px;line-height:1.35}
  .ingredient-list,.instruction-list{margin:0;padding-left:0;list-style:none}.ingredient-list li,.instruction-list li{margin:0 0 4px}
  .ingredient-list li{display:grid;grid-template-columns:90px 1fr;gap:10px;font-size:9px;line-height:1.25}
  .ingredient-list .ing-amount{font-weight:800;color:#000;white-space:nowrap}
  .ingredient-list .ing-name{color:#000}
  .instruction-list{padding-left:18px;list-style:decimal}
  .instruction-list li{font-size:9px;line-height:1.3}
  .right{text-align:right;white-space:nowrap}
  .nutrition-table{width:100%;border-collapse:collapse;font-size:9px}.nutrition-table td{padding:5px 0;border-bottom:1px solid rgba(0,0,0,.06)}
  .pdf-footer{position:absolute;left:0;right:0;bottom:0;height:14mm;display:flex;align-items:stretch;gap:0}
  .pdf-footer__logo{flex:0 0 auto;display:flex;align-items:center;justify-content:flex-start;padding:0 0 0 12mm}
  .pdf-footer__logo img{max-height:12mm;max-width:34mm;object-fit:contain;display:block}
  .pdf-footer__spacer{flex:1 1 auto}
  .pdf-footer__right{flex:0 0 52%;background:var(--hm-brand-color-soft);padding:0 12mm;display:flex;flex-direction:column;justify-content:center;gap:1mm;align-items:flex-start}
  .pdf-footer__line{display:flex;flex-wrap:wrap;gap:6px;align-items:baseline}
  .pdf-footer__name{font-weight:800;color:var(--hm-brand-color);font-size:9pt;line-height:1}
  .pdf-footer__email{color:#000;font-size:8.5pt;line-height:1}
  </style>`;
}

module.exports = { renderStyles };
