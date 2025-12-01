# Development Tasks - End-to-End Feature Development

## Overview

This document outlines the development tasks for building the Healthy Martina application, organized by phases. Each phase covers both backend (Laravel API) and frontend (React.js) development to deliver complete, working features.

## Phase 1: Recipe List & Detail Pages

### Backend Tasks

#### 1.1 Recipe List API

-   [ ] **GET /api/v1/recipes** - List recipes with pagination
    -   [ ] Implement pagination (27 items per page)
    -   [ ] Return recipe metadata (id, title, slug, image, time, portions, likes/dislikes)
    -   [ ] Include active/free flags for filtering
    -   [ ] Add sorting options (newest, popular, etc.)
    -   [ ] Write API tests

#### 1.2 Recipe Detail API

-   [ ] **GET /api/v1/recipes/{id}** - Get single recipe
    -   [ ] Return full recipe data
    -   [ ] Include recipe images (imagen_principal, imagen_secundaria)
    -   [ ] Include cooking time (tiempo)
    -   [ ] Include ingredients (via getIngredientes())
    -   [ ] Include instructions (via getInstrucciones())
    -   [ ] Include tips (via getTips()) with recipe link parsing
    -   [ ] Include portion information (via getPorciones())
    -   [ ] Include like/dislike counts (like_reactions, dislike_reactions)
    -   [ ] Include user's reaction (if authenticated)
    -   [ ] Handle sub-recipes in ingredients
    -   [ ] Write API tests

#### 1.2.1 Recipe Comments API (for detail page)

-   [ ] **GET /api/v1/recipes/{id}/comments** - List recipe comments
    -   [ ] Return comments with user info
    -   [ ] Include nested replies
    -   [ ] Include elapsed time
    -   [ ] Support pagination
    -   [ ] Write API tests

#### 1.2.2 Recipe Reactions API (for detail page)

-   [ ] **POST /api/v1/recipes/{id}/reaction** - Like/dislike recipe
    -   [ ] Create or update reaction
    -   [ ] Return updated like/dislike counts
    -   [ ] Return user's current reaction
    -   [ ] Write API tests

#### 1.3 Recipe View Tracking

-   [ ] **POST /api/v1/recipes/{id}/view** - Track recipe view
    -   [ ] Check if nutrient_info exists
    -   [ ] If not, trigger getInformacionNutrimental()
    -   [ ] Store result in nutrient_info JSON column
    -   [ ] Return success response
    -   [ ] Handle errors gracefully
    -   [ ] Write API tests

#### 1.4 Recipe Nutrition API

-   [ ] **GET /api/v1/recipes/{id}/nutrition** - Get nutrition info
    -   [ ] Return nutrient_info from JSON column
    -   [ ] Format for frontend consumption
    -   [ ] Include percentage calculations
    -   [ ] Handle missing nutrition data
    -   [ ] Write API tests

#### 1.5 Recipe Ingredients API

-   [ ] **GET /api/v1/recipes/{id}/ingredients** - Get recipe ingredients
    -   [ ] Return formatted ingredients array
    -   [ ] Include unit information (medida, medida_english, tipo_medida_id)
    -   [ ] Handle sub-recipes (return as links/objects)
    -   [ ] Include preparation methods (instruccion nombre)
    -   [ ] Write API tests

### Frontend Tasks (React.js)

#### 1.6 Recipe List Component

-   [ ] Create `RecipeList` component
    -   [ ] Fetch recipes from API
    -   [ ] Display recipe cards with images
    -   [ ] Implement pagination
    -   [ ] Add loading states
    -   [ ] Add error handling
    -   [ ] Implement infinite scroll (optional)
    -   [ ] Add filtering UI (active/free toggle)
    -   [ ] Add sorting dropdown

#### 1.7 Recipe Card Component

-   [ ] Create `RecipeCard` component
    -   [ ] Display recipe image
    -   [ ] Show recipe title
    -   [ ] Display cooking time
    -   [ ] Show portion count
    -   [ ] Display likes/dislikes
    -   [ ] Add click handler to navigate to detail
    -   [ ] Add hover effects
    -   [ ] Make responsive

#### 1.8 Recipe Detail Page

