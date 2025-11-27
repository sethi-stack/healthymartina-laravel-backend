# Phase 2: Laravel Upgrade & Full Migration Progress

## ✅ Completed

### Dependencies Installed (Laravel 11 Compatible)
- ✅ **Laravel 11.46** - Upgraded from Laravel 6
- ✅ **Backpack CRUD 6.8** - Admin panel (Laravel 11 compatible)
- ✅ **Laravel Scout 10.22** - Full-text search
- ✅ **Algolia Scout Extended 3.2** - Advanced search features
- ✅ **Laravel Cashier 15.7** - Stripe subscription management
- ✅ **Laravel Sanctum 4.0** - API authentication
- ✅ **Intervention Image 3.11** - Image manipulation
- ✅ **DomPDF 3.1** - PDF generation
- ✅ **Eloquent Sluggable 11.0** - URL slugs
- ✅ **Google Cloud Storage 1.48** - File storage
- ✅ **Google Cloud Logging 1.34** - Logging
- ✅ **Google Cloud Error Reporting 0.20** - Error tracking
- ✅ **Stripe PHP 16.6** - Payment processing
- ✅ **Maatwebsite Excel 3.1** - Excel export
- ✅ **Spatie Newsletter 5.3** - Mailchimp integration
- ✅ **Predis 2.4** - Redis client
- ✅ **PHP Units of Measure 2.2** - Unit conversions

### Structure Setup
- ✅ Created `app/Helpers/helper.php` for custom functions
- ✅ Backpack CRUD installed and configured
- ✅ API routes structure in place (`/api/v1/*`)
- ✅ Authentication working (Sanctum tokens)

## 🚧 Next Steps

### 1. Copy All Models (Priority)
Need to copy from old app → new app:

**Core Models:**
- [ ] `User.php` (update with Sanctum trait)
- [ ] `Receta.php` (Recipe)
- [ ] `Ingrediente.php` (Ingredient)
- [ ] `Tag.php`
- [ ] `Comment.php`
- [ ] `Reaction.php`
- [ ] `Bookmark.php`

**Relationship Models:**
- [ ] `RecetaInstruccionReceta.php`
- [ ] `RecetaInstruccionRecetaMedida.php`
- [ ] `Instruccion.php`
- [ ] `Medida.php`
- [ ] `TipoMedida.php`
- [ ] `RecetaResultado.php`
- [ ] `ImagenReceta.php`

**Category & Organization:**
- [ ] `Categoria.php`
- [ ] `Tipo.php`
- [ ] `Nutriente.php`
- [ ] `NutrientType.php`

**User & Preferences:**
- [ ] `NotificationPreference.php`
- [ ] `Snippet.php`

**Planning:**
- [ ] `Calendar.php`
- [ ] `Plan.php`
- [ ] `PlanReceta.php`
- [ ] `ListaIngredientes.php`

**Subscription:**
- [ ] `Subscription.php`
- [ ] `Membresia.php`
- [ ] `DetalleMembresia.php`
- [ ] `Coupon.php`

**Business:**
- [ ] `Cliente.php`
- [ ] `Miembro.php`

**Legal:**
- [ ] `PrivacyNotice.php`
- [ ] `TermsConditions.php`

**Other:**
- [ ] `Template.php`
- [ ] `Equivalence.php`
- [ ] `FormaCompra.php`
- [ ] `VideoHome.php`
- [ ] `YoutubeChannel.php`
- [ ] `WizardProgress.php`

### 2. Copy Controllers
- [ ] Copy all Backpack CRUD controllers (`Admin/*CrudController.php`)
- [ ] Copy web controllers (for any server-side rendered pages)
- [ ] Keep new API controllers separate

### 3. Copy Routes
- [ ] Copy `routes/web.php` (preserve existing web routes)
- [ ] Copy Backpack admin routes
- [ ] Keep `routes/api.php` as-is (new API routes)

### 4. Copy Views
- [ ] Copy all Blade templates for admin panel
- [ ] Copy PDF templates
- [ ] Copy email templates
- [ ] Keep admin views separate from API

