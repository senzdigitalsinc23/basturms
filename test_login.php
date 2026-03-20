<?php

// Simple test script to verify login endpoint works

$url = 'http://localhost:8000/api/v1/validation/auth/login';
$data = [
    'email' => 'admin@validation.com',
    'password' => 'admin123'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: devKey123'
]);

echo "Testing login endpoint...\n";
echo "URL: $url\n";
echo "Email: admin@validation.com\n";
echo "Password: admin123\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit(1);
}

curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

$responseData = json_decode($response, true);
if ($responseData && isset($responseData['success']) && $responseData['success']) {
    echo "\n✅ Login test PASSED!\n";
    echo "Token: " . substr($responseData['token'], 0, 50) . "...\n";
} else {
    echo "\n❌ Login test FAILED!\n";
    if (isset($responseData['message'])) {
        echo "Error: " . $responseData['message'] . "\n";
    }
}
