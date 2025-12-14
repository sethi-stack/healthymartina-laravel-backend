#!/bin/bash

# Test Lista de Ingredientes API Endpoints
# Run this after starting the Laravel server: php artisan serve

BASE_URL="http://localhost:8000/api/v1"
TOKEN=""  # Will be set after login

echo "======================================"
echo "Testing Lista de Ingredientes API"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Login to get token
echo -e "${YELLOW}1. Logging in to get auth token...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
  echo -e "${RED}❌ Login failed. Please create a test user first.${NC}"
  echo "Response: $LOGIN_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✅ Login successful${NC}"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Get first calendar ID
echo -e "${YELLOW}2. Getting calendar list...${NC}"
CALENDARS_RESPONSE=$(curl -s -X GET "${BASE_URL}/calendars" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

CALENDAR_ID=$(echo $CALENDARS_RESPONSE | grep -o '"id":[0-9]*' | head -1 | sed 's/"id"://')

if [ -z "$CALENDAR_ID" ]; then
  echo -e "${RED}❌ No calendars found. Creating one...${NC}"
  
  CREATE_CAL_RESPONSE=$(curl -s -X POST "${BASE_URL}/calendars" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
      "nombre": "Test Calendar for Lista",
      "semanas": 1
    }')
  
  CALENDAR_ID=$(echo $CREATE_CAL_RESPONSE | grep -o '"id":[0-9]*' | head -1 | sed 's/"id"://')
  
  if [ -z "$CALENDAR_ID" ]; then
    echo -e "${RED}❌ Failed to create calendar${NC}"
    echo "Response: $CREATE_CAL_RESPONSE"
    exit 1
  fi
fi

echo -e "${GREEN}✅ Using Calendar ID: $CALENDAR_ID${NC}"
echo ""

# 3. Test GET lista index
echo -e "${YELLOW}3. Testing GET /calendars/{id}/lista (Get all ingredients)...${NC}"
LISTA_RESPONSE=$(curl -s -X GET "${BASE_URL}/calendars/${CALENDAR_ID}/lista" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Response:"
echo "$LISTA_RESPONSE" | jq '.' 2>/dev/null || echo "$LISTA_RESPONSE"
echo ""

# 4. Test GET lista by category (assuming category ID 1 exists)
echo -e "${YELLOW}4. Testing GET /calendars/{id}/lista/categories/1 (Get by category)...${NC}"
CATEGORY_RESPONSE=$(curl -s -X GET "${BASE_URL}/calendars/${CALENDAR_ID}/lista/categories/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Response:"
echo "$CATEGORY_RESPONSE" | jq '.' 2>/dev/null || echo "$CATEGORY_RESPONSE"
echo ""

# 5. Test POST toggle taken
echo -e "${YELLOW}5. Testing POST /calendars/{id}/lista/toggle-taken (Mark as taken)...${NC}"
TOGGLE_RESPONSE=$(curl -s -X POST "${BASE_URL}/calendars/${CALENDAR_ID}/lista/toggle-taken" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "categoria_id": 1,
    "ingrediente_id": 1,
    "ingrediente_type": "receta"
  }')

echo "Response:"
echo "$TOGGLE_RESPONSE" | jq '.' 2>/dev/null || echo "$TOGGLE_RESPONSE"
echo ""

# 6. Test POST custom item
echo -e "${YELLOW}6. Testing POST /calendars/{id}/lista/items (Add custom ingredient)...${NC}"
CREATE_ITEM_RESPONSE=$(curl -s -X POST "${BASE_URL}/calendars/${CALENDAR_ID}/lista/items" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "cantidad": 2,
    "nombre": "Test Custom Ingredient",
    "categoria": 1
  }')

echo "Response:"
echo "$CREATE_ITEM_RESPONSE" | jq '.' 2>/dev/null || echo "$CREATE_ITEM_RESPONSE"
echo ""

# Get the created item ID
ITEM_ID=$(echo $CREATE_ITEM_RESPONSE | grep -o '"id":[0-9]*' | head -1 | sed 's/"id"://')

if [ ! -z "$ITEM_ID" ]; then
  # 7. Test PUT update custom item
  echo -e "${YELLOW}7. Testing PUT /calendars/{id}/lista/items/{itemId} (Update custom ingredient)...${NC}"
  UPDATE_ITEM_RESPONSE=$(curl -s -X PUT "${BASE_URL}/calendars/${CALENDAR_ID}/lista/items/${ITEM_ID}" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
      "cantidad": 3,
      "nombre": "Updated Custom Ingredient",
      "categoria": 1
    }')
  
  echo "Response:"
  echo "$UPDATE_ITEM_RESPONSE" | jq '.' 2>/dev/null || echo "$UPDATE_ITEM_RESPONSE"
  echo ""
  
  # 8. Test DELETE custom item
  echo -e "${YELLOW}8. Testing DELETE /calendars/{id}/lista/items/{itemId} (Delete custom ingredient)...${NC}"
  DELETE_ITEM_RESPONSE=$(curl -s -X DELETE "${BASE_URL}/calendars/${CALENDAR_ID}/lista/items/${ITEM_ID}" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
  
  echo "Response:"
  echo "$DELETE_ITEM_RESPONSE" | jq '.' 2>/dev/null || echo "$DELETE_ITEM_RESPONSE"
  echo ""
fi

# 9. Test PDF download (this will return binary, so we just check status)
echo -e "${YELLOW}9. Testing GET /calendars/{id}/lista/pdf (Download PDF)...${NC}"
PDF_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/calendars/${CALENDAR_ID}/lista/pdf?lista_ingredients={}" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/pdf")

if [ "$PDF_STATUS" = "200" ]; then
  echo -e "${GREEN}✅ PDF download endpoint returned 200 OK${NC}"
else
  echo -e "${RED}❌ PDF download failed with status: $PDF_STATUS${NC}"
fi
echo ""

# 10. Test email PDF (note: requires valid email config)
echo -e "${YELLOW}10. Testing POST /calendars/{id}/lista/pdf/email (Email PDF)...${NC}"
echo -e "${YELLOW}(Note: This may fail if email is not configured)${NC}"
EMAIL_RESPONSE=$(curl -s -X POST "${BASE_URL}/calendars/${CALENDAR_ID}/lista/pdf/email" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "recipient_email": "test@example.com",
    "lista_ingredients": "{}",
    "plantillas": ""
  }')

echo "Response:"
echo "$EMAIL_RESPONSE" | jq '.' 2>/dev/null || echo "$EMAIL_RESPONSE"
echo ""

echo -e "${GREEN}======================================"
echo "All tests completed!"
echo "======================================${NC}"


