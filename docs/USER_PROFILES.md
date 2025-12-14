# User Profiles System

## Overview

The user profile system manages individual and business user accounts, preferences, and settings. It supports different account types with varying features and capabilities.

## User Model

**Location**: `app/Models/User.php`

**Key Fields**:

-   `name`: First name
-   `last_name`: Last name
-   `email`: Email address
-   `password`: Hashed password
-   `role_id`: User role (1=free, 2=business, 3=professional)
-   `is_admin`: Admin flag
-   `theme`: PDF theme preference (1=classic, 2=modern, 3=bold)
-   `bemail`: Business email (for delivery confirmations)
-   `unit_measure`: Measurement system preference ('metric' or 'imperial')
-   `slug`: URL-friendly identifier

## Account Types

### Individual Accounts

**Purpose**: Personal meal planning and recipe management

**Features**:

-   Personal calendars
-   Recipe favorites
-   Shopping lists
-   Basic meal planning

**Limitations**:

-   Single user access
-   Personal data only
-   Standard features

### Business Accounts

**Purpose**: Professional nutritionists, dietitians, meal prep services

**Features**:

-   All individual features
-   Client management (if implemented)
-   Professional PDF templates
-   Email delivery with branding
-   Custom templates
-   Advanced analytics (if implemented)

**Role ID**: `2`

### Admin Accounts

**Purpose**: System administrators

**Features**:

-   Full system access
-   Recipe management
-   User management
-   Plan creation
-   System configuration

**Flag**: `is_admin = 1`

## User Preferences

### Theme Selection

**Field**: `user.theme`

**Values**:

-   `1`: Classic theme (traditional, professional)
-   `2`: Modern theme (clean, minimalist)
-   `3` (or other): Bold theme (vibrant, eye-catching)

**Usage**: Applied to PDF exports

**Update**: User can change in profile settings

### Measurement System

**Field**: `user.unit_measure` (or stored in preferences)

**Values**:

-   `'metric'`: Grams, kilograms, milliliters, liters
-   `'imperial'`: Ounces, pounds, teaspoons, tablespoons, cups

**Usage**:

-   Ingredient quantity display
-   Shopping list units
-   Recipe ingredient units

**Default**: May vary by region

### Business Email

**Field**: `user.bemail`

**Purpose**: Email address for delivery confirmations

**Usage**: When user sends PDFs via email, confirmation sent to bemail

**Example**: Nutritionist sends recipe to client, confirmation goes to bemail

## Profile Management

### View Profile

**Endpoint**: `GET /api/v1/user/profile`

**Returns**:

```json
{
    "id": 1,
    "name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "role_id": 3,
    "theme": 2,
    "unit_measure": "metric",
    "bemail": "business@example.com"
}
```

### Update Profile

**Endpoint**: `PUT /api/v1/user/profile`

**Parameters**:

-   `name`: First name
-   `last_name`: Last name
-   `email`: Email (requires verification)
-   `theme`: Theme preference
-   `unit_measure`: Measurement system
-   `bemail`: Business email
-   `password`: New password (optional)
-   `current_password`: Current password (for password change)

### Change Password

**Endpoint**: `POST /api/v1/user/password`

**Parameters**:

-   `current_password`: Current password
-   `password`: New password
-   `password_confirmation`: Confirm new password

## Nutritional Preferences

### Nutritional Preferences Table

**Table**: `nutritional_preferences`

**Fields**:

-   `user_id`: User
-   `nutritional_info`: JSON of preferred nutrients to display

**Purpose**: Customize which nutrients appear in PDFs and displays

**Structure**:

```json
{
    "1008": true, // Show calories
    "1005": true, // Show carbohydrates
    "1003": true, // Show protein
    "1004": true // Show fat
    // ... more nutrients
}
```

**Default**: Uses `config('constants.nutritients')` if not set

### Update Preferences

**Endpoint**: `PUT /api/v1/user/nutritional-preferences`

**Parameters**: JSON object of nutrient IDs and visibility flags

## User Roles & Permissions

### Role Model

**Location**: `app/Models/Role.php`

**Fields**:

-   `id`: Role ID
-   `name`: Role name

### Permission System

**Trait**: `HasPermissionsTrait`

**Location**: `app/Permissions/HasPermissionsTrait.php`

**Methods**:

-   `hasPermission($permission)`: Check if user has permission
-   `hasRole($role)`: Check if user has role

### Common Permissions

-   `lista_view`: View shopping lists
-   `calendar_create`: Create calendars
-   `recipe_view`: View recipes
-   `plan_copy`: Copy meal plans

## Account Settings

### Email Notifications

**Preferences** (if implemented):

-   Recipe comments
-   Meal plan updates
-   Subscription reminders
-   Delivery confirmations

### Privacy Settings

**Options** (if implemented):

-   Profile visibility
-   Recipe sharing
-   Calendar sharing

## API Endpoints

### Profile Management

-   `GET /api/v1/user/profile` - Get user profile
-   `PUT /api/v1/user/profile` - Update profile
-   `POST /api/v1/user/password` - Change password

### Preferences

-   `GET /api/v1/user/preferences` - Get preferences
-   `PUT /api/v1/user/preferences` - Update preferences
-   `GET /api/v1/user/nutritional-preferences` - Get nutritional preferences
-   `PUT /api/v1/user/nutritional-preferences` - Update nutritional preferences

## Spanish Terminology

| Spanish            | English            | Context            |
| ------------------ | ------------------ | ------------------ |
| Usuario            | User               | Account holder     |
| Perfil             | Profile            | User profile       |
| Preferencias       | Preferences        | User settings      |
| Tema               | Theme              | Visual theme       |
| Sistema de Medidas | Measurement System | Metric/Imperial    |
| Correo de Negocio  | Business Email     | Professional email |
| Contraseña         | Password           | Account password   |
| Individual         | Individual         | Personal account   |
| Negocio            | Business           | Business account   |

## Related Documentation

-   [Subscriptions](./SUBSCRIPTIONS.md)
-   [PDF Export](./PDF_EXPORT.md)
-   [Calendar System](./CALENDAR_SYSTEM.md)

