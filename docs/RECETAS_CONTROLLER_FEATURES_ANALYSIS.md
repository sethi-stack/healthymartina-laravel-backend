# RecetasController.php - Complete Features Analysis

## Overview

**Total Methods:** 38  
**Total Lines:** 1,155  
**Purpose:** Main controller handling recipes, filtering, lista, meal plans, comments, PDFs, and more.

---

## Complete Feature List with Migration Status

### 1. Recipe Browsing & Filtering

#### `recetas()` (lines 34-46)

-   **Purpose:** Redirects to recipe listing view
-   **Status:** ⚪ NOT NEEDED (view route, not API)
-   **Migration:** N/A - Web route redirect

#### `recetario()` (lines 48-282) 🔴 **CRITICAL - NOT MIGRATED**

-   **Purpose:** Main recipe listing with **COMPLEX ADVANCED FILTERING**
-   **Features:**
    -   Tag filtering (multiple tags with AND logic)
    -   Ingredient inclusion (required ingredients - ALL must be present)
    -   Ingredient exclusion (forbidden ingredients)
    -   Number of ingredients filter (min/max)
    -   Cooking time filter (min/max)
    -   Calories filter (min/max on JSON field `nutrient_info->1008->cantidad`)
    -   **30+ nutrient filters** with JSON queries (`nutrient_info->{fdc_id}->cantidad`)
    -   Subrecipe handling (parent/child relationships)
    -   "Combined with parents" logic for ingredient inclusion
    -   Session-based bookmark state management
    -   Custom pagination
-   **Complexity:** VERY HIGH
-   **Status:** ❌ **NOT MIGRATED**
-   **Current API:** `RecipeController::index()` only handles basic filters (search, tags, calories)
-   **Missing:** All advanced filtering logic
-   **Impact:** HIGH - Professional users cannot filter by detailed nutritional requirements

#### `paginate()` (lines 283-294)

-   **Purpose:** Custom pagination helper
-   **Status:** ⚪ NOT NEEDED (Laravel has built-in pagination)
-   **Migration:** N/A - Use Laravel's paginate()

#### `checkIfCombinedWithParentsIncludeAll()` (lines 296-334) 🔴 **NOT MIGRATED**

-   **Purpose:** Helper method for complex ingredient matching
-   **Logic:** Checks if a subrecipe combined with its parent recipe satisfies "include all ingredients" requirement
-   **Used by:** `recetario()` method
-   **Status:** ❌ **NOT MIGRATED** (part of advanced filtering)
-   **Impact:** Required for advanced ingredient inclusion filtering

#### `getUrl()` (lines 335-338)

-   **Purpose:** Generate recipe URL helper
-   **Status:** ⚪ NOT NEEDED (API resources handle URLs)
-   **Migration:** N/A - Use API resources

---

### 2. Filter Bookmarks

#### `saveBookmark()` (lines 340-352) 🟡 **NOT MIGRATED**

-   **Purpose:** Save current filter state as named bookmark
-   **Features:**
    -   Saves session bookmark to database
    -   Creates Bookmark record with user_id, filters (JSON), name
    -   Returns to recetario view
-   **Status:** ❌ **NOT MIGRATED**
-   **Current API:** No endpoint exists
-   **Impact:** MEDIUM - Users cannot save filter combinations

#### `getBookmark()` (lines 354-403) 🟡 **NOT MIGRATED**

-   **Purpose:** Load or delete saved filter bookmarks
-   **Features:**
    -   Load multiple bookmarks and merge filters
    -   Delete bookmarks
    -   Apply merged filters to recetario
-   **Status:** ❌ **NOT MIGRATED**
-   **Current API:** No endpoint exists
-   **Impact:** MEDIUM - Users cannot load saved searches

---

### 3. Lista de Ingredientes (Shopping Lists)

#### `calendarioLista()` (lines 405-436)

-   **Purpose:** Display lista view with all data
-   **Status:** ✅ **MIGRATED** → `ListaController::index()`
-   **API Endpoint:** `GET /api/v1/calendars/{id}/lista`

