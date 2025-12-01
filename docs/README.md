# Healthy Martina - System Documentation

## Overview

This directory contains comprehensive documentation for the Healthy Martina application, a meal planning and recipe management platform with advanced nutrition tracking, calendar-based meal scheduling, and shopping list generation.

## Documentation Index

### Core System Documentation

1. **[System Architecture](./SYSTEM_ARCHITECTURE.md)**

    - High-level system overview
    - Core components
    - Data flow
    - Technology stack

2. **[Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)**

    - Recipe structure and model
    - Ingredient system
    - Sub-recipes (recipes as ingredients)
    - Recipe search and filtering
    - Spanish terminology reference

3. **[Units and Measurements](./UNITS_AND_MEASUREMENTS.md)**

    - Measurement types and conversions
    - Automatic unit switching
    - Fractional representation
    - Portion slider functionality
    - JavaScript functions to refactor for React

4. **[Nutrition System](./NUTRITION_SYSTEM.md)**
    - FDC API integration
    - Nutrition calculation flow
    - Recursive sub-recipe calculations
    - Critical analysis and improvements
    - Spanish terminology reference

### Feature Documentation

5. **[Calendar System](./CALENDAR_SYSTEM.md)**

    - Weekly meal planning structure
    - Main and side dishes
    - Leftover functionality
    - Daily nutrition calculation
    - API endpoints

6. **[Shopping List System](./LISTA_SYSTEM.md)**

    - Automatic list generation
    - Ingredient aggregation
    - Category organization
    - Check-off functionality
    - Sub-recipe handling

7. **[PDF Export](./PDF_EXPORT.md)**

    - Recipe PDFs
    - Calendar PDFs
    - Shopping list PDFs
    - Subscription-based themes
    - Email delivery
    - Large calendar handling

8. **[Meal Plans](./MEAL_PLANS.md)**
    - Pre-configured meal plans
    - Plan browsing and preview
    - Copying plans to calendars
    - Serving calculations
    - Scaling functionality

### User & Business Documentation

9. **[Subscriptions](./SUBSCRIPTIONS.md)**

    - Subscription tiers
    - Stripe integration
    - Feature gating
    - Payment processing
    - Webhook handling

10. **[User Profiles](./USER_PROFILES.md)**
    - Individual and business accounts
    - User preferences
    - Theme selection
    - Measurement system preferences
    - Nutritional preferences

## Quick Reference

### Spanish → English Terminology

| Spanish     | English          |
| ----------- | ---------------- |
| Receta      | Recipe           |
| Ingrediente | Ingredient       |
| Instrucción | Instruction      |
| Subreceta   | Sub-recipe       |
| Porción     | Portion/Serving  |
| Medida      | Measurement/Unit |
| Cantidad    | Quantity         |
| Calendario  | Calendar         |
| Lista       | Shopping List    |
| Plan        | Meal Plan        |
| Suscripción | Subscription     |
| Tema        | Theme            |

### Key Models

-   **NewReceta**: Recipe model with nutrition calculation
-   **Ingrediente**: Ingredient database
-   **Instruccion**: Links recipes to ingredients
-   **Calendar**: Weekly meal plans
-   **ListaIngredientes**: Shopping list items
-   **Plan**: Pre-configured meal plans
-   **User**: User accounts with subscriptions

### Key API Endpoints

#### Recipes

-   `GET /api/v1/recipes` - List recipes
-   `GET /api/v1/recipes/{id}` - Get recipe
-   `POST /api/v1/recipes/{id}/view` - Track view (triggers nutrition calc)

#### Calendars

-   `GET /api/v1/calendars` - List calendars
-   `POST /api/v1/calendars` - Create calendar
-   `GET /api/v1/calendars/{id}/nutrition` - Daily nutrition

#### Shopping Lists

-   `GET /api/v1/calendars/{id}/lista` - Get shopping list
-   `POST /api/v1/calendars/{id}/lista/toggle` - Toggle check-off

#### PDF Export

-   `POST /api/v1/recipes/{id}/pdf` - Recipe PDF
-   `POST /api/v1/calendars/{id}/pdf` - Calendar PDF
-   `POST /api/v1/calendars/{id}/lista/pdf` - Shopping list PDF

## Development Notes

### React Migration

Several JavaScript functions in `resources/js/lista-dj.js` need to be refactored for React:

-   `updatePortions()` - Portion slider updates
-   `subRecipeItem()` - Sub-recipe handling
-   `repeatItem()` - Ingredient aggregation
-   `getNearestFraction()` - Fraction calculations
-   `getStringFractionValue()` - Fraction display

See [Units and Measurements](./UNITS_AND_MEASUREMENTS.md) for details.

### Critical Systems

1. **Nutrition Calculation**: Complex recursive system - see [Nutrition System](./NUTRITION_SYSTEM.md)
2. **Unit Conversions**: Automatic unit switching based on quantity
3. **Leftover Logic**: Special serving calculations for leftovers
4. **Sub-Recipe Handling**: Recipes containing other recipes

### Performance Considerations

-   Nutrition calculation on first recipe view can be slow
-   Large calendar PDFs require chunking
-   Sub-recipe chains can cause performance issues
-   Consider background jobs for heavy calculations

## Related Files

### Controllers

-   `app/Http/Controllers/RecetasController.php` - Main recipe controller
-   `app/Http/Controllers/Api/V1/Recipes/RecipeController.php` - API recipe controller
-   `app/Http/Controllers/Api/V1/Calendars/CalendarController.php` - API calendar controller

### Models

-   `app/Models/NewReceta.php` - Recipe model (critical)
-   `app/Models/Calendar.php` - Calendar model
-   `app/Models/Ingrediente.php` - Ingredient model

### Services

-   `app/Services/RecipeFilterService.php` - Advanced recipe filtering
-   `app/Services/RecipeService.php` - Recipe business logic

### Helpers

-   `app/Helpers/helper.php` - `getRelatedIngrediente()` function

### JavaScript

-   `resources/js/lista-dj.js` - Shopping list and unit conversion logic

## Additional Resources

-   [API Endpoints Reference](./API_ENDPOINTS_REFERENCE.md) - Complete API documentation
-   [Backpack Admin Debug Log](./BACKPACK_ADMIN_DEBUG_LOG.md) - Admin panel setup notes
-   [Migration Progress](./MIGRATION_PROGRESS.md) - Migration status

## Contributing

When updating documentation:

1. Keep Spanish terminology references accurate
2. Include code examples where helpful
3. Document edge cases and special logic
4. Update this index when adding new docs
5. Cross-reference related documentation
