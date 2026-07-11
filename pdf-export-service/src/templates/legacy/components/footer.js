const { esc } = require('./utils');

function renderFooterStyles() {
  return `
  .pdf-footer{position:absolute;left:0;right:0;bottom:0;height:14mm;display:flex;align-items:stretch;gap:0}
  .pdf-footer--flow{position:fixed;left:0;right:0;bottom:0;height:14mm;display:flex;align-items:stretch;gap:0;z-index:10}
  .pdf-footer--compact{height:10.5mm}
  .pdf-footer--flow.pdf-footer--compact{height:10.5mm}
  .pdf-footer__logo{flex:0 0 auto;display:flex;align-items:center;justify-content:flex-start;padding:0 0 0 12mm}
  .pdf-footer__logo img{max-height:12mm;max-width:34mm;object-fit:contain;display:block}
  .pdf-footer--compact .pdf-footer__logo{padding-left:10mm}
  .pdf-footer--flow .pdf-footer__logo{padding-left:12mm}
  .pdf-footer--compact .pdf-footer__logo img{max-height:8.5mm;max-width:24mm}
  .pdf-footer__spacer{flex:1 1 auto}
  .pdf-footer__right{flex:0 0 52%;background:var(--hm-brand-color-soft);padding:0 12mm;display:flex;flex-direction:column;justify-content:center;gap:1mm;align-items:flex-start}
  .pdf-footer--compact .pdf-footer__right{padding:0 10mm;gap:.5mm}
  .pdf-footer--flow .pdf-footer__right{padding:0 12mm}
  .pdf-footer__line{display:flex;flex-wrap:wrap;gap:6px;align-items:baseline}
  .pdf-footer__name{font-weight:800;color:var(--hm-brand-color);font-size:9pt;line-height:1}
  .pdf-footer__email{color:#000;font-size:8.5pt;line-height:1}
  .pdf-footer--compact .pdf-footer__line{gap:5px}
  .pdf-footer--compact .pdf-footer__name{font-size:7.5pt}
  .pdf-footer--compact .pdf-footer__email{font-size:7pt}`;
}

function renderFooter(model, options = {}) {
  const brandName = model?.cover?.brandName || 'Healthy Martina';
  const brandEmail = model?.cover?.brandEmail || 'cristina@healthymartina.com';
  const brandLogo = model?.cover?.brandLogo || '';
  const compactClass = options.compact ? ' pdf-footer--compact' : '';
  const flowClass = options.layout === 'flow' ? ' pdf-footer--flow' : '';

  return `<div class="pdf-footer${compactClass}${flowClass}">
    <div class="pdf-footer__logo">
      ${brandLogo ? `<img src="${esc(brandLogo)}" alt="logo" />` : ''}
    </div>
    <div class="pdf-footer__spacer"></div>
    <div class="pdf-footer__right">
      <div class="pdf-footer__line">
        <span class="pdf-footer__name">${esc(brandName)}</span>
        <span class="pdf-footer__email">${esc(brandEmail)}</span>
      </div>
    </div>
  </div>`;
}

module.exports = { renderFooter, renderFooterStyles };
