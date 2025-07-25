<?php
// Test file to check products API
include 'Api/index.php';

// Test the get_products API
$testData = [
    'action' => 'get_products'
];

$rawData = json_encode($testData);

// Simulate the API call
$_POST = $testData;

// Include the backend file to test
include 'Api/backend.php';
?> 