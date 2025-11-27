# API Endpoints Reference

Complete reference for all V1 API endpoints in the Laravel 11 backend.

## Base URL

```
/api/v1/
```

All endpoints require `Accept: application/json` header.
Protected endpoints require `Authorization: Bearer {token}` header.

---

## 🔐 Authentication (4 endpoints)

### POST `/auth/register`

Register a new user.

**Body:**

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

**Response:**

```json
{
  "user": { ... },
  "token": "1|xxxx...",
  "message": "Registration successful"
}
```

---

### POST `/auth/login`

Login existing user.

**Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**

```json
{
  "user": { ... },
  "token": "2|xxxx...",
  "message": "Login successful"
}
```

---

### GET `/auth/user` 🔒

Get authenticated user details.

**Response:**

```json
{
  "id": 1,
  "name": "John",
  "email": "john@example.com",
  ...
}
```

---

### POST `/auth/logout` 🔒

Logout and revoke current token.

**Response:**

```json
{
    "message": "Logout successful"
}
```

---

## 🍽️ Recipes (10 endpoints)

### GET `/recipes`🔒

List recipes with filtering and pagination.

**Query Parameters:**

-   `search` - Search by title
-   `tags` - Filter by tag IDs (comma-separated or array)
-   `tipo_id` - Filter by recipe type
-   `max_calories` - Maximum calories
-   `min_calories` - Minimum calories
-   `sort_by` - Sort field (default: `created_at`)
-   `sort_order` - `asc` or `desc` (default: `desc`)
-   `per_page` - Results per page (default: 15)

**Example:**

```
GET /api/v1/recipes?search=chicken&max_calories=500&per_page=10
```

---

### GET `/recipes/search` 🔒

Search recipes using Algolia/Scout.

**Query Parameters:**

-   `q` (required) - Search query (min 2 chars)
-   `per_page` - Results per page (default: 15)

**Example:**

```
GET /api/v1/recipes/search?q=chicken&per_page=10
```

---

### GET `/recipes/popular` 🔒

Get trending/popular recipes.

**Query Parameters:**

-   `limit` - Number of recipes (default: 10)
-   `days` - Time window in days (default: 30)

---

### GET `/recipes/bookmarks` 🔒

Get user's bookmarked recipes.

**Query Parameters:**

-   `per_page` - Results per page (default: 15)

---

### GET `/recipes/{slug}` 🔒

Get recipe by slug.

**Response:**

```json
{
  "id": 1,
  "titulo": "Chicken Salad",
  "slug": "chicken-salad",
  "calories": 350,
  "tags": [...],
  "tipo": {...},
  "comments": [...]
}
```

---

### GET `/recipes/{id}/similar` 🔒

Get similar recipes based on tags.

**Response:** Array of similar recipes (max 6)

---

### GET `/recipes/{id}/stats` 🔒

Get recipe engagement statistics.

**Response:**

```json
{
    "likes": 45,
    "dislikes": 2,
    "bookmarks": 23,
    "comments": 12,
    "total_reactions": 47
}
```

---

### POST `/recipes/{id}/bookmark` 🔒

Toggle bookmark for a recipe.

**Response:**

```json
{
    "bookmarked": true,
    "message": "Bookmark added"
}
```

---

### POST `/recipes/{id}/react` 🔒

Add or update reaction (like/dislike).

**Body:**

```json
{
    "is_like": true
}
```

---

### DELETE `/recipes/{id}/react` 🔒

Remove reaction from recipe.

---

## 🌿 Ingredients (3 endpoints)

### GET `/ingredients` 🔒

List/search ingredients.

**Query Parameters:**

-   `q` - Search by name
-   `categoria_id` - Filter by category
-   `sort_by` - Sort field (default: `nombre`)
-   `sort_order` - `asc` or `desc` (default: `asc`)
-   `per_page` - Results per page (default: 10)

---

### GET `/ingredients/{id}` 🔒

Get ingredient details.

---