-   [ ] Create `RecipeDetail` component
    -   [ ] Fetch recipe data on mount
    -   [ ] Display recipe header with title
    -   [ ] Display main image (imagen_principal)
    -   [ ] Display cooking time (tiempo) with icon
    -   [ ] Display portion information
    -   [ ] Show ingredients list
    -   [ ] Display instructions section
    -   [ ] Show tips section (with recipe link parsing)
    -   [ ] Display secondary image (imagen_secundaria) if available
    -   [ ] Show like/dislike buttons and counts
    -   [ ] Display comments section
    -   [ ] Add loading state
    -   [ ] Add error handling
    -   [ ] Implement 404 handling
    -   [ ] Make responsive layout

#### 1.8.1 Recipe Image Component

-   [ ] Create `RecipeImage` component
    -   [ ] Display main image (imagen_principal)
    -   [ ] Display secondary image (imagen_secundaria) if available
    -   [ ] Handle image loading states
    -   [ ] Handle image errors (fallback)
    -   [ ] Add image zoom/lightbox (optional)
    -   [ ] Make responsive

#### 1.8.2 Recipe Header Component

-   [ ] Create `RecipeHeader` component
    -   [ ] Display recipe title
    -   [ ] Display cooking time with clock icon
    -   [ ] Display portion count
    -   [ ] Show like/dislike counts
    -   [ ] Display recipe metadata
    -   [ ] Add share button (future)
    -   [ ] Make responsive

#### 1.8.3 Recipe Instructions Component

-   [ ] Create `RecipeInstructions` component
    -   [ ] Display instructions as numbered list
    -   [ ] Parse instruction text (split by newlines)
    -   [ ] Format instructions nicely
    -   [ ] Add step-by-step navigation (optional)
    -   [ ] Make responsive

#### 1.8.4 Recipe Tips Component

-   [ ] Create `RecipeTips` component
    -   [ ] Display tips list
    -   [ ] Parse tips (split by newlines)
    -   [ ] Parse recipe references (receta[123] format)
    -   [ ] Convert recipe references to clickable links
    -   [ ] Handle recipe link navigation
    -   [ ] Display tips with icons (optional)
    -   [ ] Make responsive

#### 1.8.5 Recipe Reactions Component

-   [ ] Create `RecipeReactions` component
    -   [ ] Display like button with count
    -   [ ] Display dislike button with count
    -   [ ] Show user's current reaction (highlighted)
    -   [ ] Handle like/dislike click
    -   [ ] Update counts in real-time
    -   [ ] Show loading state during API call
    -   [ ] Handle errors gracefully
    -   [ ] Require authentication for reactions

#### 1.8.6 Recipe Comments Component

-   [ ] Create `RecipeComments` component
    -   [ ] Fetch comments on mount
    -   [ ] Display comments list
    -   [ ] Show comment author and time
    -   [ ] Display nested replies
    -   [ ] Add comment form
    -   [ ] Handle @mentions in comments
    -   [ ] Support replying to comments
    -   [ ] Delete own comments (if authorized)
    -   [ ] Show loading state
    -   [ ] Handle pagination
    -   [ ] Require authentication for commenting

#### 1.9 Ingredients Display Component

-   [ ] Create `IngredientsList` component
    -   [ ] Display ingredients with quantities
    -   [ ] Show units (medida)
    -   [ ] Handle sub-recipes (display as links)
    -   [ ] Show preparation methods
    -   [ ] Format quantities properly
    -   [ ] Add ingredient checkboxes (for shopping list - future)

#### 1.10 Portion Slider Component

-   [ ] Create `PortionSlider` component
    -   [ ] Display current portion count
    -   [ ] Implement range slider
    -   [ ] Get min/max/step from recipe data
    -   [ ] Update ingredient quantities on change
    -   [ ] Call unit conversion logic
    -   [ ] Update display in real-time
    -   [ ] Store portion in URL query param (?ser=2)

#### 1.11 Unit Conversion Service

-   [ ] Create `unitConversionService.js`
    -   [ ] Port `updatePortions()` logic from lista-dj.js
    -   [ ] Port `getNearestFraction()` function
    -   [ ] Port `getStringFractionValue()` function
    -   [ ] Port `numFraction()` function
    -   [ ] Handle volume conversions (tsp → tbsp → cup)
    -   [ ] Handle weight conversions (g → kg, oz → lb)
    -   [ ] Handle metric/imperial system preference
    -   [ ] Add unit tests

