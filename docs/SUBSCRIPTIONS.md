# Subscriptions & Stripe Integration

## Overview

The subscription system manages user access levels and features through Stripe payment processing. Different subscription tiers unlock different features.

## Subscription Tiers

### Free Tier

**Role ID**: Typically `1` or `null`

**Features**:

-   Basic recipe browsing
-   Limited meal plans (only plan ID 20)
-   Basic PDF exports
-   Standard templates
-   No custom templates
-   Limited nutrition display

### Professional Tier

**Role ID**: `3`

**Features**:

-   All recipes
-   All meal plans
-   Multiple PDF themes (Classic, Modern, Bold)
-   Custom HTML templates (CKEditor)
-   Enhanced nutrition display
-   Email delivery with branding
-   Advanced filtering
-   Calendar features

### Business Tier

**Role ID**: `2` (typically)

**Features**:

-   Similar to Professional
-   Additional business features
-   Multiple user management (if implemented)

## Stripe Integration

### Payment Processing

**Library**: Laravel Cashier (Laravel\Cashier\Billable)

**Model Trait**: `User` model uses `Billable` trait

### Subscription Management

**Location**: `app/Http/Controllers/SubscriptionController.php`

### Key Methods

#### `subscribe(Request $request)`

**Purpose**: Create new subscription

**Process**:

1. Validate payment method
2. Create Stripe customer
3. Create subscription
4. Update user role
5. Return success

#### `updateSubscription(Request $request)`

**Purpose**: Update existing subscription (change plan)

**Process**:

1. Get current subscription
2. Update Stripe subscription
3. Update user role if needed
4. Return success

#### `cancelSubscription()`

**Purpose**: Cancel subscription

**Process**:

1. Cancel Stripe subscription
2. Downgrade user role
3. Preserve access until period end
4. Return success

#### `resumeSubscription()`

**Purpose**: Resume cancelled subscription

**Process**:

1. Reactivate Stripe subscription
2. Restore user role
3. Return success

## User Role Management

### Role Assignment

**On Subscription**:

```php
$user->role_id = 3; // Professional
$user->save();
```

**On Cancellation**:

```php
$user->role_id = 1; // Free
$user->save();
```

### Role Checking

**Methods**:

-   `$user->hasRole('free')` - Check if free user
-   `$user->hasRole('professional')` - Check if professional
-   `$user->role_id == 3` - Direct role check

## Feature Gating

### Recipe Access

**Free Users**:

```php
if (auth()->user()->hasRole('free')) {
    $recetas = $query->orderBy('free', 'desc')->get();
} else {
    $recetas = $query->get();
}
```

**Professional Users**: Access all recipes

### Meal Plans

**Free Users**:

```php
if (auth()->user()->hasRole('free')) {
    $planes = Plan::where('id', '20')->get();
} else {
    $planes = Plan::whereIn('tipo_id', [4, Auth::user()->role_id])->get();
}
```

### PDF Themes

**Free Users**: Basic template only

**Professional Users**:

```php
if (auth()->user()->role_id == 3) {
    if (auth()->user()->theme == 1) {
        $view = 'pdf.classic.classic-recipe';
    } elseif (auth()->user()->theme == 2) {
        $view = 'pdf.modern.modern-recipe';
    } else {
        $view = 'pdf.bold.bold-recipe';
    }
} else {
    $view = 'pdf.recipe';
}
```

### Custom Templates

**Free Users**: Not available

**Professional Users**: CKEditor for HTML templates

## Stripe Webhooks

### Webhook Events

**Subscription Created**:

-   Update user role
-   Send welcome email

**Subscription Updated**:

-   Update user role if plan changed
-   Send confirmation email

**Subscription Cancelled**:

-   Schedule role downgrade
-   Send cancellation email

**Payment Succeeded**:

-   Confirm subscription active
-   Update billing date

**Payment Failed**:

-   Send payment failure email
-   Grace period handling

### Webhook Handler

**Location**: `routes/web.php` or dedicated webhook controller

**Process**:

1. Verify Stripe signature
2. Handle event type
3. Update database
4. Send notifications
5. Return 200 status

## Subscription Plans (Stripe)

### Plan Configuration

**Stripe Dashboard**: Plans configured in Stripe

**Plan IDs**:

-   `professional_monthly`: Monthly professional subscription
-   `professional_yearly`: Yearly professional subscription
-   `business_monthly`: Monthly business subscription

### Plan Metadata

Stripe plans include metadata:

-   `role_id`: User role to assign
-   `features`: JSON of enabled features
-   `theme_access`: Available themes

## User Model Integration

### Billable Trait

**Location**: `app/Models/User.php`

**Usage**:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;

    // Subscription methods available:
    // $user->subscription('default')
    // $user->subscribed('default')
    // $user->onTrial('default')
}
```

### Subscription Fields

**Database Columns** (managed by Cashier):

-   `stripe_id`: Stripe customer ID
-   `pm_type`: Payment method type
-   `pm_last_four`: Last 4 digits of card
-   `trial_ends_at`: Trial end date
-   `subscription_ends_at`: Subscription end date

## API Endpoints

### Subscription Management

-   `POST /api/v1/subscriptions` - Create subscription
-   `GET /api/v1/subscriptions` - Get current subscription
-   `PUT /api/v1/subscriptions` - Update subscription
-   `DELETE /api/v1/subscriptions` - Cancel subscription
-   `POST /api/v1/subscriptions/resume` - Resume subscription

### Payment Methods

-   `POST /api/v1/payment-methods` - Add payment method
-   `GET /api/v1/payment-methods` - List payment methods
-   `DELETE /api/v1/payment-methods/{id}` - Remove payment method

### Invoices

-   `GET /api/v1/invoices` - List invoices
-   `GET /api/v1/invoices/{id}` - Get invoice
-   `GET /api/v1/invoices/{id}/download` - Download invoice PDF

## Spanish Terminology

| Spanish        | English        | Context            |
| -------------- | -------------- | ------------------ |
| Suscripción    | Subscription   | Payment plan       |
| Plan           | Plan           | Subscription tier  |
| Gratis         | Free           | Free tier          |
| Profesional    | Professional   | Professional tier  |
| Negocio        | Business       | Business tier      |
| Pago           | Payment        | Payment processing |
| Factura        | Invoice        | Billing document   |
| Método de Pago | Payment Method | Credit card, etc.  |

## Security Considerations

### Payment Security

1. **Never store card details**: Use Stripe tokens
2. **Verify webhooks**: Check Stripe signatures
3. **HTTPS only**: All payment endpoints
4. **Rate limiting**: Prevent abuse

### Access Control

1. **Role verification**: Check role on every feature access
2. **Subscription status**: Verify active subscription
3. **Grace period**: Handle expired subscriptions gracefully

## Related Documentation

-   [User Profiles](./USER_PROFILES.md)
-   [PDF Export](./PDF_EXPORT.md)
-   [Meal Plans](./MEAL_PLANS.md)

