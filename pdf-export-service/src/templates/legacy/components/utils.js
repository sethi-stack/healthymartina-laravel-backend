function esc(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function dayDefaultLabels() {
  return {
    day_1: 'Lunes',
    day_2: 'Martes',
    day_3: 'Miércoles',
    day_4: 'Jueves',
    day_5: 'Viernes',
    day_6: 'Sábado',
    day_7: 'Domingo',
  };
}

function mealDefaultLabels() {
  return {
    meal_1: 'Desayuno',
    meal_2: 'Lunch',
    meal_3: 'Comida',
    meal_4: 'Snack',
    meal_5: 'Cena',
    meal_6: 'Otros',
  };
}

function uniqueRecipePages(recipePages) {
  const seen = new Set();
  return (Array.isArray(recipePages) ? recipePages : []).filter((page) => {
    const recipeId = page?.recipe?.id != null ? String(page.recipe.id) : '';
    if (!recipeId || seen.has(recipeId)) {
      return false;
    }
    seen.add(recipeId);
    return true;
  });
}

module.exports = { esc, dayDefaultLabels, mealDefaultLabels, uniqueRecipePages };