#### `ListaRenderAll()` (lines 439-464)

-   **Purpose:** Get all ingredients grouped by categories (AJAX)
-   **Status:** ✅ **MIGRATED** → `ListaController::index()` (merged)
-   **API Endpoint:** `GET /api/v1/calendars/{id}/lista`

#### `ListaRender()` (lines 466-512)

-   **Purpose:** Get ingredients for specific category (AJAX)
-   **Status:** ✅ **MIGRATED** → `ListaController::category()`
-   **API Endpoint:** `GET /api/v1/calendars/{id}/lista/categories/{categoryId}`

#### `calendarioListaPdf()` (lines 514-589) ✅ **MIGRATED**

-   **Purpose:** Generate and download/email lista PDF
-   **Features:**
    -   PDF generation with 3 themes for professionals
    -   Download PDF
    -   Email PDF with delivery confirmation
-   **Status:** ✅ **MIGRATED** → `ListaPdfController::download()` and `ListaPdfController::email()`
-   **API Endpoints:**
    -   `GET /api/v1/calendars/{id}/lista/pdf`
    -   `POST /api/v1/calendars/{id}/lista/pdf/email`

#### `calendarioUpdateLista()` (lines 591-610) ✅ **MIGRATED**

-   **Purpose:** Toggle ingredient as taken/purchased
-   **Status:** ✅ **MIGRATED** → `ListaController::toggleTaken()`
-   **API Endpoint:** `POST /api/v1/calendars/{id}/lista/toggle-taken`

#### `ListaEmail()` (lines 611-638)

-   **Purpose:** Email lista as HTML (no PDF)
-   **Status:** ✅ **MIGRATED** → `ListaPdfController::emailHtml()`
-   **API Endpoint:** `POST /api/v1/calendars/{id}/lista/email-html`

#### `listaIngredientes()` (lines 639-652)

-   **Purpose:** Create custom lista ingredient
-   **Status:** ✅ **MIGRATED** → `ListaController::storeCustom()`
-   **API Endpoint:** `POST /api/v1/calendars/{id}/lista/items`

#### `UpdatelistasIngredients()` (lines 653-669) ✅ **MIGRATED**

-   **Purpose:** Update custom lista ingredient
-   **Status:** ✅ **MIGRATED** → `ListaController::updateCustom()`
-   **API Endpoint:** `PUT /api/v1/calendars/{id}/lista/items/{itemId}`

#### `deletelistasIngredients()` (lines 670-674)

-   **Purpose:** Delete custom lista ingredient
-   **Status:** ✅ **MIGRATED** → `ListaController::destroyCustom()`
-   **API Endpoint:** `DELETE /api/v1/calendars/{id}/lista/items/{itemId}`

---

### 4. Meal Plans

#### `planes()` (lines 688-702)

-   **Purpose:** List available meal plans (role-based)
-   **Status:** ✅ **MIGRATED** → `MealPlanController::index()`
-   **API Endpoint:** `GET /api/v1/plans`

#### `planesCalendario()` (lines 703-717)

-   **Purpose:** Get meal plan details with calendar
-   **Status:** ✅ **MIGRATED** → `MealPlanController::show()`
-   **API Endpoint:** `GET /api/v1/plans/{id}`

#### `copyPlanes()` (lines 718-778)

-   **Purpose:** Copy meal plan to user calendar with scaling
-   **Features:**
    -   Complex servings calculation
    -   Calendar scaling support
    -   Handles leftovers
-   **Status:** ✅ **MIGRATED** → `MealPlanController::copy()`
-   **API Endpoint:** `POST /api/v1/plans/{id}/copy`

#### `manipulateServings()` (lines 780-839)

-   **Purpose:** Calculate servings for main and side dishes (helper)
-   **Status:** ✅ **MIGRATED** → Private method in `MealPlanController`
-   **Migration:** Included in `MealPlanController::copy()`

#### `planesPdf()` (lines 841-848)

