const { esc } = require('./utils');

function renderFooterStyles() {
  return `
  .pdf-footer{position:absolute;left:0;right:0;bottom:0;height:14mm;display:flex;align-items:stretch;gap:0}
  .pdf-footer__logo{flex:0 0 auto;display:flex;align-items:center;justify-content:flex-start;padding:0 0 0 12mm}
  .pdf-footer__logo img{max-height:12mm;max-width:34mm;object-fit:contain;display:block}
  .pdf-footer__spacer{flex:1 1 auto}
  .pdf-footer__right{flex:0 0 52%;background:var(--hm-brand-color-soft);padding:0 12mm;display:flex;flex-direction:column;justify-content:center;gap:1mm;align-items:flex-start}
  .pdf-footer__line{display:flex;flex-wrap:wrap;gap:6px;align-items:baseline}
  .pdf-footer__name{font-weight:800;color:var(--hm-brand-color);font-size:9pt;line-height:1}
  .pdf-footer__email{color:#000;font-size:8.5pt;line-height:1}`;
}

function renderFooter(model) {
  const brandName = model?.cover?.brandName || 'Healthy Martina';
  const brandEmail = model?.cover?.brandEmail || 'cristina@healthymartina.com';
  const brandLogo = model?.cover?.brandLogo || '';

  return `<div class="pdf-footer">
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
