const { esc } = require('./utils');

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

module.exports = { renderFooter };
