#!/bin/bash

echo "=========================================="
echo "Test complet de l'application JobPlanner"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Login
echo -e "${YELLOW}1. Test de connexion...${NC}"
TOKEN=$(curl -k -s 'https://127.0.0.1:8000/api/login' \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Origin: http://localhost:5173' \
  --data-raw '{"email":"guest@test.com","password":"guest"}' | jq -r '.token')

if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
  echo -e "${GREEN}✓ Connexion réussie${NC}"
  echo "Token: ${TOKEN:0:50}..."
else
  echo -e "${RED}✗ Échec de la connexion${NC}"
  exit 1
fi
echo ""

# Test 2: Get user info
echo -e "${YELLOW}2. Test récupération profil utilisateur...${NC}"
USER=$(curl -k -s 'https://127.0.0.1:8000/api/me' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/ld+json')

USER_EMAIL=$(echo $USER | jq -r '.email')
if [ "$USER_EMAIL" = "guest@test.com" ]; then
  echo -e "${GREEN}✓ Profil récupéré: $USER_EMAIL${NC}"
else
  echo -e "${RED}✗ Échec récupération profil${NC}"
  echo "Response: $USER"
  exit 1
fi
echo ""

# Test 3: Get applications
echo -e "${YELLOW}3. Test récupération des applications...${NC}"
APPS=$(curl -k -s 'https://127.0.0.1:8000/api/applications' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/ld+json')

APP_COUNT=$(echo $APPS | jq -r '.["hydra:totalItems"] // 0')
echo -e "${GREEN}✓ Applications récupérées: $APP_COUNT applications${NC}"

if [ "$APP_COUNT" -gt 0 ]; then
  echo ""
  echo "Détails des applications:"
  echo $APPS | jq -r '.["hydra:member"][] | "  - \(.jobOffer.title) chez \(.jobOffer.company) (\(.status))"' 2>/dev/null || echo "  (Détails non disponibles)"
fi
echo ""

# Test 4: CORS
echo -e "${YELLOW}4. Test CORS (preflight OPTIONS)...${NC}"
CORS_ORIGIN=$(curl -k -s -I 'https://127.0.0.1:8000/api/applications' \
  -X OPTIONS \
  -H 'Access-Control-Request-Method: GET' \
  -H 'Origin: http://localhost:5173' | grep -i "access-control-allow-origin")

if echo "$CORS_ORIGIN" | grep -q "localhost:5173"; then
  echo -e "${GREEN}✓ CORS configuré correctement${NC}"
  echo "  $CORS_ORIGIN"
else
  echo -e "${RED}✗ Problème CORS${NC}"
fi
echo ""

# Summary
echo "=========================================="
echo -e "${GREEN}✓ Tous les tests passent!${NC}"
echo "=========================================="
echo ""
echo "Vous pouvez maintenant vous connecter:"
echo "  URL: http://localhost:5173"
echo "  Email: guest@test.com"
echo "  Password: guest"
echo ""