-   **Purpose:** Download meal plan as PDF
-   **Status:** ✅ **MIGRATED** → `MealPlanPdfController::download()`
-   **API Endpoint:** `GET /api/v1/plans/{id}/pdf`

#### `misPlanes()` (lines 850-853)

-   **Purpose:** View "my plans" page
-   **Status:** ⚪ NOT NEEDED (view route, not API)
-   **Migration:** N/A - Web view

---

### 5. Recipe Details & Actions

#### `receta()` (lines 865-868)

-   **Purpose:** View recipe page
-   **Status:** ⚪ NOT NEEDED (view route, not API)
-   **Migration:** N/A - Web view

#### `receta_vista()` (lines 888-919) 🟢 **NOT MIGRATED**

-   **Purpose:** Display recipe detail page with tracking
-   **Features:**
    -   Recipe details with nutrition
    -   Tips and time notes
    -   Calendar integration
    -   **View tracking** (implicit - page view)
-   **Status:** ❌ **NOT MIGRATED** (view tracking missing)
-   **Current API:** `RecipeController::show()` returns recipe data but doesn't track views
-   **Impact:** LOW - Analytics feature only

#### `reaction()` (lines 937-952)

-   **Purpose:** Add/update like/dislike reaction
-   **Status:** ✅ **MIGRATED** → `RecipeController::react()`
-   **API Endpoint:** `POST /api/v1/recipes/{id}/react`

#### `comment()` (lines 954-997)

-   **Purpose:** Add comment to recipe
-   **Features:**
    -   Comment creation
    -   Reply to comments (@mentions)
    -   Admin comments
    -   Notifications
-   **Status:** ✅ **MIGRATED** → `CommentController::store()`
-   **API Endpoint:** `POST /api/v1/recipes/{id}/comments`

#### `deleteComment()` (lines 999-1003)

-   **Purpose:** Delete comment
-   **Status:** ✅ **MIGRATED** → `CommentController::destroy()`
-   **API Endpoint:** `DELETE /api/v1/recipes/comments/{id}`

---

### 6. PDF Export

#### `pdf()` (lines 1005-1030)

-   **Purpose:** Download recipe PDF
-   **Features:**
    -   Themed PDFs for professionals (3 themes)
    -   User nutritional preferences
-   **Status:** ✅ **MIGRATED** → `PdfController::download()`
-   **API Endpoint:** `GET /api/v1/recipes/{id}/pdf`

#### `sendPdfMail()` (lines 1033-1105)

-   **Purpose:** Email recipe PDF
-   **Features:**
    -   PDF generation with themes
    -   Email to recipient
    -   Delivery confirmation to bemail
-   **Status:** ✅ **MIGRATED** → `PdfController::email()`
-   **API Endpoint:** `POST /api/v1/recipes/{id}/pdf/email`

---

### 7. Ingredients & Nutrition

#### `miRecetario()` (lines 855-863)

-   **Purpose:** View ingredient listing page
-   **Status:** ✅ **MIGRATED** → `IngredientController::index()`
-   **API Endpoint:** `GET /api/v1/ingredients`

#### `getNutrientesIngredientes()` (lines 921-924)

-   **Purpose:** Get nutrition info for ingredient
-   **Status:** ✅ **MIGRATED** → `IngredientController::instrucciones()`
-   **API Endpoint:** `GET /api/v1/ingredients/{id}/instrucciones`

---

### 8. Calendar Helpers

#### `getCalendarScheduleJson()` (lines 1106-1116) 🟡 **NOT MIGRATED**

-   **Purpose:** Get calendar schedules as JSON (AJAX)
-   **Features:**
    -   Returns main_schedule and sides_schedule for all user calendars
    -   Used by frontend for calendar management
-   **Status:** ❌ **NOT MIGRATED**
-   **Current API:** No endpoint exists
-   **Impact:** MEDIUM - Frontend may need this for calendar operations
-   **Note:** Could be part of CalendarController or separate endpoint

---

### 9. Utility/Testing Methods

#### `pruebaNutrimental()` (lines 675-678)

