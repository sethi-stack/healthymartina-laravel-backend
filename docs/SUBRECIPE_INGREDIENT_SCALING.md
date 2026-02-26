# Sub-Recipe Ingredient Scaling

This document explains the data flow, scaling formula, and existing logic for sub-recipe ingredients in the Lista (shopping list) system.

---

## The Problem

When a recipe uses another recipe as an ingredient (sub-recipe), the shopping list must display the correct **scaled quantity** of each sub-recipe ingredient — based on how many servings of the **main recipe** the user selected — not the sub-recipe's full default yield.

**Example:**
- Recipe: *Ensalada de Pollo con Yogur Griego* — default: 2 porciones; user selects: 1 porción
- Sub-recipe ingredient: *Pollo Deshebrado HM* — this recipe is used as `1 tz` per 2 porciones of Ensalada
- *Pollo Deshebrado HM* has a default yield of `3 tz`

The shopping list should show **1/6 of each Pollo ingredient** (1 tz used × 1/2 serving × 1/3 of Pollo's yield), **not** the full Pollo default.

---

## PHP Backend: Data Structure

### `getCategoriaIngredientes()` — `app/Models/Receta.php`

Called per recipe per category when building the lista. Parameters:
- `$get_servings` — user's selected servings for the main recipe
- `$porcion` — main recipe's default portions (`getPorciones()['cantidad']`)

**For a sub-recipe ingredient (`$rir->subreceta` exists):**

1. A `$subreceta` descriptor is built:
   ```php
   $subreceta = [
       'cantidad'       => $rir->rirm[0]->cantidad,           // e.g. 1 tz (how much sub-recipe the main recipe calls for)
       'porcion'        => $rir->subreceta->getPorciones()['cantidad'],   // e.g. 3 tz (sub-recipe's own default yield)
       'nombre_english' => $rir->subreceta->getPorciones()['nombre_english'],
       'medida_english' => ...,
       ...
   ];
   ```

2. The sub-recipe's own ingredients are fetched **recursively**, passing the parent's `$get_servings` and `$porcion` unchanged:
   ```php
   $subreceta_ingres = $rir->subreceta->getCategoriaIngredientes(
       $categoria_id, $meal, $day, $schedule,
       $get_servings,  // parent's selected servings (not yet scaled)
       $porcion,       // parent's default portions
       false,
       $subreceta
   );
   ```

3. The **scaled amount** of the sub-recipe needed is computed:
   ```php
   $recipe_porcion = $sub_receta->getPorciones()['cantidad'];  // parent's default (e.g. 2)
   $get_recipe_cantidad = round($rir->rirm[0]->cantidad * $get_servings / $recipe_porcion, 2);
   // e.g. 1 tz × 1 serving / 2 default = 0.5 tz of Pollo needed
   ```

4. Each sub-recipe ingredient is stored with:

   | Field | Value | Meaning |
   |-------|-------|---------|
   | `cantidad` | raw ingredient quantity from sub-recipe definition | e.g. 300g chicken in Pollo recipe |
   | `get_servings` | `$get_recipe_cantidad` | **Already scaled**: how much of the sub-recipe is needed (e.g. 0.5 tz) |
   | `porcion` | parent recipe's `$porcion` | parent's default portions (not sub-recipe's) |
   | `subrecipe.cantidad` | `$rir->rirm[0]->cantidad` | how much sub-recipe the main recipe calls for (e.g. 1 tz) |
   | `subrecipe.porcion` | sub-recipe's own default yield | e.g. 3 tz |

---

## The Correct Scaling Formula

### Regular ingredients
```
displayed_qty = cantidad × get_servings / porcion
```

### Sub-recipe ingredients
```
displayed_qty = cantidad × get_servings / subrecipe.porcion
```

Where `get_servings` is already `subrecipe_qty_in_recipe × user_servings / parent_default_portions`.

**Worked example** (Pollo ingredient: 300g chicken):
```
get_recipe_cantidad = 1 tz × 1 selected / 2 default = 0.5
displayed_qty = 300g × 0.5 / 3 = 50g chicken
```

---

## Existing JavaScript Logic (Correct — Not Changed)

### `subRecipeItem()` — `react-front-app/src/utils/subrecipes/subRecipeUtils.js`

The old jQuery-era utility already implements the correct formula. For the "direct calculation" branch (when units are compatible and match the sub-recipe's base unit):

```javascript
// Direct calculation
item.cantidad = (item.cantidad * item.get_servings) / item.subrecipe.porcion;
item.get_servings = 1;
item.porcion = 1;
```

This is mathematically equivalent to `computeScaledQty` in the React Lista page.

### `processListaData()` — `react-front-app/src/services/list-processing/listProcessingService.js`

Calls `subRecipeItem()` for each ingredient with a `subrecipe` object. This service was **never invoked** in the React Lista page — only used by the deprecated jQuery flow.

---

## The Bug and Fix (React Lista Page)

### Bug 1 — Missing scaling entirely

**Root cause**: `Lista.jsx` originally displayed raw `ing.cantidad` for all ingredients without any scaling formula. Sub-recipe ingredients showed the sub-recipe's full default yield instead of the scaled amount.

**Initial fix**: Added `computeScaledQty()` with the formula `(rawQty × getServings) / divisor`.

---

### Bug 2 — Edge case: user selects default porciones (the critical fix)

**Root cause**: A subtle ordering error inside `computeScaledQty`. When the user selects the parent recipe's **default** number of porciones (e.g., 2 of 2), the PHP backend computes:

```
get_recipe_cantidad = subrecipe_qty_in_recipe × user_servings / parent_default
                    = 1 tz × 2 / 2 = 1
```

`get_servings` is `1` — the numeric value `1`, but it means "1 tz of the sub-recipe is needed". The early-return guard `if (getServings === 1) return rawQty` was meant to skip scaling for manual items with no servings data, but it incorrectly fired here — returning the full raw ingredient quantity (3 tz worth of ingredients) instead of `rawQty × 1 / 3`.

**Symptom**: Lista showed full sub-recipe ingredient amounts even though the user's selection called for exactly 1/3 of the sub-recipe.

#### Before (buggy)

```javascript
const computeScaledQty = (ing) => {
    const rawQty = parseFloat(ing.cantidad) || 0;
    const getServings = parseFloat(ing.get_servings);
    if (!getServings || getServings === 1) {   // ← fires when get_recipe_cantidad === 1
        return rawQty;                          // ← returns full quantity — WRONG for sub-recipes
    }
    if (ing.subrecipe && typeof ing.subrecipe === 'object') {
        const subPorcion = parseFloat(ing.subrecipe.porcion) || 1;
        return (rawQty * getServings) / subPorcion;
    }
    const porcion = parseFloat(ing.porcion) || 1;
    return (rawQty * getServings) / porcion;
};
```

#### After (fixed)

```javascript
const computeScaledQty = (ing) => {
    const rawQty = parseFloat(ing.cantidad) || 0;
    // Subrecipe check MUST come before the getServings===1 guard:
    // when user selects the parent recipe's default servings, get_recipe_cantidad
    // equals exactly 1 (e.g. 1 tz), but we still need to divide by the
    // sub-recipe's own yield (e.g. 3 tz) to get the correct fraction.
    if (ing.subrecipe && typeof ing.subrecipe === 'object') {
        const getServings = parseFloat(ing.get_servings) || 0;
        const subPorcion = parseFloat(ing.subrecipe.porcion) || 1;
        return (rawQty * getServings) / subPorcion;
    }
    const getServings = parseFloat(ing.get_servings);
    if (!getServings || getServings === 1) {
        // No servings data — use raw quantity as-is (e.g. manual items)
        return rawQty;
    }
    const porcion = parseFloat(ing.porcion) || 1;
    return (rawQty * getServings) / porcion;
};
```

**The fix**: Move the sub-recipe branch **before** the early-return guard. Sub-recipe ingredients always apply `(rawQty × get_servings) / subrecipe.porcion` regardless of the numeric value of `get_servings`.

#### Worked example (user selects 2 porciones = parent default)

```
Parent: Ensalada de Pollo con Yogur Griego — default 2 porciones, user selects 2
Sub-recipe: Pollo Deshebrado HM — recipe calls for 1 tz, default yield 3 tz
Sub-recipe ingredient: chicken — 300g in Pollo recipe

get_recipe_cantidad = 1 tz × 2 selected / 2 default = 1.0
displayed_qty = 300g × 1.0 / 3 = 100g ✓   (was: 300g × 1.0 = 300g ✗)
```

This function is called for both primary ingredients and items in the `repeat` array (same ingredient across multiple meals/days).

---

## Key Field Reference

| JS Field | PHP Source | Description |
|----------|-----------|-------------|
| `ing.cantidad` | `$rir->rirm[0]->cantidad` | Raw ingredient quantity in recipe definition |
| `ing.get_servings` | `$get_servings` (regular) / `$get_recipe_cantidad` (sub-recipe) | Servings multiplier |
| `ing.porcion` | `$porcion` passed from `getRelatedIngrediente` | Parent recipe's default portions |
| `ing.subrecipe` | `$subreceta` array (or `""` if not a sub-recipe) | Sub-recipe descriptor object |
| `ing.subrecipe.cantidad` | `$rir->rirm[0]->cantidad` at sub-recipe level | Amount of sub-recipe called for in main recipe |
| `ing.subrecipe.porcion` | `$rir->subreceta->getPorciones()['cantidad']` | Sub-recipe's own default yield |

---

## Related Files

| File | Role |
|------|------|
| `app/Models/Receta.php` — `getCategoriaIngredientes()` | Builds scaled ingredient arrays for lista |
| `app/Helpers/helper.php` — `getRelatedIngrediente()` | Iterates calendar schedule, calls getCategoriaIngredientes per recipe |
| `app/Http/Controllers/Api/V1/Calendars/ListaController.php` | API endpoint; returns `{ ingredients: { [categoryId]: [...] } }` |
| `react-front-app/src/utils/subrecipes/subRecipeUtils.js` | `subRecipeItem()` — existing correct logic (jQuery era, unused in React) |
| `react-front-app/src/services/list-processing/listProcessingService.js` | `processListaData()` — wraps subRecipeItem (jQuery era, unused in React) |
| `react-front-app/src/pages/Lista.jsx` | React lista page; `computeScaledQty()` applies the correct formula |

---

## Related Documentation

- [Lista System](./LISTA_SYSTEM.md)
- [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)
- [Calendar System](./CALENDAR_SYSTEM.md)