#### 1.12 Fraction Display Component

-   [ ] Create `FractionDisplay` component
    -   [ ] Accept fraction object (int, fraction)
    -   [ ] Display integer + fraction
    -   [ ] Style fraction with smallFraction class
    -   [ ] Handle edge cases (0, whole numbers)

#### 1.13 Nutrition Display Component

-   [ ] Create `NutritionInfo` component
    -   [ ] Fetch nutrition data (trigger view API call)
    -   [ ] Display nutrition table
    -   [ ] Show percentages
    -   [ ] Add pie chart for macronutrients (optional)
    -   [ ] Add bar chart for all nutrients (optional)
    -   [ ] Handle loading state
    -   [ ] Handle missing nutrition data

#### 1.14 Recipe View Tracking

-   [ ] Implement view tracking
    -   [ ] Call POST /api/v1/recipes/{id}/view on detail page load
    -   [ ] Handle nutrition calculation trigger
    -   [ ] Show loading indicator during calculation
    -   [ ] Handle calculation errors

### Integration Tasks

#### 1.15 API Integration

-   [ ] Set up API client (axios/fetch)
-   [ ] Configure base URL
-   [ ] Add authentication headers
-   [ ] Implement request interceptors
-   [ ] Add error handling middleware
-   [ ] Set up response interceptors

#### 1.16 Routing

-   [ ] Set up React Router
-   [ ] Create routes:
    -   [ ] `/recipes` - Recipe list
    -   [ ] `/recipes/:id` - Recipe detail
    -   [ ] `/recipes/:slug` - Recipe detail by slug (optional)
-   [ ] Add navigation between pages
-   [ ] Handle 404 routes

#### 1.17 State Management

-   [ ] Set up state management (Redux/Context/Zustand)
-   [ ] Create recipe store/slice
-   [ ] Implement recipe list caching
-   [ ] Implement recipe detail caching
-   [ ] Add loading states
-   [ ] Add error states

## Phase 2: Recipe Search & Filtering

### Backend Tasks

#### 2.1 Advanced Recipe Search API

-   [ ] **GET /api/v1/recipes/search** - Advanced search
    -   [ ] Implement RecipeFilterService integration
    -   [ ] Support tag filtering
    -   [ ] Support ingredient include/exclude
    -   [ ] Support number of ingredients range
    -   [ ] Support cooking time range
    -   [ ] Support calorie range
    -   [ ] Support nutrient filtering (30+ nutrients)
    -   [ ] Handle sub-recipe ingredient checking
    -   [ ] Implement parent recipe inclusion logic
    -   [ ] Add pagination
    -   [ ] Write comprehensive API tests

#### 2.2 Filter Bookmarks API

-   [ ] **GET /api/v1/filters/bookmarks** - List bookmarks
-   [ ] **POST /api/v1/filters/bookmarks** - Save bookmark
-   [ ] **GET /api/v1/filters/bookmarks/{id}** - Get bookmark
-   [ ] **PUT /api/v1/filters/bookmarks/{id}** - Update bookmark
-   [ ] **DELETE /api/v1/filters/bookmarks/{id}** - Delete bookmark
-   [ ] **POST /api/v1/filters/bookmarks/merge** - Merge multiple bookmarks
-   [ ] Write API tests

#### 2.3 Tags API

-   [ ] **GET /api/v1/tags** - List all tags
-   [ ] Return tags with recipe counts (optional)
-   [ ] Write API tests

#### 2.4 Ingredients API (for filtering)

-   [ ] **GET /api/v1/ingredients** - List all ingredients
-   [ ] Support search by name
-   [ ] Return ingredient IDs and names
-   [ ] Write API tests

#### 2.5 Nutrient Types API

-   [ ] **GET /api/v1/nutrient-types** - List nutrient types
-   [ ] Return nutrient types with nutrients
-   [ ] Format for filter UI
-   [ ] Write API tests

### Frontend Tasks

#### 2.6 Search & Filter UI

-   [ ] Create `RecipeSearch` component
    -   [ ] Search input field
    -   [ ] Tag multi-select
    -   [ ] Ingredient include multi-select
    -   [ ] Ingredient exclude multi-select
    -   [ ] Number of ingredients range slider
    -   [ ] Cooking time range slider
    -   [ ] Calorie range slider
    -   [ ] Nutrient filters (expandable sections)
    -   [ ] Apply/Reset buttons
    -   [ ] Save filter as bookmark button

