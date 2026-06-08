const { esc } = require('./utils');
const { renderFooter } = require('./footer');

function renderRecipeStyles() {
  return `
  .doc-header--recipe{border-bottom:0;margin-bottom:12px;text-align:center}
  .doc-header--recipe h1{margin:0;font-size:16px;letter-spacing:.02em;text-transform:uppercase;color:var(--hm-brand-color)}
  .recipe-subtitle{margin-top:6px;font-size:12px;color:#000}
  .recipe-top-image{width:100%;height:357pt;overflow:hidden;margin:0 0 12px;position:relative}
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
  .recipe-content-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 22px;align-items:start}
  .recipe-content-grid-top{margin-bottom:20mm}
  .recipe-content-grid-inline-secondary{margin-top:8mm;margin-bottom:20mm}
  .recipe-content-grid-bottom{margin-bottom:20mm}
  .recipe-grid-card{break-inside:avoid;page-break-inside:avoid;min-height:120px;padding-right:4px}
  .recipe-grid-card .section-title{margin-top:0;font-size:12px}
  .recipe-grid-card p{margin:0;font-size:10px;line-height:1.35}
  .tip-block{margin:0 0 12px;padding:2px 0}
  .tip-title{margin:0 0 4px;font-size:11px;line-height:1.2;font-weight:700;color:var(--hm-brand-color)}
  .tip-desc{margin:0;font-size:10px;line-height:1.45}
  .ingredient-list,.instruction-list{margin:0;padding-left:0;list-style:none}.ingredient-list li,.instruction-list li{margin:0 0 7px}
  .ingredient-list li{display:grid;grid-template-columns:96px 1fr;gap:12px;padding:2px 0;font-size:11px;line-height:1.35}
  .ingredient-list .ing-amount{font-weight:800;color:#000;white-space:nowrap;font-size:11px}
  .ingredient-list .ing-name{color:#000;font-size:11px}
  .instruction-list{padding-left:20px;list-style:decimal}
  .instruction-list li{font-size:12px;line-height:1.42;padding-left:2px}
  .nutrition-table{width:100%;border-collapse:collapse;font-size:9px}.nutrition-table td{padding:3px 0;border-bottom:1px solid rgba(0,0,0,.06)}`;
}

function nl2br(value) {
  return esc(value || '').replace(/\n/g, '<br/>');
}

function gcd(a, b) {
  let x = Math.abs(a);
  let y = Math.abs(b);
  while (y) {
    const t = y;
    y = x % y;
    x = t;
  }
  return x || 1;
}

function toFraction(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return null;

  const sign = n < 0 ? '-' : '';
  const abs = Math.abs(n);
  const whole = Math.floor(abs + 1e-9);
  const frac = abs - whole;
  if (frac < 1e-6) return `${sign}${whole}`;

  const denominators = [2, 3, 4, 8, 16];
  let best = null;
  for (const d of denominators) {
    const num = Math.round(frac * d);
    const approx = num / d;
    const err = Math.abs(frac - approx);
    if (!best || err < best.err) {
      best = { num, den: d, err };
    }
  }

  if (!best || best.num === 0) return `${sign}${whole}`;
  if (best.num === best.den) return `${sign}${whole + 1}`;

  const div = gcd(best.num, best.den);
  const num = best.num / div;
  const den = best.den / div;

  if (whole > 0) return `${sign}${whole} ${num}/${den}`;
  return `${sign}${num}/${den}`;
}

function formatAmountWithFractions(raw) {
  const text = String(raw || '').trim();
  if (!text) return '';

  const match = text.match(/^(-?\d+(?:[.,]\d+)?)\s*(.*)$/);
  if (!match) return esc(text);

  const num = match[1].replace(',', '.');
  const rest = String(match[2] || '').trim();
  const frac = toFraction(num);
  if (!frac) return esc(text);

  return esc(`${frac}${rest ? ` ${rest}` : ''}`);
}

function estimateSecondaryContentWeight(nutrition, tipsBlocks, fallbackTips) {
  const nutritionWeight = (nutrition || []).length * 1.35;
  const tipsWeight = (tipsBlocks || []).reduce((sum, tip) => {
    const titleWords = String(tip?.title || '').trim().split(/\s+/).filter(Boolean).length;
    const descWords = String(tip?.description || '').trim().split(/\s+/).filter(Boolean).length;
    return sum + 1 + (titleWords / 8) + (descWords / 18);
  }, 0);
  const fallbackTipsWeight = fallbackTips
    ? String(fallbackTips).trim().split(/\s+/).filter(Boolean).length / 18
    : 0;

  return nutritionWeight + tipsWeight + fallbackTipsWeight;
}

