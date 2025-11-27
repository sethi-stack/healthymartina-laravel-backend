# 📋 Lista de Ingredientes API Documentation

## Overview

The Lista de Ingredientes (Ingredient Lists) API allows users to manage shopping lists generated from their calendar meal plans. This feature automatically aggregates ingredients from recipes scheduled in a calendar and allows users to:

-   View ingredients grouped by categories
-   Mark ingredients as taken/purchased
-   Add custom ingredients manually
-   Export lists to PDF with theme support
-   Email lists to recipients

## Endpoints

All endpoints require authentication via Sanctum token (`Authorization: Bearer {token}`).

Base path: `/api/v1/calendars/{calendarId}/lista`

---

### 1. Get All Ingredients

**GET** `/api/v1/calendars/{calendarId}/lista`

Retrieves all ingredients for a calendar's lista, grouped by categories.

**Response:**

```json
{
    "calendar": {
        "id": 1,
        "title": "My Weekly Plan"
    },
    "categories": [
        {
            "id": 1,
            "nombre": "Verduras",
            "sort": 1
        }
    ],
    "ingredients": {
        "1": [
            {
                "ingrediente_id": 5,
                "ingrediente": "Tomate",
                "cantidad": 2,
                "medida": "unidades",
                "categoria_id": 1,
                "day": "lunes",
                "meal": "almuerzo",
                "repeat": []
            }
        ]
    },
    "taken_ingredients": [
        {
            "calendario_id": 1,
            "categoria_id": 1,
            "ingrediente_id": 5,
            "ingrediente_type": "receta"
        }
    ],
    "custom_items": [
        {
            "id": 10,
            "calendario_id": 1,
            "cantidad": 1,
            "nombre": "Pan integral",
            "categoria": 3
        }
    ],
    "total_count": 25
}
```

---

### 2. Get Ingredients by Category

**GET** `/api/v1/calendars/{calendarId}/lista/categories/{categoryId}`

Retrieves ingredients for a specific category, sorted by calendar days.

**Response:**

```json
{
  "calendar_id": 1,
  "category": {
    "id": 1,
    "nombre": "Verduras",
    "sort": 1
  },
  "ingredients": [...],
  "ingredients_sorted": {
    "lunes": [...],
    "martes": [...]
  },
  "taken_ingredients": [...],
  "custom_items": [...],
  "count": 8
}
```

---

### 3. Toggle Ingredient as Taken

**POST** `/api/v1/calendars/{calendarId}/lista/toggle-taken`

Marks an ingredient as taken/purchased or unmarks it (toggles).

**Request Body:**

```json
{
    "categoria_id": 1,
    "ingrediente_id": 5,
    "ingrediente_type": "receta"
}
```

**Response:**

```json
{
  "success": true,
  "action": "created",
  "taken_ingredients": [...],
  "message": "Ingredient marked as taken"
}
```

---

### 4. Add Custom Ingredient

**POST** `/api/v1/calendars/{calendarId}/lista/items`

Adds a custom ingredient to the lista.

**Request Body:**

```json
{
    "cantidad": 2,
    "nombre": "Pan integral",
    "categoria": 3
}
```

**Validation:**

-   `cantidad`: required, numeric
-   `nombre`: required, string, max 255 characters
-   `categoria`: required, integer, must exist in categorias table

**Response:**

```json
{
    "success": true,
    "item": {
        "id": 10,
        "calendario_id": 1,
        "cantidad": 2,
        "nombre": "Pan integral",
        "categoria": 3,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    "message": "Custom ingredient added successfully"
}
```

---

### 5. Update Custom Ingredient

**PUT** `/api/v1/calendars/{calendarId}/lista/items/{itemId}`

Updates an existing custom ingredient.

**Request Body:**

```json
{
    "cantidad": 3,
    "nombre": "Pan integral actualizado",
    "categoria": 3
}
```

**Response:**

```json
{
  "success": true,
  "item": {...},
  "message": "Custom ingredient updated successfully"
}
```

---

### 6. Delete Custom Ingredient

**DELETE** `/api/v1/calendars/{calendarId}/lista/items/{itemId}`

Deletes a custom ingredient.

**Response:**

```json
{
    "success": true,
    "message": "Custom ingredient deleted successfully"
}
```

---

### 7. Download Lista as PDF

**GET** `/api/v1/calendars/{calendarId}/lista/pdf`

Generates and downloads the lista as a PDF file.

**Query Parameters:**

-   `lista_ingredients` (optional): JSON string of recipe ingredients

**Response:**

-   Content-Type: `application/pdf`
-   Downloads file: `{calendar_title}.pdf`

**PDF Themes:**

-   Professional users (role_id == 3) get themed PDFs based on their theme setting:
    -   Theme 1: Classic
    -   Theme 2: Modern
    -   Theme 3: Bold
-   Free users get the standard PDF template

---

### 8. Email Lista as PDF

**POST** `/api/v1/calendars/{calendarId}/lista/pdf/email`