#### 2.7 Filter Bookmarks UI

-   [ ] Create `FilterBookmarks` component
    -   [ ] Display saved bookmarks
    -   [ ] Load bookmark button
    -   [ ] Delete bookmark button
    -   [ ] Merge bookmarks functionality
    -   [ ] Bookmark name input

#### 2.8 Advanced Filter Panel

-   [ ] Create `AdvancedFilters` component
    -   [ ] Collapsible sections for each filter type
    -   [ ] Nutrient type accordions
    -   [ ] Min/max inputs for ranges
    -   [ ] Visual feedback for active filters
    -   [ ] Filter count badge

#### 2.9 Search Results Component

-   [ ] Update `RecipeList` for search results
    -   [ ] Display filtered results
    -   [ ] Show result count
    -   [ ] Show active filters
    -   [ ] Clear filters option
    -   [ ] Maintain filter state in URL

## Phase 3: Calendar System

### Backend Tasks

#### 3.1 Calendar CRUD API

-   [ ] **GET /api/v1/calendars** - List user calendars
-   [ ] **POST /api/v1/calendars** - Create calendar
-   [ ] **GET /api/v1/calendars/{id}** - Get calendar
-   [ ] **PUT /api/v1/calendars/{id}** - Update calendar
-   [ ] **DELETE /api/v1/calendars/{id}** - Delete calendar
-   [ ] Validate calendar ownership
-   [ ] Write API tests

#### 3.2 Calendar Schedule API

-   [ ] **POST /api/v1/calendars/{id}/recipes** - Add recipe to schedule
    -   [ ] Support main and side dishes
    -   [ ] Handle day and meal selection
    -   [ ] Support multiple days
    -   [ ] Handle leftover flag
    -   [ ] Update servings
-   [ ] **PUT /api/v1/calendars/{id}/recipes** - Update recipe in schedule
-   [ ] **DELETE /api/v1/calendars/{id}/recipes** - Remove recipe
-   [ ] Write API tests

#### 3.3 Calendar Nutrition API

-   [ ] **GET /api/v1/calendars/{id}/nutrition** - Daily nutrition
    -   [ ] Calculate nutrition per day
    -   [ ] Aggregate main + side dishes
    -   [ ] Scale by servings
    -   [ ] Calculate percentages
    -   [ ] Return formatted data
-   [ ] **GET /api/v1/calendars/{id}/nutrition/{day}** - Specific day
-   [ ] Write API tests

#### 3.4 Calendar Schedules API

-   [ ] **GET /api/v1/calendars/{id}/schedules** - Get schedule JSON
    -   [ ] Return main_schedule and sides_schedule
    -   [ ] Include servings
    -   [ ] Include leftovers
    -   [ ] Format for frontend
-   [ ] Write API tests

### Frontend Tasks

#### 3.5 Calendar List Component

-   [ ] Create `CalendarList` component
    -   [ ] Display user's calendars
    -   [ ] Create new calendar button
    -   [ ] Delete calendar
    -   [ ] Switch active calendar
    -   [ ] Calendar preview

#### 3.6 Calendar View Component

-   [ ] Create `CalendarView` component
    -   [ ] Display weekly grid (Monday-Sunday)
    -   [ ] Show 3 meals per day (breakfast, lunch, dinner)
    -   [ ] Display main and side dish slots
    -   [ ] Drag and drop recipes (optional)
    -   [ ] Click to add recipe
    -   [ ] Show serving sizes
    -   [ ] Display leftover indicators

#### 3.7 Recipe Picker Component

-   [ ] Create `RecipePicker` component
    -   [ ] Search/select recipe
    -   [ ] Choose main or side
    -   [ ] Select days (multi-select)
    -   [ ] Select meal (breakfast/lunch/dinner)
    -   [ ] Set serving size
    -   [ ] Toggle leftover checkbox
    -   [ ] Submit to add to calendar

#### 3.8 Daily Nutrition Component

-   [ ] Create `DailyNutrition` component
    -   [ ] Fetch daily nutrition data
    -   [ ] Display nutrition summary
    -   [ ] Show pie chart (macronutrients)
    -   [ ] Show bar chart (all nutrients)
    -   [ ] Display percentages
    -   [ ] Update on calendar changes