function renderRecipes(model, options = {}) {
  const onRecipeRendered = typeof options.onRecipeRendered === 'function'
    ? options.onRecipeRendered
    : null;
  const recipes = model.recipes || [];
  const footer = renderFooter(model);
  if (!recipes.length) return '';

  return recipes.map((r, index) => {
    if (onRecipeRendered) {
      onRecipeRendered(index + 1, recipes.length, r);
    }
    const ingredients = (r.ingredients || []).map((i) => `<li><span class="ing-amount">${formatAmountWithFractions(i.amount)}</span><span class="ing-name">${esc(i.name)}</span></li>`).join('');
    const instructions = (r.instructions || []).map((s) => `<li>${esc(s)}</li>`).join('');
    const nutrition = (r.nutrition || []).map((n) => `<tr><td>${esc(n.name)}</td><td class="right">${esc(n.amount || '')}</td></tr>`).join('');
    const tipsBlocks = (r.tipsBlocks || []).length
      ? (r.tipsBlocks || []).map((tip) => `<div class="tip-block">
          ${tip.title ? `<h3 class="tip-title">${esc(tip.title)}</h3>` : ''}
          ${tip.description ? `<p class="tip-desc">${nl2br(tip.description)}</p>` : ''}
        </div>`).join('')
      : (r.tips ? `<p class="tip-desc">${nl2br(r.tips)}</p>` : '');
    const secondaryWeight = estimateSecondaryContentWeight(r.nutrition || [], r.tipsBlocks || [], r.tips || '');
    const canInlineSecondary = secondaryWeight > 0 && secondaryWeight <= 12;

    const metaPorciones = r.porciones != null ? `${esc(r.porciones)} porciones` : '';
    const metaMinutos = r.minutos != null && r.minutos > 0 ? `${esc(r.minutos)} minutos` : '';
    const metaLine = [metaPorciones, metaMinutos].filter(Boolean).join('  ');

    return `<section class="pdf-page section-break recipe-page-primary">
      <div class="doc-header doc-header--recipe">
        <h1>${esc(r.title)}</h1>
        ${metaLine ? `<div class="recipe-subtitle"><strong>${metaPorciones}</strong>${metaMinutos ? `&nbsp;&nbsp;${metaMinutos}` : ''}</div>` : ''}
      </div>
      <div class="recipe-top-image"><img src="${esc(r.image || '')}" alt="${esc(r.title)}" /></div>
      <div class="recipe-content-grid recipe-content-grid-top">
        <article class="recipe-grid-card">
          <h2 class="section-title">Ingredientes</h2>
          <ul class="ingredient-list">${ingredients}</ul>
        </article>
        <article class="recipe-grid-card">
          <h2 class="section-title">Instrucciones</h2>
          <ol class="instruction-list">${instructions}</ol>
        </article>
      </div>
      ${canInlineSecondary ? `<div class="recipe-content-grid recipe-content-grid-inline-secondary">
        <article class="recipe-grid-card">
          <h2 class="section-title">Información nutricional</h2>
          <table class="nutrition-table">${nutrition}</table>
        </article>
        <article class="recipe-grid-card">
          <h2 class="section-title">Tips</h2>
          ${tipsBlocks}
        </article>
      </div>` : ''}
      ${footer}
    </section>${canInlineSecondary ? '' : `

    <section class="pdf-page section-break recipe-page-secondary">
      <div class="doc-header"><div class="brand-note">${esc(model?.cover?.brandName || 'Healthy Martina')}</div><h1>${esc(r.title)}</h1></div>
      <div class="recipe-content-grid recipe-content-grid-bottom">
        <article class="recipe-grid-card">
          <h2 class="section-title">Información nutricional</h2>
          <table class="nutrition-table">${nutrition}</table>
        </article>
        <article class="recipe-grid-card">
          <h2 class="section-title">Tips</h2>
          ${tipsBlocks}
        </article>
      </div>
      ${footer}
    </section>`}`;
  }).join('');
}

module.exports = { renderRecipes, renderRecipeStyles };
