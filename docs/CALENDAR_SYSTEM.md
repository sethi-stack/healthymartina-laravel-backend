# Calendar System

## Overview

The calendar system allows users to plan weekly meals (Monday-Sunday) with main and side dishes, manage servings, track leftovers, and view daily nutrition information.

## Calendar Structure

### Calendar Model

**Location**: `app/Models/Calendar.php`

**Key Fields**:

-   `user_id`: Owner of the calendar
-   `title`: Calendar name
-   `main_schedule`: JSON - Main dish schedule (day → meal → recipe_id)
-   `sides_schedule`: JSON - Side dish schedule (day → meal → recipe_id)
-   `main_servings`: JSON - Serving sizes for main dishes
-   `sides_servings`: JSON - Serving sizes for side dishes
-   `main_leftovers`: JSON - Leftover flags for main dishes
-   `sides_leftovers`: JSON - Leftover flags for side dishes
-   `main_racion`: JSON - Portion type for main dishes
-   `sides_racion`: JSON - Portion type for side dishes
-   `labels`: JSON - Custom day labels (e.g., "Monday", "Martes")

### Schedule Structure

**main_schedule / sides_schedule**:

```json
{
  "day_1": {
    "meal_1": [recipe_id, is_leftover],
    "meal_2": [recipe_id, is_leftover],
    "meal_3": [recipe_id, is_leftover]
  },
  "day_2": { ... },
  // ... day_7
}
```

**Array Format**: `[recipe_id, is_leftover_boolean]`

**Example**:

```json
{
    "day_1": {
        "meal_1": [123, false], // Recipe 123, not leftover
        "meal_2": [456, true], // Recipe 456, is leftover
        "meal_3": [789, false]
    }
}
```

### Servings Structure

**main_servings / sides_servings**:

```json
{
    "day_1": {
        "meal_1": 2, // 2 servings
        "meal_2": 1,
        "meal_3": 3
    }
    // ... other days
}
```

### Leftovers Structure

**main_leftovers / sides_leftovers**:

```json
{
    "day_1": {
        "meal_1": false,
        "meal_2": true, // This meal is a leftover
        "meal_3": false
    }
    // ... other days
}
```

## Leftover Functionality

### Purpose

Leftovers allow users to:

1. Mark meals as using leftovers from previous days
2. Automatically calculate servings based on leftover usage
3. Reduce shopping list quantities (leftovers don't need new ingredients)

### Leftover Logic

**Location**: `app/Http/Controllers/RecetasController.php` - `manipulateServings()`

**Process**:

1. **Merge Meals with Leftovers**:

    ```php
    $mergedMains = array_merge_recursive($mainMeals, $mainLeftovers);
    ```

2. **Map Recipes to Days**:

    ```php
    foreach ($mergedMains as $day => $meals) {
        foreach ($meals as $meal => $obj) {
            $mealsOnDaysMapping[$obj[0]][$day] = $obj[1] ? 'Leftover' : 'No';
        }
    }
    ```

3. **Calculate Servings**:
    ```php
    foreach ($mealsOnDaysMapping as $recipeId => $days) {
        $counts = array_count_values($days);
        if (isset($counts['Leftover']) && $counts['Leftover'] > 0) {
            // Recipe appears as leftover - count all occurrences
            $mServings[$recipeId] =
                (isset($counts['No']) ? $counts['No'] : 0) +
                (isset($counts['Leftover']) ? $counts['Leftover'] : 0);
        } else {
            // Recipe only appears as new meal
            $mServings[$recipeId] = 1;
        }
    }
    ```

**Key Insight**: If a recipe appears as a leftover anywhere, the total servings = all occurrences (leftover + new). Otherwise, it's just 1 serving.

### Leftover Carry-Over

**Special Case**: When copying a meal plan:

-   Leftovers are tracked separately
-   Servings calculation accounts for leftovers
-   Shopping list generation excludes leftover ingredients

**Location**: `app/Http/Controllers/RecetasController.php` - `copyPlanes()`

## Daily Nutrition Calculation

### Purpose

Each day shows total nutrition information at the bottom, calculated from all meals (main + side) for that day.

### Calculation Process

1. **Get All Recipes for Day**:

    - Iterate through `main_schedule[day]` and `sides_schedule[day]`
    - Collect all recipe IDs

2. **Get Nutrition per Recipe**:

    - Fetch `nutrient_info` from each recipe
    - This is per-portion nutrition

3. **Scale by Servings**:

    ```php
    foreach ($recipes as $recipe) {
        $servings = $schedule['main_servings'][$day][$meal];
        foreach ($recipe->nutrient_info as $nutrientId => $nutrient) {
            $dailyNutrition[$nutrientId] +=
                $nutrient['cantidad'] * $servings;
        }
    }
    ```

4. **Aggregate Main + Side**:

    - Sum nutrition from main dishes
    - Sum nutrition from side dishes
    - Combine totals

5. **Calculate Percentages**:
    - Use same percentage calculation as recipe detail
    - `porcentaje = cantidad * 100 / nutriente->cien_porciento`

### Display Format

-   **Values**: Total amount per nutrient (e.g., "350 kcal", "45g protein")
-   **Percentages**: % Daily Value (e.g., "17.5%")
-   **Pie Chart**: Visual representation of macronutrients
-   **Bar Chart**: All nutrients with percentages

## Meal Types

### Main Dish (Plato Principal)

-   Primary dish for the meal
-   Usually larger portion
-   Stored in `main_schedule`, `main_servings`, `main_leftovers`

### Side Dish (Acompañamiento)

-   Secondary dish
-   Usually smaller portion
-   Stored in `sides_schedule`, `sides_servings`, `sides_leftovers`

### Meal Slots

Typically 3 meals per day:

-   `meal_1`: Breakfast (Desayuno)
-   `meal_2`: Lunch (Comida)
-   `meal_3`: Dinner (Cena)

## API Endpoints

### Calendar Management

-   `GET /api/v1/calendars` - List user's calendars
-   `POST /api/v1/calendars` - Create new calendar
-   `GET /api/v1/calendars/{id}` - Get calendar details
-   `PUT /api/v1/calendars/{id}` - Update calendar
-   `DELETE /api/v1/calendars/{id}` - Delete calendar

### Schedule Management

-   `POST /api/v1/calendars/{id}/recipes` - Add recipe to schedule
-   `PUT /api/v1/calendars/{id}/recipes` - Update recipe in schedule
-   `DELETE /api/v1/calendars/{id}/recipes` - Remove recipe from schedule

### Nutrition

-   `GET /api/v1/calendars/{id}/nutrition` - Get daily nutrition breakdown
-   `GET /api/v1/calendars/{id}/nutrition/{day}` - Get specific day nutrition

### Schedules (JSON)

-   `GET /api/v1/calendars/{id}/schedules` - Get schedule as JSON

## Helper Functions

### `getRelatedIngrediente($calendario_id, $categoria_id, $type = 'list')`

**Location**: `app/Helpers/helper.php`

**Purpose**: Gets all ingredients for a calendar, optionally filtered by category.

**Returns**: Array of ingredients with:

-   Ingredient details
-   Quantities aggregated across meals
-   Unit conversions
-   Sub-recipe handling
-   Repeat ingredient aggregation

**Used by**: Shopping list generation

## Spanish Terminology

| Spanish                 | English               | Context            |
| ----------------------- | --------------------- | ------------------ |
| Calendario              | Calendar              | Weekly meal plan   |
| Plato Principal         | Main Dish             | Primary meal       |
| Acompañamiento          | Side Dish             | Secondary meal     |
| Porciones               | Servings              | Number of portions |
| Sobras                  | Leftovers             | Leftover meals     |
| Desayuno                | Breakfast             | Morning meal       |
| Comida                  | Lunch                 | Midday meal        |
| Cena                    | Dinner                | Evening meal       |
| Información Nutrimental | Nutrition Information | Daily nutrition    |

## Key Features

### 1. Multi-Calendar Support

Users can have multiple calendars:

-   Different meal plans
-   Different time periods
-   Different purposes (e.g., "Weight Loss", "Muscle Gain")

### 2. Calendar Switching

-   Session-based calendar selection
-   `calendario_id` stored in session
-   Defaults to most recent calendar

### 3. Recipe Scaling

When adding recipes to calendar:

-   Specify number of servings
-   System calculates total ingredients needed
-   Accounts for leftovers

### 4. Nutrition Aggregation

-   Real-time nutrition calculation
-   Per-day totals
-   Per-meal breakdowns
-   Percentage of daily values

## Related Documentation

-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Nutrition System](./NUTRITION_SYSTEM.md)
-   [Shopping List System](./LISTA_SYSTEM.md)
-   [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)

