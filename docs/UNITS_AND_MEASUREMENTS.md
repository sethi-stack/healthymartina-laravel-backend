# Units and Measurements System

## Overview

The system handles complex unit conversions and measurement representations. Units automatically convert based on quantity (e.g., teaspoons → tablespoons → cups). This document references the JavaScript implementation in `resources/js/lista-dj.js` which needs to be refactored for React components.

## Measurement Types (tipo_medida_id)

1. **Type 1 - Volume**: tsp, tbsp, cup, etc.
2. **Type 2 - Weight**: g, kg, oz, lb
3. **Type 3 - Count**: Whole numbers (e.g., "3 eggs")
4. **Type 4 - Special**: Text-based (e.g., "to taste")
5. **Type 5 - Pieces**: Fractional pieces (e.g., "1/2 apple")

## Unit Conversion Logic

### Volume Conversions (Type 1)

**Hierarchy**: tsp → tbsp → cup

**Conversion Rules** (from `updatePortions()` in lista-dj.js):

```javascript
if (cantidad.convert("tsp") < 3) {
    // Show as teaspoons (cdta/cdtas)
    convertido = cantidad.convert("tsp");
    // Display: "1 cdta" or "2 cdtas"
}
if (cantidad.convert("tsp") >= 3 && cantidad.convert("tbsp") < 4) {
    // Show as tablespoons (cda/cdas)
    convertido = cantidad.convert("tbsp");
    // Display: "1 cda" or "2 cdas"
}
if (cantidad.convert("tbsp") >= 4) {
    // Show as cups (tz/tzs)
    convertido = cantidad.convert("cup");
    // Display: "1 tz" or "2 tzs"
}
```

**Spanish Abbreviations**:

-   `cdta` = cucharadita (teaspoon, singular)
-   `cdtas` = cucharaditas (teaspoons, plural)
-   `cda` = cucharada (tablespoon, singular)
-   `cdas` = cucharadas (tablespoons, plural)
-   `tz` = taza (cup, singular)
-   `tzs` = tazas (cups, plural)

### Weight Conversions (Type 2)

**Metric System**:

-   If < 1000g: Display in grams (g)
-   If >= 1000g: Convert to kilograms (kg)

**Imperial System**:

-   If < 16oz: Display in ounces (oz/ozs)
-   If >= 16oz: Convert to pounds (lb/lbs)

**Conversion Logic**:

```javascript
if (window.app["unit_measure"] == "metric") {
    if (cantidad.convert("gram") < 1000) {
        // Display in grams
        texto = Math.round(convertido) + " g";
    } else {
        // Display in kilograms
        convertido = cantidad.convert("kilo");
        texto = getStringFractionValue(fraction) + " kg";
    }
} else {
    // Imperial system
    if (cantidad.convert("oz") < 16) {
        // Display in ounces
        convertido = cantidad.convert("oz");
        texto = getStringFractionValue(fraction) + " oz/ozs";
    } else {
        // Display in pounds
        convertido = cantidad.convert("lb");
        texto = getStringFractionValue(fraction) + " lb/lbs";
    }
}
```

### Count/Pieces (Type 3 & 5)

**Type 3 - Count**: Whole numbers with pluralization

```javascript
if (cantidad > 1) {
    texto += " " + medida_plural;
} else {
    texto += " " + medida;
}
```

**Type 5 - Pieces**: Fractional representation

```javascript
var fraction = getNearestPieceFraction(cantidad);
texto = getStringFractionValue(fraction);
if (cantidad > 1) {
    texto += " " + medida_plural;
} else {
    texto += " " + medida;
}
```

## Fractional Representation

### Supported Fractions

The system uses a fraction library to represent precise measurements:

**Common Fractions**:

-   1/8, 1/4, 1/3, 1/2, 2/3, 3/4
-   1/16 (for teaspoons)
-   1/32 (for very small measurements)

### Fraction Functions

#### `getNearestFraction(value, tsp = '')`

**Purpose**: Finds the nearest fraction for a decimal value.

**Logic**:

1. Extract integer and decimal parts
2. Compare decimal to predefined fractions
3. Return closest match
4. Combine with integer part

**Returns**: Object with:

-   `int`: Integer part
-   `fraction`: Fraction object (from Fraction library)

#### `getNearestPieceFraction(value)`

**Purpose**: Similar to `getNearestFraction` but optimized for piece-based measurements.

#### `getStringFractionValue(value)`

**Purpose**: Converts fraction object to display string.

**Format**:

-   If fraction is 0: `"3"` (integer only)
-   If integer is 0: `"1/2"` (fraction only)
-   If both: `"2 <span class='smallFraction'>1/2</span>"` (integer + fraction)