#### 3.9 Calendar Meal Component

-   [ ] Create `CalendarMeal` component
    -   [ ] Display recipe card in calendar slot
    -   [ ] Show serving size
    -   [ ] Show leftover badge
    -   [ ] Edit meal button
    -   [ ] Remove meal button
    -   [ ] Click to view recipe

## Phase 4: Shopping List (Lista)

### Backend Tasks

#### 4.1 Shopping List Generation API

-   [ ] **GET /api/v1/calendars/{id}/lista** - Generate shopping list
    -   [ ] Use getRelatedIngrediente() helper
    -   [ ] Aggregate ingredients by category
    -   [ ] Handle sub-recipes
    -   [ ] Handle unit conversions
    -   [ ] Exclude leftover ingredients
    -   [ ] Return categorized list
-   [ ] **GET /api/v1/calendars/{id}/lista/category/{categoryId}** - Category list
-   [ ] Write API tests

#### 4.2 Shopping List Check-Off API

-   [ ] **POST /api/v1/calendars/{id}/lista/toggle** - Toggle check-off
    -   [ ] Update lista_ingrediente_taken table
    -   [ ] Return updated list
    -   [ ] Validate ownership
-   [ ] Write API tests

#### 4.3 Custom List Items API

-   [ ] **POST /api/v1/calendars/{id}/lista/items** - Add custom item
-   [ ] **PUT /api/v1/calendars/{id}/lista/items/{itemId}** - Update item
-   [ ] **DELETE /api/v1/calendars/{id}/lista/items/{itemId}** - Delete item
-   [ ] Write API tests

#### 4.4 Categories API

-   [ ] **GET /api/v1/categories** - List ingredient categories
-   [ ] Return sorted by sort field
-   [ ] Write API tests

### Frontend Tasks

#### 4.5 Shopping List Component

-   [ ] Create `ShoppingList` component
    -   [ ] Fetch shopping list on calendar change
    -   [ ] Display by category
    -   [ ] Show ingredient quantities
    -   [ ] Display units
    -   [ ] Show check-off checkboxes
    -   [ ] Update on check-off
    -   [ ] Add custom item button

#### 4.6 Shopping List Category Component

-   [ ] Create `ShoppingListCategory` component
    -   [ ] Display category name
    -   [ ] List ingredients in category
    -   [ ] Show aggregated quantities
    -   [ ] Handle unit conversions
    -   [ ] Collapsible sections

#### 4.7 Shopping List Item Component

-   [ ] Create `ShoppingListItem` component
    -   [ ] Display ingredient name
    -   [ ] Show quantity with unit
    -   [ ] Display check-off checkbox
    -   [ ] Strikethrough when checked
    -   [ ] Handle click to toggle

#### 4.8 Custom Item Form

-   [ ] Create `CustomItemForm` component
    -   [ ] Item name input
    -   [ ] Category select
    -   [ ] Quantity input
    -   [ ] Unit select
    -   [ ] Submit button
    -   [ ] Validation

#### 4.9 Unit Aggregation Logic

-   [ ] Port `repeatItem()` from lista-dj.js
-   [ ] Port `subRecipeItem()` logic
-   [ ] Create aggregation service
-   [ ] Handle unit conversions during aggregation
-   [ ] Add unit tests

## Phase 5: PDF Export

### Backend Tasks

#### 5.1 Recipe PDF API

-   [ ] **POST /api/v1/recipes/{id}/pdf** - Generate recipe PDF
    -   [ ] Accept portion parameter
    -   [ ] Accept recipe_ingredients JSON
    -   [ ] Accept plantillas (templates) for professional users
    -   [ ] Select view based on user theme
    -   [ ] Generate PDF
    -   [ ] Return PDF download
-   [ ] **POST /api/v1/recipes/{id}/pdf/email** - Email recipe PDF
    -   [ ] Validate recipient email
    -   [ ] Generate PDF
    -   [ ] Send email with attachment
    -   [ ] Send delivery confirmation
-   [ ] Write API tests

#### 5.2 Calendar PDF API

-   [ ] **POST /api/v1/calendars/{id}/pdf** - Generate calendar PDF
    -   [ ] Handle chunking for large calendars
    -   [ ] Support export_stitch parameter
    -   [ ] Generate multi-page PDF
    -   [ ] Return PDF download
