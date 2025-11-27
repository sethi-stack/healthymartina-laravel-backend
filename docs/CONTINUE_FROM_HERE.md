# 🚀 Continue Laravel 11 Migration: Lista de Ingredientes (Phase 1)

## 📍 Current Status

### ✅ Completed (Phases 1-10)
- **48 API endpoints** fully functional
- **41 models** migrated
- **52 controllers** copied
- **18 packages** upgraded to Laravel 11
- Basic CRUD for: Auth, Recipes, Calendars, Subscriptions, Comments, PDF Export

### ⏳ Current Task: RecetasController Migration - Phase 1
**Working on:** Lista de Ingredientes (Calendar Ingredient Lists)  
**Lines:** 405-674 of RecetasController.php  
**Priority:** HIGH ⭐⭐⭐ (Most used feature after recipes)

---

## 🎯 Your Mission

Migrate the **Lista de Ingredientes** feature from `RecetasController.php` to new V1 API controllers.

### What is "Lista de Ingredientes"?
- Calendar-based ingredient lists generated from user's meal plans
- Shows all ingredients needed for recipes in a calendar
- Grouped by categories (produce, dairy, meat, etc.)
- Users can mark items as "taken" (checked off)
- Can add custom ingredients manually
- Exports to PDF with themes (Classic/Modern/Bold for professionals)
- Can email the PDF

---

## 📋 Methods to Migrate (9 total)

### From RecetasController.php:

1. **`calendarioLista()`** (lines 405-436)
   - Get calendar's lista de ingredientes
   - Retrieves taken/checked ingredients from `lista_ingrediente_taken` table
   - Groups by categories

2. **`ListaRenderAll()`** (lines 439-464)
   - Get ALL ingredients grouped by category
   - Uses helper: `getRelatedIngrediente()` from `app/Helpers/helper.php`

3. **`ListaRender()`** (lines 466-512)
   - Get ingredients for SPECIFIC category
   - Professional users get modal HTML (skip for API)
   - Sorts by calendar day labels

4. **`calendarioListaPdf()`** (lines 514-589) 🔥 CRITICAL
   - Generate PDF with theme support (3 themes for professionals)
   - Download OR email PDF
   - Uses DomPDF
   - Email with delivery confirmation
   - Complex ingredient counting

5. **`calendarioUpdateLista()`** (lines 591-610)
   - Toggle ingredient as "taken/checked"
   - Updates `lista_ingrediente_taken` table

6. **`ListaEmail()`** (lines 611-638)
   - Email lista as HTML (without PDF)

7. **`listaIngredientes()`** (lines 639-652)
   - Create custom lista ingredient
   - Validates: cantidad, nombre, categoria

8. **`UpdatelistasIngredients()`** (lines 653-669)
   - Update custom ingredient

9. **`deletelistasIngredients()`** (lines 670-674)
   - Delete custom ingredient

---

## 🏗️ Controllers to Create

### 1. `ListaController.php`
**Location:** `app/Http/Controllers/Api/V1/Calendars/ListaController.php`

**Methods needed:**
```php
- index(Request $request, int $calendarId)           // Get all ingredients
- categories(Request $request, int $calendarId)      // Get by category
- toggleTaken(Request $request, int $calendarId)     // Mark taken
- storeCustom(Request $request, int $calendarId)     // Add custom item
- updateCustom(Request $request, int $calendarId, int $itemId)  // Update item
- destroyCustom(int $calendarId, int $itemId)        // Delete item
```

### 2. `ListaPdfController.php`
**Location:** `app/Http/Controllers/Api/V1/Calendars/ListaPdfController.php`

**Methods needed:**
```php
- download(Request $request, int $calendarId)        // Download PDF
- email(Request $request, int $calendarId)           // Email PDF
```

---

## 🛣️ API Routes to Add

Add to `routes/api.php`:

```php
// Lista de Ingredientes (Calendar ingredient lists)
Route::prefix('calendars/{calendarId}/lista')->group(function () {
    Route::get('/', [ListaController::class, 'index'])
        ->name('api.v1.calendars.lista.index');
    
    Route::get('/categories/{categoryId}', [ListaController::class, 'categories'])
        ->name('api.v1.calendars.lista.categories');
    
    Route::post('/toggle-taken', [ListaController::class, 'toggleTaken'])
        ->name('api.v1.calendars.lista.toggle');
    
    Route::post('/items', [ListaController::class, 'storeCustom'])
        ->name('api.v1.calendars.lista.items.store');
    
    Route::put('/items/{itemId}', [ListaController::class, 'updateCustom'])
        ->name('api.v1.calendars.lista.items.update');
    
    Route::delete('/items/{itemId}', [ListaController::class, 'destroyCustom'])
        ->name('api.v1.calendars.lista.items.destroy');
    
    // PDF Export
    Route::get('/pdf', [ListaPdfController::class, 'download'])
        ->name('api.v1.calendars.lista.pdf');
    
    Route::post('/pdf/email', [ListaPdfController::class, 'email'])
        ->name('api.v1.calendars.lista.pdf.email');
});
```

---

## 📦 Models Already Available

- ✅ `Calendar` - Calendar model
- ✅ `ListaIngredientes` - Custom lista ingredients
- ✅ `Categoria` - Ingredient categories
- ⚠️ `lista_ingrediente_taken` - Table (direct DB access, no model)

---

## 🔧 Helper Functions Available

From `app/Helpers/helper.php`:

```php
getRelatedIngrediente($calendario_id, $categoria_id, $use = "list")
// Returns ingredients for a calendar/category
// Used by ListaRenderAll() and ListaRender()

todaySpanishDay()
// Returns current date in Spanish format
// Used in emails
```