## Portion Slider Functionality

### How It Works

1. **Base Quantity**: Recipe stores base quantity per portion
2. **Portion Slider**: User adjusts number of servings
3. **Calculation**: `newQuantity = baseQuantity * currentPortions / basePortions`
4. **Unit Conversion**: Convert to appropriate unit based on new quantity
5. **Display**: Show with fractional representation if needed

### Implementation (from `updatePortions()`)

```javascript
var porcionBase = $(this).children("p.cantidad").data("porcion");
var porcionActual = $('.options .slide input[type="range"]').val();
cantidad = (porcionActual * cantidad) / porcionBase;
```

### Special Cases

**Sub-recipes**: When a recipe contains another recipe:

-   Sub-recipe has its own portion system
-   Parent recipe specifies quantity of sub-recipe
-   Conversion needed if units differ
-   Function: `subRecipeItem()` handles this

**Repeated Ingredients**: Same ingredient appears multiple times:

-   Aggregates quantities
-   Converts to common unit if needed
-   Function: `repeatItem()` handles this

## Unit Conversion Library

The system uses **Unitz** library for unit conversions:

```javascript
var unit1 = Unitz.parse(item.cantidad + " " + item.medida_english);
var converted = unit1.convert(target_unit);
```

**Supported Units**:

-   Volume: tsp, tbsp, cup, ml, l, etc.
-   Weight: g, kg, oz, lb, etc.

## Key Functions to Refactor for React

### 1. `updatePortions(render = '')`

**Current**: jQuery-based DOM manipulation
**Needs**: React state management, component-based

**Functionality**:

-   Updates ingredient quantities based on portion slider
-   Handles unit conversions
-   Updates display text with fractions

### 2. `subRecipeItem(item)`

**Purpose**: Handles sub-recipe unit conversions

**Logic**:

-   Checks if units are convertible
-   Normalizes units if needed
-   Calculates serving adjustments

### 3. `repeatItem(item)`

**Purpose**: Aggregates repeated ingredients

**Logic**:

-   Combines quantities
-   Converts to common unit
-   Handles unit mismatches

### 4. `normalizeUnits(item)`

**Purpose**: Normalizes units for sub-recipes

**Logic**:

-   Converts between compatible unit types
-   Adjusts serving calculations
-   Handles special cases (e.g., category_id == 6)

### 5. `getNearestFraction(value, tsp = '')`

**Purpose**: Fraction calculation

**Needs**: Pure function, no DOM dependencies

### 6. `getStringFractionValue(value)`

**Purpose**: Fraction display formatting

**Needs**: React component for fraction rendering

### 7. `numFraction(value, tipo_id, medida_english)`

**Purpose**: Converts numeric value to fraction string based on type

**Used by**: Portion slider display

## Measurement Type Reference

| tipo_medida_id | Type    | Examples                | Conversion Logic                |
| -------------- | ------- | ----------------------- | ------------------------------- |
| 1              | Volume  | tsp, tbsp, cup          | Hierarchical (tsp → tbsp → cup) |
| 2              | Weight  | g, kg, oz, lb           | Metric/Imperial system based    |
| 3              | Count   | eggs, pieces            | Simple pluralization            |
| 4              | Special | "to taste", "as needed" | Text-based, no conversion       |
| 5              | Pieces  | 1/2 apple, 3/4 onion    | Fractional pieces               |

## Spanish Terminology

| Spanish            | English           | Context           |
| ------------------ | ----------------- | ----------------- |
| Medida             | Measurement/Unit  | General term      |
| Cantidad           | Quantity          | Amount            |
| Porción            | Portion/Serving   | Single serving    |
| Porciones          | Portions/Servings | Multiple servings |
| Cucharadita (cdta) | Teaspoon          | Volume unit       |
| Cucharada (cda)    | Tablespoon        | Volume unit       |
| Taza (tz)          | Cup               | Volume unit       |
| Gramo (g)          | Gram              | Weight unit       |
| Kilogramo (kg)     | Kilogram          | Weight unit       |
| Onza (oz)          | Ounce             | Weight unit       |
| Libra (lb)         | Pound             | Weight unit       |

## React Refactoring Recommendations

1. **Create Unit Conversion Service**: Pure JavaScript functions, no DOM
2. **Fraction Component**: React component for displaying fractions
3. **Unit Display Component**: Handles unit conversion and display
4. **Portion Slider Component**: Manages portion state and calculations
5. **Ingredient Quantity Component**: Displays quantity with proper units
6. **Measurement Type Enum**: TypeScript/PropTypes for type safety

## Related Documentation

-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Nutrition System](./NUTRITION_SYSTEM.md)
-   [Calendar System](./CALENDAR_SYSTEM.md)

