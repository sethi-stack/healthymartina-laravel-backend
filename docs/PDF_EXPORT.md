# PDF Export System

## Overview

The PDF export system generates printable documents for recipes, calendars, and shopping lists. It supports multiple themes, subscription-based features, and email delivery.

## PDF Generation Library

**Library**: DomPDF (Barryvdh\DomPDF)

**Usage**:

```php
use Barryvdh\DomPDF\Facade as PDF;

$pdf = PDF::loadView('pdf.recipe', ['recipe' => $recipe]);
return $pdf->download('recipe.pdf');
```

## Export Types

### 1. Recipe PDF

**Endpoint**: `POST /pdf/receta/{recipe_id}`

**Parameters**:

-   `porcion`: Number of servings (from slider)
-   `recipe_ingredients`: JSON of adjusted ingredient quantities
-   `plantillas`: HTML template (for professional users)

**Views** (based on subscription):

-   **Free/Standard**: `pdf.recipe`
-   **Professional Theme 1**: `pdf.classic.classic-recipe`
-   **Professional Theme 2**: `pdf.modern.modern-recipe`
-   **Professional Theme 3**: `pdf.bold.bold-recipe`

**Content**:

-   Recipe title and images
-   Ingredients (scaled by portion)
-   Instructions
-   Nutrition information (if professional)
-   Tips
-   Custom template content (if provided)

### 2. Calendar PDF

**Endpoint**: `POST /pdf/calendario/{calendar_id}`

**Parameters**:

-   `export_stitch`: Flag for multi-page stitching
-   `filename`: Output filename
-   `total`: Total number of pages
-   `isMail`: Email delivery flag
-   `calendrio_recipient_email_address`: Recipient email

**Process** (for large calendars):

1. **Chunk Recipes**: Split into batches of 10 recipes
2. **Generate Partial PDFs**: Create PDF for each batch
3. **Stitch Together**: Combine all partial PDFs
4. **Final PDF**: Single combined document

**Views**:

-   **Free/Standard**: `pdf.calendario-pdf`
-   **Professional**: Theme-based views

**Content**:

-   Weekly schedule (Monday-Sunday)
-   Main and side dishes per meal
-   Serving sizes
-   Leftover indicators
-   Daily nutrition summary
-   Shopping list (optional)

### 3. Shopping List PDF

**Endpoint**: `POST /pdf/lista/{calendar_id}`

**Parameters**:

-   `lista_ingredients`: JSON of shopping list items
-   `lista_recipient_email_address`: Recipient email (optional)
-   `plantillas`: HTML template (for professional users)

**Views**:

-   **Free/Standard**: `pdf.lista-pdf`
-   **Professional Theme 1**: `pdf.classic.classic-lista`
-   **Professional Theme 2**: `pdf.modern.modern-lista`
-   **Professional Theme 3**: `pdf.bold.bold-lista`

**Content**:

-   Ingredients organized by category
-   Quantities and units
-   Check-off boxes
-   Custom items
-   Template content (if professional)

## Subscription-Based Features

### Free/Standard Users

-   Basic PDF templates
-   No custom templates
-   Limited nutrition display
-   Standard formatting

### Professional Users

**Features**:

-   Multiple theme options
-   Custom HTML templates (CKEditor)
-   Enhanced nutrition display
-   Professional formatting
-   Email delivery with branding

### Theme Selection

**User Preference**: `user.theme` field

-   `1`: Classic theme
-   `2`: Modern theme
-   `3` (or other): Bold theme

## Email Delivery

### Recipe Email

**Endpoint**: `POST /pdf/receta/{recipe_id}/email`

**Process**:

1. Generate PDF
2. Validate recipient email
3. Send email with PDF attachment
4. Send delivery confirmation to user's business email

**Email Templates**:

-   **To Recipient**: `email.send-recipe`
-   **To User**: `email.delivery-email`

### Calendar Email

**Endpoint**: `POST /pdf/calendario/{calendar_id}/email`

**Process**:

1. Generate calendar PDF (with chunking if large)
2. Validate recipient email
3. Send email with PDF attachment
4. Send delivery confirmation

**Email Templates**:

