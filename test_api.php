<?php
<<<<<<< HEAD
// Simple test to check backend response
$url = 'http://localhost/enguio/backend.php';
$data = json_encode(['action' => 'get_suppliers']);
=======
// Test the purchase order API directly
echo "Testing Purchase Order API directly...\n";
>>>>>>> 687011100542853d6bad6ac9c30c4dfff5304d80

// Simulate the API call by setting up the environment
$_GET['action'] = 'suppliers';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Include the API file
include 'purchase_order_api.php';
?> 