-   **Purpose:** Testing method for nutrition calculations
-   **Status:** ⚪ NOT NEEDED (testing only)
-   **Migration:** N/A

#### `recetasAlgolia()` (lines 680-686)

-   **Purpose:** Sync all recipes to Algolia search
-   **Status:** ⚪ NOT NEEDED (one-time migration script)
-   **Migration:** N/A - Use Laravel Scout

#### `testNutriente()` (lines 869-874)

-   **Purpose:** Testing method for nutrition data
-   **Status:** ⚪ NOT NEEDED (testing only)
-   **Migration:** N/A

#### `saveJson()` (lines 875-886)

-   **Purpose:** One-time migration script to save nutrition JSON
-   **Status:** ⚪ NOT NEEDED (one-time script)
-   **Migration:** N/A

#### `adjustSubrecetas()` (lines 1117-1153)

-   **Purpose:** One-time migration script to fix subrecipe links in tips
-   **Status:** ⚪ NOT NEEDED (one-time script)
-   **Migration:** N/A

---

## Migration Summary

### ✅ Fully Migrated (18 methods)

| Method                        | New Location                                 | API Endpoint                                   |
| ----------------------------- | -------------------------------------------- | ---------------------------------------------- |
| `calendarioLista()`           | `ListaController::index()`                   | `GET /calendars/{id}/lista`                    |
| `ListaRenderAll()`            | `ListaController::index()`                   | `GET /calendars/{id}/lista`                    |
| `ListaRender()`               | `ListaController::category()`                | `GET /calendars/{id}/lista/categories/{catId}` |
| `calendarioListaPdf()`        | `ListaPdfController::download()` + `email()` | `GET/POST /calendars/{id}/lista/pdf`           |
| `calendarioUpdateLista()`     | `ListaController::toggleTaken()`             | `POST /calendars/{id}/lista/toggle-taken`      |
| `ListaEmail()`                | `ListaPdfController::emailHtml()`            | `POST /calendars/{id}/lista/email-html`        |
| `listaIngredientes()`         | `ListaController::storeCustom()`             | `POST /calendars/{id}/lista/items`             |
| `UpdatelistasIngredients()`   | `ListaController::updateCustom()`            | `PUT /calendars/{id}/lista/items/{itemId}`     |
| `deletelistasIngredients()`   | `ListaController::destroyCustom()`           | `DELETE /calendars/{id}/lista/items/{itemId}`  |
| `planes()`                    | `MealPlanController::index()`                | `GET /plans`                                   |
| `planesCalendario()`          | `MealPlanController::show()`                 | `GET /plans/{id}`                              |
| `copyPlanes()`                | `MealPlanController::copy()`                 | `POST /plans/{id}/copy`                        |
| `manipulateServings()`        | `MealPlanController::copy()` (private)       | N/A                                            |
| `planesPdf()`                 | `MealPlanPdfController::download()`          | `GET /plans/{id}/pdf`                          |
| `reaction()`                  | `RecipeController::react()`                  | `POST /recipes/{id}/react`                     |
| `comment()`                   | `CommentController::store()`                 | `POST /recipes/{id}/comments`                  |
| `deleteComment()`             | `CommentController::destroy()`               | `DELETE /recipes/comments/{id}`                |
| `pdf()`                       | `PdfController::download()`                  | `GET /recipes/{id}/pdf`                        |
| `sendPdfMail()`               | `PdfController::email()`                     | `POST /recipes/{id}/pdf/email`                 |
| `miRecetario()`               | `IngredientController::index()`              | `GET /ingredients`                             |
| `getNutrientesIngredientes()` | `IngredientController::instrucciones()`      | `GET /ingredients/{id}/instrucciones`          |

**Total:** 21 methods migrated (55%)

---

### ❌ NOT MIGRATED - Critical Features (3 methods)

#### 1. `recetario()` 🔴 **HIGH PRIORITY**

-   **Lines:** 48-282
-   **Complexity:** VERY HIGH
-   **Features Missing:**
    -   Advanced nutrient filtering (30+ nutrients)
    -   JSON column queries (`nutrient_info->{fdc_id}->cantidad`)
    -   Ingredient inclusion/exclusion with subrecipe logic
    -   "Combined with parents" ingredient matching
    -   Session bookmark state management