---

## 📄 PDF Views Available

Professional users (role_id == 3) get themed PDFs:

**Theme 1 (Classic):**
- `resources/views/pdf/classic/classic-lista.blade.php`

**Theme 2 (Modern):**
- `resources/views/pdf/modern/modern-lista.blade.php`

**Theme 3 (Bold):**
- `resources/views/pdf/bold/bold-lista.blade.php`

**Free users:**
- `resources/views/pdf/lista-pdf.blade.php`

---

## 🎨 Resources to Create (Optional)

```php
app/Http/Resources/Lista/
├── ListaResource.php          // For lista items
└── CategoryResource.php       // For categories
```

---

## ⚠️ Important Implementation Notes

### 1. User Authorization
```php
// Always check calendar belongs to user
$calendar = Calendar::where('id', $calendarId)
    ->where('user_id', Auth::id())
    ->firstOrFail();
```

### 2. Taken Ingredients
```php
// Table: lista_ingrediente_taken
// Columns: calendario_id, categoria_id, ingrediente_id, ingrediente_type
// No Eloquent model - use DB facade
```

### 3. PDF Generation Logic
```php
// Get user theme from auth()->user()->theme (1, 2, or 3)
// Professional users (role_id == 3) get themes
// Free users get standard PDF
// Count ingredients: (recipe_ingredients + custom) - taken
```

### 4. Email Functionality
```php
// Send to custom email or auth()->user()->email
// Attach PDF
// Send delivery confirmation to auth()->user()->bemail
// Use Mail facade with views:
//   - email.send-lista-mail
//   - email.delivery-email
```

---

## 📊 Database Tables

### `lista_ingredientes`
```sql
- id
- calendario_id
- cantidad
- nombre
- categoria
- created_at, updated_at
```

### `lista_ingrediente_taken`
```sql
- calendario_id
- categoria_id
- ingrediente_id
- ingrediente_type
```

### `categorias`
```sql
- id
- nombre
- sort
- created_at, updated_at
```

---

## 🧪 Testing Steps

1. **Create ListaController** with all 6 methods
2. **Create ListaPdfController** with 2 methods
3. **Add routes** to `routes/api.php`
4. **Test with curl/Postman:**
   ```bash
   # Get lista
   curl -X GET http://localhost:8000/api/v1/calendars/1/lista \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   
   # Toggle taken
   curl -X POST http://localhost:8000/api/v1/calendars/1/lista/toggle-taken \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"categoria_id":1,"ingrediente_id":5,"ingrediente_type":"recipe"}'
   
   # Download PDF
   curl -X GET http://localhost:8000/api/v1/calendars/1/lista/pdf \
     -H "Authorization: Bearer YOUR_TOKEN" \
     --output lista.pdf
   ```

---

## 📚 Reference Files

**Main reference:**
- `app/Http/Controllers/RecetasController.php` (lines 405-674)

**Already migrated (for reference):**
- `app/Http/Controllers/Api/V1/Recipes/PdfController.php` (similar PDF logic)
- `app/Http/Controllers/Api/V1/Calendars/CalendarController.php` (calendar access patterns)

**Helper:**
- `app/Helpers/helper.php` (getRelatedIngrediente, todaySpanishDay)

**Documentation:**
- `RECETAS_CONTROLLER_MIGRATION_ROADMAP.md` (full roadmap)
- `API_ENDPOINTS_REFERENCE.md` (existing API reference)

---

## 🎯 Success Criteria

✅ 8 new API endpoints working  
✅ Can retrieve lista for any calendar  
✅ Can toggle ingredients as taken  
✅ Can add/edit/delete custom ingredients  
✅ PDF generation works with themes  
✅ PDF email delivery works  
✅ All endpoints return proper JSON  
✅ Authorization checks in place  

---

## 💡 Suggested Prompt for New Chat

```
I'm continuing a Laravel 11 migration project. I need to implement Phase 1: Lista de Ingredientes (Calendar Ingredient Lists) API endpoints.

Current status:
- 48 API endpoints already working
- Laravel 11 with Sanctum authentication
- Working from RecetasController.php lines 405-674

Task: Create 2 new controllers (ListaController and ListaPdfController) to migrate 9 methods from RecetasController:
1. calendarioLista() - get lista
2. ListaRenderAll() - get all by category
3. ListaRender() - get specific category
4. calendarioListaPdf() - generate PDF (with 3 theme support)
5. calendarioUpdateLista() - toggle taken
6. ListaEmail() - email HTML
7. listaIngredientes() - create custom item
8. UpdatelistasIngredients() - update item
9. deletelistasIngredients() - delete item

Need 8 API endpoints:
- GET /api/v1/calendars/{id}/lista
- GET /api/v1/calendars/{id}/lista/categories/{categoryId}
- POST /api/v1/calendars/{id}/lista/toggle-taken
- POST /api/v1/calendars/{id}/lista/items
- PUT /api/v1/calendars/{id}/lista/items/{itemId}
- DELETE /api/v1/calendars/{id}/lista/items/{itemId}
- GET /api/v1/calendars/{id}/lista/pdf
- POST /api/v1/calendars/{id}/lista/pdf/email

Please read CONTINUE_FROM_HERE.md for full details and start implementing.

Location: /Users/dj/Documents/Programming/healthymartina/healthymartina_app/laravel-backend-app/
```

---

**Ready to start!** 🚀  
**Estimated time:** 2-3 days  
**Next after this:** Phase 2 - Meal Plans