-   [ ] **POST /api/v1/calendars/{id}/pdf/email** - Email calendar PDF
-   [ ] Write API tests

#### 5.3 Shopping List PDF API

-   [ ] **POST /api/v1/calendars/{id}/lista/pdf** - Generate shopping list PDF
    -   [ ] Accept lista_ingredients JSON
    -   [ ] Accept plantillas for professional users
    -   [ ] Generate PDF
    -   [ ] Return PDF download
-   [ ] **POST /api/v1/calendars/{id}/lista/pdf/email** - Email shopping list PDF
-   [ ] Write API tests

### Frontend Tasks

#### 5.4 PDF Export UI

-   [ ] Create `PDFExportButton` component
    -   [ ] Export button
    -   [ ] Loading state during generation
    -   [ ] Success notification
    -   [ ] Error handling
    -   [ ] Download PDF on success

#### 5.5 Email PDF Form

-   [ ] Create `EmailPDFForm` component (Professional users)
    -   [ ] Recipient email input
    -   [ ] Custom template editor (CKEditor)
    -   [ ] Send button
    -   [ ] Success/error messages
    -   [ ] Validation

#### 5.6 PDF Generation Service

-   [ ] Create `pdfService.js`
    -   [ ] Recipe PDF generation
    -   [ ] Calendar PDF generation
    -   [ ] Shopping list PDF generation
    -   [ ] Email PDF functionality
    -   [ ] Handle large calendar chunking
    -   [ ] Progress tracking

## Phase 6: Meal Plans

### Backend Tasks

#### 6.1 Meal Plans API

-   [ ] **GET /api/v1/plans** - List available plans
    -   [ ] Filter by user role
    -   [ ] Return plan metadata
-   [ ] **GET /api/v1/plans/{id}** - Get plan details
-   [ ] **GET /api/v1/plans/{id}/preview** - Preview plan calendar
-   [ ] **POST /api/v1/plans/{id}/copy** - Copy plan to calendar
    -   [ ] Implement manipulateServings() logic
    -   [ ] Handle scaling factor
    -   [ ] Create new calendar
    -   [ ] Set as active
-   [ ] **GET /api/v1/plans/{id}/pdf** - Export plan PDF
-   [ ] Write API tests

### Frontend Tasks

#### 6.2 Meal Plans List Component

-   [ ] Create `MealPlansList` component
    -   [ ] Display available plans
    -   [ ] Plan cards with preview
    -   [ ] Filter by category (optional)
    -   [ ] Preview button
    -   [ ] Copy button

#### 6.3 Meal Plan Preview Component

-   [ ] Create `MealPlanPreview` component
    -   [ ] Display plan calendar
    -   [ ] Show weekly schedule
    -   [ ] Display plan description
    -   [ ] Copy to calendar button
    -   [ ] Scale factor input

#### 6.4 Copy Plan Form

-   [ ] Create `CopyPlanForm` component
    -   [ ] Calendar name input
    -   [ ] Scale factor slider/input
    -   [ ] Preview of scaled servings
    -   [ ] Copy button
    -   [ ] Success handling

## Phase 7: User Authentication & Profiles

### Backend Tasks

#### 7.1 Authentication API

-   [ ] **POST /api/v1/auth/register** - User registration
-   [ ] **POST /api/v1/auth/login** - User login
-   [ ] **POST /api/v1/auth/logout** - User logout
-   [ ] **POST /api/v1/auth/refresh** - Refresh token
-   [ ] Implement Laravel Sanctum
-   [ ] Write API tests

#### 7.2 User Profile API

-   [ ] **GET /api/v1/user/profile** - Get profile
-   [ ] **PUT /api/v1/user/profile** - Update profile
-   [ ] **POST /api/v1/user/password** - Change password
-   [ ] Write API tests

#### 7.3 User Preferences API

-   [ ] **GET /api/v1/user/preferences** - Get preferences
-   [ ] **PUT /api/v1/user/preferences** - Update preferences
-   [ ] **GET /api/v1/user/nutritional-preferences** - Get nutritional preferences
-   [ ] **PUT /api/v1/user/nutritional-preferences** - Update nutritional preferences
-   [ ] Write API tests

### Frontend Tasks

