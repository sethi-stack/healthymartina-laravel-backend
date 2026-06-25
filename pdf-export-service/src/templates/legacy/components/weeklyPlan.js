const { esc, dayDefaultLabels, mealDefaultLabels } = require('./utils');
const { renderFooter } = require('./footer');

function renderWeeklyPlanStyles() {
  return `
  .calendar-page{padding-bottom:24mm}
  .weekly-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px 22px}
  .day-card{break-inside:avoid;page-break-inside:avoid}
  .day-title{margin:0 0 8px;font-size:16px;line-height:1;font-weight:800;color:var(--hm-brand-color);text-transform:uppercase}
  .meal-row{display:flex;align-items:flex-start;gap:8px;margin-bottom:10px}
  .meal-images{width:62px;display:flex;flex-direction:column;gap:2px;flex:0 0 62px}
  .meal-images img{width:62px;height:36px;object-fit:cover;display:block}
  .meal-images img.item-taken{filter:grayscale(100%);opacity:.55}
  .meal-image-fallback{width:62px;height:36px;background:#fafafa;border:1px solid #eee}
  .meal-copy{min-width:0;flex:1}
  .meal-name{font-size:11px;font-weight:800;color:var(--hm-brand-color);text-transform:uppercase;margin-bottom:2px}
  .meal-desc{font-size:8px;line-height:1.3;color:#111}
  .calendar-page--dense{padding:10mm 10mm 18mm}
  .calendar-page--dense .section-title{margin:8px 0 6px;font-size:11px}
  .calendar-page--dense .weekly-grid{gap:10px 16px}
  .calendar-page--dense .day-title{margin-bottom:6px;font-size:14px}
  .calendar-page--dense .meal-row{gap:6px;margin-bottom:6px}
  .calendar-page--dense .meal-images{width:54px;flex-basis:54px}
  .calendar-page--dense .meal-images img,
  .calendar-page--dense .meal-image-fallback{width:54px;height:32px}
  .calendar-page--dense .meal-name{font-size:10px;margin-bottom:1px}
  .calendar-page--dense .meal-desc{font-size:7.2px;line-height:1.18}`;
}

function estimateCalendarDensity(days, mealOrder) {
  return days.reduce((score, day) => {
    const mealScore = mealOrder.reduce((acc, mealKey) => {
      const items = day.meals?.[mealKey] || [];
      if (!items.length) return acc;

      const descriptionLength = items.reduce((sum, item) => {
        return sum + String(item.title || '').length + (item.racion ? 4 : 0);
      }, 0);

      return acc + 1.2 + Math.ceil(descriptionLength / 42) * 0.45 + Math.max(0, items.length - 1) * 0.35;
    }, 0);

    return score + mealScore + 0.8;
  }, 0);
}

function renderWeeklyPlan(model) {
  if (!model.weeklyPlan?.days?.length) return '';

  const dayLabels = { ...dayDefaultLabels(), ...(model.weeklyPlan?.dayLabels || {}) };
  const mealLabels = { ...mealDefaultLabels(), ...(model.weeklyPlan?.mealLabels || {}) };
  const days = model.weeklyPlan.days;
  const mealOrder = ['meal_1', 'meal_2', 'meal_3', 'meal_4', 'meal_5', 'meal_6'];
  const denseMode = estimateCalendarDensity(days, mealOrder) >= 31;

  const dayCards = days.map((d) => {
    const mealBlocks = mealOrder
      .map((mealKey) => {
        const meal = d.meals?.[mealKey] || null;
        const items = meal?.items || [];
        if (!items.length) return '';

        const images = items
          .filter((item) => !!item.image)
          .map((item) => {
            const cls = item.leftover ? 'item-taken' : '';
            return `<img class="${cls}" data-pdf-calendar-image="1" src="${esc(item.image)}" alt="${esc(item.title)}" />`;
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
            <div class="meal-name">${esc(meal?.label || mealLabels[mealKey] || mealKey)}</div>
            <div class="meal-desc">${description}</div>
          </div>
        </div>`;
      })
      .filter(Boolean)
      .join('');

    return `<article class="day-card">
      <h3 class="day-title">${esc(d.label || dayLabels[d.dayKey] || d.dayKey)}</h3>
      ${mealBlocks}
    </article>`;
  }).join('');

  const pageClass = denseMode ? 'pdf-page section-break calendar-page calendar-page--dense' : 'pdf-page section-break calendar-page';
  return `<section class="${pageClass}">
    <div class="section-title">Calendario Semanal</div>
    <div class="weekly-grid">${dayCards}</div>
    ${renderFooter(model, { compact: denseMode })}
  </section>`;
}

module.exports = { renderWeeklyPlan, renderWeeklyPlanStyles };
