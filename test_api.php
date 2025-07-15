<?php
// Test the purchase order API directly
echo "Testing Purchase Order API directly...\n";

// Simulate the API call by setting up the environment
$_GET['action'] = 'suppliers';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Include the API file
include 'purchase_order_api.php';
?> 