-   **Current API:** `RecipeController::index()` only has basic filters
-   **Impact:** HIGH - Professional users cannot filter by detailed nutrition
-   **Estimated Effort:** 2-3 days

#### 2. `checkIfCombinedWithParentsIncludeAll()` 🔴 **HIGH PRIORITY**

-   **Lines:** 296-334
-   **Purpose:** Helper for complex ingredient matching
-   **Used by:** `recetario()` method
-   **Status:** Part of advanced filtering feature
-   **Impact:** Required for ingredient inclusion filtering
-   **Estimated Effort:** Included in recetario() migration

#### 3. `saveBookmark()` + `getBookmark()` 🟡 **MEDIUM PRIORITY**

-   **Lines:** 340-403
-   **Purpose:** Save/load filter bookmarks
-   **Features:**
    -   Save current filter state as named bookmark
    -   Load multiple bookmarks and merge filters
    -   Delete bookmarks
-   **Status:** Session-based, needs database migration
-   **Impact:** MEDIUM - Convenience feature
-   **Estimated Effort:** 1 day

---

### ⚠️ PARTIALLY MIGRATED (1 method)

#### `receta_vista()` 🟢 **LOW PRIORITY**

-   **Lines:** 888-919
-   **Purpose:** Recipe detail view with tracking
-   **Status:**
    -   ✅ Recipe data: `RecipeController::show()` returns full recipe
    -   ❌ View tracking: Not implemented
-   **Impact:** LOW - Analytics only
-   **Estimated Effort:** 4 hours

---

### ⚠️ POTENTIALLY MISSING (1 method)

#### `getCalendarScheduleJson()` 🟡 **MEDIUM PRIORITY**

-   **Lines:** 1106-1116
-   **Purpose:** Get calendar schedules as JSON for frontend
-   **Status:** ❌ **NOT MIGRATED**
-   **Current API:** CalendarController has full calendar data, but this specific endpoint may be needed
-   **Impact:** MEDIUM - Frontend may depend on this specific format
-   **Estimated Effort:** 2 hours

---

### ⚪ NOT NEEDED (12 methods)

| Method                | Reason                          |
| --------------------- | ------------------------------- |
| `recetas()`           | Web route redirect              |
| `paginate()`          | Laravel has built-in pagination |
| `getUrl()`            | API resources handle URLs       |
| `misPlanes()`         | Web view                        |
| `receta()`            | Web view                        |
| `pruebaNutrimental()` | Testing only                    |
| `recetasAlgolia()`    | One-time migration script       |
| `testNutriente()`     | Testing only                    |
| `saveJson()`          | One-time migration script       |
| `adjustSubrecetas()`  | One-time migration script       |

---

## Cross-Check with Migrated API

### RecipeController.php (Current API)

**What's Implemented:**

-   ✅ Basic recipe listing with pagination
-   ✅ Search by name (Algolia/Scout)
-   ✅ Basic filters: tags, calories (min/max), tipo_id
-   ✅ Recipe details by slug
-   ✅ Bookmark toggle
-   ✅ Reactions (like/dislike)
-   ✅ Recipe stats
-   ✅ Similar recipes
-   ✅ Popular recipes

**What's MISSING from `recetario()`:**

-   ❌ Advanced nutrient filtering (30+ nutrients)
-   ❌ Ingredient inclusion (ALL required)
-   ❌ Ingredient exclusion
-   ❌ Number of ingredients filter
-   ❌ Cooking time filter
-   ❌ Subrecipe parent/child logic
-   ❌ "Combined with parents" ingredient matching
-   ❌ Filter bookmark integration
-   ❌ Session-based filter state

**Gap Analysis:**

-   Current `RecipeController::index()` handles ~20% of `recetario()` functionality
-   Missing ~80% of advanced filtering features

---

## Critical Missing Features

### 1. Advanced Recipe Filtering (`recetario()`) 🔴

