# RecetasController Migration Roadmap

## 🎯 Overview

`RecetasController.php` is the **CORE** of the application (1155 lines). It handles:

-   Advanced recipe filtering with nutrients
-   Lista de ingredientes (ingredient lists) & PDF export
-   Meal plans (viewing, copying, PDF)
-   Recipe bookmarks/filters
-   Calendar integration

---

## 📊 Current Migration Status

### ✅ Already Migrated (25%)

-   ✅ `comment()` → CommentController@store (lines 954-997)
-   ✅ `deleteComment()` → CommentController@destroy (lines 999-1003)
-   ✅ `pdf()` → PdfController@download (lines 1005-1030)
-   ✅ `sendPdfMail()` → PdfController@email (lines 1033-1105)
-   ✅ `reaction()` → RecipeService@addReaction (lines 937-952)

### ⏳ NOT YET MIGRATED (75%)

---

## 🗺️ Complete Migration Roadmap

### Phase A: Recipe Browsing & Advanced Filtering (Lines 34-282)

**Priority: HIGH** - Core recipe discovery

#### Methods to Migrate:

1. **`recetario()` (lines 48-282)** - Main recipe listing with COMPLEX filtering

    - Tags filtering
    - Ingredient inclusion/exclusion
    - Number of ingredients filter
    - Cooking time filter
    - Calories filter
    - **30+ nutrients filtering** (complex JSON queries)
    - Subrecipe handling (parent/child relationships)
    - Bookmark session management

    **Complexity: VERY HIGH** ⚠️

    ```php
    // Filter by nutrients stored in JSON column
    $query->where('nutrient_info->' . $clave . '->cantidad', '>', (int) $nutriente['min']);

    // Handle subrecipes with parent relationships
    $matchingChildrenForExclude = RecetaInstruccionReceta::where('receta_id', $receta->id)
        ->whereNotNull('subreceta_id')->get(['subreceta_id']);
    ```

2. **`paginate()` (lines 283-294)** - Custom pagination helper

3. **`checkIfCombinedWithParentsIncludeAll()` (lines 296-334)** - Complex ingredient logic
    - Checks if parent+child recipes combined satisfy "include all" ingredients

**API Endpoints Needed:**

-   `GET /api/v1/recipes/advanced-filter` - Advanced filtering with nutrients
-   Keep existing `GET /api/v1/recipes` for simple filtering

---

### Phase B: Bookmark & Filter Management (Lines 335-403)

**Priority: MEDIUM** - User saved filters

#### Methods to Migrate:

1. **`getUrl()` (line 335-338)** - Helper for recipe URLs (skip, use Resource)

2. **`saveBookmark()` (lines 340-352)** - Save filter as bookmark
    - Saves current session filter state
    - Creates bookmark with name
3. **`getBookmark()` (lines 354-403)** - Load/delete saved bookmarks
    - Load bookmarks and merge filters
    - Delete bookmarks

**API Endpoints Needed:**

-   `POST /api/v1/filters/save` - Save current filter as bookmark
-   `GET /api/v1/filters/bookmarks` - List saved filter bookmarks
-   `POST /api/v1/filters/bookmarks/load` - Load bookmark(s)
-   `DELETE /api/v1/filters/bookmarks/{id}` - Delete bookmark

---

### Phase C: Lista de Ingredientes (Ingredient Lists) (Lines 405-674)

**Priority: HIGH** - Critical feature

#### Methods to Migrate:

1. **`calendarioLista()` (lines 405-436)** - View calendar's lista page

    - Gets calendar's lista de ingredientes
    - Retrieves taken/checked ingredients
    - Categories

2. **`ListaRenderAll()` (lines 439-464)** - Get all lista ingredients (AJAX)

    - Gets ingredients grouped by category
    - Uses helper: `getRelatedIngrediente()`

3. **`ListaRender()` (lines 466-512)** - Get ingredients for specific category (AJAX)

    - Professional users get modal HTML
    - Sorts by calendar day labels

4. **`calendarioListaPdf()` (lines 514-589)** - 🔥 **LISTA PDF EXPORT**

    - **Theme support** (Classic/Modern/Bold for professionals)
    - Download or email PDF
    - Complex ingredient counting
    - Email with delivery confirmation

