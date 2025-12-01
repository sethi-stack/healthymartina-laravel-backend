# Shopping List (Lista) System

## Overview

The shopping list system automatically generates ingredient lists from calendar meal plans. It aggregates ingredients across all meals, handles unit conversions, manages sub-recipes, and supports check-off functionality for shopping.

## Lista Generation Process

### Trigger

**Location**: `app/Http/Controllers/RecetasController.php`

**Methods**:

-   `calendarioLista()` - Main lista view
-   `ListaRenderAll()` - Get all ingredients (AJAX)
-   `ListaRender($id)` - Get ingredients for specific category (AJAX)

### Process Flow

1. **Get Calendar**: Retrieve user's active calendar
2. **Get Categories**: Load ingredient categories (Categoria model)
3. **Iterate Categories**: For each category, get related ingredients
4. **Aggregate Ingredients**: Combine quantities across all meals
5. **Handle Sub-Recipes**: Process recipes used as ingredients
6. **Unit Conversion**: Convert to appropriate units
7. **Generate HTML**: Create shopping list display

## Helper Function: `getRelatedIngrediente()`

**Location**: `app/Helpers/helper.php`

**Signature**: `getRelatedIngrediente($calendario_id, $categoria_id, $type = 'list')`

**Purpose**: Core function that generates ingredient list for a calendar and category.

### Process

1. **Get Calendar Schedule**:

    ```php
    $calendar = Calendar::find($calendario_id);
    $mainSchedule = json_decode($calendar->main_schedule, true);
    $sidesSchedule = json_decode($calendar->sides_schedule, true);
    ```

2. **Iterate Through Days and Meals**:

    ```php
    foreach ($mainSchedule as $day => $meals) {
        foreach ($meals as $meal => $recipeData) {
            $recipeId = $recipeData[0];
            $isLeftover = $recipeData[1] ?? false;

            if (!$isLeftover) {
                // Process recipe ingredients
            }
        }
    }
    ```

3. **Get Recipe Ingredients**:

    ```php
    $recipe = Receta::find($recipeId);
    $ingredients = $recipe->getIngredientes();
    $servings = $schedule['main_servings'][$day][$meal];
    ```

4. **Scale Quantities**:

    ```php
    foreach ($ingredients as $ingredient) {
        $scaledQuantity = $ingredient['cantidad'] * $servings / $recipe->getPorciones()['cantidad'];
    }
    ```

5. **Aggregate by Ingredient**:

    - Group by `ingrediente_id`
    - Sum quantities
    - Handle unit conversions if needed

6. **Handle Sub-Recipes**:

    - Recursively get sub-recipe ingredients
    - Scale by sub-recipe portion
    - Convert units if necessary

7. **Handle Repeats**:
    - Same ingredient appears multiple times
    - Aggregate quantities
    - Convert to common unit

## Ingredient Aggregation

### Same Ingredient, Different Meals

**Example**:

-   Monday Breakfast: 2 cups flour
-   Monday Lunch: 1 cup flour
-   Tuesday Breakfast: 1.5 cups flour

**Result**: 4.5 cups flour (aggregated)

### Unit Conversion During Aggregation

**Challenge**: Same ingredient might use different units in different recipes.

**Solution**: Convert all to common unit (usually grams for weight, base unit for volume).

**Logic** (from `lista-dj.js` - `repeatItem()`):

```javascript
if (!isRepeatItemUnitsDifferent(subItem.medida_english, item.medida_english)) {
    // Same unit - just add
    item.cantidad += subItem.cantidad;
} else {
    // Different units - convert
    var unit2 = Unitz.parse(subItem.cantidad + " " + subItem.medida_english);
    var unit_convert2 = unit2.convert(item.medida_english);
    item.cantidad = unit_convert2 + itemQty;
}
```

## Category Organization

### Categories (Categorias)

Ingredients are organized by category:

-   Vegetables (Verduras)
-   Fruits (Frutas)
-   Proteins (Proteínas)
-   Grains (Granos)
-   Dairy (Lácteos)
-   Spices (Especias)
-   etc.

**Model**: `app/Models/Categoria.php`

**Key Fields**:

-   `nombre`: Category name
-   `sort`: Display order

### Category-Based Display

Shopping list is organized by category:

```
Verduras
  - 2 lbs tomatoes
  - 1 lb onions
  - 3 cups lettuce

Frutas
  - 5 apples
  - 2 lbs bananas
```

## Check-Off Functionality

### Database Table

**Table**: `lista_ingrediente_taken`

**Structure**:

-   `calendario_id`: Calendar
-   `categoria_id`: Category
-   `ingrediente_id`: Ingredient ID
-   `ingrediente_type`: Type ('receta' or 'ingrediente')

### Toggle Function

**Location**: `app/Http/Controllers/RecetasController.php`

**Method**: `calendarioUpdateLista()`

**Logic**:

```php
$taken = DB::table('lista_ingrediente_taken')
    ->where([
        'calendario_id' => $request->calendario_id,
        'categoria_id' => $request->ingred_cat,
        'ingrediente_id' => $request->ingred_id,
        'ingrediente_type' => $request->ingred_type
    ])
    ->first();

if ($taken) {
    // Remove check
    DB::table('lista_ingrediente_taken')->where(...)->delete();
} else {
    // Add check
    DB::table('lista_ingrediente_taken')->insert([...]);
}
```

### Display Logic

Checked items are visually marked (strikethrough, checkbox checked) and excluded from ingredient count.

## Custom List Items

### ListaIngredientes Model

**Location**: `app/Models/ListaIngredientes.php`

**Purpose**: Allows users to add custom items not from recipes.

**Fields**:

-   `calendario_id`: Calendar
-   `categoria`: Category ID
-   `nombre`: Item name
-   `cantidad`: Quantity
-   `medida`: Unit

### CRUD Operations

-   `POST /calendario/lista` - Add custom item
-   `PUT /calendario/lista/{id}` - Update custom item
-   `DELETE /calendario/lista/{id}` - Delete custom item

## Sub-Recipe Handling in Lista

### Challenge

When a recipe contains another recipe as an ingredient:

-   Need to get sub-recipe's ingredients
-   Scale by sub-recipe portion
-   Handle unit conversions
-   Aggregate with other ingredients

### Process

1. **Detect Sub-Recipe**:

    ```php
    if (!$ingredient['es_ingrediente']) {
        // This is a sub-recipe
        $subRecipe = Receta::find($subRecipeId);
    }
    ```

2. **Get Sub-Recipe Ingredients**:

    ```php
    $subIngredients = $subRecipe->getIngredientes(true); // Only ingredients, no sub-recipes
    ```

3. **Scale by Sub-Recipe Portion**:

    ```php
    $subRecipePortion = $ingredient['cantidad']; // Amount of sub-recipe used
    $subRecipeBasePortion = $subRecipe->getPorciones()['cantidad'];

    foreach ($subIngredients as $subIng) {
        $scaled = $subIng['cantidad'] * $subRecipePortion / $subRecipeBasePortion;
    }
    ```

4. **Handle Unit Conversions**:
    - If sub-recipe unit differs from parent, convert
    - Use Unitz library for conversions

## JavaScript Processing (lista-dj.js)

### Key Functions

#### `getListaIngredientsAll()`

**Purpose**: Fetches all ingredients for calendar (all categories).

**Process**:

1. AJAX call to `ListaRenderAll` endpoint
2. Process each category's ingredients
3. Render to calendar lista section

#### `getListaIngredients()`

**Purpose**: Fetches ingredients for specific categories (lazy loading).

**Process**:

1. Iterate through category elements
2. AJAX call per category
3. Render to category section

#### `processListaData(data, categario_id)`

**Purpose**: Processes ingredient data for a category.

**Handles**:

-   Sub-recipe conversion (`subRecipeItem()`)
-   Repeat aggregation (`repeatItem()`)
-   HTML generation

#### `processAllListaData(data, categario_id)`

**Purpose**: Processes all ingredients for calendar view.

\*\*Similar to `processListaData` but for full calendar display.

#### `subRecipeItem(item)`

**Purpose**: Converts sub-recipe ingredient to regular ingredient format.

**Logic**:

-   Check if units are convertible
-   Normalize units if needed
-   Calculate serving adjustments

#### `repeatItem(item)`

**Purpose**: Aggregates repeated ingredients.

**Logic**:

-   Combine quantities
-   Convert to common unit if needed
-   Handle unit mismatches

## API Endpoints

### Lista Generation

-   `GET /api/v1/calendars/{id}/lista` - Get full shopping list
-   `GET /api/v1/calendars/{id}/lista/category/{categoryId}` - Get category list

### Check-Off

-   `POST /api/v1/calendars/{id}/lista/toggle` - Toggle ingredient check

### Custom Items

-   `POST /api/v1/calendars/{id}/lista/items` - Add custom item
-   `PUT /api/v1/calendars/{id}/lista/items/{itemId}` - Update custom item
-   `DELETE /api/v1/calendars/{id}/lista/items/{itemId}` - Delete custom item

## Spanish Terminology

| Spanish               | English            | Context             |
| --------------------- | ------------------ | ------------------- |
| Lista                 | List/Shopping List | Shopping list       |
| Lista de Ingredientes | Ingredient List    | Shopping list       |
| Categoría             | Category           | Ingredient category |
| Ingrediente           | Ingredient         | Shopping item       |
| Cantidad              | Quantity           | Amount needed       |
| Medida                | Measurement        | Unit                |
| Marcar                | Check/Mark         | Check off item      |
| Desmarcar             | Uncheck            | Uncheck item        |

## Related Documentation

-   [Calendar System](./CALENDAR_SYSTEM.md)
-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)
-   [PDF Export](./PDF_EXPORT.md)
