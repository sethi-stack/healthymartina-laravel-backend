# Advanced Recipe Filtering API Documentation

This document describes the advanced recipe filtering system that replaces the original `recetario()` method with comprehensive filtering capabilities.

## Overview

The advanced filtering system provides:
- **Complex nutrient filtering** (30+ nutrients with JSON queries)
- **Ingredient inclusion/exclusion** with subrecipe logic
- **Tag filtering** with AND logic
- **Cooking time and ingredient count** filtering
- **Calorie filtering** with min/max ranges
- **Filter bookmarks** for saving and loading filter configurations
- **Recipe view tracking** for analytics

---

## 🔥 Advanced Recipe Filtering

### POST `/api/v1/recipes/advanced-filter`

Apply complex filtering to recipes with all the advanced logic from the original `recetario()` method.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "tags": [1, 2, 3],
  "ingrediente_incluir": [10, 15, 20],
  "ingrediente_excluir": [5, 8],
  "num_ingredientes": {
    "min": 2,
    "max": 8
  },
  "num_tiempo": {
    "min": 10,
    "max": 45
  },
  "calorias": {
    "min": 100,
    "max": 500
  },
  "nutrientes": {
    "1005": {
      "min": 10,
      "max": 100
    },
    "1079": {
      "min": 5,
      "max": 25
    }
  },
  "page": 1,
  "per_page": 27
}
```

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "titulo": "Pasta with Vegetables",
      "slug": "pasta-with-vegetables",
      "tiempo": 30,
      "porciones": 4,
      "calories": 350,
      "imagen_principal": "https://example.com/image.jpg",
      "tags": [
        {
          "id": 1,
          "nombre": "Vegetarian"
        }
      ],
      "nutrient_info": {
        "1008": {
          "cantidad": 350,
          "unidad": "kcal"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 27,
    "total": 45,
    "last_page": 2,
    "from": 1,
    "to": 27,
    "has_more_pages": true
  },
  "filters_applied": {
    "tags": [1, 2, 3],
    "calorias": {
      "min": 100,
      "max": 500
    }
  },
  "total_filtered": 45
}
```

**Filter Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `tags` | array | Tag IDs (AND logic - all must be present) |
| `ingrediente_incluir` | array | Ingredient IDs that ALL must be present |
| `ingrediente_excluir` | array | Ingredient IDs to exclude |
| `num_ingredientes.min` | integer | Minimum number of ingredients (0-10) |
| `num_ingredientes.max` | integer | Maximum number of ingredients (0-10) |
| `num_tiempo.min` | integer | Minimum cooking time in minutes (0-60) |
| `num_tiempo.max` | integer | Maximum cooking time in minutes (0-60) |
| `calorias.min` | integer | Minimum calories (0-900) |
| `calorias.max` | integer | Maximum calories (0-900) |
| `nutrientes` | object | Nutrient filters by FDC ID |
| `nutrientes.{fdc_id}.min` | number | Minimum nutrient amount |
| `nutrientes.{fdc_id}.max` | number | Maximum nutrient amount |
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (1-100, default: 27) |

---

## 📊 Filter Metadata

### GET `/api/v1/recipes/filter-metadata`

Get all available filter options and default values.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "tags": [
    {
      "id": 1,
      "nombre": "Vegetarian"
    },
    {
      "id": 2,
      "nombre": "Gluten-Free"
    }
  ],
  "ingredientes": [
    {
      "id": 1,
      "nombre": "Tomato"
    },
    {
      "id": 2,
      "nombre": "Onion"
    }
  ],
  "nutrient_types": [
    {
      "id": 1,
      "nombre": "Macronutrients",
      "nutrientes": [
        {
          "id": 1,
          "nombre": "Protein",
          "fdc_id": 1003,
          "unidad_medida": "g",
          "factor": 1,
          "cien_porciento": 50
        }
      ]
    }
  ],
  "defaults": {
    "tags": [],
    "num_ingredientes": {
      "min": 0,
      "max": 10
    },
    "num_tiempo": {
      "min": 0,
      "max": 60
    },
    "calorias": {
      "min": 0,
      "max": 900
    },
    "nutrientes": {
      "1005": {
        "min": 0,
        "max": 130
      }
    }
  }
}
```

---

## 🔖 Filter Bookmarks

### GET `/api/v1/filters/bookmarks`

Get all user's saved filter bookmarks.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Low Carb Vegetarian",
      "filters": {
        "tags": [1],
        "calorias": {
          "min": 0,
          "max": 300
        },
        "nutrientes": {
          "1005": {
            "min": 0,
            "max": 50
          }
        }
      },
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "total": 1
}
```

### POST `/api/v1/filters/bookmarks`