Generates the lista as PDF and emails it to a recipient.

**Request Body:**

```json
{
    "recipient_email": "user@example.com",
    "lista_ingredients": "{}",
    "plantillas": ""
}
```

**Validation:**

-   `recipient_email`: optional, email format, defaults to user's email
-   `lista_ingredients`: required, valid JSON string
-   `plantillas`: optional, string

**Features:**

-   Sends PDF attachment to recipient
-   Sends delivery confirmation to user's business email (bemail) if set
-   Uses Spanish date format for email content

**Response:**

```json
{
    "success": true,
    "message": "Se envió por mail exitosamente"
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "El correo electrónico del destinatario no es válido"
}
```

---

### 9. Email Lista as HTML (No PDF)

**POST** `/api/v1/calendars/{calendarId}/lista/email-html`

Sends the lista as an HTML email without PDF attachment.

**Request Body:**

```json
{
    "lista_ingredients": "{}"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Lista enviada por correo electrónico exitosamente"
}
```

---

## Authorization

All endpoints verify that:

1. User is authenticated (Sanctum token)
2. Calendar belongs to the authenticated user

**Unauthorized access returns:**

```json
{
    "message": "No query results for model [App\\Models\\Calendar] {id}"
}
```

---

## Helper Functions Used

### `getRelatedIngrediente($calendario_id, $categoria_id, $use = "list")`

Located in `app/Helpers/helper.php`

-   Aggregates ingredients from all recipes in the calendar
-   Groups by category
-   Handles servings and leftovers
-   Merges duplicate ingredients
-   Returns sorted ingredient array

### `todaySpanishDay()`

Returns current date in Spanish format for email content.

---

## Database Tables

### `lista_ingredientes`

Custom ingredients added by user.

```sql
- id (primary key)
- calendario_id (foreign key)
- cantidad (decimal)
- nombre (string)
- categoria (integer, foreign key to categorias)
- created_at, updated_at
```

### `lista_ingrediente_taken`

Tracks which ingredients are marked as taken/purchased.

```sql
- calendario_id (integer)
- categoria_id (integer)
- ingrediente_id (integer)
- ingrediente_type (string: 'receta' or 'custom')
```

_Note: This table has no Eloquent model, accessed via DB facade._

### `categorias`

Ingredient categories.

```sql
- id (primary key)
- nombre (string)
- sort (integer)
- created_at, updated_at
```

---

## Models

### `ListaIngredientes`

-   **Location:** `app/Models/ListaIngredientes.php`
-   **Table:** `lista_ingredientes`
-   **Fillable:** All except `id`

### `Calendar`

-   **Location:** `app/Models/Calendar.php`
-   **Table:** `calendars`
-   **Relations:** `belongsTo User`

### `Categoria`

-   **Location:** `app/Models/Categoria.php`
-   **Table:** `categorias`
-   **Relations:** `hasMany Ingrediente`

---

## Testing

Run the test script:

```bash
./test-lista-api.sh
```

Or manually with curl:

```bash
# Get lista
curl -X GET http://localhost:8000/api/v1/calendars/1/lista \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Toggle taken
curl -X POST http://localhost:8000/api/v1/calendars/1/lista/toggle-taken \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"categoria_id":1,"ingrediente_id":5,"ingrediente_type":"receta"}'

# Add custom item
curl -X POST http://localhost:8000/api/v1/calendars/1/lista/items \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"cantidad":2,"nombre":"Test Item","categoria":1}'
```

---

## Migration Roadmap Status

✅ **Phase 1 Complete: Lista de Ingredientes** (9 methods migrated)

**Controllers Created:**

-   ✅ `ListaController.php` (6 methods)
-   ✅ `ListaPdfController.php` (3 methods)

**API Resources Created:**

-   ✅ `ListaItemResource.php`
-   ✅ `CategoryResource.php`

**Routes Added:**

-   ✅ 9 new API endpoints

**Next Phase:** Meal Plans (Phase 2)

---

## Notes

1. **PDF Views:** Ensure these Blade views exist:

    - `resources/views/pdf/lista-pdf.blade.php` (free users)
    - `resources/views/pdf/classic/classic-lista.blade.php` (theme 1)
    - `resources/views/pdf/modern/modern-lista.blade.php` (theme 2)
    - `resources/views/pdf/bold/bold-lista.blade.php` (theme 3)

2. **Email Views:** Ensure these exist:

    - `resources/views/email/send-lista-mail.blade.php`
    - `resources/views/email/delivery-email.blade.php`
    - `resources/views/email/lista-email.blade.php`

3. **Email Configuration:** Email endpoints require proper MAIL configuration in `.env`

4. **Permissions:** Original code checked `lista_view` permission - removed for API simplicity. Consider adding middleware if needed.

---

## Error Handling

All endpoints use Laravel's standard exception handling:

-   **404:** Calendar not found or doesn't belong to user
-   **422:** Validation errors
-   **500:** Server errors (email failures, PDF generation issues)
