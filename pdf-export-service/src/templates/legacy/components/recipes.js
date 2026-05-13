const { esc } = require('./utils');
const { renderFooter } = require('./footer');

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
      ${footer}
    </section>

    <section class="pdf-page section-break recipe-page-secondary">
      <div class="doc-header"><div class="brand-note">Healthy Martina</div><h1>${esc(r.title)}</h1></div>
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
    </section>`;
  }).join('');
}

module.exports = { renderRecipes };
