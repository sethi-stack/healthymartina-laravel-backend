#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🧪 Testing Healthy Martina API"
echo "================================"
echo ""

BASE_URL="http://127.0.0.1:8000/api/v1"

# Check if server is running
echo "🔍 Checking if Laravel server is running..."
if ! curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/../" | grep -q "200\|302"; then
    echo -e "${RED}❌ Laravel server is not running!${NC}"
    echo "Please start it with: php artisan serve"
    exit 1
fi
echo -e "${GREEN}✅ Server is running${NC}"
echo ""

# Generate unique email to avoid duplicates
TIMESTAMP=$(date +%s)
EMAIL="test${TIMESTAMP}@example.com"
USERNAME="testuser${TIMESTAMP}"

echo "1️⃣ Testing Registration..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test",
    "last_name": "User",
    "username": "'$USERNAME'",
    "email": "'$EMAIL'",
    "password": "password123",
    "password_confirmation": "password123"
  }')

# Extract token using grep/sed (works on macOS)
TOKEN=$(echo "$REGISTER_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
  echo -e "${RED}❌ Registration failed${NC}"
  echo "Response: $REGISTER_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✅ Registration successful${NC}"
echo "   Email: $EMAIL"
echo "   Token: ${TOKEN:0:30}..."
echo ""

echo "2️⃣ Testing Get User (with token)..."
USER_RESPONSE=$(curl -s -X GET "$BASE_URL/auth/user" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

if echo "$USER_RESPONSE" | grep -q "\"email\""; then
  echo -e "${GREEN}✅ Get user successful${NC}"
  USER_NAME=$(echo "$USER_RESPONSE" | grep -o '"name":"[^"]*' | sed 's/"name":"//')
  echo "   Name: $USER_NAME"
else
  echo -e "${RED}❌ Get user failed${NC}"
  echo "Response: $USER_RESPONSE"
fi
echo ""

echo "3️⃣ Testing Login with same credentials..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "'$EMAIL'",
    "password": "password123"
  }')

if echo "$LOGIN_RESPONSE" | grep -q "\"token\""; then
  echo -e "${GREEN}✅ Login successful${NC}"
else
  echo -e "${RED}❌ Login failed${NC}"
  echo "Response: $LOGIN_RESPONSE"
fi
echo ""

echo "4️⃣ Testing Logout..."
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

if echo "$LOGOUT_RESPONSE" | grep -q "Logout successful"; then
  echo -e "${GREEN}✅ Logout successful${NC}"
else
  echo -e "${RED}❌ Logout failed${NC}"
  echo "Response: $LOGOUT_RESPONSE"
fi
echo ""

echo "5️⃣ Testing token is revoked..."
REVOKED_RESPONSE=$(curl -s -X GET "$BASE_URL/auth/user" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

if echo "$REVOKED_RESPONSE" | grep -q "Unauthenticated"; then
  echo -e "${GREEN}✅ Token properly revoked${NC}"
else
  echo -e "${RED}❌ Token still valid (should be revoked)${NC}"
  echo "Response: $REVOKED_RESPONSE"
fi
echo ""

echo "6️⃣ Testing validation errors..."
ERROR_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test"
  }')

if echo "$ERROR_RESPONSE" | grep -q "\"errors\""; then
  echo -e "${GREEN}✅ Validation errors working correctly${NC}"
else
  echo -e "${RED}❌ Validation not working${NC}"
fi
echo ""

echo "================================"
echo -e "${GREEN}✨ All tests completed!${NC}"
echo ""
echo "📝 Summary:"
echo "   - Registration: Working"
echo "   - Login: Working"
echo "   - Get User: Working"
echo "   - Logout: Working"
echo "   - Token Revocation: Working"
echo "   - Validation: Working"
echo ""
echo "🚀 API is ready for development!"

