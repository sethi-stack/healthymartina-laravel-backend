# Recipes and Ingredients System

## Overview

The recipe system is the core of the application. Recipes contain ingredients, instructions, nutrition information, and can reference other recipes as sub-recipes.

## Recipe Structure

### Recipe Model (NewReceta)

**Location**: `app/Models/NewReceta.php`

**Key Attributes**:

-   `titulo` (title): Recipe name
-   `tiempo` (time): Cooking time in minutes
-   `porciones` (portions): Default number of servings
-   `instrucciones` (instructions): Cooking instructions (text)
-   `tips`: Additional tips (can reference other recipes)
-   `imagen_principal` (main image): Primary recipe image
-   `imagen_secundaria` (secondary image): In-recipe image
-   `nutrient_info`: JSON column storing calculated nutrition data
-   `nutrient_data`: JSON column for additional nutrition metadata
-   `slug`: URL-friendly identifier

### Recipe-Instruction Relationship

Recipes don't directly have ingredients. Instead, they use a three-level relationship:

```
Receta → RecetaInstruccionReceta → Instruccion → Ingrediente
```

**Why this structure?**

-   Allows ingredients to have preparation methods (e.g., "chopped onion", "diced tomatoes")
-   Supports sub-recipes (a recipe can be an ingredient)
-   Enables complex measurement relationships

### RecetaInstruccionReceta (RIR)

**Purpose**: Links a recipe to an instruction with ordering and notes.

**Key Fields**:

-   `receta_id`: The recipe
-   `instruccion_id`: The instruction (can be null if subrecipe)
-   `subreceta_id`: If this is a sub-recipe reference (alternative to instruccion_id)
-   `orden`: Display order
-   `nota`: Additional notes

### Instruccion

**Purpose**: Represents an ingredient with its preparation method.

**Key Fields**:

-   `ingrediente_id`: The actual ingredient
-   `nombre`: Preparation method (e.g., "chopped", "diced", "NA" for none)

### Ingrediente

**Purpose**: Base ingredient database.

**Key Fields**:

-   `nombre`: Ingredient name
-   `fdc_raw`: JSON data from USDA FDC API (nutrition information)
-   `equivalencia_gramos`: Gram equivalent for unit conversions

## Sub-Recipes

A recipe can contain another recipe as an ingredient. This is handled through:

1. **RecetaInstruccionReceta** with `subreceta_id` set (instead of `instruccion_id`)
2. **RecetaInstruccionRecetaMedida (RIRM)**: Links the sub-recipe to a measurement

**Example**: A "Lasagna" recipe might contain "Marinara Sauce" as a sub-recipe ingredient.

### Sub-Recipe Measurement Handling

When a recipe uses another recipe as an ingredient:

-   The sub-recipe has its own portion measurements
-   The parent recipe specifies how much of the sub-recipe to use
-   Unit conversion may be needed if measurement types differ
-   Nutrition is calculated recursively (see [Nutrition System](./NUTRITION_SYSTEM.md))

## Key Methods in NewReceta Model

### `getIngredientes($solo_ingredientes = false)`

**Purpose**: Retrieves all ingredients for a recipe, including sub-recipes.

**Returns**: Array of ingredient data including:

-   `ingrediente_id`: ID (if regular ingredient)
-   `ingrediente`: Display name
-   `medida`: Unit abbreviation (Spanish)
-   `medida_plural`: Plural form
-   `medida_english`: English unit name
-   `cantidad`: Quantity
-   `tipo_medida_id`: Measurement type (1=volume, 2=weight, 3=count, 4=special, 5=pieces)
-   `es_ingrediente`: Boolean (true for ingredients, false for sub-recipes)
-   `info_nutrimental`: Raw FDC data

**Special Handling**:

-   If `$solo_ingredientes = true`, excludes sub-recipes
-   Sub-recipes are returned as links: `<a href="/receta/{slug}">{title}</a>`

### `getIngredientesIds($solo_ingredientes = false)`

**Purpose**: Returns only ingredient IDs (used for filtering).

**Returns**: Array of `['ingrediente_id' => id]`

