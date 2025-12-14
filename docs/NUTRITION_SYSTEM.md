# Nutrition Information System

## Overview

The nutrition system integrates with the USDA Food Data Central (FDC) API to calculate comprehensive nutrition information for recipes. This is one of the most complex parts of the system, involving recursive calculations for sub-recipes and automatic generation on first recipe view.

## FDC API Integration

### What is FDC?

**Food Data Central (FDC)** is the USDA's comprehensive food database providing detailed nutrient information for thousands of foods.

### Data Storage

**Location**: `ingredientes.fdc_raw` (JSON column)

**Structure**:

```json
{
    "foodNutrients": [
        {
            "nutrient": {
                "id": 1008,
                "name": "Energy",
                "unitName": "kcal"
            },
            "amount": 250
        }
        // ... more nutrients
    ]
}
```

### How FDC Data is Obtained

1. Admin imports ingredient from FDC API
2. Raw JSON response stored in `fdc_raw` column
3. Used for nutrition calculations

## Nutrition Calculation Flow

### Trigger: First Recipe View

**Location**: `app/Http/Controllers/Api/V1/Recipes/RecipeController.php`

**Method**: `view()` - Tracks recipe views

**Logic**:

```php
if (!$recipe->nutrient_info) {
    // First time viewing - calculate nutrition
    $recipe->getInformacionNutrimental();
}
```

### Main Calculation Method

**Location**: `app/Models/NewReceta.php`

**Method**: `getInformacionNutrimental($porcentajeOverride = 100, $porcionesOverride = 0, $numeroDivision = 0, $porcionesDivision = 0)`

**Comment in code**: "When I wrote this code, only God and I understood what it did, now only God knows"

## Method Parameters Explained

### `$porcentajeOverride` (default: 100)

**Purpose**: Percentage of recipe used (for sub-recipes)

**Usage**:

-   `100` = Full recipe (main recipe calculation)
-   `< 100` = Partial recipe (sub-recipe calculation)

**Example**: If a sub-recipe represents 50% of a parent recipe's portion, pass `50`.

### `$porcionesOverride` (default: 0)

**Purpose**: Number of servings to calculate for (for sub-recipes)

**Usage**:

-   `0` = Use recipe's default portions
-   `> 0` = Override with specific portion count

### `$numeroDivision` (default: 0)

**Purpose**: Numerator for sub-recipe calculations

**Usage**: Used in complex sub-recipe percentage calculations

### `$porcionesDivision` (default: 0)

**Purpose**: Denominator for sub-recipe calculations

**Usage**: Used in complex sub-recipe percentage calculations

## Calculation Process

### Step 1: Iterate Through Recipe Instructions

```php
$rir = $this->recetaInstruccionReceta; // Get all recipe-instruction relationships
foreach ($rir as $r) {
    // Process each ingredient/sub-recipe
}
```

### Step 2: Handle Regular Ingredients

**For each ingredient instruction**:

1. **Calculate Total Grams**:

    ```php
    $totalGrams = $r->getGramosTotales(); // Gets total grams for this instruction
    $totalGrams = $totalGrams / $this->getPorciones(false)['cantidad']; // Divide by portions
    ```

2. **Get FDC Data**:

    ```php
    $fdcRaw = json_decode($r->instruccion->ingrediente->fdc_raw, true);
    $foodNutrients = $fdcRaw['foodNutrients'];
    ```

3. **Calculate Nutrient Values**:

    ```php
    $baseGram = 100; // FDC data is per 100g
    $totalNutrientValue = $totalGrams * $foodNutrient['amount'] / $baseGram;
    ```

4. **Accumulate Nutrients**:
    ```php
    $nutrientInfoArray[$id]['cantidad'] += $totalNutrientValue;
    ```

### Step 3: Handle Sub-Recipes (Recursive)

**Complex Logic for Sub-Recipe Nutrition**:

1. **Find Compatible Measurements**:

    ```php
    // Check if sub-recipe has same measurement type
    if ($recetaResultado->medida_id == $r->rirm[0]->medida_id) {
        // Direct match - no conversion needed
    } else if ($recetaResultado->medida->tipo_medida_id == $r->rirm[0]->medida->tipo_medida_id) {
        // Same type (e.g., both volume) - convert units
    }
    ```

2. **Calculate Sub-Recipe Percentage**:

    ```php
    $subporcionPorcion = $r->rirm[0]['cantidad'] / $this->getPorciones($recetaResultadoFinal->medida_id)['cantidad'];
    $subCreacionFinal = $subporcionPorcion * 100 / $recetaResultadoFinal->cantidad * $this->getPorciones($recetaResultadoFinal->medida_id)['cantidad'];
    ```

3. **Recursive Call**:
    ```php
    $otherRecipesTable[] = $r->subreceta->getInformacionNutrimental(
        $subCreacionFinal,  // Percentage override
        $this->getPorciones(false)['cantidad'], // Parent portions
        $r->rirm[0]['cantidad'], // Sub-recipe quantity
        $recetaResultadoFinalEmpty->cantidad // Sub-recipe portion size
    );
    ```

### Step 4: Merge Sub-Recipe Nutrition

```php
foreach ($otherRecipesTable as $otherRecipeTable) {
    foreach ($otherRecipeTable['info'] as $nutrientId => $nutrientInfo) {
        if ($porcentajeOverride == 100) {
            // Main recipe - add directly
            $data[$nutrientId]['cantidad'] += $nutrientInfo['cantidad'] * 1;
        } else {
            // Sub-recipe - scale by division
            $data[$nutrientId]['cantidad'] += ($nutrientInfo['cantidad'] * $numeroDivision / $porcionesOverride);
        }
    }
}
```

