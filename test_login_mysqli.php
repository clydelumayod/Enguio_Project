<?php
// Test script for login and get_current_user actions in backend_mysqli.php

// Test 1: Try to get current user without login (should fail)
echo "=== Test 1: Get current user without login ===\n";
$test1 = file_get_contents('http://localhost/Enguio_Project/Api/backend_mysqli.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['action' => 'get_current_user'])
    ]
]));
echo "Response: " . $test1 . "\n\n";

// Test 2: Try to login (you'll need to provide valid credentials)
echo "=== Test 2: Login attempt ===\n";
echo "Note: You'll need to provide valid username and password\n";
echo "Please check the database for valid credentials and update this script\n\n";

// Test 3: Generate captcha
echo "=== Test 3: Generate captcha ===\n";
$test3 = file_get_contents('http://localhost/Enguio_Project/Api/backend_mysqli.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['action' => 'generate_captcha'])
    ]
]));
echo "Response: " . $test3 . "\n\n";

echo "=== Instructions ===\n";
echo "1. If Test 1 shows 'No active session found', that's expected\n";
echo "2. For Test 2, you need to provide valid credentials from your database\n";
echo "3. Test 3 should return a captcha question and answer\n";
echo "4. After successful login, Test 1 should work and return user data\n";
?> 