**Current State:**

-   `RecipeController::index()` has basic filters only
-   No nutrient filtering
-   No ingredient inclusion/exclusion
-   No subrecipe logic

**Required Implementation:**

```php
POST /api/v1/recipes/advanced-filter
{
  "tags": [1, 2, 3],
  "ingrediente_incluir": [5, 10, 15],  // ALL required
  "ingrediente_excluir": [20, 25],
  "num_ingredientes": {"min": 3, "max": 8},
  "num_tiempo": {"min": 15, "max": 60},
  "calorias": {"min": 200, "max": 500},
  "nutrientes": {
    "1005": {"min": 10, "max": 50},  // Protein
    "1079": {"min": 5, "max": 20},   // Carbs
    // ... 30+ more nutrients
  }
}
```

**Complexity:**

-   JSON column queries: `nutrient_info->{fdc_id}->cantidad`
-   Factor calculations for nutrients
-   Subrecipe parent/child relationships
-   "Combined with parents" ingredient matching
-   Collection filtering after query

---

### 2. Filter Bookmarks 🟡

**Current State:**

-   No API endpoints
-   Bookmarks stored in session (old system)
-   Need database migration

**Required Implementation:**

```php
POST /api/v1/filters/bookmarks
{
  "name": "High Protein Low Carb"
}

GET /api/v1/filters/bookmarks
GET /api/v1/filters/bookmarks/{id}
DELETE /api/v1/filters/bookmarks/{id}
```

---

### 3. Recipe View Tracking 🟢

**Current State:**

-   `RecipeController::show()` returns recipe data
-   No view tracking

**Required Implementation:**

```php
POST /api/v1/recipes/{id}/view  // Track view
GET /api/v1/profile/recent-views  // Get view history
```

---

### 4. Calendar Schedule JSON 🟡

**Current State:**

-   `CalendarController` has full calendar CRUD
-   May need specific endpoint for frontend

**Required Implementation:**

```php
GET /api/v1/calendars/schedules
// Returns: {calendar_id: {main_schedule, sides_schedule}}
```

---

## Summary Statistics

| Category                    | Count | Status |
| --------------------------- | ----- | ------ |
| **Total Methods**           | 38    | -      |
| **Fully Migrated**          | 21    | ✅ 55% |
| **Not Migrated (Critical)** | 3     | ❌ 8%  |
| **Not Migrated (Medium)**   | 2     | ⚠️ 5%  |
| **Not Needed**              | 12    | ⚪ 32% |

**Migration Progress:** 55% of methods migrated  
**Feature Parity:** ~80% (core features working, advanced filtering missing)

---

## Recommendations

### Immediate Priority 🔴

1. **Implement Advanced Recipe Filtering**
    - Create `RecipeFilterService` for complex logic
    - Add `POST /api/v1/recipes/advanced-filter` endpoint
    - Implement nutrient JSON queries
    - Add subrecipe parent/child logic
    - **Estimated:** 2-3 days

### Medium Priority 🟡

2. **Filter Bookmarks**

    - Create `FilterBookmarkController`
    - Migrate from session to database
    - Add 4 API endpoints
    - **Estimated:** 1 day

3. **Calendar Schedule JSON**
    - Add endpoint if frontend requires it
    - **Estimated:** 2 hours

### Low Priority 🟢

4. **Recipe View Tracking**
    - Add view tracking endpoint
    - **Estimated:** 4 hours

---

## Conclusion

**Current Status:**

-   ✅ 21/38 methods migrated (55%)
-   ✅ Core features working (lista, meal plans, basic recipes)
-   ❌ Advanced filtering missing (critical for professionals)
-   ⚠️ Filter bookmarks missing (convenience feature)

**Next Steps:**

1. Implement `recetario()` advanced filtering as `POST /api/v1/recipes/advanced-filter`
2. Add filter bookmark endpoints
3. Add view tracking if needed
4. Add calendar schedule JSON if frontend requires it

**Total Remaining Effort:** ~4-5 days for 100% feature parity
