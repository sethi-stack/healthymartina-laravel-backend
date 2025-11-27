# Testing the API with Existing Database

## Current Situation

✅ **What's Working:**

-   Laravel 11 installed
-   Sanctum configured
-   API endpoints created (auth/login, auth/register, etc.)
-   Controllers, Requests, Resources built

❌ **What's NOT Working (yet):**

-   Tests fail because they use fresh SQLite (missing `username`, `last_name` columns)
-   Models don't match the real database structure

## Solution: Use the Real Database

### Step 1: Find the Old Laravel Database Config

```bash
cd /Users/dj/Documents/Programming/healthymartina/healthymartina_app
cat .env | grep "^DB_"
```

You'll see something like:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthymartina
DB_USERNAME=root
DB_PASSWORD=secret
```

### Step 2: Copy Database Config to New API

```bash
cd laravel-backend-app

# Edit .env file
nano .env
```

Update these lines to match the old app:

```env
DB_CONNECTION=mysql  # or postgresql, whatever old app uses
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthymartina  # SAME database as old app
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### Step 3: Copy the User Model from Old App

The old app has the correct User model with all the right fields. Let's use it:

```bash
cd /Users/dj/Documents/Programming/healthymartina/healthymartina_app

# Backup the new User model
mv laravel-backend-app/app/Models/User.php laravel-backend-app/app/Models/User.php.new

# Copy the real User model
cp app/User.php laravel-backend-app/app/Models/User.php
```

Then edit `laravel-backend-app/app/Models/User.php` to:

1. Keep all existing code
2. Add `use Laravel\Sanctum\HasApiTokens;`
3. Add `HasApiTokens` to the traits list

### Step 4: Test Manually (Recommended)

Since the database already has data, let's test manually first:

```bash
cd laravel-backend-app

# Start the server
php artisan serve
```

In another terminal, test with real database:

```bash
# Try to register a NEW user (will write to real database!)
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "API",
    "last_name": "Test",
    "username": "apitest123",
    "email": "apitest@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

If it works, you'll get back a token! 🎉

### Step 5: Test with Existing User

If there's already a user in the database:

```bash
# Login with existing user
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "existing@example.com",
    "password": "their_password"
  }'
```

## For Automated Tests

### Option A: Use a Separate Test Database

```bash
# Create test database (copy of production)
mysqldump -u root -p healthymartina > backup.sql
mysql -u root -p -e "CREATE DATABASE healthymartina_test;"
mysql -u root -p healthymartina_test < backup.sql
```

Update `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="healthymartina_test"/>
```

### Option B: Use DatabaseTransactions

Update tests to use `DatabaseTransactions` instead of `RefreshDatabase`:

```php
<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions; // Changed!
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions; // Rolls back after each test

    // Tests here...
}
```

## Quick Start Guide

### 1. Connect to Existing Database

```bash
# Edit .env in new laravel-backend-app
DB_CONNECTION=mysql
DB_DATABASE=healthymartina  # Same as old app!
```

### 2. Start Server

```bash
cd laravel-backend-app
php artisan serve
```

### 3. Run Manual Test Script

```bash
./test-api.sh
```

This will:

-   Check if server is running
-   Register a new user
-   Get user info with token
-   Login with same user
-   Logout
-   Verify token is revoked

### 4. Check It Works

If you see ✅ green checkmarks, the API is working with the real database!

## What to Do Next

Once connected to the real database:

1. ✅ **Copy all Models** from old app to new app

    - Receta, Calendar, Ingrediente, etc.
    - Add `HasApiTokens` to User model only

2. ✅ **Create Recipe API Endpoints**

    - RecipeController
    - RecipeService
    - RecipeResource

3. ✅ **Create Calendar API Endpoints**

    - CalendarController
    - CalendarService
    - CalendarResource

4. ✅ **Test each endpoint** as you build it

## Important Notes

-   **Same Database**: Both old and new Laravel apps share the same database
-   **No Migrations**: Don't run migrations in the new app
-   **Models**: Copy from old app, don't create new ones
-   **Testing**: Use real data or DatabaseTransactions for tests
-   **Admin Panel**: Stays in old app, untouched
-   **API**: New app only provides API endpoints

## Troubleshooting

### "Connection Refused"

-   Check database is running: `mysql -u root -p`
-   Check credentials in `.env`

### "Table doesn't exist"

-   You're not connected to the right database
-   Check `DB_DATABASE` in `.env`

### "Column not found"

-   Model doesn't match database structure
-   Copy model from old app instead of using new one

### Tests Still Failing

-   Use `DatabaseTransactions` not `RefreshDatabase`
-   Or use a test database copy
-   Or skip automated tests for now, use manual testing

## Success Criteria

✅ Manual curl tests work
✅ Can register new user
✅ Can login existing user
✅ Token authentication works
✅ Data appears in same database as old app

Once these work, you're ready to build more API endpoints!
