#!/bin/bash

# Test script for Advanced Recipe Filtering API
# Make sure to update the BASE_URL and TOKEN

BASE_URL="http://localhost:8000/api/v1"
TOKEN="your-auth-token-here"

echo "🔥 Testing Advanced Recipe Filtering API"
echo "========================================"

# Test 1: Get filter metadata
echo ""
echo "📊 Test 1: Get filter metadata"
curl -X GET "$BASE_URL/recipes/filter-metadata" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'

# Test 2: Advanced filter with basic parameters
echo ""
echo "🔍 Test 2: Advanced filtering - Basic"
curl -X POST "$BASE_URL/recipes/advanced-filter" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "calorias": {
      "min": 100,
      "max": 500
    },
    "num_tiempo": {
      "min": 10,
      "max": 30
    },
    "page": 1,
    "per_page": 5
  }' \
  -s | jq '.'

# Test 3: Advanced filter with nutrients
echo ""
echo "🧬 Test 3: Advanced filtering - With nutrients"
curl -X POST "$BASE_URL/recipes/advanced-filter" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "calorias": {
      "min": 200,
      "max": 400
    },
    "nutrientes": {
      "1003": {
        "min": 10,
        "max": 50
      }
    },
    "per_page": 3
  }' \
  -s | jq '.'

# Test 4: Create filter bookmark
echo ""
echo "🔖 Test 4: Create filter bookmark"
curl -X POST "$BASE_URL/filters/bookmarks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Low Calorie Filter",
    "filters": {
      "calorias": {
        "min": 100,
        "max": 300
      },
      "num_tiempo": {
        "min": 5,
        "max": 20
      }
    }
  }' \
  -s | jq '.'

# Test 5: Get all bookmarks
echo ""
echo "📋 Test 5: Get all filter bookmarks"
curl -X GET "$BASE_URL/filters/bookmarks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'

# Test 6: Get calendar schedules
echo ""
echo "📅 Test 6: Get calendar schedules"
curl -X GET "$BASE_URL/calendars/schedules" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'

# Test 7: Track recipe view
echo ""
echo "👁️ Test 7: Track recipe view (ID: 1)"
curl -X POST "$BASE_URL/recipes/1/track-view" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'

echo ""
echo "✅ All tests completed!"
echo ""
echo "📝 Notes:"
echo "- Update TOKEN variable with a valid auth token"
echo "- Update BASE_URL if using different host/port"
echo "- Some tests may fail if no data exists in database"
echo "- Use 'php artisan serve' to start the development server"