Save a new filter bookmark.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "High Protein Low Carb",
  "filters": {
    "tags": [1, 2],
    "calorias": {
      "min": 200,
      "max": 400
    },
    "nutrientes": {
      "1003": {
        "min": 20,
        "max": 50
      },
      "1005": {
        "min": 0,
        "max": 30
      }
    }
  }
}
```

**Response:**
```json
{
  "message": "Filter bookmark saved successfully",
  "data": {
    "id": 2,
    "name": "High Protein Low Carb",
    "filters": {
      "tags": [1, 2],
      "calorias": {
        "min": 200,
        "max": 400
      }
    },
    "created_at": "2024-01-15T11:00:00.000000Z"
  }
}
```

### GET `/api/v1/filters/bookmarks/{id}`

Get a specific filter bookmark.

### PUT `/api/v1/filters/bookmarks/{id}`

Update a filter bookmark.

### DELETE `/api/v1/filters/bookmarks/{id}`

Delete a filter bookmark.

### DELETE `/api/v1/filters/bookmarks`

Delete multiple filter bookmarks.

**Request Body:**
```json
{
  "bookmark_ids": [1, 2, 3]
}
```

### POST `/api/v1/filters/bookmarks/load-and-filter`

Load multiple bookmarks, merge their filters, and apply to recipe search.

**Request Body:**
```json
{
  "bookmark_ids": [1, 2],
  "page": 1,
  "per_page": 27
}
```

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "titulo": "Recipe Title"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 15
  },
  "merged_filters": {
    "tags": [1, 2, 3],
    "calorias": {
      "min": 200,
      "max": 300
    }
  },
  "bookmarks_used": {
    "1": "Low Carb Vegetarian",
    "2": "High Protein"
  }
}
```

---

## 📈 Recipe View Tracking

### POST `/api/v1/recipes/{id}/track-view`

Track when a user views a recipe (for analytics).

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "View tracked",
  "recipe_id": 123,
  "recipe_title": "Pasta with Vegetables"
}
```

---

## 📅 Calendar Schedules

### GET `/api/v1/calendars/schedules`

Get calendar schedules as JSON (replaces `getCalendarScheduleJson`).

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "1": {
      "id": 1,
      "main_schedule": {
        "monday": {
          "breakfast": 123,
          "lunch": 456
        }
      },
      "sides_schedule": {
        "monday": {
          "breakfast": [789],
          "lunch": [101, 102]
        }
      }
    },
    "2": {
      "id": 2,
      "main_schedule": {},
      "sides_schedule": {}
    }
  }
}
```

---

## 🔧 Technical Implementation

### Complex Filtering Logic

The advanced filtering system implements the exact logic from the original `recetario()` method:

1. **Tag Filtering**: Uses `whereHas` with AND logic - all specified tags must be present
2. **Ingredient Inclusion**: Requires ALL specified ingredients to be present, with subrecipe parent/child logic
3. **Ingredient Exclusion**: Excludes recipes containing ANY of the specified ingredients, including in subrecipes
4. **Nutrient Filtering**: Uses JSON column queries with factor calculations for unit conversions
5. **Subrecipe Logic**: Implements `checkIfCombinedWithParentsIncludeAll()` for complex parent-child ingredient matching

### Performance Considerations

- Database queries are optimized with proper indexes
- Complex filtering is done in two phases: database queries + collection filtering
- Pagination is handled manually for filtered collections
- JSON column queries use native database JSON operators

### Filter Merging Logic

When loading multiple bookmarks:
- **Arrays** (tags, ingredients): Merged with `array_unique`
- **Ranges** (calories, time, nutrients): Most restrictive values are used
  - `min`: Takes the maximum of all minimums
  - `max`: Takes the minimum of all maximums

---

## 🚀 Migration Status

| Original Method | Status | New Endpoint |
|----------------|--------|--------------|
| `recetario()` | ✅ **MIGRATED** | `POST /api/v1/recipes/advanced-filter` |
| `checkIfCombinedWithParentsIncludeAll()` | ✅ **MIGRATED** | Included in filter service |
| `saveBookmark()` | ✅ **MIGRATED** | `POST /api/v1/filters/bookmarks` |
| `getBookmark()` | ✅ **MIGRATED** | `POST /api/v1/filters/bookmarks/load-and-filter` |
| `receta_vista()` (tracking) | ✅ **MIGRATED** | `POST /api/v1/recipes/{id}/track-view` |
| `getCalendarScheduleJson()` | ✅ **MIGRATED** | `GET /api/v1/calendars/schedules` |

**All critical missing features have been implemented!** 🎉

The advanced filtering system now provides 100% feature parity with the original `recetario()` method, plus modern API design with proper validation, error handling, and documentation.