5. **`calendarioUpdateLista()` (lines 591-610)** - Toggle ingredient as "taken"

    - Marks/unmarks ingredients in `lista_ingrediente_taken` table

6. **`ListaEmail()` (lines 611-638)** - Email lista (HTML)

7. **`listaIngredientes()` (lines 639-652)** - Create custom lista ingredient

8. **`UpdatelistasIngredients()` (lines 653-669)** - Update custom ingredient

9. **`deletelistasIngredients()` (lines 670-674)** - Delete custom ingredient

**API Endpoints Needed:**

-   `GET /api/v1/calendars/{id}/lista` - Get lista de ingredientes
-   `GET /api/v1/calendars/{id}/lista/categories` - Get by category
-   `GET /api/v1/calendars/{id}/lista/pdf` - Download PDF
-   `POST /api/v1/calendars/{id}/lista/pdf/email` - Email PDF
-   `POST /api/v1/calendars/{id}/lista/toggle-taken` - Mark taken
-   `POST /api/v1/calendars/{id}/lista/items` - Add custom item
-   `PUT /api/v1/calendars/{id}/lista/items/{itemId}` - Update item
-   `DELETE /api/v1/calendars/{id}/lista/items/{itemId}` - Delete item

---

### Phase D: Meal Plans (Lines 688-848)

**Priority: HIGH** - Important feature

#### Methods to Migrate:

1. **`planes()` (lines 688-702)** - List available meal plans

    - Filters by user role (free vs paid)
    - Returns view with plans

2. **`planesCalendario()` (lines 703-717)** - View specific plan calendar

    - Shows plan's calendar structure

3. **`copyPlanes()` (lines 718-778)** - 🔥 **COPY MEAL PLAN TO USER CALENDAR**

    - Complex servings calculation
    - Scales servings by user input
    - Creates new calendar from plan template
    - Handles main meals and sides separately

4. **`manipulateServings()` (lines 780-839)** - Helper for servings calculation

    - Calculates leftovers
    - Maps meals to days of week
    - Very complex logic

5. **`planesPdf()` (lines 841-848)** - Export plan as PDF
    - Landscape orientation

**API Endpoints Needed:**

-   `GET /api/v1/plans` - List available plans
-   `GET /api/v1/plans/{id}` - Get plan details
-   `GET /api/v1/plans/{id}/calendar` - View plan calendar
-   `POST /api/v1/plans/{id}/copy` - Copy plan to user calendar (with scaling)
-   `GET /api/v1/plans/{id}/pdf` - Download plan PDF

---

### Phase E: Recipe Viewing & Helpers (Lines 850-924)

**Priority: LOW** - Mostly web views

#### Methods to Migrate:

1. **`misPlanes()` (line 850-853)** - View user's plans (web only)

2. **`miRecetario()` (lines 855-863)** - View user's recipe book (web only)

3. **`receta()` / `receta_vista()` (lines 865-919)** - Recipe detail view (web only)

    - Complex nutritional info sorting
    - Tips processing
    - Notes processing

4. **`getNutrientesIngredientes()` (lines 921-924)** - Get recipe nutrients
    - Already available in RecipeResource

**API Endpoints Needed:**

-   `GET /api/v1/recipes/{slug}/nutritional-info` - Detailed nutritional data
-   (Most of this is already covered by RecipeResource)

---

### Phase F: Utility & Admin Functions (Lines 675-1116)

**Priority: LOW** - Admin/utility tools

#### Methods to Migrate:

1. **`pruebaNutrimental()` (lines 675-678)** - Test function (skip)

2. **`recetasAlgolia()` (lines 680-686)** - Reindex Algolia (admin tool)

    - Could be artisan command instead

3. **`saveJson()` (lines 875-886)** - Test function (skip)

4. **`testNutriente()` (lines 869-874)** - Test function (skip)

5. **`getCalendarScheduleJson()` (lines 1106-1116)** - Get calendar JSON (AJAX)

    - Returns calendar schedules

6. **`adjustSubrecetas()` (lines 1117-1153)** - One-time migration script (skip)

**API Endpoints Needed:**

-   `GET /api/v1/admin/recipes/reindex` - Reindex Algolia (admin only)
-   `GET /api/v1/calendars/schedules` - Get calendar schedules JSON

