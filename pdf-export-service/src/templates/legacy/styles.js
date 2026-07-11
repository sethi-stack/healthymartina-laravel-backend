const { renderCoverStyles } = require('./components/cover');
const { renderWeeklyPlanStyles } = require('./components/weeklyPlan');
const { renderNutritionSummaryStyles } = require('./components/nutrition');
const { renderListaStyles } = require('./components/lista');
const { renderRecipeStyles } = require('./components/recipes');
const { renderFooterStyles } = require('./components/footer');

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
  const sectionStyles = [
    renderCoverStyles(),
    renderWeeklyPlanStyles(),
    renderNutritionSummaryStyles(),
    renderListaStyles(),
    renderRecipeStyles(),
    renderFooterStyles(),
  ].join('\n');

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
  .pdf-page:last-of-type{page-break-after:auto;break-after:auto}
  .section-break{break-before:page;page-break-before:always}
  .section-break:first-child{break-before:auto;page-break-before:auto}
  .recipe-avoid-break{break-inside:avoid;page-break-inside:avoid}
  .doc-header{border-bottom:3px solid #111;padding-bottom:8px;margin-bottom:14px}
  .doc-header h1{margin:0 0 2px;font-size:20px;color:var(--hm-brand-color);font-weight:700}
  .brand-note{float:right;text-align:right;font-size:8px;color:#555;padding-top:2px}
  .section-title{font-size:12px;font-weight:700;color:var(--hm-brand-color);margin:14px 0 8px;padding-bottom:4px;border-bottom:1px solid #ececec;text-transform:uppercase;letter-spacing:.04em}
  .item-taken{color:#bbb;text-decoration:line-through}
  .right{text-align:right;white-space:nowrap}
  ${sectionStyles}
  </style>`;
}

module.exports = { renderStyles };