### Step 5: Calculate Percentages

```php
foreach ($data as $nutrientId => $nutrientInfo) {
    $nutriente = $nutrientes->first(function ($item) use ($nutrientId) {
        return $item->fdc_id == $nutrientId;
    });

    if ($nutriente->cien_porciento > 0) {
        $porcentaje = $cantidad * 100 / $nutriente->cien_porciento;
    } else {
        $porcentaje = '-';
    }

    $data[$nutrientId]['porcentaje'] = $porcentaje;
}
```

### Step 6: Save to Database

```php
$this->nutrient_info = $data; // Store in JSON column
$this->save();
```

## Nutrient Data Structure

### Stored Format (nutrient_info JSON)

```json
{
    "1008": {
        "id": 1,
        "orden": 1,
        "nombre": "Energy",
        "cantidad": 350.5,
        "unidad_medida": "kcal",
        "porcentaje": 17.5,
        "color": "#a1b2c3",
        "mostrar": true
    },
    "1005": {
        "id": 2,
        "orden": 2,
        "nombre": "Carbohydrate, by difference",
        "cantidad": 45.2,
        "unidad_medida": "g",
        "porcentaje": 15.1,
        "color": "#d4e5f6",
        "mostrar": true
    }
    // ... more nutrients
}
```

### Field Descriptions

-   **Key**: FDC nutrient ID (e.g., 1008 = Energy/Calories)
-   `id`: Local nutrient database ID
-   `orden`: Display order
-   `nombre`: Nutrient name
-   `cantidad`: Amount (in unit_medida)
-   `unidad_medida`: Unit (g, mg, kcal, etc.)
-   `porcentaje`: Percentage of daily value
-   `color`: Chart color (randomly generated)
-   `mostrar`: Whether to display in UI

## Common FDC Nutrient IDs

| FDC ID | Nutrient Name                  | Unit |
| ------ | ------------------------------ | ---- |
| 1008   | Energy (Calories)              | kcal |
| 1005   | Carbohydrate, by difference    | g    |
| 1003   | Protein                        | g    |
| 1004   | Total lipid (fat)              | g    |
| 1258   | Fiber, total dietary           | g    |
| 1079   | Fiber, insoluble               | g    |
| 1087   | Calcium, Ca                    | mg   |
| 1089   | Iron, Fe                       | mg   |
| 1090   | Magnesium, Mg                  | mg   |
| 1091   | Phosphorus, P                  | mg   |
| 1092   | Potassium, K                   | mg   |
| 1093   | Sodium, Na                     | mg   |
| 1095   | Zinc, Zn                       | mg   |
| 1103   | Copper, Cu                     | mg   |
| 1104   | Manganese, Mn                  | mg   |
| 1162   | Vitamin C, total ascorbic acid | mg   |
| 1165   | Thiamin                        | mg   |
| 1166   | Riboflavin                     | mg   |
| 1167   | Niacin                         | mg   |
| 1175   | Vitamin B-6                    | mg   |
| 1177   | Folate, total                  | µg   |
| 1178   | Folic acid                     | µg   |
| 1180   | Vitamin B-12                   | µg   |
| 1183   | Vitamin A, RAE                 | µg   |
| 1185   | Vitamin E (alpha-tocopherol)   | mg   |

## Spanish Terminology Reference

| Spanish                 | English               | Context             |
| ----------------------- | --------------------- | ------------------- |
| Información Nutrimental | Nutrition Information | General term        |
| Nutriente               | Nutrient              | Individual nutrient |
| Cantidad                | Quantity/Amount       | Nutrient amount     |
| Unidad de Medida        | Unit of Measure       | g, mg, kcal, etc.   |
| Porcentaje              | Percentage            | % Daily Value       |
| Calorías                | Calories              | Energy              |
| Carbohidratos           | Carbohydrates         | Carbs               |
| Proteínas               | Proteins              | Protein             |
| Grasas                  | Fats                  | Lipids              |
| Fibra                   | Fiber                 | Dietary fiber       |
| Calcio                  | Calcium               | Mineral             |
| Hierro                  | Iron                  | Mineral             |
| Vitamina                | Vitamin               | Vitamins            |

## Critical Analysis

### Complexity Issues

1. **Recursive Calculations**: Sub-recipes call themselves recursively, making debugging difficult
2. **Multiple Parameters**: Four parameters with complex interactions
3. **Unit Conversions**: Sub-recipe calculations involve unit conversions
4. **Percentage Calculations**: Complex percentage math for partial recipes
5. **Measurement Compatibility**: Checking if measurements are compatible adds complexity

### Potential Improvements

1. **Extract to Service**: Move calculation logic to `NutritionCalculationService`
2. **Simplify Parameters**: Use a DTO/Value Object instead of 4 parameters
3. **Add Caching**: Cache intermediate calculations
4. **Better Error Handling**: Handle missing FDC data gracefully
5. **Unit Tests**: Add comprehensive tests for edge cases
6. **Documentation**: Add inline comments explaining complex calculations

### Performance Considerations

1. **First View Delay**: Calculation on first view can be slow for complex recipes
2. **Recursive Depth**: Deep sub-recipe chains can cause performance issues
3. **Database Queries**: Multiple queries for ingredients and sub-recipes
4. **JSON Parsing**: Parsing FDC data for each ingredient

### Recommendations

1. **Background Jobs**: Move calculation to queue job for first view
2. **Progressive Loading**: Show cached data while calculating
3. **Batch Processing**: Pre-calculate nutrition for all recipes
4. **Optimization**: Cache FDC data parsing results

## Related Documentation

-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Calendar System](./CALENDAR_SYSTEM.md) - Daily nutrition aggregation
-   [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)

