# Database Setup for Laravel Backend API

## Important: No Schema Migrations!

This new Laravel API backend **uses the existing database schema** from the original Laravel application.

**We will NOT create new migrations or modify the database structure.**

## Setup Options

### Option 1: Connect to Existing Database (Recommended)

Point the new Laravel API to the same database as the old Laravel app.

**Steps:**

1. Check the database configuration in the old Laravel app:

```bash
cd /Users/dj/Documents/Programming/healthymartina/healthymartina_app
cat .env | grep DB_
```

2. Copy those same database credentials to the new API `.env`:

```bash
cd laravel-backend-app
nano .env
```

3. Update these values to match the old app:

```env
DB_CONNECTION=mysql  # or whatever the old app uses
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthymartina  # same database name
DB_USERNAME=root
DB_PASSWORD=your_password
```

4. **Do NOT run migrations!** The schema already exists.

### Option 2: Copy Database for Testing

If you want to test without affecting the production/development database:

```bash
# Export from old database
mysqldump -u root -p healthymartina > healthymartina_backup.sql

# Create new test database
mysql -u root -p -e "CREATE DATABASE healthymartina_test;"

# Import
mysql -u root -p healthymartina_test < healthymartina_backup.sql

# Update new API .env to use test database
DB_DATABASE=healthymartina_test
```

## Current Issue with Tests

The tests are failing because we created a **fresh Laravel installation** which created a new SQLite database with only the default `users` table (missing `username`, `last_name`, etc.).

### Fix for Tests

**Option A: Use MySQL for tests (connect to existing DB)**

Update `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="healthymartina_test"/>
```

**Option B: Skip migrations in tests**

Since the schema already exists, we should:

1. Connect tests to a copy of the real database
2. Use `RefreshDatabase` trait carefully (it will try to migrate)
3. Or use `DatabaseTransactions` instead (doesn't migrate, just rolls back)

## Models: Copy from Old Laravel App

Instead of creating new models, we should **copy the existing models** from the old Laravel app:

```bash
# Copy models
cp -r /Users/dj/Documents/Programming/healthymartina/healthymartina_app/app/Models/* \
      /Users/dj/Documents/Programming/healthymartina/healthymartina_app/laravel-backend-app/app/Models/

# Copy User model
cp /Users/dj/Documents/Programming/healthymartina/healthymartina_app/app/User.php \
   /Users/dj/Documents/Programming/healthymartina/healthymartina_app/laravel-backend-app/app/Models/User.php
```

Then update the models to:

-   Add `HasApiTokens` trait for Sanctum
-   Keep all existing relationships
-   Keep all existing methods
-   Keep all existing casts and attributes

## What We CAN Modify

✅ **Controllers** - Create new API controllers
✅ **Services** - Create new service classes
✅ **Requests** - Create new validation classes
✅ **Resources** - Create new API response transformers
✅ **Routes** - Create new API routes
✅ **Middleware** - Add new middleware
✅ **Config files** - Update configs
✅ **Models** - Add traits (like `HasApiTokens`), but keep existing structure

## What We CANNOT Modify

❌ **Database schema** - Use existing structure
❌ **Migrations** - Don't create new ones for app tables
❌ **Model relationships** - Keep existing ones
❌ **Column names** - Use existing column names

## Next Steps

1. **Stop using the fresh SQLite database**
2. **Connect to the real MySQL/PostgreSQL database**
3. **Copy existing models** instead of creating new ones
4. **Update tests** to use the real database structure
5. **Continue building API controllers** that work with existing models

## Testing Strategy

Since we're using an existing schema:

1. **Integration Tests**: Test against a copy of the real database
2. **Use DatabaseTransactions**: Rollback after each test
3. **Seed Test Data**: Create factories based on existing models
4. **Don't use RefreshDatabase**: It tries to run migrations

Example test setup:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions; // Not RefreshDatabase!

class RegisterTest extends TestCase
{
    use DatabaseTransactions; // Rolls back, doesn't migrate

    // ... tests
}
```

## Summary

**This new Laravel API is a "wrapper" around the existing database.**

We're building:

-   Modern API endpoints
-   Clean service architecture
-   Sanctum authentication
-   API resources

But we're **NOT** changing:

-   Database structure
-   Existing models (except adding Sanctum trait)
-   Data storage patterns