-   **To Recipient**: `email.send-calendario`
-   **To User**: `email.delivery-email`

### Shopping List Email

**Endpoint**: `POST /pdf/lista/{calendar_id}/email`

**Process**:

1. Generate shopping list PDF
2. Validate recipient email
3. Send email with PDF attachment
4. Send delivery confirmation

**Email Templates**:

-   **To Recipient**: `email.send-lista-mail`
-   **To User**: `email.delivery-email`

## Custom Templates (Professional)

### Template System

**Editor**: CKEditor (WYSIWYG)

**Storage**: HTML content passed as `plantillas` parameter

**Usage**: Injected into PDF views:

```php
@if(isset($plantillas) && !empty($plantillas))
    {!! $plantillas !!}
@endif
```

### Template Variables

Templates can include:

-   Recipe information
-   Nutrition data
-   Calendar schedule
-   Shopping list items

## Large Calendar Handling

### Problem

Calendars with many recipes can generate very large PDFs, causing:

-   Memory issues
-   Timeout errors
-   Poor performance

### Solution: Chunking

**Process** (from `exportCalendarioProfessional()`):

1. **Get Recipe Count**:

    ```php
    $response = $this->getRecipeCount($calendar);
    $recipeCount = $response['recetas'];
    ```

2. **Chunk Recipes**:

    ```php
    $chunkSize = 10;
    $chunks = [];
    for ($i = 0; $i < count($recipes); $i += $chunkSize) {
        $chunks[] = array_slice($recipes, $i, $chunkSize);
    }
    ```

3. **Generate Partial PDFs**:

    ```php
    foreach ($chunks as $chunkIndex => $chunk) {
        $pdf = $this->generatePartialCalendarPDF($chunk, $calendar);
        // Store temporarily
    }
    ```

4. **Stitch Together**:
    ```php
    $finalPDF = $this->stitchPDFs($partialPDFs);
    ```

### Error Handling

If PDF exceeds size limit:

-   Show error message
-   Suggest printing without images
-   Allow retry with reduced content

## PDF Views Location

**Base Path**: `resources/views/pdf/`

**Structure**:

```
pdf/
  ├── recipe.blade.php (Free/Standard)
  ├── lista-pdf.blade.php (Free/Standard)
  ├── calendario-pdf.blade.php (Free/Standard)
  ├── classic/
  │   ├── classic-recipe.blade.php
  │   ├── classic-lista.blade.php
  │   └── classic-calendario.blade.php
  ├── modern/
  │   ├── modern-recipe.blade.php
  │   ├── modern-lista.blade.php
  │   └── modern-calendario.blade.php
  └── bold/
      ├── bold-recipe.blade.php
      ├── bold-lista.blade.php
      └── bold-calendario.blade.php
```

## API Endpoints

### Recipe PDF

-   `POST /api/v1/recipes/{id}/pdf` - Generate recipe PDF
-   `POST /api/v1/recipes/{id}/pdf/email` - Email recipe PDF

### Calendar PDF

-   `POST /api/v1/calendars/{id}/pdf` - Generate calendar PDF
-   `POST /api/v1/calendars/{id}/pdf/email` - Email calendar PDF

### Shopping List PDF

-   `POST /api/v1/calendars/{id}/lista/pdf` - Generate shopping list PDF
-   `POST /api/v1/calendars/{id}/lista/pdf/email` - Email shopping list PDF

## Spanish Terminology

| Spanish           | English       | Context              |
| ----------------- | ------------- | -------------------- |
| Exportar          | Export        | Generate PDF         |
| PDF               | PDF           | Document format      |
| Plantilla         | Template      | Custom HTML template |
| Enviar por Correo | Send by Email | Email delivery       |
| Tema              | Theme         | PDF theme/style      |
| Clásico           | Classic       | Theme option         |
| Moderno           | Modern        | Theme option         |
| Negrita           | Bold          | Theme option         |

## Related Documentation

-   [Recipes and Ingredients](./RECIPES_AND_INGREDIENTS.md)
-   [Calendar System](./CALENDAR_SYSTEM.md)
-   [Shopping List System](./LISTA_SYSTEM.md)
-   [Subscriptions](./SUBSCRIPTIONS.md)

