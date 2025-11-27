# Quick Start Guide - Testing the API

## ✅ Setup Complete!

Your Laravel 11 API backend is now working with your existing database.

## 🚀 Start the Server

```bash
cd laravel-backend-app
php artisan serve
```

Server runs at: `http://127.0.0.1:8000`

## 🧪 Test with Postman

### 1. Register a New User

**URL:** `POST http://127.0.0.1:8000/api/v1/auth/register`

**Headers:**

```
Content-Type: application/json
```

Note: You don't need `Accept: application/json` anymore - all `/api/*` routes automatically return JSON now!

**Body (JSON):**

```json
{
    "name": "John",
    "last_name": "Doe",
    "username": "johndoe123",
    "email": "john123@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response (201):**

```json
{
    "user": {
        "id": 123,
        "name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "username": "johndoe123",
        "email": "john123@example.com",
        "email_verified_at": null,
        "created_at": "2025-11-27T...",
        "updated_at": "2025-11-27T..."
    },
    "token": "1|abc123xyz...",
    "message": "Registration successful"
}
```

**Validation Error (422):**

```json
{
    "message": "The username has already been taken. (and 1 more error)",
    "errors": {
        "username": ["The username has already been taken."],
        "email": ["The email has already been taken."]
    }
}
```

### 2. Login

**URL:** `POST http://127.0.0.1:8000/api/v1/auth/login`

**Body:**

```json
{
    "email": "john123@example.com",
    "password": "password123"
}
```

**Success Response (200):**

```json
{
  "user": {...},
  "token": "2|xyz789...",
  "message": "Login successful"
}
```

**Error Response (422):**

```json
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

### 3. Get Current User (Protected)

**URL:** `GET http://127.0.0.1:8000/api/v1/auth/user`

**Headers:**

```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Success Response (200):**

```json
{
  "id": 123,
  "name": "John",
  "email": "john123@example.com",
  ...
}
```

**Unauthorized (401):**

```json
{
    "message": "Unauthenticated."
}
```

### 4. Logout

**URL:** `POST http://127.0.0.1:8000/api/v1/auth/logout`

**Headers:**

```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Success Response (200):**

```json
{
    "message": "Logout successful"
}
```

## 📝 What Works Now

✅ User registration with validation
✅ User login
✅ Token-based authentication (Sanctum)
✅ Get current user info
✅ Logout (revokes token)
✅ Automatic JSON responses for all API routes
✅ Proper error handling with JSON errors
✅ Connected to existing database
✅ Creates users in the same `users` table as old app
✅ No database schema changes needed

## 🔧 Database Setup

-   **Database:** `hm_app_local` (your existing database)
-   **New Table Added:** `personal_access_tokens` (for Sanctum tokens)
-   **All other tables:** Using existing schema (no changes)

## 🎯 Testing Tips

1. **Change username/email** for each test to avoid duplicate errors
2. **Save the token** from login/register to test protected routes
3. **No need for Accept header** - all `/api/*` routes return JSON automatically
4. **Use the token** in Authorization header: `Bearer YOUR_TOKEN`

## ⚡ Quick cURL Tests

### Register

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test",
    "last_name": "User",
    "username": "testuser999",
    "email": "test999@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test999@example.com",
    "password": "password123"
  }'
```

### Get User (replace TOKEN)

```bash
curl http://127.0.0.1:8000/api/v1/auth/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Logout

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🐛 Troubleshooting

### "Connection refused"

-   Server not running. Run: `php artisan serve`

### "The username has already been taken"

-   User already exists. Change username/email

### "Unauthenticated"

-   Token missing or invalid
-   Check Authorization header format: `Bearer YOUR_TOKEN`

### "Getting HTML instead of JSON"

-   This should be fixed now, but if it happens:
-   Make sure URL has NO trailing slash
-   Try adding `Accept: application/json` header

## 📚 Next Steps

Now that auth is working, we can:

1. ✅ Copy models from old Laravel app (Receta, Calendar, etc.)
2. ✅ Create Recipe API endpoints
3. ✅ Create Calendar API endpoints
4. ✅ Build React frontend
5. ✅ Connect React to API

## 🎉 Success!

Your modern Laravel 11 API is up and running with:

-   Sanctum authentication
-   Proper error handling
-   JSON responses
-   Connected to existing database
-   No schema migrations needed

**The authentication system is production-ready!** 🚀
