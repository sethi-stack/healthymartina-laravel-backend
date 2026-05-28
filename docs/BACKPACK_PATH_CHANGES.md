# Backpack “Path” Notes (CoreUIv2 + PRO upgrade)

This project uses **Backpack PRO** with the **CoreUIv2** theme (`config/backpack/ui.php` → `view_namespace` is `backpack.theme-coreuiv2::`).

Historically, parts of the admin were customized for **AdminLTE + FontAwesome 4** and some custom field views were copy/pasted into `resources/views/vendor/backpack/**`.

During the PRO/CoreUIv2 upgrade, the main goal is:

- keep all changes in **app-level overrides** (not `vendor/`)
- make the old custom views work with CoreUIv2/Basset
- remove duplicate injections that cause “already declared” JS errors

## Where Backpack customization lives

- View overrides: `laravel-backend-app/resources/views/vendor/backpack/**`
- Backpack config: `laravel-backend-app/config/backpack/**`
- Theme config (CoreUIv2): vendor theme views live in `laravel-backend-app/vendor/backpack/theme-coreuiv2/**`

Backpack’s `backpack_view('…')` will resolve to the active theme namespace first, but **any file you place under**:

`resources/views/vendor/backpack/<theme>/…`

will override the theme’s view (example below).

## Changes made in this upgrade (and why)

### 1) Fix Basset URLs pointing to the wrong host/port

**Problem**

Assets were requested from `http://localhost/storage/basset/...` (port 80) which caused:

`net::ERR_CONNECTION_REFUSED`

when the app was actually served on `http://localhost:8000`.

**Fix**

- Updated `laravel-backend-app/.env`:
  - `APP_URL=http://localhost:8000`

After changing env/config values, run:

- `php artisan config:clear`
- `php artisan view:clear`

### 2) Remove duplicate script stack rendering (root cause of “already declared”)

**Problem**

CoreUIv2 layout already renders:

- `@stack('before_scripts')`
- `@stack('after_scripts')`

But an override existed at:

- `laravel-backend-app/resources/views/vendor/backpack/ui/inc/scripts.blade.php`

that also rendered the same stacks/yields, which caused some inline `<script>` blocks (from field views) to be output twice → errors like:

- `Identifier 'sidebarClass' has already been declared`
- `Identifier '$dtCachedInfo' has already been declared`

**Fix**

- `laravel-backend-app/resources/views/vendor/backpack/ui/inc/scripts.blade.php`
  - removed the `@yield/@stack before_scripts/after_scripts` lines from this file

This makes the CoreUIv2 layout the single source of truth for stacking scripts.

### 3) Keep legacy validation logic inside custom views

**Problem**

Some custom field views relied on a legacy JS helper called `validateField(...)` (attribute-driven checks like `validate_required`, `validate_min`, etc.).

Backpack PRO/CoreUIv2 does not ship that global helper (and it’s not part of Backpack’s public JS API), so on the Recetas CRUD we saw:

- `Uncaught TypeError: window.validateField is not a function`

**Fix**

- Implemented a small `validateField(...)` function locally inside the custom field views that need it:
  - `laravel-backend-app/resources/views/vendor/backpack/crud/fields/lista_ingredientes.blade.php`
  - `laravel-backend-app/resources/views/vendor/backpack/crud/fields/lista_resultado.blade.php`

### 4) Migrate legacy icons to CoreUIv2’s icon set (Line Awesome)

**Problem**

Legacy custom views were using FontAwesome 4 classes (eg `fa fa-plus-circle`), but CoreUIv2 uses **Line Awesome** (`la la-…`).

Instead of adding FontAwesome back globally, we migrated the custom buttons/icons to Line Awesome:

- `laravel-backend-app/app/Http/Controllers/Admin/RecetaCrudController.php`
- `laravel-backend-app/resources/views/vendor/backpack/crud/fields/lista_ingredientes.blade.php`
- `laravel-backend-app/resources/views/vendor/backpack/crud/fields/lista_resultado.blade.php`

### 5) Replace legacy AdminLTE Select2 asset paths (404s)

**Problem**

Several custom field views referenced AdminLTE’s old Select2 assets:

- `/vendor/adminlte/bower_components/select2/dist/...`

Those paths don’t exist in the CoreUIv2/Basset setup, causing:

- `GET ...select2.min.js ... 404 (Not Found)`

**Fix**

Updated these files to load Select2 via Basset/CDN:

- `laravel-backend-app/resources/views/vendor/backpack/crud/fields/select2_multiple_aura.blade.php`
- `laravel-backend-app/resources/views/vendor/backpack/crud/fields/ingredientes_recetas_field.blade.php`
- `laravel-backend-app/resources/views/vendor/backpack/crud/fields/insertar-receta-tips.blade.php`

## Notes on removed “workaround” overrides

We briefly added extra safeguards (like switching `let` → `var` or scoping scripts) to stop redeclaration errors.

After fixing the **root cause** (duplicate stack rendering), those were rolled back/removed:

- The theme sidebar override created for `sidebarClass` was deleted.
- The temporary IIFE wrapper in `datatables_logic.blade.php` was removed.

If redeclaration errors reappear, first re-check for **duplicate includes or duplicate `@stack()` output**, not the JS itself.

## Quick debug checklist

1. Clear caches after env/view changes:
   - `php artisan config:clear && php artisan view:clear`
2. Confirm the app URL (host + port) matches what you load in the browser:
   - `APP_URL`
3. If you see “already been declared”:
   - search for the variable name in compiled output
   - then check whether the same partial is included twice or stacks are rendered twice

## Related docs

- `laravel-backend-app/docs/BACKPACK_ADMIN_DEBUG_LOG.md` (historical debugging notes)
