# Healthy Martina - Laravel Backend API

Modern Laravel 11 API backend for the Healthy Martina application.

## Architecture

This is a **modern, feature-based Laravel API** following clean architecture principles with:

-   **Laravel 11** - Latest framework version
-   **Laravel Sanctum** - SPA and API token authentication
-   **Versioned API** - `/api/v1/*` endpoints
-   **Service Layer Pattern** - Business logic separated from controllers
-   **Repository Pattern** - Data access abstraction
-   **Form Requests** - Input validation
-   **API Resources** - Response transformation
-   **DTOs** - Data transfer objects

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Auth/           # Authentication endpoints
│   │           ├── Recipes/        # Recipe management
│   │           ├── Calendar/       # Meal planning & calendars
│   │           ├── Plans/          # Meal plans
│   │           ├── User/           # User profile & preferences
│   │           ├── Subscription/   # Membership & payments
│   │           └── Ingredients/    # Ingredient search
│   ├── Requests/                   # Form validation classes
│   │   ├── Auth/
│   │   ├── Recipe/
│   │   ├── Calendar/
│   │   └── User/
│   ├── Resources/                  # API response transformers
│   │   ├── Recipe/
│   │   ├── Calendar/
│   │   └── User/
│   └── Middleware/
├── Services/                       # Business logic layer
│   ├── Recipe/
│   ├── Calendar/
│   ├── User/
│   ├── Subscription/
│   └── Shared/
├── Repositories/                   # Data access layer
│   ├── Recipe/
│   ├── Calendar/
│   └── User/
├── Actions/                        # Single-purpose action classes
│   ├── Recipe/
│   ├── Calendar/
│   └── User/
└── DataTransferObjects/           # DTOs for data transfer
    ├── Recipe/
    ├── Calendar/
    └── User/
```

## Setup

### Requirements

-   PHP 8.3+
-   Composer 2.x
-   MySQL/PostgreSQL/SQLite

### Installation

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Install API scaffolding (already done)
php artisan install:api
```

### Configuration

Update `.env` with your database and application settings:

```env
APP_NAME="Healthy Martina API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthymartina
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

## API Endpoints

### Authentication

All API endpoints are versioned under `/api/v1/`.

#### Public Endpoints

```
POST   /api/v1/auth/register    # User registration
POST   /api/v1/auth/login       # User login
```

**Register Request:**

```json
{
    "name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Login Request:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**

```json
{
    "user": {
        "id": 1,
        "name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "username": "johndoe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "1|abc123...",
    "message": "Login successful"
}
```

#### Protected Endpoints (Require Authentication)

All protected endpoints require the `Authorization` header:

```
Authorization: Bearer {token}
```

```
POST   /api/v1/auth/logout      # User logout
GET    /api/v1/auth/user        # Get authenticated user
```

### Upcoming Endpoints

The following endpoint groups will be implemented next:

-   **Recipes** (`/api/v1/recipes`)

    -   GET `/recipes` - List recipes with filters
    -   GET `/recipes/{slug}` - Get recipe details
    -   POST `/recipes/{id}/comments` - Add comment
    -   POST `/recipes/{id}/reactions` - Toggle reaction
    -   POST `/recipes/{id}/pdf` - Export PDF

-   **Calendar** (`/api/v1/calendars`)

    -   GET `/calendars` - List user calendars
    -   POST `/calendars` - Create calendar
    -   GET `/calendars/{id}` - Get calendar details
    -   PUT `/calendars/{id}` - Update calendar
    -   DELETE `/calendars/{id}` - Delete calendar
    -   POST `/calendars/{id}/recipes` - Add recipe to calendar
    -   DELETE `/calendars/{id}/recipes/{recipeId}` - Remove recipe

-   **Shopping Lists** (`/api/v1/calendars/{id}/shopping-list`)

    -   GET `/calendars/{id}/shopping-list` - Get shopping list
    -   POST `/calendars/{id}/shopping-list/pdf` - Export PDF

-   **Plans** (`/api/v1/plans`)

    -   GET `/plans` - List meal plans
    -   POST `/plans` - Create plan
    -   GET `/plans/{id}` - Get plan details

-   **User** (`/api/v1/user`)

    -   GET `/user/profile` - Get profile
    -   PUT `/user/profile` - Update profile
    -   GET `/user/preferences` - Get preferences
    -   PUT `/user/preferences` - Update preferences

-   **Subscriptions** (`/api/v1/subscriptions`)
    -   GET `/memberships` - List available memberships
    -   POST `/subscriptions` - Create subscription
    -   PUT `/subscriptions` - Update subscription
    -   POST `/subscriptions/cancel` - Cancel subscription

## Authentication Flow

1. **User registers or logs in** via `/api/v1/auth/register` or `/api/v1/auth/login`
2. **API returns JWT token** in response
3. **React app stores token** (localStorage/sessionStorage)
4. **All subsequent requests** include token in Authorization header
5. **Token remains valid** until user logs out or token expires

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/V1/Auth/LoginTest.php

# Run with coverage
php artisan test --coverage
```

## Development

```bash
# Start development server
php artisan serve

# Watch for file changes (if using Laravel Mix/Vite)
npm run dev

# Run code style fixer
./vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

## Next Steps

### Phase 1: Complete (✅)

-   [x] Set up Laravel 11
-   [x] Install Sanctum
-   [x] Create directory structure
-   [x] Implement authentication endpoints
-   [x] Create User resource
-   [x] Set up API routes with versioning

### Phase 2: In Progress

-   [ ] Create Recipe API endpoints
-   [ ] Implement RecipeService
-   [ ] Create RecipeRepository
-   [ ] Add Form Requests for recipes
-   [ ] Create API Resources for recipes
-   [ ] Implement filtering and search

### Phase 3: Planned

-   [ ] Calendar API endpoints
-   [ ] Shopping list functionality
-   [ ] PDF generation service
-   [ ] Subscription management
-   [ ] User preferences
-   [ ] Background jobs queue

## Notes

-   **Backpack Admin** remains in the main Laravel app (separate from this API)
-   **PDF Generation** will be handled server-side (Laravel)
-   **Email Templates** remain in Laravel
-   **React Frontend** will consume these APIs
-   **No changes** to existing admin functionality

## Documentation

-   [Laravel 11 Documentation](https://laravel.com/docs/11.x)
-   [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
-   [API Resource Classes](https://laravel.com/docs/11.x/eloquent-resources)

## License

Proprietary - Healthy Martina
