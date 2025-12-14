# System Architecture & User Stories

## Overview

This document provides a high-level overview of the Healthy Martina application architecture and user stories. The system is a comprehensive meal planning and recipe management platform with advanced nutrition tracking, calendar-based meal scheduling, and shopping list generation.

## Core Components

### 1. Recipes System

-   Recipe listing with metadata
-   Advanced recipe search (by title, nutrients, ingredients, tags)
-   Recipe detail pages with ingredients, instructions, and nutrition info
-   Support for sub-recipes (recipes as ingredients)

### 2. Ingredients & Instructions

-   Ingredients stored as "Instructions" (Instruccion model)
-   Ingredients can be regular items or other recipes (sub-recipes)
-   Complex unit/measurement system with automatic conversions
-   Support for multiple measurement types (volume, weight, pieces, etc.)

### 3. Units & Measurements

-   Dynamic unit conversion based on quantity
-   Automatic unit switching (e.g., tsp → tbsp → cup)
-   Fractional representation for precise measurements
-   Support for metric and imperial systems

### 4. Nutrition Information System

-   Integration with USDA FDC (Food Data Central) API
-   Automatic nutrition calculation on first recipe view
-   Recursive nutrition calculation for sub-recipes
-   Percentage-based daily value calculations
-   Support for 30+ nutrients stored in JSON columns

### 5. Calendar System

-   Weekly meal planning (Monday-Sunday)
-   Main and Side dish slots per meal
-   Portion/serving management
-   Leftover tracking and carry-over logic
-   Daily nutrition aggregation

### 6. Shopping List (Lista)

-   Automatic generation from calendar meals
-   Categorized by ingredient type
-   Unit aggregation across multiple meals
-   Check-off functionality for shopping

### 7. PDF Export

-   Recipe PDFs with nutrition info
-   Calendar PDFs (weekly meal plans)
-   Shopping list PDFs
-   Subscription-based template system
-   Email delivery support

### 8. Meal Plans

-   Pre-configured meal plan templates
-   Admin-created reusable plans
-   Copy/clone functionality
-   Scale serving sizes

### 9. Subscriptions

-   Stripe integration for payment processing
-   Multiple subscription tiers (free, professional, business)
-   Feature gating based on subscription level

### 10. User Profiles

-   Individual user accounts
-   Business accounts
-   Preference management
-   Theme selection (for PDFs)

## Data Flow

```
User → Recipe Search/Filter → Recipe Detail → Nutrition Calculation (if first view)
     ↓
Calendar → Add Recipes → Calculate Servings → Generate Shopping List
     ↓
Export PDF → Email/Save
```

## Key Models

-   **NewReceta** (Receta): Core recipe model with nutrition calculation
-   **Ingrediente**: Ingredient database
-   **Instruccion**: Links recipes to ingredients with quantities
-   **Calendar**: Weekly meal plans
-   **ListaIngredientes**: Shopping list items
-   **Plan**: Pre-configured meal plan templates
-   **User**: User accounts with subscription info

## Technology Stack

-   **Backend**: Laravel 11 (PHP)
-   **Database**: MySQL
-   **Payment**: Stripe
-   **PDF Generation**: DomPDF
-   **External API**: USDA FDC API for nutrition data
-   **Frontend**: React (planned migration)

## Related Documentation

-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Units and Measurements](./UNITS_AND_MEASUREMENTS.md)
-   [Nutrition System](./NUTRITION_SYSTEM.md)
-   [Calendar System](./CALENDAR_SYSTEM.md)
-   [Shopping List System](./LISTA_SYSTEM.md)
-   [PDF Export](./PDF_EXPORT.md)
-   [Meal Plans](./MEAL_PLANS.md)
-   [Subscriptions](./SUBSCRIPTIONS.md)
-   [User Profiles](./USER_PROFILES.md)

