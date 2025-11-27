# API Testing Guide

## Method 1: Using cURL (Terminal)

### Start the Laravel Server

```bash
cd laravel-backend-app
php artisan serve
```

Server will run at: `http://127.0.0.1:8000`

### Test Registration

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Expected Response:**
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
    "created_at": "2024-11-27T...",
    "updated_at": "2024-11-27T..."
  },
  "token": "1|abc123xyz...",
  "message": "Registration successful"
}
```

### Test Login

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Save the token from the response!**

### Test Get Current User (Protected Route)

Replace `YOUR_TOKEN_HERE` with the actual token from login/register:

```bash
curl -X GET http://127.0.0.1:8000/api/v1/auth/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Test Logout

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Method 2: Using Postman

### Setup

1. Download Postman: https://www.postman.com/downloads/
2. Create a new collection: "Healthy Martina API"

### Create Requests

#### 1. Register User
- **Method:** POST
- **URL:** `http://127.0.0.1:8000/api/v1/auth/register`
- **Headers:**
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- **Body (raw JSON):**
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

#### 2. Login
- **Method:** POST
- **URL:** `http://127.0.0.1:8000/api/v1/auth/login`
- **Headers:**
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- **Body (raw JSON):**
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```
- **After sending:** Copy the `token` from response

#### 3. Get Current User
- **Method:** GET
- **URL:** `http://127.0.0.1:8000/api/v1/auth/user`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

#### 4. Logout
- **Method:** POST
- **URL:** `http://127.0.0.1:8000/api/v1/auth/logout`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

### Postman Environment Variables

Create an environment to store the token:

1. Click "Environments" → "Create Environment"
2. Add variable: `api_token`
3. In your Login request, add this to the "Tests" tab:
   ```javascript
   var response = pm.response.json();
   pm.environment.set("api_token", response.token);
   ```
4. Now use `{{api_token}}` in Authorization headers

---

## Method 3: Using HTTPie (Prettier cURL)

Install HTTPie:
```bash
# macOS
brew install httpie

# Other systems
pip install httpie
```

### Test Endpoints

```bash
# Register
http POST http://127.0.0.1:8000/api/v1/auth/register \
  name="John" \
  last_name="Doe" \
  username="johndoe" \
  email="john@example.com" \
  password="password123" \
  password_confirmation="password123"

# Login
http POST http://127.0.0.1:8000/api/v1/auth/login \
  email="john@example.com" \
  password="password123"

# Get user (replace TOKEN)
http GET http://127.0.0.1:8000/api/v1/auth/user \
  "Authorization: Bearer TOKEN"

# Logout
http POST http://127.0.0.1:8000/api/v1/auth/logout \
  "Authorization: Bearer TOKEN"
```

---

## Method 4: Using Laravel's Built-in Testing

I've created PHPUnit tests for you. Run them with:

```bash
cd laravel-backend-app
php artisan test
```

Or test specific features:
```bash
# Test only authentication
php artisan test --filter=Auth

# Test with coverage
php artisan test --coverage
```

---

## Method 5: Using REST Client VS Code Extension

1. Install "REST Client" extension in VS Code
2. Create a file: `api-tests.http`
3. Add this content:

```http
### Variables
@baseUrl = http://127.0.0.1:8000/api/v1
@token = YOUR_TOKEN_HERE

### Register User
POST {{baseUrl}}/auth/register
Content-Type: application/json

{
  "name": "John",
  "last_name": "Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

### Login
POST {{baseUrl}}/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}

### Get Current User
GET {{baseUrl}}/auth/user
Authorization: Bearer {{token}}

### Logout
POST {{baseUrl}}/auth/logout
Authorization: Bearer {{token}}
```

Click "Send Request" above each request to test.

---

## Testing Validation Errors

### Test Invalid Registration (Missing Fields)

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John",
    "email": "invalid-email"
  }'
```

**Expected Response (422 Validation Error):**
```json
{
  "message": "The last name field is required. (and 4 more errors)",
  "errors": {
    "last_name": ["The last name field is required."],
    "username": ["The username field is required."],
    "email": ["The email field must be a valid email address."],
    "password": ["The password field is required."]
  }
}
```

### Test Invalid Login

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "wrong@example.com",
    "password": "wrongpassword"
  }'
```

**Expected Response (422 Error):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### Test Protected Route Without Token

```bash
curl -X GET http://127.0.0.1:8000/api/v1/auth/user \
  -H "Accept: application/json"
```

**Expected Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## Quick Test Script

I've created a test script for you. Run it with:

```bash
cd laravel-backend-app
php artisan test:api
```

Or manually create this bash script:

```bash
#!/bin/bash

echo "🧪 Testing Healthy Martina API"
echo "================================"
echo ""

BASE_URL="http://127.0.0.1:8000/api/v1"

echo "1️⃣ Testing Registration..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test",
    "last_name": "User",
    "username": "testuser'$(date +%s)'",
    "email": "test'$(date +%s)'@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }')

TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ Registration failed"
  echo $REGISTER_RESPONSE
  exit 1
fi

echo "✅ Registration successful"
echo "Token: ${TOKEN:0:20}..."
echo ""

echo "2️⃣ Testing Get User (with token)..."
USER_RESPONSE=$(curl -s -X GET "$BASE_URL/auth/user" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo $USER_RESPONSE | grep -q "email"
if [ $? -eq 0 ]; then
  echo "✅ Get user successful"
else
  echo "❌ Get user failed"
fi
echo ""

echo "3️⃣ Testing Logout..."
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo $LOGOUT_RESPONSE | grep -q "Logout successful"
if [ $? -eq 0 ]; then
  echo "✅ Logout successful"
else
  echo "❌ Logout failed"
fi
echo ""

echo "4️⃣ Testing token is revoked..."
curl -s -X GET "$BASE_URL/auth/user" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | grep -q "Unauthenticated"

if [ $? -eq 0 ]; then
  echo "✅ Token properly revoked"
else
  echo "❌ Token still valid (should be revoked)"
fi

echo ""
echo "================================"
echo "✨ All tests completed!"
```

Save as `test-api.sh`, make executable, and run:
```bash
chmod +x test-api.sh
./test-api.sh
```

---

## Common Issues & Solutions

### Issue: Connection Refused
**Problem:** `curl: (7) Failed to connect to 127.0.0.1 port 8000`
**Solution:** Make sure Laravel server is running:
```bash
php artisan serve
```

### Issue: 419 CSRF Token Mismatch
**Problem:** Getting CSRF errors
**Solution:** Always include `Accept: application/json` header

### Issue: 401 Unauthenticated
**Problem:** Protected routes return 401
**Solution:** 
1. Make sure you're sending the `Authorization: Bearer TOKEN` header
2. Check the token is valid (not expired or revoked)
3. Token format must be exactly: `Bearer YOUR_TOKEN` (note the space)

### Issue: 422 Validation Error
**Problem:** Request validation fails
**Solution:** Check the `errors` object in response for specific field errors

---

## Recommended Testing Flow

1. **Start Server:** `php artisan serve`
2. **Register a User:** Test registration endpoint
3. **Copy Token:** Save the token from response
4. **Test Protected Routes:** Use token in Authorization header
5. **Test Logout:** Verify token is revoked
6. **Test Invalid Token:** Confirm 401 response

---

## Next Steps

Once authentication is working, you can:

1. Create more endpoints (recipes, calendars, etc.)
2. Write automated tests
3. Add rate limiting
4. Implement refresh tokens
5. Add API documentation (Swagger/OpenAPI)

Happy testing! 🚀

