const { esc, dayDefaultLabels, mealDefaultLabels } = require('./utils');
const { renderFooter } = require('./footer');

function renderWeeklyPlan(model) {
  if (!model.weeklyPlan?.days?.length) return '';

  const dayLabels = dayDefaultLabels();
  const mealLabels = mealDefaultLabels();
  const days = model.weeklyPlan.days;
  const mealOrder = ['meal_1', 'meal_2', 'meal_3', 'meal_4', 'meal_5', 'meal_6'];

  const dayCards = days.map((d) => {
    const mealBlocks = mealOrder
      .map((mealKey) => {
        const items = d.meals?.[mealKey] || [];
        if (!items.length) return '';

        const images = items
          .filter((item) => !!item.image)
          .map((item) => {
            const cls = item.leftover ? 'item-taken' : '';
            return `<img class="${cls}" src="${esc(item.image)}" alt="${esc(item.title)}" />`;
          })
          .join('');

        const description = items
          .map((item) => {
            const qty = item.racion && Number(item.racion) > 1 ? `x${esc(item.racion)} ` : '';
            const cls = item.leftover ? 'item-taken' : '';
            return `<span class="${cls}">${qty}${esc(item.title)}</span>`;
          })
          .join(' , ');

        return `<div class="meal-row">
          <div class="meal-images">${images || '<div class="meal-image-fallback"></div>'}</div>
          <div class="meal-copy">
            <div class="meal-name">${esc(mealLabels[mealKey] || mealKey)}</div>
            <div class="meal-desc">${description}</div>
          </div>
        </div>`;
      })
      .filter(Boolean)
      .join('');

    return `<article class="day-card">
      <h3 class="day-title">${esc(dayLabels[d.dayKey] || d.dayKey)}</h3>
      ${mealBlocks}
    </article>`;
  }).join('');

  return `<section class="pdf-page section-break">
    <div class="section-title">Calendario Semanal</div>
    <div class="weekly-grid">${dayCards}</div>
    ${renderFooter(model)}
  </section>`;
}

module.exports = { renderWeeklyPlan };
