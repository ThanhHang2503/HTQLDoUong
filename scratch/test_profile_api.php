<?php
/**
 * Test Profile API (Self-update)
 */
require_once __DIR__ . '/../config.php';
session_start();

// Mock login as user #2
$_SESSION['account_id'] = 2; 

function testProfileApi($payload) {
    // Manually include the API logic to test it without real HTTP request
    // or use curl if you want real request.
    // For simplicity, let's just use curl to test the actual file.
    
    $url = "http://localhost/HTQLDoUong/api/user/profile.php";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Cookie handling for session
    $cookie = "PHPSESSID=" . session_id();
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
}

echo "--- Test 1: Valid Phone ---\n";
testProfileApi(['update_profile' => true, 'phone' => '0911222333']);

echo "\n--- Test 2: Invalid Age (too young) ---\n";
testProfileApi(['update_profile' => true, 'birth_date' => '2015-01-01']);

echo "\n--- Test 3: Invalid Phone (contains letters) ---\n";
testProfileApi(['update_profile' => true, 'phone' => '090abc123']);
?>