---

## 🎯 Recommended Migration Order

### **PHASE 1: Lista de Ingredientes (Critical)** ⭐⭐⭐

Lines 405-674 (9 methods)

-   Calendar ingredient lists - most used feature after recipes
-   PDF generation is complex
-   ~2-3 days work

### **PHASE 2: Meal Plans** ⭐⭐⭐

Lines 688-848 (5 methods)

-   Important monetization feature
-   Complex servings calculations
-   ~2 days work

### **PHASE 3: Advanced Recipe Filtering** ⭐⭐

Lines 48-282 (1 massive method)

-   Very complex but crucial
-   30+ nutrient filters
-   Subrecipe logic
-   ~3-4 days work

### **PHASE 4: Filter Bookmarks** ⭐

Lines 335-403 (3 methods)

-   User convenience feature
-   ~1 day work

### **PHASE 5: Utilities & Admin**

Lines 675-1116 (6 methods)

-   Low priority
-   ~1 day work

---

## 📦 New Controllers Needed

```
app/Http/Controllers/Api/V1/
├── Recipes/
│   ├── RecipeController.php ✅ (exists)
│   ├── CommentController.php ✅ (exists)
│   ├── PdfController.php ✅ (exists)
│   └── AdvancedFilterController.php ⚠️ (NEW - for advanced filtering)
│
├── Calendars/
│   ├── CalendarController.php ✅ (exists)
│   ├── ListaController.php ⚠️ (NEW - lista de ingredientes)
│   └── ListaPdfController.php ⚠️ (NEW - lista PDF export)
│
├── Plans/
│   ├── PlanController.php ⚠️ (NEW)
│   └── PlanPdfController.php ⚠️ (NEW)
│
└── Filters/
    └── BookmarkController.php ⚠️ (NEW)
```

---

## 🚨 Critical Complexity Notes

### 1. **Nutrient Filtering** (Very Complex)

```php
// Queries JSON column with 30+ different nutrients
$query->where('nutrient_info->' . $nutrienteId . '->cantidad', '>=', $minValue);

// Each nutrient has:
// - min/max values
// - factor conversions
// - cien_porciento (100% daily value)
```

### 2. **Subrecipe Logic** (Complex)

```php
// Recipes can have child recipes (subrecetas)
// When filtering by ingredients:
// - Must check parent recipes
// - Must check child recipes
// - Must check if parent + child combined satisfy filters
```

### 3. **Lista PDF Export** (Complex)

```php
// Must:
// - Get all recipe ingredients from calendar
// - Group by category
// - Remove "taken" ingredients
// - Add custom lista ingredients
// - Count total ingredients
// - Generate themed PDF (3 themes for professionals)
// - Email with delivery confirmation
```

### 4. **Meal Plan Servings** (Very Complex)

```php
// Must calculate:
// - Main meals servings
// - Side meals servings
// - Leftovers tracking
// - Servings scaling by user input
// - Map to 7 days of week
// - Handle main + side interactions
```

---

## 📊 Estimated Timeline

| Phase                    | Methods | Complexity | Days          |
| ------------------------ | ------- | ---------- | ------------- |
| Lista (Ingredient Lists) | 9       | High       | 2-3           |
| Meal Plans               | 5       | High       | 2             |
| Advanced Filters         | 1       | Very High  | 3-4           |
| Filter Bookmarks         | 3       | Medium     | 1             |
| Utilities                | 6       | Low        | 1             |
| **TOTAL**                | **24**  | -          | **9-11 days** |

---

## ✅ Immediate Next Steps

1. **Create ListaController** - Start with Phase 1 (lista de ingredientes)
2. **Create ListaPdfController** - Handle lista PDF generation
3. **Test with existing database** - Lista is most critical feature
4. **Then move to Meal Plans** - Second most important

---

## 🔧 Helper Functions to Consider

From `app/Helpers/helper.php`:

-   `getRelatedIngrediente()` - Used by shopping list
-   `todaySpanishDay()` - Used by emails
-   Need to verify these exist and work

---

**Status**: Ready to start Phase 1 (Shopping List Migration)
**Current Progress**: 25% of RecetasController migrated
**Target**: 100% migration in ~10 days of focused work
