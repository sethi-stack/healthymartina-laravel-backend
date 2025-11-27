#!/bin/bash

echo "🧪 Testing Register Endpoint"
echo "=============================="
echo ""

# Check if server is running
echo "1. Checking if server is running..."
if ! curl -s http://127.0.0.1:8000/up | grep -q "OK"; then
    echo "❌ Server not running!"
    echo "Please run: php artisan serve"
    exit 1
fi
echo "✅ Server is running"
echo ""

# Test register endpoint
echo "2. Testing POST /api/v1/auth/register"
echo "   URL: http://127.0.0.1:8000/api/v1/auth/register"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST http://127.0.0.1:8000/api/v1/auth/register \
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

# Split response and status code
HTTP_BODY=$(echo "$RESPONSE" | sed '$d')
HTTP_STATUS=$(echo "$RESPONSE" | tail -n1)

echo "Status Code: $HTTP_STATUS"
echo ""
echo "Response:"
echo "$HTTP_BODY" | python3 -m json.tool 2>/dev/null || echo "$HTTP_BODY"
echo ""

if [ "$HTTP_STATUS" = "201" ]; then
    echo "✅ Registration successful!"
elif [ "$HTTP_STATUS" = "422" ]; then
    echo "⚠️  Validation error (expected if user exists)"
elif [ "$HTTP_STATUS" = "500" ]; then
    echo "❌ Server error - check laravel.log"
    echo "   Run: tail storage/logs/laravel.log"
else
    echo "❌ Unexpected response"
    if echo "$HTTP_BODY" | grep -q "<!DOCTYPE html>"; then
        echo "   Got HTML response instead of JSON!"
        echo "   This usually means:"
        echo "   - Wrong URL (check you're using /api/v1/auth/register)"
        echo "   - Missing Accept: application/json header"
    fi
fi

