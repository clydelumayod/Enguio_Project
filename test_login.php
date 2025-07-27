<?php
// Test script to verify login functionality
require_once 'Api/backend.php';

// Test data from the database
$testUsers = [
    [
        'username' => 'ezay',
        'password' => '1234',
        'expected_role' => 'inventory'
    ],
    [
        'username' => 'bayot',
        'password' => '1234',
        'expected_role' => 'cashier'
    ],
    [
        'username' => 'bayot2',
        'password' => '1234',
        'expected_role' => 'pharmacist'
    ]
];

echo "Testing Login Functionality\n";
echo "==========================\n\n";

foreach ($testUsers as $user) {
    echo "Testing user: {$user['username']}\n";
    
    // Simulate the login request
    $data = [
        'action' => 'login',
        'username' => $user['username'],
        'password' => $user['password'],
        'captcha' => '5',
        'captchaAnswer' => '5'
    ];
    
    // Store the data globally for the backend to access
    $GLOBALS['data'] = $data;
    
    // Capture the output
    ob_start();
    
    // Include the backend logic
    try {
        // Simulate the login action
        $username = $data['username'];
        $password = $data['password'];
        $captcha = $data['captcha'];
        $captchaAnswer = $data['captchaAnswer'];

        // Validate inputs
        if (empty($username) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Username and password are required"]);
            exit;
        }

        // Verify captcha
        if (empty($captcha) || empty($captchaAnswer) || $captcha !== $captchaAnswer) {
            echo json_encode(["success" => false, "message" => "Invalid captcha"]);
            exit;
        }

        // Check if user exists and is active
        $stmt = $conn->prepare("
            SELECT e.emp_id, e.username, e.password, e.status, e.Fname, e.Lname, r.role 
            FROM tbl_employee e 
            JOIN tbl_role r ON e.role_id = r.role_id 
            WHERE e.username = :username AND e.status = 'Active'
        ");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check password - handle both hashed and plain text passwords
        $passwordValid = false;
        if ($user_data) {
            // First try to verify as hashed password
            if (password_verify($password, $user_data['password'])) {
                $passwordValid = true;
            } 
            // If that fails, check if it's a plain text password (for backward compatibility)
            elseif ($password === $user_data['password']) {
                $passwordValid = true;
            }
        }

        if ($user_data && $passwordValid) {
            $result = [
                "success" => true,
                "message" => "Login successful",
                "role" => $user_data['role'],
                "user_id" => $user_data['emp_id'],
                "full_name" => $user_data['Fname'] . ' ' . $user_data['Lname']
            ];
        } else {
            $result = ["success" => false, "message" => "Invalid username or password"];
        }
        
    } catch (Exception $e) {
        $result = ["success" => false, "message" => "An error occurred: " . $e->getMessage()];
    }
    
    $output = ob_get_clean();
    
    // Parse the result
    $response = json_decode(json_encode($result), true);
    
    if ($response['success']) {
        echo "✅ SUCCESS: Login successful\n";
        echo "   Role: {$response['role']}\n";
        echo "   Expected Role: {$user['expected_role']}\n";
        echo "   Full Name: {$response['full_name']}\n";
        
        if ($response['role'] === $user['expected_role']) {
            echo "   ✅ Role matches expected\n";
        } else {
            echo "   ❌ Role mismatch!\n";
        }
    } else {
        echo "❌ FAILED: {$response['message']}\n";
    }
    
    echo "\n";
}

echo "Test completed!\n";
?> 