### GET `/ingredients/{id}/instrucciones` 🔒

Get instructions for ingredient.

---

## 📅 Calendars (6 endpoints)

### GET `/calendars` 🔒

List user's calendars.

---

### POST `/calendars` 🔒

Create new calendar.

**Body:**

```json
{
    "nombre": "My Meal Plan",
    "semanas": 4,
    "calendario": "{}",
    "data_semanal": "{}"
}
```

---

### GET `/calendars/{id}` 🔒

Get calendar by ID.

---

### PUT `/calendars/{id}` 🔒

Update calendar.

---

### DELETE `/calendars/{id}` 🔒

Delete calendar.

---

### POST `/calendars/{id}/copy` 🔒

Copy existing calendar.

**Body:**

```json
{
    "nombre": "Copy of My Meal Plan"
}
```

---

## 👤 User Profile (5 endpoints)

### GET `/profile` 🔒

Get user profile.

---

### PUT `/profile` 🔒

Update user profile.

**Body:**

```json
{
    "name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "telefono": "+1234567890",
    "fecha_nacimiento": "1990-01-01"
}
```

---

### PUT `/profile/password` 🔒

Change password.

**Body:**

```json
{
    "current_password": "oldpass123",
    "password": "newpass123",
    "password_confirmation": "newpass123"
}
```

---

### POST `/profile/photo` 🔒

Upload profile photo.

**Body:** multipart/form-data

-   `photo` - Image file (max 2MB)

---

### DELETE `/profile` 🔒

Delete user account.

**Body:**

```json
{
    "password": "mypassword"
}
```

---

## 💳 Subscriptions (8 endpoints)

### GET `/subscriptions/plans` 🔒

Get available membership plans.

---

### GET `/subscriptions/stripe-plans` 🔒

Get Stripe plans from API.

---

### GET `/subscriptions/current` 🔒

Get user's current subscription.

**Response:**

```json
{
  "subscribed": true,
  "subscription": {
    "id": 1,
    "name": "default",
    "stripe_status": "active",
    "is_active": true,
    "is_on_trial": false,
    ...
  }
}
```

---

### POST `/subscriptions/setup-intent` 🔒

Create setup intent for payment method.

**Response:**

```json
{
    "client_secret": "seti_xxx..."
}
```

---

### POST `/subscriptions/subscribe` 🔒

Subscribe to a plan.

**Body:**

```json
{
    "payment_method": "pm_xxx...",
    "plan": "price_xxx..."
}
```

---

### PUT `/subscriptions/update-plan` 🔒

Change subscription plan.

**Body:**

```json
{
    "plan": "price_xxx..."
}
```

---

### POST `/subscriptions/cancel` 🔒

Cancel subscription (grace period applies).

---

### POST `/subscriptions/resume` 🔒

Resume cancelled subscription (if on grace period).

---

## 📊 Summary

| Category       | Endpoints | Protected |
| -------------- | --------- | --------- |
| Authentication | 4         | 2         |
| Recipes        | 10        | 10        |
| Ingredients    | 3         | 3         |
| Calendars      | 6         | 6         |
| Profile        | 5         | 5         |
| Subscriptions  | 8         | 8         |
| **TOTAL**      | **36**    | **34**    |

---

## 🔑 Authentication

All protected endpoints (🔒) require authentication using Laravel Sanctum.

**Include in headers:**

```
Authorization: Bearer {token}
Accept: application/json
```

**Token obtained from:**

-   `/auth/register`
-   `/auth/login`

---

## 📄 Pagination

All list endpoints return paginated results:

```json
{
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

---

## 🚀 Testing

See `API_TESTING_GUIDE.md` and `TESTING_WITH_EXISTING_DATABASE.md` for testing instructions.

Quick test:

```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test","last_name":"User","username":"testuser","email":"test@test.com","password":"password","password_confirmation":"password"}'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@test.com","password":"password"}'

# Get recipes (use token from login)
curl -X GET http://localhost:8000/api/v1/recipes \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```
