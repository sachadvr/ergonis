#!/bin/bash

echo "=========================================="
echo "Testing CORS Configuration"
echo "=========================================="
echo ""

echo "1. Testing OPTIONS (preflight) request..."
echo "------------------------------------------"
curl -i 'http://localhost:8000/api/login' \
  -X OPTIONS \
  -H 'Access-Control-Request-Method: POST' \
  -H 'Access-Control-Request-Headers: content-type' \
  -H 'Origin: http://localhost:5173'
echo ""
echo ""

echo "2. Testing POST request with CORS headers..."
echo "------------------------------------------"
curl -i 'http://localhost:8000/api/login' \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Origin: http://localhost:5173' \
  -d '{"username":"test","password":"test"}'
echo ""
echo ""

echo "=========================================="
echo "Expected CORS Headers:"
echo "=========================================="
echo "Access-Control-Allow-Origin: http://localhost:5173"
echo "Access-Control-Allow-Methods: GET, OPTIONS, POST, PUT, PATCH, DELETE"
echo "Access-Control-Allow-Headers: content-type, authorization, x-requested-with, accept"
echo "Access-Control-Max-Age: 3600"
echo "=========================================="
