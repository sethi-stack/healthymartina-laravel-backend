const { esc } = require("./utils");

function renderCoverStyles() {
    return `
  .page-cover{
    padding:0;
    background:#fff;
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
    height:60vh;
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
    padding-top:28px;
    background:transparent;
  }
  .cover-logo{
    order:5;
    margin-top:8px;
    height:18mm;
    max-width:60mm;
    object-fit:contain;
    display:block;
  }
  .cover-title{
    font-size:26px;
    line-height:1.08;
    letter-spacing:.06em;
    margin:0 0 9px;
    text-transform:uppercase;
    font-weight:700;
    color:var(--hm-brand-color);
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
    font-size:21px;
    line-height:1.08;
    font-weight:700;
    color:var(--hm-brand-color);
    margin-bottom:6px;
  }
  .cover-email{
    font-size:21px;
    line-height:1.08;
    font-weight:800;
    color:#101010;
    margin-bottom:0;
  }`;
}

function renderCover(model) {
    if (!model.cover) return "";
    const c = model.cover;

    return `<section class="pdf-page page-cover section-break">
    <div class="cover-photo"><img src="${esc(c.image)}" alt="${esc(c.title)}" /></div>
    <div class="cover-footer">
      <h1 class="cover-title">${esc(c.title)}</h1>
      <div class="cover-rule"></div>
      <div class="cover-brand">${esc(c.brandName)}</div>
      <div class="cover-email">${esc(c.brandEmail)}</div>
      ${c.brandLogo ? `<img class="cover-logo" src="${esc(c.brandLogo)}" alt="logo" />` : ""}
    </div>
  </section>`;
}

module.exports = { renderCover, renderCoverStyles };
