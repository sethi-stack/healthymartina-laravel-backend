# Meal Plans System

## Overview

Meal plans are pre-configured weekly meal schedules created by administrators. Users can browse, preview, and copy meal plans to their own calendars. This allows for quick meal planning using professionally designed templates.

## Plan Model

**Location**: `app/Models/Plan.php`

**Key Fields**:

-   `nombre`: Plan name
-   `tipo_id`: Plan type (4 = public, matches user role_id for private)
-   `plan_receta`: Relationship to Calendar model (the actual meal schedule)
-   `deleted_at`: Soft delete timestamp

## Plan Structure

### Plan → Calendar Relationship

A Plan contains a Calendar object that stores:

-   Weekly schedule (main_schedule, sides_schedule)
-   Servings (main_servings, sides_servings)
-   Leftovers (main_leftovers, sides_leftovers)
-   Portion types (main_racion, sides_racion)
-   Labels (custom day names)

**Note**: Plans reuse the Calendar model structure but are owned by the system, not individual users.

## Plan Types

### Public Plans (tipo_id = 4)

-   Available to all users
-   Created by administrators
-   Examples: "Weight Loss Plan", "Muscle Gain Plan", "Vegetarian Week"

### Private Plans (tipo_id = user role_id)

-   Available only to specific user roles
-   Can be customized per role
-   Examples: Role-specific meal plans

### Free User Plans

-   Limited to plan ID 20
-   Basic meal plans only
-   Restricted features

## Plan Browsing

### Endpoint

**GET** `/planes`

**Logic**:

```php
if (auth()->user()->hasRole('free')) {
    $planes = Plan::where('id', '20')->get();
} else {
    $planes = Plan::whereNull('deleted_at')
        ->whereIn('tipo_id', [4, Auth::user()->role_id])
        ->get();
}
```

**Returns**: List of available meal plans

## Plan Preview

### Endpoint

**GET** `/planes/{id}/calendario`

**Purpose**: Preview meal plan before copying

**Returns**: Calendar view with plan's schedule

**Access Control**:

-   Must match user's role_id or be public (tipo_id = 4)
-   Returns 404 if not accessible

## Copying a Meal Plan

### Endpoint

**POST** `/planes/{id}/copy`

**Purpose**: Copy meal plan to user's calendar

**Parameters**:

-   `calendar_title`: Name for new calendar
-   `calendar_scale`: Scaling factor for servings (e.g., 1.5 = 50% more)

### Copy Process

**Location**: `app/Http/Controllers/RecetasController.php` - `copyPlanes()`

**Steps**:

1. **Get Plan and Calendar**:

    ```php
    $plan = Plan::find($id);
    $sourceCalendar = $plan->plan_receta;
    ```

2. **Calculate Servings**:

    ```php
    $servingsCalculated = $this->manipulateServings($sourceCalendar);
    // Returns: [$mServings, $sServings]
    ```

3. **Scale Servings**:

    ```php
    foreach ($mainServings as $day => $meals) {
        foreach ($meals as $meal => $serving) {
            $mServings[$day][$meal] =
                ($servingsCalculated[0][$recipeId] + $sideAdd) *
                $request->calendar_scale *
                $originalServing;
        }
    }
    ```

4. **Create New Calendar**:

    ```php
    $newCalendar = Calendar::create([
        'user_id' => Auth::user()->id,
        'title' => $request->calendar_title,
        'main_schedule' => $sourceCalendar->main_schedule,
        'main_leftovers' => $sourceCalendar->main_leftovers,
        'main_servings' => json_encode($mServings),
        'main_racion' => json_encode(config('constants.main_racion')),
        'sides_schedule' => $sourceCalendar->sides_schedule,
        'sides_leftovers' => $sourceCalendar->sides_leftovers,
        'sides_servings' => json_encode($sServings),
        'sides_racion' => json_encode(config('constants.sides_racion')),
        'labels' => $sourceCalendar->labels,
    ]);
    ```