#### 7.5 Authentication Components

-   [ ] Create `Login` component
-   [ ] Create `Register` component
-   [ ] Create `ForgotPassword` component
-   [ ] Create `ResetPassword` component
-   [ ] Implement auth context/store
-   [ ] Add protected routes
-   [ ] Add auth guards

#### 7.6 User Profile Components

-   [ ] Create `UserProfile` component
-   [ ] Create `ProfileSettings` component
-   [ ] Create `ThemeSelector` component
-   [ ] Create `MeasurementSystemSelector` component
-   [ ] Create `NutritionalPreferences` component
-   [ ] Add form validation
-   [ ] Add success/error notifications

## Phase 8: Subscriptions & Payments

### Backend Tasks

#### 8.1 Subscription API

-   [ ] **POST /api/v1/subscriptions** - Create subscription
-   [ ] **GET /api/v1/subscriptions** - Get current subscription
-   [ ] **PUT /api/v1/subscriptions** - Update subscription
-   [ ] **DELETE /api/v1/subscriptions** - Cancel subscription
-   [ ] **POST /api/v1/subscriptions/resume** - Resume subscription
-   [ ] Integrate Stripe
-   [ ] Handle webhooks
-   [ ] Write API tests

#### 8.2 Payment Methods API

-   [ ] **POST /api/v1/payment-methods** - Add payment method
-   [ ] **GET /api/v1/payment-methods** - List payment methods
-   [ ] **DELETE /api/v1/payment-methods/{id}** - Remove payment method
-   [ ] Write API tests

#### 8.3 Invoices API

-   [ ] **GET /api/v1/invoices** - List invoices
-   [ ] **GET /api/v1/invoices/{id}** - Get invoice
-   [ ] **GET /api/v1/invoices/{id}/download** - Download invoice PDF
-   [ ] Write API tests

### Frontend Tasks

#### 8.4 Subscription Components

-   [ ] Create `SubscriptionPlans` component
-   [ ] Create `SubscriptionCard` component
-   [ ] Create `SubscribeForm` component
-   [ ] Create `SubscriptionStatus` component
-   [ ] Create `CancelSubscription` component
-   [ ] Integrate Stripe Elements
-   [ ] Handle payment processing
-   [ ] Add success/error handling

#### 8.5 Payment Methods Components

-   [ ] Create `PaymentMethodsList` component
-   [ ] Create `AddPaymentMethod` component
-   [ ] Create `RemovePaymentMethod` component
-   [ ] Integrate Stripe Elements

#### 8.6 Invoices Components

-   [ ] Create `InvoicesList` component
-   [ ] Create `InvoiceDetail` component
-   [ ] Add download functionality

## Phase 9: Recipe Interactions (Enhanced)

### Backend Tasks

#### 9.1 Recipe Comments API (Additional Features)

-   [ ] **POST /api/v1/recipes/{id}/comments** - Add comment
    -   [ ] Handle @mentions
    -   [ ] Handle responses to other comments
    -   [ ] Send notifications to mentioned users
    -   [ ] Send notifications to recipe author
    -   [ ] Validate comment content
-   [ ] **PUT /api/v1/comments/{id}** - Update comment
-   [ ] **DELETE /api/v1/comments/{id}** - Delete comment
    -   [ ] Check authorization (own comment or admin)
-   [ ] **POST /api/v1/comments/{id}/report** - Report comment
-   [ ] Write API tests

#### 9.2 Comment Notifications API

-   [ ] **GET /api/v1/notifications** - List user notifications
-   [ ] **PUT /api/v1/notifications/{id}/read** - Mark as read
-   [ ] **PUT /api/v1/notifications/read-all** - Mark all as read
-   [ ] Write API tests

### Frontend Tasks

#### 9.3 Enhanced Comments Component

-   [ ] Enhance `RecipeComments` component
    -   [ ] Real-time comment updates (WebSocket/polling)
    -   [ ] Comment editing
    -   [ ] Comment reporting
    -   [ ] Rich text editor for comments
    -   [ ] Image upload in comments (optional)
    -   [ ] Comment sorting (newest, oldest, most liked)
    -   [ ] Comment likes (optional feature)

#### 9.4 Notifications Component

-   [ ] Create `Notifications` component
    -   [ ] Display notification list
    -   [ ] Mark as read functionality
    -   [ ] Notification badges
    -   [ ] Real-time notification updates