### `getPorciones($active = true)`

**Purpose**: Gets the portion/serving information for a recipe.

**Returns**: Array with:

-   `cantidad`: Number of servings
-   `step`: Increment step for slider
-   `nombre`: Singular name (e.g., "Porción")
-   `nombre_plural`: Plural name (e.g., "Porciones")

**Logic**:

-   If `$active = true`: Returns the active portion type
-   If no active portion: Falls back to portion type ID 10 (default)
-   If `$active = false`: Returns all portion types
-   If `$active` is a number: Returns specific portion type

### `getPorcionesList()`

**Purpose**: Returns all available portion types for a recipe.

**Returns**: Array of portion configurations with `active` flag.

### `getInstrucciones()`

**Purpose**: Parses instruction text into array.

**Returns**: Array of instruction lines (split by newlines).

### `getTips()`

**Purpose**: Parses tips and converts recipe references to links.

**Special Feature**: Supports inline recipe references:

-   Format: `receta[123]` where 123 is recipe ID
-   Converts to: `<a href="/receta/{slug}">{title}</a>`

### `getHasSubrecetaAttribute()`

**Purpose**: Checks if recipe contains any sub-recipes.

**Returns**: Boolean

## Recipe Search & Filtering

### Search Parameters

1. **Tags**: Filter by recipe tags
2. **Ingredients Include**: Must contain ALL specified ingredients
3. **Ingredients Exclude**: Must NOT contain any specified ingredients
4. **Number of Ingredients**: Min/max ingredient count
5. **Cooking Time**: Min/max time in minutes
6. **Calories**: Min/max calorie range
7. **Nutrients**: Min/max for 30+ nutrients

### Advanced Filtering Logic

**Location**: `app/Services/RecipeFilterService.php`

**Key Features**:

-   JSON column queries for nutrient filtering
-   Sub-recipe ingredient checking
-   Parent recipe inclusion when sub-recipe matches
-   Complex ingredient inclusion/exclusion logic

**Special Case - Ingredient Include "ALL"**:

-   Recipe must contain ALL specified ingredients
-   If sub-recipe has some ingredients, checks if parent + sub-recipe together have all
-   Method: `checkIfCombinedWithParentsIncludeAll()`

## Recipe View Tracking

When a recipe is viewed for the first time:

1. Nutrition information is calculated (see [Nutrition System](./NUTRITION_SYSTEM.md))
2. Result is stored in `nutrient_info` JSON column
3. Subsequent views use cached data

## API Endpoints

### Recipe Listing

-   `GET /api/v1/recipes` - List recipes with pagination
-   `GET /api/v1/recipes/search` - Advanced search/filter

### Recipe Detail

-   `GET /api/v1/recipes/{id}` - Get single recipe
-   `GET /api/v1/recipes/{id}/ingredients` - Get recipe ingredients
-   `GET /api/v1/recipes/{id}/nutrition` - Get nutrition info

### Recipe Interactions

-   `POST /api/v1/recipes/{id}/view` - Track recipe view (triggers nutrition calc)
-   `POST /api/v1/recipes/{id}/reaction` - Like/dislike
-   `POST /api/v1/recipes/{id}/comment` - Add comment

## Spanish Terminology Reference

| Spanish     | English           | Description                        |
| ----------- | ----------------- | ---------------------------------- |
| Receta      | Recipe            | Main recipe entity                 |
| Ingrediente | Ingredient        | Base ingredient                    |
| Instrucción | Instruction       | Ingredient with preparation method |
| Subreceta   | Sub-recipe        | Recipe used as ingredient          |
| Porción     | Portion/Serving   | Single serving unit                |
| Porciones   | Portions/Servings | Multiple servings                  |
| Medida      | Measurement       | Unit of measure                    |
| Cantidad    | Quantity          | Amount                             |
| Tiempo      | Time              | Cooking time                       |
| Tips        | Tips              | Additional notes/tips              |

## Related Documentation

-   [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)
-   [Nutrition System](./NUTRITION_SYSTEM.md)
-   [Calendar System](./CALENDAR_SYSTEM.md)