5. **Set as Active**:
    ```php
    session(['calendario_id' => $newCalendar->id]);
    ```

### Serving Calculation Logic

**Method**: `manipulateServings($calendar)`

**Purpose**: Calculates total servings needed for each recipe, accounting for leftovers.

**Process**:

1. **Merge Meals with Leftovers**:

    ```php
    $mergedMains = array_merge_recursive($mainMeals, $mainLeftovers);
    ```

2. **Map Recipes to Days**:

    ```php
    foreach ($mergedMains as $day => $meals) {
        foreach ($meals as $meal => $obj) {
            $mealsOnDaysMapping[$obj[0]][$day] =
                $obj[1] ? 'Leftover' : 'No';
        }
    }
    ```

3. **Calculate Total Servings**:
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

**Key Insight**: If a recipe appears as a leftover anywhere, total servings = all occurrences. Otherwise, it's just 1 serving.

### Scaling Factor

**Purpose**: Allows users to scale meal plan for different numbers of people.

**Example**:

-   Original plan: 2 servings per meal
-   Scale: 1.5
-   Result: 3 servings per meal

**Formula**:

```
newServing = calculatedServing * scale * originalServing
```

## Plan PDF Export

### Endpoint

**GET** `/planes/{id}/pdf`

**Purpose**: Export meal plan as PDF

**Process**:

```php
$plan = Plan::find($request->plan_id);
$calendar = $plan->plan_receta;
$calendar->title = $plan->nombre;
$pdf = PDF::loadView('pdf.calendario-pdf', ['calendar' => $calendar])
    ->setPaper('a4', 'landscape');
return $pdf->download($plan->nombre . '.pdf');
```

## Admin Plan Management

### Creating Plans

**Location**: Backpack Admin Panel

**Process**:

1. Create Calendar with meal schedule
2. Create Plan linked to Calendar
3. Set tipo_id (4 for public, role_id for private)

### Plan Structure Best Practices

1. **Complete Weeks**: Plans should cover full 7 days
2. **Balanced Meals**: Include variety of main and side dishes
3. **Leftover Strategy**: Plan for leftovers to reduce cooking
4. **Nutritional Balance**: Ensure daily nutrition targets
5. **Clear Labels**: Use descriptive plan names

## API Endpoints

### Plan Browsing

-   `GET /api/v1/plans` - List available plans
-   `GET /api/v1/plans/{id}` - Get plan details
-   `GET /api/v1/plans/{id}/preview` - Preview plan calendar

### Plan Operations

-   `POST /api/v1/plans/{id}/copy` - Copy plan to user calendar
-   `GET /api/v1/plans/{id}/pdf` - Export plan as PDF

## Spanish Terminology

| Spanish         | English   | Context                  |
| --------------- | --------- | ------------------------ |
| Plan            | Plan      | Meal plan                |
| Plan de Comidas | Meal Plan | Weekly meal schedule     |
| Copiar          | Copy      | Copy plan to calendar    |
| Escala          | Scale     | Scaling factor           |
| Previsualizar   | Preview   | View plan before copying |
| Plantilla       | Template  | Pre-configured plan      |

## Use Cases

### 1. Quick Meal Planning

User wants to plan meals quickly:

1. Browse available plans
2. Preview plan
3. Copy to calendar
4. Adjust servings if needed

### 2. Scaling for Family

User wants to scale plan for larger family:

1. Select plan
2. Set scale factor (e.g., 2.0 for double)
3. Copy plan
4. System automatically scales all servings

### 3. Professional Templates

Nutritionist creates plan:

1. Design meal schedule in admin
2. Set as public plan
3. Clients can copy and customize

## Related Documentation

-   [Calendar System](./CALENDAR_SYSTEM.md)
-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [PDF Export](./PDF_EXPORT.md)
-   [Subscriptions](./SUBSCRIPTIONS.md)