## Phase 10: Advanced Features

### Backend Tasks

#### 10.1 Recipe Recommendations API

-   [ ] **GET /api/v1/recipes/recommendations** - Get recommendations
    -   [ ] Based on user history
    -   [ ] Based on similar recipes
    -   [ ] Based on calendar meals
-   [ ] Write API tests

#### 10.2 Recipe Favorites API

-   [ ] **POST /api/v1/recipes/{id}/favorite** - Add to favorites
-   [ ] **DELETE /api/v1/recipes/{id}/favorite** - Remove from favorites
-   [ ] **GET /api/v1/recipes/favorites** - List favorites
-   [ ] Write API tests

#### 10.3 Recipe Sharing API

-   [ ] **POST /api/v1/recipes/{id}/share** - Generate share link
-   [ ] **GET /api/v1/recipes/shared/{token}** - View shared recipe
-   [ ] Write API tests

### Frontend Tasks

#### 10.4 Recommendations Component

-   [ ] Create `RecipeRecommendations` component
-   [ ] Display recommended recipes
-   [ ] Update based on user activity

#### 10.5 Favorites Component

-   [ ] Create `FavoritesList` component
-   [ ] Add favorite button to recipe cards
-   [ ] Display favorites page

#### 10.6 Sharing Component

-   [ ] Create `ShareRecipe` component
-   [ ] Generate share link
-   [ ] Copy to clipboard
-   [ ] Social media sharing (optional)

## Testing & Quality Assurance

### Backend Testing

-   [ ] Unit tests for all services
-   [ ] API endpoint tests
-   [ ] Integration tests
-   [ ] Test nutrition calculation edge cases
-   [ ] Test sub-recipe handling
-   [ ] Test unit conversions
-   [ ] Test leftover calculations

### Frontend Testing

-   [ ] Component unit tests
-   [ ] Integration tests
-   [ ] E2E tests for critical flows
-   [ ] Test unit conversion logic
-   [ ] Test portion slider
-   [ ] Test calendar interactions
-   [ ] Test shopping list generation

### Performance

-   [ ] Optimize nutrition calculation (background jobs)
-   [ ] Implement API response caching
-   [ ] Optimize database queries
-   [ ] Implement pagination everywhere
-   [ ] Lazy load images
-   [ ] Code splitting for React

## Documentation

### API Documentation

-   [ ] Complete API endpoint documentation
-   [ ] Request/response examples
-   [ ] Error code documentation
-   [ ] Authentication documentation

### Frontend Documentation

-   [ ] Component documentation
-   [ ] State management documentation
-   [ ] Routing documentation
-   [ ] Service documentation

## Deployment

### Backend Deployment

-   [ ] Set up production environment
-   [ ] Configure database
-   [ ] Set up queue workers
-   [ ] Configure caching
-   [ ] Set up monitoring
-   [ ] Configure backups

### Frontend Deployment

-   [ ] Build optimization
-   [ ] Environment configuration
-   [ ] CDN setup
-   [ ] Error tracking
-   [ ] Analytics integration

## Notes

-   Each phase should be completed end-to-end before moving to the next
-   Backend APIs should be tested before frontend integration
-   Frontend components should be tested in isolation
-   Integration testing should follow component completion
-   Performance testing should be done after each phase
-   Documentation should be updated as features are completed

## Priority Order

1. **Phase 1** - Recipe List & Detail (Foundation)
    - Includes: Recipe listing, detail page with images, time, tips, instructions, ingredients, portion slider, nutrition info, comments, and like/dislike reactions
2. **Phase 7** - Authentication (Required for user features)
    - Note: Comments and reactions in Phase 1 require basic auth
3. **Phase 2** - Recipe Search (Enhances Phase 1)
4. **Phase 3** - Calendar System (Core feature)
5. **Phase 4** - Shopping List (Depends on Phase 3)
6. **Phase 5** - PDF Export (Depends on Phase 1, 3, 4)
7. **Phase 6** - Meal Plans (Depends on Phase 3)
8. **Phase 8** - Subscriptions (Monetization)
9. **Phase 9** - Recipe Interactions (Enhanced features - notifications, editing, etc.)
10. **Phase 10** - Advanced Features (Enhancement)