### 5. Copy Config Files
- [ ] `config/backpack/*.php`
- [ ] `config/scout.php`
- [ ] `config/services.php` (Algolia, Mailchimp, Google Cloud)
- [ ] `config/filesystems.php` (Google Cloud Storage disk)
- [ ] Update `.env` with all credentials

### 6. Copy Helpers
- [ ] Copy content of `app/Helpers/helper.php`

### 7. Database
- [ ] Already using existing database ✅
- [ ] No schema changes needed ✅

### 8. Build API Endpoints (New)
- [ ] Recipe API (list, show, filter, search)
- [ ] Calendar API
- [ ] User Profile API
- [ ] Subscription API

## Breaking Changes from Laravel 6 → 11

### Handled Automatically:
- ✅ Updated package versions
- ✅ Namespace changes (all packages Laravel 11 compatible)

### Need Manual Update:
- [ ] Route model binding syntax (minor)
- [ ] Date casting (Carbon 2 → 3)
- [ ] Mail class updates (if using custom mail)
- [ ] Queue job syntax (if using jobs)

## Current Architecture

```
laravel-backend-app/
├── /api/v1/*               ← API for React (Sanctum auth)
├── /admin/*                ← Backpack admin (to be copied)
├── app/
│   ├── Models/             ← All models (to be copied)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/     ← New API controllers ✅
│   │   │   └── Admin/      ← Backpack controllers (to copy)
│   │   ├── Requests/
│   │   │   └── Auth/       ← API validation ✅
│   │   └── Resources/
│   │       └── User/       ← API resources ✅
│   └── Helpers/
│       └── helper.php      ← Custom functions (to copy)
├── routes/
│   ├── api.php             ← New API routes ✅
│   └── web.php             ← Admin/web routes (to copy)
└── resources/
    └── views/              ← Admin views (to copy)
```

## Testing Plan

### Phase 1: Authentication ✅
- [x] API registration works
- [x] API login works
- [x] Token authentication works
- [x] Logout works

### Phase 2: Admin Panel
- [ ] Backpack admin accessible
- [ ] Can log into admin
- [ ] CRUD operations work
- [ ] All admin features functional

### Phase 3: Models & Relationships
- [ ] All models copied
- [ ] Relationships working
- [ ] Accessors/mutators working
- [ ] Scout search working

### Phase 4: API Endpoints
- [ ] Recipe API returns data
- [ ] Calendar API works
- [ ] User API functional
- [ ] Subscriptions API operational

## Migration Strategy

**Approach: Incremental Copy & Test**

1. **Copy models first** (foundation)
2. **Test models** (relationships, methods)
3. **Copy admin controllers** (Backpack)
4. **Test admin panel** (full functionality)
5. **Build new API endpoints** (for React)
6. **Test API** (Postman/React)
7. **Deploy** (old app still running as backup)

## Rollback Plan

- Old Laravel app remains untouched
- Can switch back instantly
- Database shared (no migration issues)
- DNS switch if needed

## Estimated Timeline

- ✅ **Phase 1 Complete:** Auth API (2 hours)
- 🚧 **Phase 2 In Progress:** Dependencies & Setup (1 hour)
- ⏳ **Phase 3:** Copy Models (2-3 hours)
- ⏳ **Phase 4:** Copy Controllers & Views (2-3 hours)
- ⏳ **Phase 5:** Build Recipe API (3-4 hours)
- ⏳ **Phase 6:** Build Calendar API (2-3 hours)
- ⏳ **Phase 7:** Testing & Fixes (4-6 hours)

**Total:** ~16-22 hours of development

## Success Criteria

✅ All admin functionality works (Backpack)
✅ All API endpoints functional
✅ Scout search working
✅ PDF generation working
✅ Stripe payments working
✅ File uploads to Google Cloud working
✅ All tests passing
✅ Zero data loss

---

**Status:** Dependencies installed, ready to copy models
**Next:** Copy all models from old app to new app

