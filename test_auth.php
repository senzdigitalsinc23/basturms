<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     AGH Validation System - Auth Test                 ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$baseUrl = 'http://localhost:8000/api/v1';
$apiKey = 'devKey123';

// Test 1: Login
echo "🔐 Test 1: Login...\n";
$loginData = [
    'email' => 'admin@validation.com',
    'password' => 'admin123'
];

$ch = curl_init("$baseUrl/validation/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "  ✗ Login failed with HTTP $httpCode\n";
    echo "  Response: $response\n";
    exit(1);
}

$loginResponse = json_decode($response, true);
if (!isset($loginResponse['token'])) {
    echo "  ✗ No token in response\n";
    echo "  Response: $response\n";
    exit(1);
}

$token = $loginResponse['token'];
echo "  ✓ Login successful\n";
echo "  Token: " . substr($token, 0, 30) . "...\n";
echo "  User: {$loginResponse['user']['name']} ({$loginResponse['user']['role']})\n\n";

// Test 2: Get current user
echo "👤 Test 2: Get Current User...\n";
$ch = curl_init("$baseUrl/validation/auth/me");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "X-API-Key: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "  ✗ Get user failed with HTTP $httpCode\n";
    echo "  Response: $response\n";
    exit(1);
}

$userResponse = json_decode($response, true);
echo "  ✓ User retrieved successfully\n";
echo "  Name: {$userResponse['user']['name']}\n";
echo "  Email: {$userResponse['user']['email']}\n\n";

// Test 3: Get staff list
echo "👥 Test 3: Get Staff List...\n";
$ch = curl_init("$baseUrl/validation/staff");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "X-API-Key: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "  ✗ Get staff failed with HTTP $httpCode\n";
    echo "  Response: $response\n";
    exit(1);
}

$staffResponse = json_decode($response, true);
echo "  ✓ Staff list retrieved successfully\n";
echo "  Total staff: " . count($staffResponse['staff']) . "\n";
if (count($staffResponse['staff']) > 0) {
    echo "  Sample: {$staffResponse['staff'][0]['name']} ({$staffResponse['staff'][0]['role']})\n";
}
echo "\n";

// Test 4: Get units
echo "🏢 Test 4: Get Units...\n";
$ch = curl_init("$baseUrl/validation/units");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "X-API-Key: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "  ✗ Get units failed with HTTP $httpCode\n";
    echo "  Response: $response\n";
    exit(1);
}

$unitsResponse = json_decode($response, true);
echo "  ✓ Units retrieved successfully\n";
echo "  Total units: " . count($unitsResponse['units']) . "\n\n";

// Test 5: Validate staff
echo "✅ Test 5: Validate Staff...\n";
$validationData = [
    'staffIds' => [1, 2],
    'month' => 'March',
    'year' => 2026
];

$ch = curl_init("$baseUrl/validations");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($validationData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token",
    "X-API-Key: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "  ✗ Validation failed with HTTP $httpCode\n";
    echo "  Response: $response\n";
    exit(1);
}

echo "  ✓ Staff validated successfully\n\n";

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  ✓ All authentication tests passed!                   ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
