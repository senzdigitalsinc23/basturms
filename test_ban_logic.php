<?php

$url = 'http://localhost:8000/api/v1/students'; 

echo "=== Testing Ban/Jail Logic ===\n";
echo "Target: $url\n\n";

// 1. Initial connection check
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Initial Request Code: $code\n\n";

if ($code == 401) {
    echo "Note: 401 is expected if not authenticated, but RateLimiter runs before Auth.\n";
}

// 2. Trigger Ban
echo "2. Triggering violations to get banned...\n";
// We need to hit 429 multiple times. The current limit is ~60/min.
// We need to exceed it 5 times (maxViolations = 5).

$maxViolations = 5;
$rateLimit = 60; 

// We'll send batches. Each batch intentionally exceeds the limit to trigger ONE violation.
// Since the 'violation' counter increments when we hit 429, we just need to hammer it.

$mh = curl_multi_init();
$handles = [];
$totalSent = 0;
$banTriggered = false;

// Send enough requests to trigger multiple violations
// sending 400 requests should easily trigger 5 separate violations if simple logic holds,
// or at least get us deep into 429 land where violations increment.
$totalRequests = 400; 
$concurrency = 20;

echo "Sending $totalRequests requests...\n";

for ($i = 0; $i < $totalRequests; $i++) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_NOBODY, true); // Removed to enforce GET
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    curl_multi_add_handle($mh, $ch);
    $handles[$i] = $ch;

    if (count($handles) >= $concurrency || $i == $totalRequests - 1) {
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        foreach ($handles as $h) {
            $c = curl_getinfo($h, CURLINFO_HTTP_CODE);
            
            if ($c == 429) {
                // Check Retry-After header
                $headerSize = curl_getinfo($h, CURLINFO_HEADER_SIZE);
                // We can't easily parse headers here without keeping content, 
                // but if we see 429, it's working.
                
                // If we get a long Retry-After (e.g. > 3000), we know we are banned.
                // But getting header content from curl_multi is tricky without CURLOPT_HEADER functionality enabled right.
                // We enabled output header true, so it's in the output if we fetched it, but we did NOBODY.
                // Let's assume verifying 429 persistency is enough for now, 
                // or we do a single check after the batch.
            }
            curl_multi_remove_handle($mh, $h);
            curl_close($h);
        }
        $handles = [];
    }
}

curl_multi_close($mh);

echo "\n3. Checking Ban Status...\n";
// Now send one simple request. If banned, Retry-After should be huge (near 3600).
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Final Response Code: $code\n";

if ($code == 429) {
    if (preg_match('/Retry-After:\s*(\d+)/i', $response, $matches)) {
        $retryAfter = (int)$matches[1];
        echo "Retry-After: $retryAfter seconds\n";
        
        if ($retryAfter > 600) {
            echo "SUCCESS: Ban confirmed (Retry-After > 600s).\n";
        } else {
            echo "FAILURE: 429 received but Retry-After seems normal ($retryAfter). Violations count might not have reached threshold.\n";
        }
    } else {
        echo "FAILURE: 429 received but no Retry-After header found.\n";
    }
} else {
    echo "FAILURE: Expected 429 (Banned), got $code. Ban logic failed.\n";
}
