<?php
// Start output buffering to prevent unwanted output
ob_start();

session_start();

// CORS and content-type headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log errors to a file for debugging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Database connection using PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection error: " . $e->getMessage()
    ]);
    exit;
}

// Clear any output that might have been generated
ob_clean();

// Read and decode incoming JSON request
$rawData = file_get_contents("php://input");
error_log("Raw input: " . $rawData);

$data = json_decode($rawData, true);

// Check if JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input: " . json_last_error_msg(),
        "raw" => $rawData
    ]);
    exit;
}

// Check if 'action' is set
if (!isset($data['action'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing action"
    ]);
    exit;
}

// Action handler
$action = $data['action'];
error_log("Processing action: " . $action);

try {
    switch ($action) {
    case 'add_employee':
        try {
            // Extract and sanitize input data
            $fname = isset($data['fname'])&& !empty($data['fname']) ? trim($data['fname']) : '';
            $mname = isset($data['mname']) && !empty($data['mname'])? trim($data['mname']) : '';
            $lname = isset($data['lname']) && !empty($data['lname'])? trim($data['lname']) : '';
            $email = isset($data['email']) ? trim($data['email']) : '';
            $contact = isset($data['contact_num']) ? trim($data['contact_num']) : '';
            $role_id = isset($data['role_id']) ? trim($data['role_id']) : '';
            $shift_id = isset($data['shift_id']) && $data['shift_id'] !== null && $data['shift_id'] !== '' ? (int)$data['shift_id'] : null;
            $username = isset($data['username']) ? trim($data['username']) : '';
            $password = isset($data['password']) ? trim($data['password']) : '';
            $age = isset($data['age']) ? trim($data['age']) : '';
            $address = isset($data['address']) ? trim($data['address']) : '';
            $status = isset($data['status']) ? trim($data['status']) : 'Active';
            $gender = isset($data['gender']) ? trim($data['gender']) : '';
            $birthdate = isset($data['birthdate']) ? trim($data['birthdate']) : '';

            // Only require shift_id for cashier (3) and pharmacist (2)
            if (($role_id == 2 || $role_id == 3) && empty($shift_id)) {
                echo json_encode(["success" => false, "message" => "Shift is required."]);
                exit;
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare the SQL statement
            $stmt = $conn->prepare("
                INSERT INTO tbl_employee (
                    Fname, Mname, Lname, email, contact_num, role_id, shift_id,
                    username, password, age, address, status,gender,birthdate
                ) VALUES (
                    :fname, :mname, :lname, :email, :contact_num, :role_id, :shift_id,
                    :username, :password, :age, :address, :status, :gender, :birthdate
                )
            ");

            // Bind parameters
            $stmt->bindParam(":fname", $fname, PDO::PARAM_STR);
            $stmt->bindParam(":mname", $mname, PDO::PARAM_STR);
            $stmt->bindParam(":lname", $lname, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":contact_num", $contact, PDO::PARAM_STR);
            $stmt->bindParam(":role_id", $role_id, PDO::PARAM_INT);
            if ($shift_id !== null) {
                $stmt->bindValue(":shift_id", $shift_id, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":shift_id", null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(":age", $age, PDO::PARAM_INT);
            $stmt->bindParam(":address", $address, PDO::PARAM_STR);
            $stmt->bindParam(":status", $status, PDO::PARAM_STR);
            $stmt->bindParam(":gender", $gender, PDO::PARAM_STR);
            $stmt->bindParam(":birthdate", $birthdate, PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Employee added successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add employee"]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;

    case 'login':
        try {
            $username = isset($data['username']) ? trim($data['username']) : '';
            $password = isset($data['password']) ? trim($data['password']) : '';
            $captcha = isset($data['captcha']) ? trim($data['captcha']) : '';
            $captchaAnswer = isset($data['captchaAnswer']) ? trim($data['captchaAnswer']) : '';

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

            // Check if user exists (regardless of status)
            $stmt = $conn->prepare("
                SELECT e.emp_id, e.username, e.password, e.status, e.Fname, e.Lname, e.role_id, e.shift_id, r.role 
                FROM tbl_employee e 
                JOIN tbl_role r ON e.role_id = r.role_id 
                WHERE e.username = :username
            ");
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If user exists but is inactive, return a specific message
            if ($user && strcasecmp($user['status'] ?? '', 'Active') !== 0) {
                echo json_encode(["success" => false, "message" => "User is inactive. Please contact the administrator."]);
                break;
            }

            // Check password - handle both hashed and plain text passwords
            $passwordValid = false;
            if ($user) {
                // First try to verify as hashed password
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } 
                // If that fails, check if it's a plain text password (for backward compatibility)
                elseif ($password === $user['password']) {
                    $passwordValid = true;
                }
            }

            if ($user && $passwordValid) {
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['emp_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['Fname'] . ' ' . $user['Lname'];

                // Log login activity to tbl_login
                try {
                    $loginStmt = $conn->prepare("
                        INSERT INTO tbl_login (emp_id, role_id, username, login_time, login_date, ip_address) 
                        VALUES (:emp_id, :role_id, :username, NOW(), CURDATE(), :ip_address)
                    ");
                    
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    $loginStmt->bindParam(':emp_id', $user['emp_id'], PDO::PARAM_INT);
                    $loginStmt->bindParam(':role_id', $user['role_id'], PDO::PARAM_INT);
                    $loginStmt->bindParam(':username', $user['username'], PDO::PARAM_STR);
                    $loginStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
                    
                    $loginStmt->execute();
                    
                    // Store login_id in session for logout tracking
                    $_SESSION['login_id'] = $conn->lastInsertId();
                    $login_id_inserted = $_SESSION['login_id'];
                    
                } catch (Exception $loginLogError) {
                    error_log("Login logging error: " . $loginLogError->getMessage());
                    // Continue with login even if logging fails
                }

                // Terminal/location handling: prefer explicit route, else infer from role
                $route = strtolower(trim($data['route'] ?? ''));
                $location_label = null;
                $terminal_name = null;
                if ($route !== '') {
                    if (strpos($route, 'pos_convenience') !== false) { $location_label = 'convenience'; $terminal_name = 'Convenience POS'; }
                    elseif (strpos($route, 'pos_pharmacy') !== false) { $location_label = 'pharmacy'; $terminal_name = 'Pharmacy POS'; }
                    elseif (strpos($route, 'inventory_con') !== false) { $location_label = 'inventory'; $terminal_name = 'Inventory Terminal'; }
                    elseif (strpos($route, 'admin') !== false) { $location_label = 'admin'; $terminal_name = 'Admin Terminal'; }
                }
                if (!$terminal_name) {
                    $roleLower = strtolower((string)($user['role'] ?? ''));
                    if (strpos($roleLower, 'cashier') !== false || strpos($roleLower, 'pos') !== false) { $location_label = 'convenience'; $terminal_name = 'Convenience POS'; }
                    elseif (strpos($roleLower, 'pharmacist') !== false) { $location_label = 'pharmacy'; $terminal_name = 'Pharmacy POS'; }
                    elseif (strpos($roleLower, 'inventory') !== false) { $location_label = 'inventory'; $terminal_name = 'Inventory Terminal'; }
                    else { $location_label = 'admin'; $terminal_name = 'Admin Terminal'; }
                }

                $terminal_id = null;
                if ($terminal_name) {
                    try {
                        // Ensure terminal exists and update shift
                        $termSel = $conn->prepare("SELECT terminal_id, shift_id FROM tbl_pos_terminal WHERE terminal_name = :name LIMIT 1");
                        $termSel->execute([':name' => $terminal_name]);
                        $term = $termSel->fetch(PDO::FETCH_ASSOC);
                        $user_shift_id = $user['shift_id'] ?? null;
                        if ($term) {
                            $terminal_id = (int)$term['terminal_id'];
                            if ($user_shift_id && (int)$term['shift_id'] !== (int)$user_shift_id) {
                                $upd = $conn->prepare("UPDATE tbl_pos_terminal SET shift_id = :shift WHERE terminal_id = :tid");
                                $upd->execute([':shift' => $user_shift_id, ':tid' => $terminal_id]);
                            }
                        } else {
                            $ins = $conn->prepare("INSERT INTO tbl_pos_terminal (terminal_name, shift_id) VALUES (:name, :shift)");
                            $ins->execute([':name' => $terminal_name, ':shift' => $user_shift_id]);
                            $terminal_id = (int)$conn->lastInsertId();
                        }

                        // Optionally annotate login row with location/terminal if columns exist
                        if (!empty($login_id_inserted)) {
                            try {
                                $tryUpd = $conn->prepare("UPDATE tbl_login SET location = :loc WHERE login_id = :lid");
                                $tryUpd->execute([':loc' => $location_label, ':lid' => $login_id_inserted]);
                            } catch (Exception $ignore) {}
                            try {
                                $tryUpd2 = $conn->prepare("UPDATE tbl_login SET terminal_id = :tid WHERE login_id = :lid");
                                $tryUpd2->execute([':tid' => $terminal_id, ':lid' => $login_id_inserted]);
                            } catch (Exception $ignore) {}
                            try {
                                $tryUpd3 = $conn->prepare("UPDATE tbl_login SET shift_id = :sid WHERE login_id = :lid");
                                $tryUpd3->execute([':sid' => $user_shift_id, ':lid' => $login_id_inserted]);
                            } catch (Exception $ignore) {}
                        }
                    } catch (Exception $terminalError) {
                        error_log('Terminal handling error: ' . $terminalError->getMessage());
                    }
                }

                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "role" => $user['role'],
                    "user_id" => $user['emp_id'],
                    "full_name" => $user['Fname'] . ' ' . $user['Lname'],
                    "terminal_id" => $terminal_id,
                    "terminal_name" => $terminal_name,
                    "location" => $location_label,
                    "shift_id" => $user['shift_id'] ?? null
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid username or password"]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;

    case 'logout':
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
            }

            $empId = $_SESSION['user_id'] ?? null;
            $loginId = $_SESSION['login_id'] ?? null;
            // Fallback to client-provided emp_id when session cookies aren't present (CORS, different port, etc.)
            if (!$empId && isset($data['emp_id'])) {
                $empId = intval($data['emp_id']);
            }

            try {
                $updated = 0;
                if ($loginId && $empId) {
                    // Update the known session login row
                    $logoutStmt = $conn->prepare("UPDATE tbl_login SET logout_time = CURTIME(), logout_date = CURDATE() WHERE login_id = :login_id AND emp_id = :emp_id");
                    $logoutStmt->bindParam(':login_id', $loginId, PDO::PARAM_INT);
                    $logoutStmt->bindParam(':emp_id', $empId, PDO::PARAM_INT);
                    $logoutStmt->execute();
                    $updated = $logoutStmt->rowCount();
                    error_log('[logout] update by session login_id='.$loginId.' emp_id='.$empId.' affected='.$updated);
                }
                if ($updated === 0 && $empId) {
                    // Fallback: find the most recent OPEN login record for this employee
                    $findStmt = $conn->prepare("SELECT login_id FROM tbl_login WHERE emp_id = :emp_id AND (logout_time IS NULL OR logout_time = '00:00:00') ORDER BY login_id DESC LIMIT 1");
                    $findStmt->bindParam(':emp_id', $empId, PDO::PARAM_INT);
                    $findStmt->execute();
                    $row = $findStmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && isset($row['login_id'])) {
                        $fallbackLogout = $conn->prepare("UPDATE tbl_login SET logout_time = CURTIME(), logout_date = CURDATE() WHERE login_id = :login_id");
                        $fallbackLogout->bindParam(':login_id', $row['login_id'], PDO::PARAM_INT);
                        $fallbackLogout->execute();
                        $updated = $fallbackLogout->rowCount();
                        error_log('[logout] update by open row login_id='.$row['login_id'].' affected='.$updated);
                    }
                }
                if ($updated === 0 && $empId) {
                    // Final fallback: update the most recent row for this employee
                    $findAny = $conn->prepare("SELECT login_id FROM tbl_login WHERE emp_id = :emp_id ORDER BY login_id DESC LIMIT 1");
                    $findAny->bindParam(':emp_id', $empId, PDO::PARAM_INT);
                    $findAny->execute();
                    $last = $findAny->fetch(PDO::FETCH_ASSOC);
                    if ($last && isset($last['login_id'])) {
                        $updAny = $conn->prepare("UPDATE tbl_login SET logout_time = CURTIME(), logout_date = CURDATE() WHERE login_id = :login_id");
                        $updAny->bindParam(':login_id', $last['login_id'], PDO::PARAM_INT);
                        $updAny->execute();
                        error_log('[logout] forced update latest login_id='.$last['login_id'].' affected='.$updAny->rowCount());
                    }
                }
                } catch (Exception $logoutLogError) {
                    error_log("Logout logging error: " . $logoutLogError->getMessage());
            }

            // Clear session only after writing logout record
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred during logout: ' . $e->getMessage()]);
        }
        break;

    case 'register_terminal_route':
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            $empId = $_SESSION['user_id'] ?? ($data['emp_id'] ?? null);
            $route = strtolower(trim($data['route'] ?? ''));
            if (!$empId || $route === '') {
                echo json_encode(['success' => false, 'message' => 'Missing emp_id or route']);
                break;
            }

            // Get employee shift
            $emp = null;
            try {
                $st = $conn->prepare("SELECT shift_id, role_id FROM tbl_employee WHERE emp_id = :id LIMIT 1");
                $st->execute([':id' => $empId]);
                $emp = $st->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
            $user_shift_id = $emp['shift_id'] ?? null;

            // Map route → terminal/location
            $location_label = 'admin';
            $terminal_name = 'Admin Terminal';
            if (strpos($route, 'pos_convenience') !== false) { $location_label = 'convenience'; $terminal_name = 'Convenience POS'; }
            elseif (strpos($route, 'pos_pharmacy') !== false) { $location_label = 'pharmacy'; $terminal_name = 'Pharmacy POS'; }
            elseif (strpos($route, 'inventory_con') !== false) { $location_label = 'inventory'; $terminal_name = 'Inventory Terminal'; }
            elseif (strpos($route, 'admin') !== false) { $location_label = 'admin'; $terminal_name = 'Admin Terminal'; }

            // Ensure terminal exists and update shift
            $termSel = $conn->prepare("SELECT terminal_id, shift_id FROM tbl_pos_terminal WHERE terminal_name = :name LIMIT 1");
            $termSel->execute([':name' => $terminal_name]);
            $term = $termSel->fetch(PDO::FETCH_ASSOC);
            if ($term) {
                $terminal_id = (int)$term['terminal_id'];
                if ($user_shift_id && (int)$term['shift_id'] !== (int)$user_shift_id) {
                    $upd = $conn->prepare("UPDATE tbl_pos_terminal SET shift_id = :shift WHERE terminal_id = :tid");
                    $upd->execute([':shift' => $user_shift_id, ':tid' => $terminal_id]);
                }
            } else {
                $ins = $conn->prepare("INSERT INTO tbl_pos_terminal (terminal_name, shift_id) VALUES (:name, :shift)");
                $ins->execute([':name' => $terminal_name, ':shift' => $user_shift_id]);
                $terminal_id = (int)$conn->lastInsertId();
            }

            // Annotate most recent open login row
            try {
                $findStmt = $conn->prepare("SELECT login_id FROM tbl_login WHERE emp_id = :emp AND (logout_time IS NULL OR logout_time = '00:00:00') ORDER BY login_id DESC LIMIT 1");
                $findStmt->execute([':emp' => $empId]);
                $row = $findStmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['login_id'])) {
                    try { $upd1 = $conn->prepare("UPDATE tbl_login SET terminal_id = :tid WHERE login_id = :lid"); $upd1->execute([':tid' => $terminal_id, ':lid' => $row['login_id']]); } catch (Exception $e) {}
                    try { $upd2 = $conn->prepare("UPDATE tbl_login SET location = :loc WHERE login_id = :lid"); $upd2->execute([':loc' => $location_label, ':lid' => $row['login_id']]); } catch (Exception $e) {}
                    try { if ($user_shift_id) { $upd3 = $conn->prepare("UPDATE tbl_login SET shift_id = :sid WHERE login_id = :lid"); $upd3->execute([':sid' => $user_shift_id, ':lid' => $row['login_id']]); } } catch (Exception $e) {}
                }
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'data' => [
                'terminal_id' => $terminal_id,
                'terminal_name' => $terminal_name,
                'location' => $location_label,
                'shift_id' => $user_shift_id
            ]]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    case 'get_login_activity':
        try {
            $limit = isset($data['limit']) ? intval($data['limit']) : 200;
            $search = isset($data['search']) ? trim($data['search']) : '';
            $date_from = isset($data['date_from']) ? trim($data['date_from']) : '';
            $date_to = isset($data['date_to']) ? trim($data['date_to']) : '';

            $clauses = [];
            $params = [];

            if ($search !== '') {
                $clauses[] = '(l.username LIKE ? OR e.Fname LIKE ? OR e.Lname LIKE ?)';
                $term = "%$search%";
                $params[] = $term; $params[] = $term; $params[] = $term;
            }
            if ($date_from !== '') { $clauses[] = 'l.login_date >= ?'; $params[] = $date_from; }
            if ($date_to !== '') { $clauses[] = 'l.login_date <= ?'; $params[] = $date_to; }

            $whereSql = count($clauses) ? ('WHERE ' . implode(' AND ', $clauses)) : '';

            $sql = "
                SELECT 
                    l.login_id, l.emp_id, l.role_id, l.username,
                    l.login_time, l.login_date, l.logout_time, l.logout_date,
                    l.ip_address,
                    e.Fname, e.Lname, r.role,
                    -- Compute terminal/location label without requiring extra columns
                    CASE 
                        WHEN LOWER(r.role) LIKE '%admin%' THEN 'Admin Terminal'
                        WHEN LOWER(r.role) LIKE '%cashier%' OR LOWER(r.role) LIKE '%pos%' THEN 'Convenience POS'
                        WHEN LOWER(r.role) LIKE '%pharmacist%' THEN 'Pharmacy POS'
                        WHEN LOWER(r.role) LIKE '%inventory%' THEN 'Inventory Terminal'
                        ELSE 'Admin Terminal'
                    END AS terminal_name
                FROM tbl_login l
                LEFT JOIN tbl_employee e ON l.emp_id = e.emp_id
                LEFT JOIN tbl_role r ON l.role_id = r.role_id
                $whereSql
                ORDER BY l.login_id DESC
                LIMIT $limit
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = is_array($rows) ? count($rows) : 0;
            
            // If no rows, try a fallback simple query (helps diagnose join/data issues)
            $fallback = [];
            if ($rowCount === 0) {
                $fb = $conn->prepare("SELECT * FROM tbl_login ORDER BY login_id DESC LIMIT 5");
                $fb->execute();
                $fallback = $fb->fetchAll(PDO::FETCH_ASSOC);
            }

            // Debug: log how many rows were found
            error_log('[get_login_activity] rows=' . $rowCount . ', fallback=' . count($fallback));

            echo json_encode(['success' => true, 'data' => $rows, 'fallback' => $fallback]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => []]);
        }
        break;

    case 'get_login_activity_count':
        try {
            // Count today's logins and logouts (each recorded row counts once; rows with today's logout but older login also counted)
            $stmt = $conn->prepare("SELECT 
                    SUM(CASE WHEN login_date = CURDATE() THEN 1 ELSE 0 END) AS logins_today,
                    SUM(CASE WHEN logout_date = CURDATE() THEN 1 ELSE 0 END) AS logouts_today
                FROM tbl_login");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['logins_today' => 0, 'logouts_today' => 0];
            $total = (int)$row['logins_today'] + (int)$row['logouts_today'];
            echo json_encode(['success' => true, 'data' => ['logins_today' => (int)$row['logins_today'], 'logouts_today' => (int)$row['logouts_today'], 'total' => $total]]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => ['logins_today' => 0, 'logouts_today' => 0, 'total' => 0]]);
        }
        break;

    case 'generate_captcha':
        try {
            // Generate a simple math captcha
            $num1 = rand(1, 10);
            $num2 = rand(1, 10);
            $answer = $num1 + $num2;
            
            echo json_encode([
                "success" => true,
                "question" => "What is $num1 + $num2?",
                "answer" => $answer
            ]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;

   
        case 'display_employee':
            try {
                $stmt = $conn->prepare("SELECT emp_id,Fname,Mname,Lname,email,contact_num,role_id,shift_id,username,age,address,status,gender,birthdate FROM tbl_employee");
                $stmt->execute();
                $employee = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
                if ($employee) {
                    echo json_encode([
                        "success" => true,
                        "employees" => $employee
                    ]);
                } else {
                    echo json_encode([
                        "success" => true,
                        "employees" => [],
                        "message" => "No employees found"
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $e->getMessage(),
                    "employees" => []
                ]);
            }
            break;

    case 'update_employee_status':
        try {
            $emp_id = isset($data['id']) ? trim($data['id']) : '';
            $newStatus = isset($data['status']) ? trim($data['status']) : '';

            $stmt = $conn->prepare("UPDATE tbl_employee SET status = :status WHERE emp_id = :id");
            $stmt->bindParam(":status", $newStatus, PDO::PARAM_STR);
            $stmt->bindParam(":id", $emp_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Status updated successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update status"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;
        //convenience
    case 'add_convenience_product':
        try{
             $product_name = isset($data['product_name'])&& !empty($data['product_name']) ? trim($data['product_name']) : '';
            $category = isset($data['category']) && !empty($data['category'])? trim($data['category']) : '';
            $barcode = isset($data['barcode']) && !empty($data['barcode'])? trim($data['barcode']) : '';
            $description = isset($data['description']) && !empty($data['description']) ? trim($data['description']) : '';
            $expiration = isset($data['expiration']) && !empty($data['expiration']) ? trim($data['expiration']) : '';

            $quantity = isset($data['quantity']) && !empty($data['quantity']) ? trim($data['quantity']) : '';
            $unit_price = isset($data['unit_price']) && !empty($data['unit_price']) ? trim($data['unit_price']) : '';
            $brand = isset($data['brand_id']) && !empty($data['brand_id']) ? trim($data['brand_id']) : '';
           
           

            // Prepare the SQL statement
            $stmt = $conn->prepare("
                INSERT INTO tbl_product (
                    product_name, category, barcode, description, expiration, quantity, unit_price,
                    brand_id
                ) VALUES (
                    :product_name, :category, :barcode, :description, :expiration, :quantity, :unit_price,
                    :brand_id
                )
            ");

            // Bind parameters
            $stmt->bindParam(":product_name", $product_name, PDO::PARAM_STR);
            $stmt->bindParam(":category", $category, PDO::PARAM_STR);
            $stmt->bindParam(":barcode", $barcode, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":expiration", $expiration, PDO::PARAM_STR);
            $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
            $stmt->bindParam(":unit_price", $unit_price, PDO::PARAM_INT);
            $stmt->bindParam(":brand_id", $brand, PDO::PARAM_STR);
           
            // Execute the statement
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Product added successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add product"]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;
        //pharmacy
          case 'add_pharmacy_product':
        try{
             $product_name = isset($data['product_name'])&& !empty($data['product_name']) ? trim($data['product_name']) : '';
            $category = isset($data['category']) && !empty($data['category'])? trim($data['category']) : '';
            $barcode = isset($data['barcode']) && !empty($data['barcode'])? trim($data['barcode']) : '';
            $description = isset($data['description']) && !empty($data['description']) ? trim($data['description']) : '';
            $prescription = isset($data['prescription']) && !empty($data['prescription']) ? trim($data['prescription']) : '';
            $expiration = isset($data['expiration']) && !empty($data['expiration']) ? trim($data['expiration']) : '';
            $quantity = isset($data['quantity']) && !empty($data['quantity']) ? trim($data['quantity']) : '';
            $unit_price = isset($data['unit_price']) && !empty($data['unit_price']) ? trim($data['unit_price']) : '';
            $brand = isset($data['brand_id']) && !empty($data['brand_id']) ? trim($data['brand_id']) : '';
           
           

            // Prepare the SQL statement
            $stmt = $conn->prepare("
                INSERT INTO tbl_product (
                    product_name, category, barcode, description, prescription, expiration, quantity, unit_price,
                    brand_id
                ) VALUES (
                    :product_name, :category, :barcode, :description, :prescription, :expiration, :quantity, :unit_price,
                    :brand_id
                )
            ");

            // Bind parameters
            $stmt->bindParam(":product_name", $product_name, PDO::PARAM_STR);
            $stmt->bindParam(":category", $category, PDO::PARAM_STR);
            $stmt->bindParam(":barcode", $barcode, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":prescription", $prescription, PDO::PARAM_STR);
            $stmt->bindParam(":expiration", $expiration, PDO::PARAM_STR);
            $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
            $stmt->bindParam(":unit_price", $unit_price, PDO::PARAM_INT);
            $stmt->bindParam(":brand_id", $brand, PDO::PARAM_STR);
           
            // Execute the statement
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Product added successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add product"]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;
        //brand section
        case 'addBrand':
    try {
        $brand_name = isset($data['brand']) && !empty($data['brand']) ? trim($data['brand']) : '';

        // Validate input
        if (!$brand_name) {
            echo json_encode(["success" => false, "message" => "Brand name is required"]);
            exit;
        }

        // Check for duplicates
        $checkStmt = $conn->prepare("SELECT * FROM tbl_brand WHERE brand = :brand");
        $checkStmt->bindParam(":brand", $brand_name, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Brand already exists"]);
            exit;
        }

        // Insert new brand
        $stmt = $conn->prepare("INSERT INTO tbl_brand (brand) VALUES (:brand)");
        $stmt->bindParam(":brand", $brand_name, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Brand added successfully"]);
        } else {
            // Return specific database error
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . implode(", ", $stmt->errorInfo())
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
    }
    break;
    case 'displayBrand':
        try {
            // Get all brands with their product count (without is_archived)
            $stmt = $conn->prepare("
                SELECT 
                    b.brand_id, 
                    b.brand, 
                    COUNT(p.product_id) AS product_count
                FROM tbl_brand b
                LEFT JOIN tbl_product p ON b.brand_id = p.brand_id
                GROUP BY b.brand_id, b.brand
                ORDER BY b.brand_id
            ");
            $stmt->execute();
            $brand = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if ($brand) {
                echo json_encode([
                    "success" => true,
                    "brand" => $brand
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "brand" => [],
                    "message" => "No brands found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "brand" => []
            ]);
        }
        break;
        
         case 'deleteBrand':  
    try {
        $brand_id = isset($data['brand_id']) ? intval($data['brand_id']) : 0;
        
        // Validate input
        if ($brand_id <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid brand ID"]);
            break;
        }

        // Use prepared statement with proper DELETE syntax
        $stmt = $conn->prepare("DELETE FROM tbl_brand WHERE brand_id = :brand_id");
        $stmt->bindParam(":brand_id", $brand_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Brand deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to delete brand"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false, 
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
    break;

    case 'add_brand':
        try {
            $brand_name = isset($data['brand_name']) ? trim($data['brand_name']) : '';
            
            if (empty($brand_name)) {
                echo json_encode(["success" => false, "message" => "Brand name is required"]);
                break;
            }
            
            // Check if brand already exists
            $checkStmt = $conn->prepare("SELECT brand_id FROM tbl_brand WHERE brand = ?");
            $checkStmt->execute([$brand_name]);
            $existingBrand = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingBrand) {
                echo json_encode([
                    "success" => true, 
                    "brand_id" => $existingBrand['brand_id'],
                    "message" => "Brand already exists"
                ]);
                break;
            }
            
            // Insert new brand
            $stmt = $conn->prepare("INSERT INTO tbl_brand (brand) VALUES (?)");
            $stmt->execute([$brand_name]);
            $brand_id = $conn->lastInsertId();
            
            echo json_encode([
                "success" => true, 
                "brand_id" => $brand_id,
                "message" => "Brand added successfully"
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false, 
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'add_product':
        try {
            // Extract and sanitize data
            $product_name = isset($data['product_name']) ? trim($data['product_name']) : '';
            $category = isset($data['category']) ? trim($data['category']) : '';
            $barcode = isset($data['barcode']) ? trim($data['barcode']) : '';
            $description = isset($data['description']) ? trim($data['description']) : '';
            $variation = isset($data['variation']) ? trim($data['variation']) : '';
            $prescription = isset($data['prescription']) ? intval($data['prescription']) : 0;
            $bulk = isset($data['bulk']) ? intval($data['bulk']) : 0;
            $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
            $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
            $srp = isset($data['srp']) ? floatval($data['srp']) : $unit_price; // Default SRP to unit_price if not provided
            $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : 0;
            $brand_id = isset($data['brand_id']) ? intval($data['brand_id']) : 30; // Default to first brand (30)
            $expiration = isset($data['expiration']) ? trim($data['expiration']) : null;
            $date_added = isset($data['date_added']) ? trim($data['date_added']) : date('Y-m-d');
            $status = isset($data['status']) ? trim($data['status']) : 'active';
            $stock_status = isset($data['stock_status']) ? trim($data['stock_status']) : 'in stock';
            $reference = isset($data['reference']) ? trim($data['reference']) : '';
            $entry_by = isset($data['entry_by']) ? trim($data['entry_by']) : 'admin';
            $order_no = isset($data['order_no']) ? trim($data['order_no']) : '';
            
            // Handle location_id - convert location name to ID if needed
            $location_id = null;
            if (isset($data['location_id'])) {
                $location_id = intval($data['location_id']);
            } elseif (isset($data['location'])) {
                // If location name is provided, find the location_id
                $locStmt = $conn->prepare("SELECT location_id FROM tbl_location WHERE location_name = ?");
                $locStmt->execute([trim($data['location'])]);
                $location = $locStmt->fetch(PDO::FETCH_ASSOC);
                $location_id = $location ? $location['location_id'] : 2; // Default to warehouse (ID 2)
            } else {
                $location_id = 2; // Default to warehouse
            }
            
            // Validate brand_id exists
            $brandCheckStmt = $conn->prepare("SELECT brand_id FROM tbl_brand WHERE brand_id = ?");
            $brandCheckStmt->execute([$brand_id]);
            if (!$brandCheckStmt->fetch()) {
                // If brand_id doesn't exist, use the first available brand
                $firstBrandStmt = $conn->prepare("SELECT brand_id FROM tbl_brand ORDER BY brand_id LIMIT 1");
                $firstBrandStmt->execute();
                $firstBrand = $firstBrandStmt->fetch(PDO::FETCH_ASSOC);
                $brand_id = $firstBrand ? $firstBrand['brand_id'] : 30;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Create batch record first
            $batch_id = null;
            if ($reference) {
                $batchStmt = $conn->prepare("
                    INSERT INTO tbl_batch (
                        batch, supplier_id, location_id, entry_date, entry_time, 
                        entry_by, order_no
                    ) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)
                ");
                $batchStmt->execute([$reference, $supplier_id, $location_id, $entry_by, $order_no]);
                $batch_id = $conn->lastInsertId();
            }
            
            // Prepare insert statement for product
            $stmt = $conn->prepare("
                INSERT INTO tbl_product (
                    product_name, category, barcode, description, prescription, bulk,
                    expiration, date_added, quantity, unit_price, srp, brand_id, supplier_id,
                    location_id, batch_id, status, Variation, stock_status
                ) VALUES (
                    :product_name, :category, :barcode, :description, :prescription, :bulk,
                    :expiration, :date_added, :quantity, :unit_price, :srp, :brand_id, :supplier_id,
                    :location_id, :batch_id, :status, :variation, :stock_status
                )
            ");
    
            // Bind parameters
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':barcode', $barcode);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':prescription', $prescription);
            $stmt->bindParam(':bulk', $bulk);
            $stmt->bindParam(':expiration', $expiration);
            $stmt->bindParam(':date_added', $date_added);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':unit_price', $unit_price);
            $stmt->bindParam(':srp', $srp);
            $stmt->bindParam(':brand_id', $brand_id);
            $stmt->bindParam(':supplier_id', $supplier_id);
            $stmt->bindParam(':location_id', $location_id);
            $stmt->bindParam(':batch_id', $batch_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':variation', $variation);
            $stmt->bindParam(':stock_status', $stock_status);
    
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(["success" => true, "message" => "Product added successfully"]);
            } else {
                $conn->rollback();
                echo json_encode(["success" => false, "message" => "Failed to add product"]);
            }
    
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'update_product':
        try {
            // Extract and sanitize data
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $product_name = isset($data['product_name']) ? trim($data['product_name']) : '';
            $category = isset($data['category']) ? trim($data['category']) : '';
            $barcode = isset($data['barcode']) ? trim($data['barcode']) : '';
            $description = isset($data['description']) ? trim($data['description']) : '';
            $prescription = isset($data['prescription']) ? intval($data['prescription']) : 0;
            $bulk = isset($data['bulk']) ? intval($data['bulk']) : 0;
            $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
            $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
            $srp = isset($data['srp']) ? floatval($data['srp']) : $unit_price; // Default SRP to unit_price if not provided
            $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : 0;
            $brand_id = isset($data['brand_id']) ? intval($data['brand_id']) : 0;
            $expiration = isset($data['expiration']) ? trim($data['expiration']) : null;
            
            if ($product_id <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID"
                ]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Update product
            $stmt = $conn->prepare("
                UPDATE tbl_product SET 
                    product_name = ?,
                    category = ?,
                    barcode = ?,
                    description = ?,
                    prescription = ?,
                    bulk = ?,
                    quantity = ?,
                    unit_price = ?,
                    srp = ?,
                    supplier_id = ?,
                    brand_id = ?,
                    expiration = ?,
                    stock_status = CASE 
                        WHEN ? <= 0 THEN 'out of stock'
                        WHEN ? <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                WHERE product_id = ?
            ");
            
            $stmt->execute([
                $product_name,
                $category,
                $barcode,
                $description,
                $prescription,
                $bulk,
                $quantity,
                $unit_price,
                $srp,
                $supplier_id,
                $brand_id,
                $expiration,
                $quantity,
                $quantity,
                $product_id
            ]);
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Product updated successfully"
            ]);
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;


        case 'enhanced_fifo_transfer':
    require_once '../enhanced_fifo_transfer_system.php';
    
    try {
        $fifoSystem = new EnhancedFifoTransferSystem($conn);
        $result = $fifoSystem->performEnhancedFifoTransfer($data);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Enhanced FIFO Transfer Error: ' . $e->getMessage()
        ]);
    }
    break;

case 'get_fifo_stock_status':
    require_once '../enhanced_fifo_transfer_system.php';
    
    try {
        $product_id = $data['product_id'] ?? 0;
        $location_id = $data['location_id'] ?? null;
        
        $fifoSystem = new EnhancedFifoTransferSystem($conn);
        $result = $fifoSystem->getFifoStockStatus($product_id, $location_id);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error getting FIFO stock status: ' . $e->getMessage()
        ]);
    }
    break;

case 'check_fifo_availability':
    try {
        $product_id = $data['product_id'] ?? 0;
        $location_id = $data['location_id'] ?? 0;
        $requested_quantity = $data['requested_quantity'] ?? 0;
        
        // Get FIFO stock for availability check
        $stmt = $conn->prepare("
            SELECT 
                ss.available_quantity,
                ss.batch_reference,
                b.entry_date,
                ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_rank
            FROM tbl_stock_summary ss
            JOIN tbl_batch b ON ss.batch_id = b.batch_id
            WHERE ss.product_id = ? 
            AND b.location_id = ?
            AND ss.available_quantity > 0
            ORDER BY b.entry_date ASC, ss.summary_id ASC
        ");
        
        $stmt->execute([$product_id, $location_id]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_available = 0;
        foreach ($batches as $batch) {
            $total_available += $batch['available_quantity'];
        }
        
        $is_available = $total_available >= $requested_quantity;
        
        echo json_encode([
            "success" => true,
            "is_available" => $is_available,
            "total_available" => $total_available,
            "requested_quantity" => $requested_quantity,
            "batches_count" => count($batches),
            "next_batches" => array_slice($batches, 0, 3) // Show first 3 batches that would be used
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error checking FIFO availability: " . $e->getMessage()
        ]);
    }
    break;

case 'get_products_oldest_batch_for_transfer':
    try {
        $location_id = $data['location_id'] ?? null;
        
        $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
        $params = [];
        
        if ($location_id) {
            $whereClause .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        // Simple query to get products directly from tbl_product
        $stmt = $conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.category,
                p.barcode,
                p.description,
                COALESCE(p.Variation, '') as variation,
                COALESCE(b.brand, '') as brand,
                COALESCE(s.supplier_name, '') as supplier_name,
                COALESCE(p.srp, p.unit_price) as srp,
                p.location_id,
                l.location_name,
                p.quantity as total_quantity,
                p.quantity as oldest_batch_quantity,
                p.unit_price as unit_cost,
                'N/A' as batch_reference,
                'N/A' as entry_date,
                'N/A' as expiration_date,
                1 as total_batches
            FROM tbl_product p
            LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
            LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            $whereClause
            AND p.quantity > 0
            ORDER BY p.product_name ASC
        ");
        
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "data" => $products
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage(),
            "data" => []
        ]);
    }
    break;

    case 'get_products_oldest_batch':
        try {
            $location_id = $data['location_id'] ?? null;
            
            $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
            $params = [];
            
            if ($location_id) {
                $whereClause .= " AND p.location_id = ?";
                $params[] = $location_id;
            }
            
            // Query to get products with oldest batch information for warehouse display
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    COALESCE(p.Variation, '') as variation,
                    COALESCE(b.brand, '') as brand,
                    COALESCE(s.supplier_name, '') as supplier_name,
                    COALESCE(p.srp, p.unit_price) as srp,
                    p.unit_price,
                    p.location_id,
                    l.location_name,
                    p.stock_status,
                    p.date_added,
                    p.status,
                    -- Oldest batch information
                    oldest_batch.batch_id,
                    oldest_batch.batch_reference,
                    oldest_batch.entry_date,
                    oldest_batch.expiration_date,
                    oldest_batch.quantity as oldest_batch_quantity,
                    oldest_batch.unit_cost,
                    oldest_batch.entry_time,
                    oldest_batch.entry_by,
                    -- Total quantity across all batches
                    total_qty.total_quantity,
                    -- Count of total batches
                    total_qty.total_batches,
                    -- Fallback to product quantity if no stock summary
                    COALESCE(total_qty.total_quantity, p.quantity) as quantity
                FROM tbl_product p
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                -- Get oldest batch for each product
                LEFT JOIN (
                    SELECT 
                        ss.product_id,
                        ss.batch_id,
                        bt.batch as batch_reference,
                        bt.entry_date,
                        bt.entry_time,
                        bt.entry_by,
                        ss.expiration_date,
                        ss.available_quantity as quantity,
                        ss.unit_cost,
                        ROW_NUMBER() OVER (
                            PARTITION BY ss.product_id 
                            ORDER BY bt.entry_date ASC, bt.batch_id ASC
                        ) as batch_rank
                    FROM tbl_stock_summary ss
                    INNER JOIN tbl_batch bt ON ss.batch_id = bt.batch_id
                    WHERE ss.available_quantity > 0
                ) oldest_batch ON p.product_id = oldest_batch.product_id AND oldest_batch.batch_rank = 1
                -- Get total quantities
                LEFT JOIN (
                    SELECT 
                        product_id,
                        SUM(available_quantity) as total_quantity,
                        COUNT(*) as total_batches
                    FROM tbl_stock_summary
                    WHERE available_quantity > 0
                    GROUP BY product_id
                ) total_qty ON p.product_id = total_qty.product_id
                $whereClause
                ORDER BY p.product_name ASC
            ");
            
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $products
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_inventory_kpis':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as physicalAvailable,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhandInventory,
                    COUNT(CASE WHEN p.quantity <= 10 THEN 1 END) as newOrderLineQty,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) * 100.0 / COUNT(*), 1) as returnRate,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'in stock' THEN 1 END) * 100.0 / COUNT(*), 1) as sellRate,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as outOfStock
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
            ");
            $stmt->execute($params);
            $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($kpis);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_supply_by_location':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    l.location_name as location,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhand,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY l.location_name
                ORDER BY onhand DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $supplyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($supplyData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_return_rate_by_product':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) * 100.0 / COUNT(*), 1) as returnRate
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.product_name
                HAVING returnRate > 0
                ORDER BY returnRate DESC
                LIMIT 12
            ");
            $stmt->execute($params);
            $returnData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($returnData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_stockout_items':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    -p.quantity as stockout
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND p.stock_status = 'out of stock'
                ORDER BY stockout ASC
                LIMIT 15
            ");
            $stmt->execute($params);
            $stockoutData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stockoutData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_products':
    try {
        $location_id = $data['location_id'] ?? null;
        $for_transfer = $data['for_transfer'] ?? false;
        
        $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
        $params = [];
        
        if ($location_id) {
            $whereClause .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        // If for transfer, show OLD and NEW quantities separately for FIFO management
        if ($for_transfer) {
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    p.Variation,
                    p.brand_id,
                    p.supplier_id,
                    p.location_id,
                    p.unit_price,
                    p.stock_status,
                    s.supplier_name,
                    b.brand,
                    l.location_name,
                    ss.batch_id,
                    ss.batch_reference,
                    b.entry_date,
                    b.entry_by,
                    COALESCE(p.date_added, CURDATE()) as date_added,
                    -- Show OLD quantity (oldest batch)
                    (SELECT ss2.available_quantity 
                     FROM tbl_stock_summary ss2 
                     INNER JOIN tbl_batch b2 ON ss2.batch_id = b2.batch_id 
                     WHERE ss2.product_id = p.product_id 
                     AND ss2.available_quantity > 0
                     AND b2.entry_date = (
                         SELECT MIN(b3.entry_date) 
                         FROM tbl_batch b3 
                         INNER JOIN tbl_stock_summary ss3 ON b3.batch_id = ss3.batch_id 
                         WHERE ss3.product_id = p.product_id AND ss3.available_quantity > 0
                     )
                     LIMIT 1) as old_quantity,
                    -- Show NEW quantity (newest batch)
                    (SELECT ss2.available_quantity 
                     FROM tbl_stock_summary ss2 
                     INNER JOIN tbl_batch b2 ON ss2.batch_id = b2.batch_id 
                     WHERE ss2.product_id = p.product_id 
                     AND ss2.available_quantity > 0
                     AND b2.entry_date = (
                         SELECT MAX(b3.entry_date) 
                         FROM tbl_batch b3 
                         INNER JOIN tbl_stock_summary ss3 ON b3.batch_id = ss3.batch_id 
                         WHERE ss3.product_id = p.product_id AND ss3.available_quantity > 0
                     )
                     LIMIT 1) as new_quantity,
                    -- Show total quantity
                    ss.available_quantity as total_quantity
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
                INNER JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE ss.available_quantity > 0
                AND (p.status IS NULL OR p.status <> 'archived')
                $whereClause
                GROUP BY p.product_id, p.product_name, p.category, p.barcode, p.description, p.Variation, 
                         p.brand_id, p.supplier_id, p.location_id, p.unit_price, p.stock_status, 
                         s.supplier_name, b.brand, l.location_name, ss.batch_id, ss.batch_reference, 
                         b.entry_date, b.entry_by, ss.available_quantity
                ORDER BY p.product_name ASC
            ");
        } else {
            // Original query for regular product listing with FIFO information
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name,
                    batch.batch_id,
                    batch.batch as batch_reference,
                    batch.entry_date,
                    batch.entry_time,
                    batch.entry_by,
                    COALESCE(p.date_added, CURDATE()) as date_added,
                    ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY batch.entry_date ASC, batch.batch_id ASC) as fifo_order,
                    (SELECT quantity FROM tbl_stock_movements WHERE product_id = p.product_id ORDER BY movement_date DESC LIMIT 1) as quantity_change,
                    (SELECT movement_date FROM tbl_stock_movements WHERE product_id = p.product_id ORDER BY movement_date DESC LIMIT 1) as last_updated
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN (
                    SELECT 
                        p2.product_id,
                        b2.batch_id,
                        b2.batch,
                        b2.entry_date,
                        b2.entry_time,
                        b2.entry_by
                    FROM tbl_product p2
                    LEFT JOIN tbl_batch b2 ON p2.batch_id = b2.batch_id
                    WHERE p2.batch_id IS NOT NULL
                    GROUP BY p2.product_id
                    HAVING MIN(b2.entry_date) = b2.entry_date
                ) batch ON p.product_id = batch.product_id
                $whereClause
                ORDER BY p.product_name ASC, batch.entry_date ASC
            ");
        }
        
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => $products
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage(),
            "data" => []
        ]);
    }
    break;

    case 'get_suppliers':
        try {
            $stmt = $conn->prepare("
                SELECT * FROM tbl_supplier 
                WHERE status != 'archived' OR status IS NULL
                ORDER BY supplier_id DESC
            ");
            $stmt->execute();
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $suppliers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_brands':
        try {
            $stmt = $conn->prepare("SELECT * FROM tbl_brand ORDER BY brand_id DESC");
            $stmt->execute();
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $brands
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_categories':
        try {
            $stmt = $conn->prepare("SELECT * FROM tbl_category ORDER BY category_id");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $categories
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_locations':
        try {
            $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_id");
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $locations
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_inventory_staff':
        try {
            $stmt = $conn->prepare("
                SELECT emp_id, CONCAT(Fname, ' ', Lname) as name 
                FROM tbl_employee 
                WHERE status = 'Active'
                ORDER BY Fname, Lname
            ");
            $stmt->execute();
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $staff
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_transfers_with_details':
        try {
            $stmt = $conn->prepare("
                SELECT 
                    th.transfer_header_id,
                    th.date,
                    th.status,
                    th.note,
                    sl.location_name as source_location_name,
                    dl.location_name as destination_location_name,
                    e.Fname as employee_name,
                    COUNT(td.product_id) as total_products,
                    SUM(td.qty * p.unit_price) as total_value
                FROM tbl_transfer_header th
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
                LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
                LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                LEFT JOIN tbl_product p ON td.product_id = p.product_id
                GROUP BY th.transfer_header_id
                ORDER BY th.transfer_header_id DESC
            ");
            $stmt->execute();
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get products for each transfer
            foreach ($transfers as &$transfer) {
                $stmt2 = $conn->prepare("
                    SELECT 
                        p.product_name, p.category, p.barcode, p.unit_price,
                        p.Variation, p.description, p.brand_id,
                        b.brand,
                        td.qty as qty
                    FROM tbl_transfer_dtl td
                    JOIN tbl_product p ON td.product_id = p.product_id
                    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                    WHERE td.transfer_header_id = ?
                ");
                $stmt2->execute([$transfer['transfer_header_id']]);
                $transfer['products'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                "success" => true,
                "data" => $transfers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'create_transfer':
        try {
            $source_location_id = $data['source_location_id'] ?? 0;
            $destination_location_id = $data['destination_location_id'] ?? 0;
            $employee_id = $data['employee_id'] ?? 0;
            $status = $data['status'] ?? 'approved'; // Use 'approved' to match database enum
            $products = $data['products'] ?? [];
            
            // Strict validation for locations
            if ($source_location_id == $destination_location_id) {
                error_log("[TRANSFER ERROR] Source and destination locations are the same! Source: $source_location_id, Destination: $destination_location_id");
                echo json_encode(["success" => false, "message" => "Source and destination locations cannot be the same!"]);
                break;
            }
            if ($destination_location_id == 0) {
                error_log("[TRANSFER ERROR] Invalid destination location! Destination: $destination_location_id");
                echo json_encode(["success" => false, "message" => "Invalid destination location!"]);
                break;
            }
            // Check if destination exists
            $locCheck = $conn->prepare("SELECT location_id, location_name FROM tbl_location WHERE location_id = ?");
            $locCheck->execute([$destination_location_id]);
            $destLoc = $locCheck->fetch(PDO::FETCH_ASSOC);
            if (!$destLoc) {
                error_log("[TRANSFER ERROR] Destination location does not exist! ID: $destination_location_id");
                echo json_encode(["success" => false, "message" => "Destination location does not exist!"]);
                break;
            }
            error_log("[TRANSFER] Source: $source_location_id, Destination: $destination_location_id ({$destLoc['location_name']})");
            
            if (empty($products)) {
                echo json_encode(["success" => false, "message" => "No products to transfer"]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Validate product quantities before transfer
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $transfer_qty = $product['quantity'];
                
                // Check current quantity - look for product in source location
                $checkStmt = $conn->prepare("
                    SELECT quantity, product_name, location_id 
                    FROM tbl_product 
                    WHERE product_id = ? AND location_id = ?
                ");
                $checkStmt->execute([$product_id, $source_location_id]);
                $currentProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentProduct) {
                    error_log("[TRANSFER ERROR] Product not found in source location - Product ID: $product_id");
                    throw new Exception("Product not found in source location - Product ID: " . $product_id);
                }
                
                if ($currentProduct['quantity'] < $transfer_qty) {
                    error_log("[TRANSFER ERROR] Insufficient quantity for product: {$currentProduct['product_name']} (Available: {$currentProduct['quantity']}, Requested: $transfer_qty)");
                    throw new Exception("Insufficient quantity for product: " . $currentProduct['product_name'] . 
                                     ". Available: " . $currentProduct['quantity'] . ", Requested: " . $transfer_qty);
                }
                
                // Log for debugging
                error_log("[TRANSFER VALIDATION] Product ID: $product_id, Name: " . $currentProduct['product_name'] . ", Available: " . $currentProduct['quantity'] . ", Requested: $transfer_qty");
            }
            
            // Insert transfer header
            $stmt = $conn->prepare("
                INSERT INTO tbl_transfer_header (
                    source_location_id, destination_location_id, employee_id, 
                    status, date
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$source_location_id, $destination_location_id, $employee_id, $status]);
            $transfer_header_id = $conn->lastInsertId();
            
            // Insert transfer details and process the transfer
            $stmt2 = $conn->prepare("
                INSERT INTO tbl_transfer_dtl (
                    transfer_header_id, product_id, qty
                ) VALUES (?, ?, ?)
            ");
            
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $transfer_qty = $product['quantity'];
                
                // Insert transfer detail
                $stmt2->execute([
                    $transfer_header_id,
                    $product_id,
                    $transfer_qty
                ]);
                
                // Get the original product details from source location
                $productStmt = $conn->prepare("
                    SELECT product_name, category, barcode, description, prescription, bulk,
                           expiration, unit_price, brand_id, supplier_id, batch_id, status, Variation
                    FROM tbl_product 
                    WHERE product_id = ? AND location_id = ?
                    LIMIT 1
                ");
                $productStmt->execute([$product_id, $source_location_id]);
                $productDetails = $productStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productDetails) {
                    // Decrease quantity in source location
                    $updateSourceStmt = $conn->prepare("
                        UPDATE tbl_product 
                        SET quantity = quantity - ?,
                            stock_status = CASE 
                                WHEN quantity - ? <= 0 THEN 'out of stock'
                                WHEN quantity - ? <= 10 THEN 'low stock'
                                ELSE 'in stock'
                            END
                        WHERE product_id = ? AND location_id = ?
                    ");
                    $updateSourceStmt->execute([$transfer_qty, $transfer_qty, $transfer_qty, $product_id, $source_location_id]);
                    
                    // Check if the product quantity becomes 0 or less after transfer
                    $checkRemainingStmt = $conn->prepare("
                        SELECT quantity 
                        FROM tbl_product 
                        WHERE product_id = ? AND location_id = ?
                    ");
                    $checkRemainingStmt->execute([$product_id, $source_location_id]);
                    $remainingQty = $checkRemainingStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // If quantity is 0 or less, mark as out of stock but keep the record
                    // DO NOT DELETE the product record as it breaks transfer references
                    if ($remainingQty && $remainingQty['quantity'] <= 0) {
                        $updateStockStmt = $conn->prepare("
                            UPDATE tbl_product 
                            SET stock_status = 'out of stock',
                                quantity = 0
                            WHERE product_id = ? AND location_id = ?
                        ");
                        $updateStockStmt->execute([$product_id, $source_location_id]);
                        error_log("Updated product to out of stock in source location - Product ID: $product_id, Quantity set to 0");
                    }
                    
                    // Check if a product with the same barcode exists in destination
                    $checkBarcodeStmt = $conn->prepare("
                        SELECT product_id, quantity 
                        FROM tbl_product 
                        WHERE barcode = ? AND location_id = ?
                    ");
                    $checkBarcodeStmt->execute([$productDetails['barcode'], $destination_location_id]);
                    $existingBarcodeProduct = $checkBarcodeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingBarcodeProduct) {
                        // Update existing product with same barcode
                        $updateDestStmt = $conn->prepare("
                            UPDATE tbl_product 
                            SET quantity = quantity + ?,
                                stock_status = CASE 
                                    WHEN quantity + ? <= 0 THEN 'out of stock'
                                    WHEN quantity + ? <= 10 THEN 'low stock'
                                    ELSE 'in stock'
                                END
                            WHERE product_id = ? AND location_id = ?
                        ");
                        $updateDestStmt->execute([$transfer_qty, $transfer_qty, $transfer_qty, $existingBarcodeProduct['product_id'], $destination_location_id]);
                        
                        error_log("Updated existing product with same barcode in destination - Product ID: " . $existingBarcodeProduct['product_id'] . ", Added Qty: $transfer_qty");
                    } else {
                        // Create new product entry in destination location with original barcode
                        $insertDestStmt = $conn->prepare("
                            INSERT INTO tbl_product (
                                product_name, category, barcode, description, prescription, bulk,
                                expiration, quantity, unit_price, brand_id, supplier_id,
                                location_id, batch_id, status, Variation, stock_status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insertResult = $insertDestStmt->execute([
                            $productDetails['product_name'],
                            $productDetails['category'],
                            $productDetails['barcode'],
                            $productDetails['description'],
                            $productDetails['prescription'],
                            $productDetails['bulk'],
                            $productDetails['expiration'],
                            $transfer_qty,
                            $productDetails['unit_price'],
                            $productDetails['brand_id'],
                            $productDetails['supplier_id'],
                            $destination_location_id,
                            $productDetails['batch_id'],
                            $productDetails['status'],
                            $productDetails['Variation'],
                            $transfer_qty <= 0 ? 'out of stock' : ($transfer_qty <= 10 ? 'low stock' : 'in stock')
                        ]);
                        
                        if ($insertResult) {
                            error_log("Created new product in destination - Product ID: $product_id, Barcode: " . $productDetails['barcode'] . ", Qty: $transfer_qty");
                        } else {
                            throw new Exception("Failed to create product entry in destination location");
                        }
                    }
                    
                    // Log the transfer
                    error_log("Transfer completed - Product ID: $product_id, Quantity: $transfer_qty, From: $source_location_id, To: $destination_location_id");
                }
            }
            
            $conn->commit();
            
            // Log final transfer summary
            error_log("Transfer completed successfully - Transfer ID: $transfer_header_id, Products: " . count($products));
            
            echo json_encode([
                "success" => true, 
                "message" => "Transfer completed successfully. Products moved to destination location.",
                "transfer_id" => $transfer_header_id,
                "products_transferred" => count($products),
                "source_location" => $source_location_id,
                "destination_location" => $destination_location_id
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'add_supplier':
        try {
            $supplier_name = $data['supplier_name'] ?? '';
            $supplier_address = $data['supplier_address'] ?? '';
            $supplier_contact = $data['supplier_contact'] ?? '';
            $supplier_email = $data['supplier_email'] ?? '';
            $primary_phone = $data['primary_phone'] ?? '';
            $primary_email = $data['primary_email'] ?? '';
            $contact_person = $data['contact_person'] ?? '';
            $contact_title = $data['contact_title'] ?? '';
            $payment_terms = $data['payment_terms'] ?? '';
            $lead_time_days = $data['lead_time_days'] ?? '';
            $order_level = $data['order_level'] ?? '';
            $credit_rating = $data['credit_rating'] ?? '';
            $notes = $data['notes'] ?? '';
            
            $stmt = $conn->prepare("
                INSERT INTO tbl_supplier (
                    supplier_name, supplier_address, supplier_contact, supplier_email,
                    primary_phone, primary_email, contact_person, contact_title,
                    payment_terms, lead_time_days, order_level, credit_rating, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $supplier_name, $supplier_address, $supplier_contact, $supplier_email,
                $primary_phone, $primary_email, $contact_person, $contact_title,
                $payment_terms, $lead_time_days, $order_level, $credit_rating, $notes
            ]);
            
            echo json_encode(["success" => true, "message" => "Supplier added successfully"]);
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'update_supplier':
        try {
            $supplier_id = $data['supplier_id'] ?? 0;
            $supplier_name = $data['supplier_name'] ?? '';
            $supplier_address = $data['supplier_address'] ?? '';
            $supplier_contact = $data['supplier_contact'] ?? '';
            $supplier_email = $data['supplier_email'] ?? '';
            $contact_person = $data['contact_person'] ?? '';
            $payment_terms = $data['payment_terms'] ?? '';
            $lead_time_days = $data['lead_time_days'] ?? '';
            $notes = $data['notes'] ?? '';
            
            $stmt = $conn->prepare("
                UPDATE tbl_supplier SET 
                    supplier_name = ?, supplier_address = ?, supplier_contact = ?,
                    supplier_email = ?, contact_person = ?, payment_terms = ?,
                    lead_time_days = ?, notes = ?
                WHERE supplier_id = ?
            ");
            
            $stmt->execute([
                $supplier_name, $supplier_address, $supplier_contact,
                $supplier_email, $contact_person, $payment_terms,
                $lead_time_days, $notes, $supplier_id
            ]);
            
            echo json_encode(["success" => true, "message" => "Supplier updated successfully"]);
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete_supplier':
        try {
            $supplier_id = $data['supplier_id'] ?? 0;
            $reason = $data['reason'] ?? 'Archived by user';
            $archived_by = $data['archived_by'] ?? 'admin';
            
            // Get supplier details before archiving
            $stmt = $conn->prepare("SELECT * FROM tbl_supplier WHERE supplier_id = ?");
            $stmt->execute([$supplier_id]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$supplier) {
                echo json_encode(["success" => false, "message" => "Supplier not found"]);
                break;
            }

            $conn->beginTransaction();

            try {
                // Update supplier status to archived
                $stmt = $conn->prepare("UPDATE tbl_supplier SET status = 'archived' WHERE supplier_id = ?");
                $stmt->execute([$supplier_id]);
                
                // Add to archive table
                $stmt = $conn->prepare("
                    INSERT INTO tbl_archive (
                        item_id, item_type, item_name, item_description, category, 
                        archived_by, archived_date, archived_time, reason, status, original_data
                    ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?, 'Archived', ?)
                ");
                $stmt->execute([
                    $supplier_id,
                    'Supplier',
                    $supplier['supplier_name'],
                    $supplier['supplier_address'] ?? '',
                    'Suppliers',
                    $archived_by,
                    $reason,
                    json_encode($supplier)
                ]);

                $conn->commit();
                echo json_encode(["success" => true, "message" => "Supplier archived successfully"]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete_product':
        try {
            $product_id = $data['product_id'] ?? 0;
            $reason = $data['reason'] ?? 'Archived by user';
            $archived_by = $data['archived_by'] ?? 'admin';
            
            // Get product details before archiving
            $stmt = $conn->prepare("SELECT * FROM tbl_product WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo json_encode(["success" => false, "message" => "Product not found"]);
                break;
            }

            $conn->beginTransaction();

            try {
                // Update product status to archived
                $stmt = $conn->prepare("UPDATE tbl_product SET status = 'archived' WHERE product_id = ?");
                $stmt->execute([$product_id]);
                
                // Add to archive table
                $stmt = $conn->prepare("
                    INSERT INTO tbl_archive (
                        item_id, item_type, item_name, item_description, category, 
                        archived_by, archived_date, archived_time, reason, status, original_data
                    ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?, 'Archived', ?)
                ");
                $stmt->execute([
                    $product_id,
                    'Product',
                    $product['product_name'],
                    $product['description'] ?? '',
                    $product['category'] ?? '',
                    $archived_by,
                    $reason,
                    json_encode($product)
                ]);

                $conn->commit();
                echo json_encode(["success" => true, "message" => "Product archived successfully"]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'update_transfer_status':
        try {
            $transfer_header_id = $data['transfer_header_id'] ?? 0;
            $new_status = $data['status'] ?? '';
            $employee_id = $data['employee_id'] ?? 0;
            $notes = $data['notes'] ?? '';
            
            if (!$transfer_header_id || !$new_status) {
                echo json_encode(["success" => false, "message" => "Transfer ID and status are required"]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Update transfer status
            $stmt = $conn->prepare("
                UPDATE tbl_transfer_header 
                SET status = ? 
                WHERE transfer_header_id = ?
            ");
            $stmt->execute([$new_status, $transfer_header_id]);
            
            // If status is "Completed", add products to destination location
            if ($new_status === 'Completed') {
                // Get transfer details
                $transferStmt = $conn->prepare("
                    SELECT th.source_location_id, th.destination_location_id, td.product_id, td.qty
                    FROM tbl_transfer_header th
                    JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                    WHERE th.transfer_header_id = ?
                ");
                $transferStmt->execute([$transfer_header_id]);
                $transferDetails = $transferStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($transferDetails as $detail) {
                    $product_id = $detail['product_id'];
                    $qty = $detail['qty'];
                    $destination_location_id = $detail['destination_location_id'];
                    
                    // Get the original product details
                    $productStmt = $conn->prepare("
                        SELECT product_name, category, barcode, description, prescription, bulk,
                               expiration, unit_price, brand_id, supplier_id, batch_id, status, Variation
                        FROM tbl_product 
                        WHERE product_id = ?
                        LIMIT 1
                    ");
                    $productStmt->execute([$product_id]);
                    $productDetails = $productStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($productDetails) {
                        // Check if product exists in destination location
                        $checkStmt = $conn->prepare("
                            SELECT product_id, quantity 
                            FROM tbl_product 
                            WHERE product_id = ? AND location_id = ?
                        ");
                        $checkStmt->execute([$product_id, $destination_location_id]);
                        $existingProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existingProduct) {
                            // Update existing product quantity
                            $updateStmt = $conn->prepare("
                                UPDATE tbl_product 
                                SET quantity = quantity + ?,
                                    stock_status = CASE 
                                        WHEN quantity + ? <= 0 THEN 'out of stock'
                                        WHEN quantity + ? <= 10 THEN 'low stock'
                                        ELSE 'in stock'
                                    END
                                WHERE product_id = ? AND location_id = ?
                            ");
                            $updateStmt->execute([$qty, $qty, $qty, $product_id, $destination_location_id]);
                        } else {
                            // Create new product entry in destination location
                            $insertStmt = $conn->prepare("
                                INSERT INTO tbl_product (
                                    product_name, category, barcode, description, prescription, bulk,
                                    expiration, quantity, unit_price, brand_id, supplier_id,
                                    location_id, batch_id, status, Variation, stock_status
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $insertStmt->execute([
                                $productDetails['product_name'],
                                $productDetails['category'],
                                $productDetails['barcode'],
                                $productDetails['description'],
                                $productDetails['prescription'],
                                $productDetails['bulk'],
                                $productDetails['expiration'],
                                $qty,
                                $productDetails['unit_price'],
                                $productDetails['brand_id'],
                                $productDetails['supplier_id'],
                                $destination_location_id,
                                $productDetails['batch_id'],
                                $productDetails['status'],
                                $productDetails['Variation'],
                                $qty <= 0 ? 'out of stock' : ($qty <= 10 ? 'low stock' : 'in stock')
                            ]);
                        }
                    }
                }
            }
            
            // Log the status change
            $logStmt = $conn->prepare("
                INSERT INTO tbl_transfer_log (
                    transfer_header_id, status, employee_id, notes, log_date
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$transfer_header_id, $new_status, $employee_id, $notes]);
            
            $conn->commit();
            echo json_encode([
                "success" => true, 
                "message" => "Transfer status updated to " . $new_status . 
                            ($new_status === 'Completed' ? ". Products added to destination location." : "")
            ]);
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete_transfer':
        try {
            $transfer_header_id = $data['transfer_header_id'] ?? 0;
            
            if (!$transfer_header_id) {
                echo json_encode(["success" => false, "message" => "Transfer ID is required"]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Get transfer details to restore quantities
            $transferStmt = $conn->prepare("
                SELECT th.source_location_id, td.product_id, td.qty
                FROM tbl_transfer_header th
                JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                WHERE th.transfer_header_id = ?
            ");
            $transferStmt->execute([$transfer_header_id]);
            $transferDetails = $transferStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore quantities to source location
            foreach ($transferDetails as $detail) {
                $updateStmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity + ?,
                        stock_status = CASE 
                            WHEN quantity + ? <= 0 THEN 'out of stock'
                            WHEN quantity + ? <= 10 THEN 'low stock'
                            ELSE 'in stock'
                        END
                    WHERE product_id = ?
                ");
                $updateStmt->execute([$detail['qty'], $detail['qty'], $detail['qty'], $detail['product_id']]);
            }
            
            // Delete transfer details
            $deleteDetailsStmt = $conn->prepare("DELETE FROM tbl_transfer_dtl WHERE transfer_header_id = ?");
            $deleteDetailsStmt->execute([$transfer_header_id]);
            
            // Delete transfer header
            $deleteHeaderStmt = $conn->prepare("DELETE FROM tbl_transfer_header WHERE transfer_header_id = ?");
            $deleteHeaderStmt->execute([$transfer_header_id]);
            
            $conn->commit();
            echo json_encode(["success" => true, "message" => "Transfer deleted successfully. Quantities restored to source location."]);
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'get_batches':
        try {
            $stmt = $conn->prepare("
                SELECT 
                    b.batch_id,
                    b.batch,
                    b.entry_date,
                    b.entry_time,
                    b.entry_by,
                    b.order_no,
                    s.supplier_name,
                    l.location_name,
                    COUNT(p.product_id) as product_count,
                    SUM(p.quantity * p.unit_price) as total_value
                FROM tbl_batch b
                LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON b.location_id = l.location_id
                LEFT JOIN tbl_product p ON b.batch_id = p.batch_id
                WHERE b.batch IS NOT NULL AND b.batch != ''
                GROUP BY b.batch_id, b.batch, b.entry_date, b.entry_time, b.entry_by, b.order_no, s.supplier_name, l.location_name
                ORDER BY b.batch_id DESC
            ");
            $stmt->execute();
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $batches
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;
    
    case 'get_locations_for_filter':
        try {
            $stmt = $conn->prepare("
                SELECT DISTINCT location_name 
                FROM tbl_location 
                ORDER BY location_name
            ");
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $locations
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_products_by_location':
        try {
            $location_name = $data['location_name'] ?? '';
            
            if (empty($location_name)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Location name is required"
                ]);
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name,
                    batch.batch as batch_reference,
                    batch.entry_date,
                    batch.entry_by
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
                WHERE (p.status IS NULL OR p.status <> 'archived')
                AND l.location_name = ?
                ORDER BY p.product_name ASC
            ");
            $stmt->execute([$location_name]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $products
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'check_barcode':
        try {
            $barcode = $data['barcode'] ?? '';
            $location_name = $data['location_name'] ?? null;
            
            if (empty($barcode)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Barcode is required"
                ]);
                break;
            }
            
            $whereClause = "WHERE p.barcode = ?";
            $params = [$barcode];
            
            if ($location_name) {
                $whereClause .= " AND l.location_name = ?";
                $params[] = $location_name;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND (p.status IS NULL OR p.status <> 'archived')
                LIMIT 1
            ");
            $stmt->execute($params);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo json_encode([
                    "success" => true,
                    "product" => $product,
                    "message" => "Product found"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "product" => null,
                    "message" => "Product not found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "product" => null
            ]);
        }
        break;

    case 'update_product_stock':
        try {
            $product_id = $data['product_id'] ?? 0;
            $new_quantity = $data['new_quantity'] ?? 0;
            $batch_reference = $data['batch_reference'] ?? '';
            $expiration_date = $data['expiration_date'] ?? null;
            $unit_cost = $data['unit_cost'] ?? 0;
            $entry_by = $data['entry_by'] ?? 'admin';
            
            if ($product_id <= 0 || $new_quantity <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID or quantity"
                ]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Get current product details including current quantity
            $productStmt = $conn->prepare("
                SELECT product_name, category, barcode, description, prescription, bulk,
                       expiration, unit_price, brand_id, supplier_id, location_id, status, Variation, quantity
                FROM tbl_product 
                WHERE product_id = ?
                LIMIT 1
            ");
            $productStmt->execute([$product_id]);
            $productDetails = $productStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$productDetails) {
                throw new Exception("Product not found");
            }
            
            $old_quantity = $productDetails['quantity'];
            $quantity_change = $new_quantity; // This is the amount being added
            
            // Create batch record if batch reference is provided
            $batch_id = null;
            if ($batch_reference) {
                $batchStmt = $conn->prepare("
                    INSERT INTO tbl_batch (
                        batch, supplier_id, location_id, entry_date, entry_time, 
                        entry_by, order_no
                    ) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)
                ");
                $batchStmt->execute([$batch_reference, $productDetails['supplier_id'], $productDetails['location_id'], $entry_by, '']);
                $batch_id = $conn->lastInsertId();
            }
            
            // Update product quantity
            $updateStmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = quantity + ?,
                    stock_status = CASE 
                        WHEN quantity + ? <= 0 THEN 'out of stock'
                        WHEN quantity + ? <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END,
                    batch_id = COALESCE(?, batch_id),
                    expiration = COALESCE(?, expiration)
                WHERE product_id = ?
            ");
            $updateStmt->execute([$new_quantity, $new_quantity, $new_quantity, $batch_id, $expiration_date, $product_id]);
            
            // Create FIFO stock entry if batch_id is available
            if ($batch_id) {
                $fifoStmt = $conn->prepare("
                    INSERT INTO tbl_fifo_stock (
                        product_id, batch_id, batch_reference, quantity, available_quantity, unit_cost,
                        expiration_date, entry_date, entry_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)
                ");
                $fifoStmt->execute([
                    $product_id, $batch_id, $batch_reference, $new_quantity, $new_quantity,
                    $unit_cost, $expiration_date, $entry_by
                ]);
            }
            
            // Record the stock movement for tracking quantity changes
            $movementStmt = $conn->prepare("
                INSERT INTO tbl_stock_movements (
                    product_id, batch_id, movement_type, quantity, remaining_quantity,
                    unit_cost, expiration_date, reference_no, notes, created_by
                ) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, ?, ?)
            ");
            $movementStmt->execute([
                $product_id,
                $batch_id ?: $productDetails['batch_id'],
                $quantity_change,
                $old_quantity + $new_quantity,
                $unit_cost,
                $expiration_date,
                $batch_reference,
                "Stock added: +{$quantity_change} units. Old: {$old_quantity}, New: " . ($old_quantity + $new_quantity),
                $entry_by
            ]);
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Stock updated successfully with FIFO tracking"
            ]);
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_quantity_history':
        try {
            $product_id = $data['product_id'] ?? 0;
            
            if ($product_id <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID"
                ]);
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    sm.movement_id,
                    sm.movement_type,
                    sm.quantity as quantity_change,
                    sm.remaining_quantity,
                    sm.unit_cost,
                    sm.movement_date,
                    sm.reference_no,
                    sm.notes,
                    sm.created_by,
                    b.batch_reference,
                    b.entry_date as batch_date
                FROM tbl_stock_movements sm
                LEFT JOIN tbl_batch b ON sm.batch_id = b.batch_id
                WHERE sm.product_id = ?
                ORDER BY sm.movement_date DESC
                LIMIT 20
            ");
            $stmt->execute([$product_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $history
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_movement_history':
        try {
            $search = $data['search'] ?? '';
            $movement_type = $data['movement_type'] ?? 'all';
            $location = $data['location'] ?? 'all';
            $date_range = $data['date_range'] ?? 'all';
            
            // Build WHERE clause for filtering
            $whereConditions = [];
            $params = [];
            
            if ($search) {
                $whereConditions[] = "(p.product_name LIKE ? OR p.barcode LIKE ? OR e.Fname LIKE ? OR e.Lname LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($location !== 'all') {
                $whereConditions[] = "(sl.location_name = ? OR dl.location_name = ?)";
                $params[] = $location;
                $params[] = $location;
            }
            
            if ($date_range !== 'all') {
                switch ($date_range) {
                    case 'today':
                        $whereConditions[] = "DATE(th.date) = CURDATE()";
                        break;
                    case 'week':
                        $whereConditions[] = "th.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $whereConditions[] = "th.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                        break;
                }
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $stmt = $conn->prepare("
                SELECT 
                    th.transfer_header_id as id,
                    p.product_name,
                    p.barcode as productId,
                    'Transfer' as movementType,
                    td.qty as quantity,
                    sl.location_name as fromLocation,
                    dl.location_name as toLocation,
                    CONCAT(e.Fname, ' ', e.Lname) as movedBy,
                    th.date,
                    TIME(th.date) as time,
                    CASE 
                        WHEN th.status = '' OR th.status IS NULL THEN 'Completed'
                        WHEN th.status = 'pending' THEN 'Pending'
                        WHEN th.status = 'approved' THEN 'Completed'
                        WHEN th.status = 'rejected' THEN 'Cancelled'
                        ELSE th.status
                    END as status,
                    NULL as notes,
                    CONCAT('TR-', th.transfer_header_id) as reference,
                    p.category,
                    p.description,
                    p.unit_price,
                    b.brand
                FROM tbl_transfer_header th
                JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                JOIN tbl_product p ON td.product_id = p.product_id
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
                LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                $whereClause
                ORDER BY th.date DESC, th.transfer_header_id DESC
            ");
            $stmt->execute($params);
            $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $movements
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_fifo_stock':
        try {
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            
            if ($product_id <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID"
                ]);
                break;
            }
            
            // Query to get FIFO stock data for the product with batch dates
            $stmt = $conn->prepare("
                SELECT 
                    fs.fifo_id as summary_id,
                    fs.batch_id,
                    fs.batch_id as batch_number,
                    fs.batch_reference,
                    fs.available_quantity,
                    fs.unit_cost,
                    fs.expiration_date,
                    fs.quantity as total_quantity,
                    b.entry_date as batch_date,
                    b.entry_time as batch_time,
                    ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, fs.fifo_id ASC) as fifo_order,
                    CASE 
                        WHEN fs.expiration_date IS NULL THEN NULL
                        ELSE DATEDIFF(fs.expiration_date, CURDATE())
                    END as days_until_expiry
                FROM tbl_fifo_stock fs
                JOIN tbl_batch b ON fs.batch_id = b.batch_id
                WHERE fs.product_id = ? AND fs.available_quantity > 0
                ORDER BY b.entry_date ASC, fs.fifo_id ASC
            ");
            
            $stmt->execute([$product_id]);
            $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $fifoData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'consume_stock_fifo':
        try {
            $product_id = $data['product_id'] ?? 0;
            $quantity = $data['quantity'] ?? 0;
            $reference_no = $data['reference_no'] ?? '';
            $notes = $data['notes'] ?? '';
            $created_by = $data['created_by'] ?? 'admin';
            
            if ($product_id <= 0 || $quantity <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID or quantity"
                ]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Get FIFO stock data for the product
            $fifoStmt = $conn->prepare("
                SELECT 
                    fs.batch_id,
                    fs.batch_reference,
                    fs.available_quantity,
                    fs.unit_cost
                FROM tbl_fifo_stock fs
                JOIN tbl_batch b ON fs.batch_id = b.batch_id
                WHERE fs.product_id = ? AND fs.available_quantity > 0
                ORDER BY b.entry_date ASC, fs.fifo_id ASC
            ");
            $fifoStmt->execute([$product_id]);
            $fifoStock = $fifoStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($fifoStock)) {
                throw new Exception("No FIFO stock available for this product");
            }
            
            $remaining_quantity = $quantity;
            $consumed_batches = [];
            
            // Consume stock from FIFO order
            foreach ($fifoStock as $batch) {
                if ($remaining_quantity <= 0) break;
                
                $batch_quantity = min($remaining_quantity, $batch['available_quantity']);
                
                // Update FIFO stock
                $updateStmt = $conn->prepare("
                    UPDATE tbl_fifo_stock 
                    SET available_quantity = available_quantity - ?
                    WHERE batch_id = ? AND product_id = ?
                ");
                $updateStmt->execute([$batch_quantity, $batch['batch_id'], $product_id]);
                
                // Update main product quantity
                $productStmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity - ?,
                        stock_status = CASE 
                            WHEN quantity - ? <= 0 THEN 'out of stock'
                            WHEN quantity - ? <= 10 THEN 'low stock'
                            ELSE 'in stock'
                        END
                    WHERE product_id = ?
                ");
                $productStmt->execute([$batch_quantity, $batch_quantity, $batch_quantity, $product_id]);
                
                $consumed_batches[] = [
                    'batch_reference' => $batch['batch_reference'],
                    'quantity' => $batch_quantity,
                    'unit_cost' => $batch['unit_cost']
                ];
                
                $remaining_quantity -= $batch_quantity;
            }
            
            if ($remaining_quantity > 0) {
                throw new Exception("Insufficient stock available. Only " . ($quantity - $remaining_quantity) . " units consumed.");
            }
            
            // Log the consumption
            $logStmt = $conn->prepare("
                INSERT INTO tbl_stock_consumption (
                    product_id, quantity, reference_no, notes, created_by, consumed_date
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$product_id, $quantity, $reference_no, $notes, $created_by]);
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Stock consumed successfully using FIFO method",
                "consumed_batches" => $consumed_batches
            ]);
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_expiring_products':
        try {
            $days_threshold = $data['days_threshold'] ?? 30;
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.barcode,
                    p.category,
                    p.quantity,
                    p.unit_price,
                    b.brand,
                    s.supplier_name,
                    p.expiration,
                    DATEDIFF(p.expiration, CURDATE()) as days_until_expiry
                FROM tbl_product p
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                WHERE p.expiration IS NOT NULL 
                AND p.expiration >= CURDATE()
                AND DATEDIFF(p.expiration, CURDATE()) <= ?
                AND (p.status IS NULL OR p.status <> 'archived')
                ORDER BY p.expiration ASC
            ");
            
            $stmt->execute([$days_threshold]);
            $expiringProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $expiringProducts
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    // Inventory Dashboard Actions
    case 'get_inventory_kpis':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Get main KPIs
            $stmt = $conn->prepare("
                SELECT 
                    SUM(p.quantity) as physicalAvailable,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhandInventory,
                    COUNT(CASE WHEN p.quantity <= 10 THEN 1 END) as newOrderLineQty,
                    COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) as returned,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) * 100.0 / COUNT(*), 2) as returnRate,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'in stock' THEN 1 END) * 100.0 / COUNT(*), 2) as sellRate,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as outOfStock
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
            ");
            $stmt->execute($params);
            $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($kpis);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_supply_by_product':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhand,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.product_name
                ORDER BY onhand DESC
                LIMIT 11
            ");
            $stmt->execute($params);
            $supplyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($supplyData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_supply_by_location':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    l.location_name as location,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhand,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY l.location_name
                ORDER BY onhand DESC
            ");
            $stmt->execute($params);
            $supplyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($supplyData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_return_rate_by_product':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) * 100.0 / COUNT(*), 1) as returnRate
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.product_name
                HAVING returnRate > 0
                ORDER BY returnRate DESC
                LIMIT 12
            ");
            $stmt->execute($params);
            $returnData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($returnData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_stockout_items':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    -p.quantity as stockout
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND p.stock_status = 'out of stock'
                ORDER BY stockout ASC
                LIMIT 15
            ");
            $stmt->execute($params);
            $stockoutData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stockoutData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_product_kpis':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as physicalAvailable,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhandInventory,
                    COUNT(CASE WHEN p.quantity <= 10 THEN 1 END) as newOrderLineQty,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'out of stock' THEN 1 END) * 100.0 / COUNT(*), 1) as returnRate,
                    ROUND(COUNT(CASE WHEN p.stock_status = 'in stock' THEN 1 END) * 100.0 / COUNT(*), 1) as sellRate,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as outOfStock
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.product_name
                ORDER BY physicalAvailable DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $productKPIs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($productKPIs);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    // Warehouse-specific API endpoints
    case 'get_warehouse_kpis':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            // Build WHERE conditions
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($location_filter && $location_filter !== 'Warehouse') {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            } else if ($location_filter === 'Warehouse') {
                // Only filter by warehouse if specifically requested
                $whereConditions[] = "p.location_id = 2";
            }
            // If no location filter or 'All' is selected, don't filter by location
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Get warehouse-specific KPIs using PDO
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT p.product_id) as totalProducts,
                    COUNT(DISTINCT s.supplier_id) as totalSuppliers,
                    ROUND(COUNT(DISTINCT p.product_id) * 100.0 / 1000, 1) as storageCapacity,
                    SUM(p.quantity * p.unit_price) as warehouseValue,
                    COUNT(CASE WHEN p.quantity <= 10 AND p.quantity > 0 THEN 1 END) as lowStockItems,
                    COUNT(CASE WHEN p.expiration IS NOT NULL AND p.expiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiringSoon,
                    COUNT(DISTINCT b.batch_id) as totalBatches,
                    COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as activeTransfers
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
                LEFT JOIN tbl_transfer_dtl td ON p.product_id = td.product_id
                LEFT JOIN tbl_transfer_header t ON td.transfer_header_id = t.transfer_header_id
                $whereClause
            ");
            $stmt->execute($params);
            $warehouseKPIs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($warehouseKPIs);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_warehouse_supply_by_product':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            // Build WHERE conditions
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($location_filter && $location_filter !== 'Warehouse') {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            } else {
                // Default to warehouse products only
                $whereConditions[] = "p.location_id = 2";
            }
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhand,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.product_name
                ORDER BY onhand DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $supplyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($supplyData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_warehouse_supply_by_location':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            // Always filter for warehouse products (location_id = 2) unless specific location is requested
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($location_filter && $location_filter !== 'Warehouse') {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            } else {
                // Default to warehouse products only
                $whereConditions[] = "p.location_id = 2";
            }
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    l.location_name as location,
                    SUM(CASE WHEN p.stock_status = 'in stock' THEN p.quantity ELSE 0 END) as onhand,
                    SUM(CASE WHEN p.stock_status = 'low stock' THEN p.quantity ELSE 0 END) as softReserved,
                    SUM(CASE WHEN p.stock_status = 'out of stock' THEN p.quantity ELSE 0 END) as returned
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY l.location_name
                ORDER BY onhand DESC
                LIMIT 8
            ");
            $stmt->execute($params);
            $supplyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($supplyData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_warehouse_stockout_items':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            // Always filter for warehouse products (location_id = 2) unless specific location is requested
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($location_filter && $location_filter !== 'Warehouse') {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            } else {
                // Default to warehouse products only
                $whereConditions[] = "p.location_id = 2";
            }
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    -p.quantity as stockout
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND p.stock_status = 'out of stock'
                ORDER BY stockout ASC
                LIMIT 12
            ");
            $stmt->execute($params);
            $stockoutData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stockoutData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_warehouse_product_kpis':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            // Always filter for warehouse products (location_id = 2) unless specific location is requested
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($location_filter && $location_filter !== 'Warehouse') {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            } else {
                // Default to warehouse products only
                $whereConditions[] = "p.location_id = 2";
            }
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    p.quantity,
                    p.unit_price,
                    s.supplier_name as supplier,
                    b.batch as batch,
                    p.status,
                    p.onhandInventory
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
                $whereClause
                ORDER BY p.quantity DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $productKPIs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($productKPIs);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    // Chart-specific API endpoints
    case 'get_top_products_by_quantity':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    p.quantity
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                ORDER BY p.quantity DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($topProducts);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_stock_distribution_by_category':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.category,
                    SUM(p.quantity) as quantity
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY p.category
                ORDER BY quantity DESC
                LIMIT 8
            ");
            $stmt->execute($params);
            $categoryDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($categoryDistribution);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_fast_moving_items_trend':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Generate sample trend data for fast-moving items
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $trendData = [];
            
            // Get top 3 products by quantity
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    p.quantity
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                ORDER BY p.quantity DESC
                LIMIT 3
            ");
            $stmt->execute($params);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($topProducts as $product) {
                foreach ($months as $month) {
                    $trendData[] = [
                        'product' => $product['product'],
                        'month' => $month,
                        'quantity' => rand(50, 200) // Sample trend data
                    ];
                }
            }
            
            echo json_encode($trendData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_critical_stock_alerts':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name as product,
                    p.quantity
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND p.quantity <= 10
                ORDER BY p.quantity ASC
                LIMIT 10
            ");
            $stmt->execute($params);
            $criticalAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($criticalAlerts);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_inventory_by_branch_category':
        try {
            $product_filter = isset($data['product']) && $data['product'] !== 'All' ? $data['product'] : null;
            $location_filter = isset($data['location']) && $data['location'] !== 'All' ? $data['location'] : null;
            
            $whereConditions = ["(p.status IS NULL OR p.status <> 'archived')"];
            $params = [];
            
            if ($product_filter) {
                $whereConditions[] = "p.category = ?";
                $params[] = $product_filter;
            }
            
            if ($location_filter) {
                $whereConditions[] = "l.location_name = ?";
                $params[] = $location_filter;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $stmt = $conn->prepare("
                SELECT 
                    l.location_name as location,
                    p.category,
                    SUM(p.quantity) as quantity
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                GROUP BY l.location_name, p.category
                ORDER BY l.location_name, quantity DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $branchCategoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($branchCategoryData);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_products_by_location_name':
        try {
            $location_name = $data['location_name'] ?? '';
            
            if (empty($location_name)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Location name is required"
                ]);
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name,
                    batch.batch as batch_reference,
                    batch.entry_date,
                    batch.entry_by,
                    COALESCE(p.date_added, CURDATE()) as date_added
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
                WHERE (p.status IS NULL OR p.status <> 'archived')
                AND l.location_name = ?
                ORDER BY p.product_name ASC
            ");
            $stmt->execute([$location_name]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $products
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_archived_products':
        try {
            $stmt = $conn->prepare("SELECT * FROM tbl_product WHERE status = 'inactive'");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                "success" => true,
                "data" => $products
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_reports_data':
        try {
            // Get inventory analytics data
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT p.product_id) as totalProducts,
                    COUNT(CASE WHEN p.quantity <= 10 AND p.quantity > 0 THEN 1 END) as lowStockItems,
                    COUNT(CASE WHEN p.quantity = 0 THEN 1 END) as outOfStockItems,
                    SUM(p.quantity * p.unit_price) as totalValue
                FROM tbl_product p
                WHERE (p.status IS NULL OR p.status <> 'archived')
            ");
            $stmt->execute();
            $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get top categories distribution
            $stmt = $conn->prepare("
                SELECT 
                    p.category as category_name,
                    COUNT(p.product_id) as product_count,
                    ROUND(COUNT(p.product_id) * 100.0 / (SELECT COUNT(*) FROM tbl_product WHERE (status IS NULL OR status <> 'archived')), 1) as percentage
                FROM tbl_product p
                WHERE (p.status IS NULL OR p.status <> 'archived')
                GROUP BY p.category
                ORDER BY product_count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $topCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent stock movements for reports
            $stmt = $conn->prepare("
                SELECT 
                    sm.movement_id,
                    p.product_name as title,
                    CASE 
                        WHEN sm.movement_type = 'IN' THEN 'Stock In Report'
                        WHEN sm.movement_type = 'OUT' THEN 'Stock Out Report'
                        ELSE 'Stock Adjustment Report'
                    END as type,
                    sm.created_by as generatedBy,
                    DATE(sm.movement_date) as date,
                    TIME(sm.movement_date) as time,
                    'Completed' as status,
                    CONCAT(ROUND(RAND() * 5 + 0.5, 1), ' MB') as fileSize,
                    CASE WHEN RAND() > 0.5 THEN 'PDF' ELSE 'Excel' END as format,
                    CONCAT(
                        CASE 
                            WHEN sm.movement_type = 'IN' THEN 'Stock received'
                            WHEN sm.movement_type = 'OUT' THEN 'Stock consumed'
                            ELSE 'Stock adjusted'
                        END,
                        ' - ', p.product_name, ' (', sm.quantity, ' units)'
                    ) as description
                FROM tbl_stock_movements sm
                JOIN tbl_product p ON sm.product_id = p.product_id
                ORDER BY sm.movement_date DESC
                LIMIT 20
            ");
            $stmt->execute();
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get transfer reports
            $stmt = $conn->prepare("
                SELECT 
                    th.transfer_header_id as movement_id,
                    CONCAT('Transfer Report #', th.transfer_header_id) as title,
                    'Transfer Report' as type,
                    'System' as generatedBy,
                    th.date,
                    '12:00 PM' as time,
                    CASE 
                        WHEN th.status = 'approved' THEN 'Completed'
                        WHEN th.status = 'pending' THEN 'In Progress'
                        ELSE 'Failed'
                    END as status,
                    CONCAT(ROUND(RAND() * 3 + 0.5, 1), ' MB') as fileSize,
                    'PDF' as format,
                    CONCAT(
                        'Transfer from ', 
                        (SELECT location_name FROM tbl_location WHERE location_id = th.source_location_id),
                        ' to ',
                        (SELECT location_name FROM tbl_location WHERE location_id = th.destination_location_id),
                        ' - ', COUNT(td.product_id), ' products'
                    ) as description
                FROM tbl_transfer_header th
                LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                GROUP BY th.transfer_header_id
                ORDER BY th.date DESC
                LIMIT 10
            ");
            $stmt->execute();
            $transferReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Combine all reports
            $allReports = array_merge($reports, $transferReports);
            
            // Sort by date (newest first)
            usort($allReports, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            echo json_encode([
                "success" => true,
                "analytics" => $analytics,
                "topCategories" => $topCategories,
                "reports" => $allReports
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_inventory_summary_report':
        try {
            $location_id = $data['location_id'] ?? null;
            
            $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
            $params = [];
            
            if ($location_id) {
                $whereClause .= " AND p.location_id = ?";
                $params[] = $location_id;
            }

            $stmt = $conn->prepare("
                SELECT 
                    p.product_name,
                    p.barcode,
                    p.quantity,
                    p.unit_price,
                    p.stock_status,
                    p.category as category_name,
                    b.brand,
                    l.location_name,
                    s.supplier_name,
                    p.expiration,
                    (p.quantity * p.unit_price) as total_value
                FROM tbl_product p
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                $whereClause
                ORDER BY p.product_name
            ");
            $stmt->execute($params);
            $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true,
                "data" => $inventoryData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_low_stock_report':
        try {
            $threshold = $data['threshold'] ?? 10;
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name,
                    p.barcode,
                    p.quantity,
                    p.unit_price,
                    c.category_name,
                    b.brand,
                    l.location_name,
                    s.supplier_name,
                    s.supplier_contact,
                    s.supplier_email,
                    (p.quantity * p.unit_price) as total_value
                FROM tbl_product p
                LEFT JOIN tbl_category c ON p.category_id = c.category_id
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                WHERE (p.status IS NULL OR p.status <> 'archived')
                AND p.quantity <= ? AND p.quantity > 0
                ORDER BY p.quantity ASC
            ");
            $stmt->execute([$threshold]);
            $lowStockData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true,
                "data" => $lowStockData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_expiry_report':
        try {
            $days_threshold = $data['days_threshold'] ?? 30;
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_name,
                    p.barcode,
                    p.quantity,
                    p.expiration,
                    DATEDIFF(p.expiration, CURDATE()) as days_until_expiry,
                    c.category_name,
                    b.brand,
                    l.location_name,
                    (p.quantity * p.unit_price) as total_value
                FROM tbl_product p
                LEFT JOIN tbl_category c ON p.category_id = c.category_id
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                WHERE (p.status IS NULL OR p.status <> 'archived')
                AND p.expiration IS NOT NULL
                AND p.expiration <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND p.quantity > 0
                ORDER BY p.expiration ASC
            ");
            $stmt->execute([$days_threshold]);
            $expiryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true,
                "data" => $expiryData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_movement_history_report':
        try {
            $start_date = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $data['end_date'] ?? date('Y-m-d');
            $movement_type = $data['movement_type'] ?? null;
            
            $whereConditions = ["sm.movement_date BETWEEN ? AND ?"];
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            
            if ($movement_type) {
                $whereConditions[] = "sm.movement_type = ?";
                $params[] = $movement_type;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            $stmt = $conn->prepare("
                SELECT 
                    sm.movement_id,
                    p.product_name,
                    p.barcode,
                    sm.movement_type,
                    sm.quantity,
                    sm.unit_cost,
                    sm.expiration_date,
                    sm.movement_date,
                    sm.reference_no,
                    sm.notes,
                    sm.created_by,
                    l.location_name,
                    (sm.quantity * sm.unit_cost) as total_cost
                FROM tbl_stock_movements sm
                JOIN tbl_product p ON sm.product_id = p.product_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                ORDER BY sm.movement_date DESC
            ");
            $stmt->execute($params);
            $movementData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true,
                "data" => $movementData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'reset_password':
        try {
            $emp_id = isset($data['emp_id']) ? (int)$data['emp_id'] : 0;
            $new_password = isset($data['new_password']) ? trim($data['new_password']) : '';

            // Validation
            if (empty($emp_id) || $emp_id <= 0) {
                echo json_encode(["success" => false, "message" => "Invalid employee ID."]);
                exit;
            }

            if (empty($new_password) || strlen($new_password) < 3) {
                echo json_encode(["success" => false, "message" => "Password must be at least 3 characters long."]);
                exit;
            }

            // Check if employee exists
            $checkStmt = $conn->prepare("SELECT emp_id FROM tbl_employee WHERE emp_id = :emp_id");
            $checkStmt->bindParam(":emp_id", $emp_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() === 0) {
                echo json_encode(["success" => false, "message" => "Employee not found."]);
                exit;
            }

            // Hash the new password
            $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password
            $updateStmt = $conn->prepare("UPDATE tbl_employee SET password = :password WHERE emp_id = :emp_id");
            $updateStmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
            $updateStmt->bindParam(":emp_id", $emp_id, PDO::PARAM_INT);

            if ($updateStmt->execute()) {
                echo json_encode([
                    "success" => true, 
                    "message" => "Password reset successfully.",
                    "emp_id" => $emp_id
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update password."]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action: " . $action]);
        break;

        case 'deleteSupplier':
            // Log raw input
            $rawInput = file_get_contents('php://input');
            error_log("Raw Input: " . $rawInput);
        
            // Decode JSON
            $input = json_decode($rawInput, true);
        
            // Log decoded input
            error_log("Decoded Input: " . print_r($input, true));
        
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid JSON received",
                    "error" => json_last_error_msg()
                ]);
                exit;
            }
        
            if (!isset($input['action'])) {
                echo json_encode(["success" => false, "message" => "Missing action"]);
                exit;
            }
        
            if (!isset($input['supplier_id'])) {
                echo json_encode(["success" => false, "message" => "Missing supplier_id"]);
                exit;
            }
        
            $supplier_id = intval($input['supplier_id']);
            if ($supplier_id <= 0) {
                echo json_encode(["success" => false, "message" => "Invalid supplier ID"]);
                exit;
            }
        
            $stmt = $conn->prepare("UPDATE tbl_supplier SET deleted_at = NOW() WHERE supplier_id = ?");
            
            try {
                if ($stmt->execute([$supplier_id])) {
                    echo json_encode(["success" => true, "message" => "Supplier archived"]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Failed to archive supplier"
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "An error occurred: " . $e->getMessage()
                ]);
            }
            break;

        case 'get_discounts':
            try {
                $stmt = $conn->prepare("SELECT discount_id, discount_rate, discount_type FROM tbl_discount ORDER BY discount_id ASC");
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Normalize numeric rate
                foreach ($rows as &$r) {
                    $r['discount_rate'] = (float)$r['discount_rate'];
                }
                echo json_encode([ 'success' => true, 'data' => $rows ]);
            } catch (Exception $e) {
                echo json_encode([ 'success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => [] ]);
            }
            break;

        case 'save_pos_sale':
            try {
                // Expected payload: transactionId, totalAmount, referenceNumber, terminalName, items:[{product_id, quantity, price}]
                $transactionId = $data['transactionId'] ?? null;
                $totalAmount = isset($data['totalAmount']) ? (float)$data['totalAmount'] : 0.0;
                $referenceNumber = $data['referenceNumber'] ?? null;
                $terminalName = trim($data['terminalName'] ?? 'Convenience POS');
                $items = $data['items'] ?? [];

                if (!$transactionId || $totalAmount <= 0 || !is_array($items) || count($items) === 0) {
                    echo json_encode([ 'success' => false, 'message' => 'Invalid sale payload' ]);
                    break;
                }

                $conn->beginTransaction();

                // Ensure terminal exists or create it
                $stmt = $conn->prepare("SELECT terminal_id FROM tbl_pos_terminal WHERE terminal_name = :name LIMIT 1");
                $stmt->execute([ ':name' => $terminalName ]);
                $terminalId = $stmt->fetchColumn();
                if (!$terminalId) {
                    $ins = $conn->prepare("INSERT INTO tbl_pos_terminal (terminal_name, shift_id) VALUES (:name, NULL)");
                    $ins->execute([ ':name' => $terminalName ]);
                    $terminalId = (int)$conn->lastInsertId();
                }

                // Insert sales header
                $hdr = $conn->prepare("INSERT INTO tbl_pos_sales_header (transaction_id, total_amount, reference_number, terminal_id) VALUES (:txn, :total, :ref, :terminal)");
                $hdr->execute([
                    ':txn' => $transactionId,
                    ':total' => $totalAmount,
                    ':ref' => $referenceNumber,
                    ':terminal' => $terminalId,
                ]);
                $salesHeaderId = (int)$conn->lastInsertId();

                // Insert details
                $dtl = $conn->prepare("INSERT INTO tbl_pos_sales_details (sales_header_id, product_id, quantity, price) VALUES (:hdr, :pid, :qty, :price)");
                foreach ($items as $it) {
                    $pid = isset($it['product_id']) ? (int)$it['product_id'] : (isset($it['id']) ? (int)$it['id'] : 0);
                    $qty = (int)($it['quantity'] ?? 0);
                    $price = (float)($it['price'] ?? 0);
                    if ($pid > 0 && $qty > 0) {
                        $dtl->execute([
                            ':hdr' => $salesHeaderId,
                            ':pid' => $pid,
                            ':qty' => $qty,
                            ':price' => $price,
                        ]);
                    }
                }

                $conn->commit();
                echo json_encode([ 'success' => true, 'message' => 'Sale saved', 'data' => [ 'sales_header_id' => $salesHeaderId, 'terminal_id' => $terminalId ] ]);
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                echo json_encode([ 'success' => false, 'message' => 'Database error: ' . $e->getMessage() ]);
            }
            break;

        case 'save_pos_transaction':
            try {
                $transactionId = $data['transactionId'] ?? null;
                $paymentTypeRaw = trim((string)($data['paymentType'] ?? ''));
                $paymentType = '';
                $pt = strtolower($paymentTypeRaw);
                if ($pt === 'cash') $paymentType = 'Cash';
                elseif ($pt === 'gcash' || $pt === 'g-cash' || $pt === 'g cash') $paymentType = 'GCash';
                else $paymentType = $paymentTypeRaw;

                // Prefer session user_id; fall back to provided empId
                if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
                $empId = $_SESSION['user_id'] ?? ($data['empId'] ?? null);
                $customerId = $data['customerId'] ?? null;

                if (!$transactionId || !$paymentType) {
                    echo json_encode([ 'success' => false, 'message' => 'Invalid transaction payload' ]);
                    break;
                }

                $stmt = $conn->prepare("INSERT INTO tbl_pos_transaction (transaction_id, date, time, emp_id, customer_id, payment_type) VALUES (:txn, CURDATE(), CURTIME(), :emp, :cust, :ptype)");
                $stmt->execute([
                    ':txn' => $transactionId,
                    ':emp' => $empId,
                    ':cust' => $customerId,
                    ':ptype' => $paymentType,
                ]);

                echo json_encode([ 'success' => true ]);
            } catch (Exception $e) {
                echo json_encode([ 'success' => false, 'message' => 'Database error: ' . $e->getMessage() ]);
            }
            break;
        
           case 'restoreSupplier':
            $data = json_decode(file_get_contents('php://input'), true);
        
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid JSON input"
                ]);
                exit;
            }
        
            $supplier_id = intval($data['supplier_id'] ?? 0);
            if ($supplier_id <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Missing or invalid supplier ID"
                ]);
                exit;
            }
        
            try {
                $stmt = $conn->prepare("UPDATE tbl_supplier SET deleted_at = NULL WHERE supplier_id = ?");
                if ($stmt->execute([$supplier_id])) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Supplier restored"
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Error restoring supplier"
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Error restoring supplier",
                    "error" => $e->getMessage()
                ]);
            }
            break;
        
            case 'displayArchivedSuppliers':
            try {
                $stmt = $conn->query("SELECT * FROM tbl_supplier WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
                $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "suppliers" => $suppliers]);
            } catch (Exception $e) {
                echo json_encode(["success" => false, "message" => "Error fetching archived suppliers: " . $e->getMessage()]);
            }
            break;

        case 'get_archived_items':
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        archive_id as id,
                        item_name as name,
                        item_description as description,
                        item_type as type,
                        category,
                        archived_by,
                        DATE(archived_date) as archivedDate,
                        TIME(archived_time) as archivedTime,
                        reason,
                        status,
                        original_data
                    FROM tbl_archive 
                    ORDER BY archived_date DESC, archived_time DESC
                ");
                $stmt->execute();
                $archivedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    "success" => true,
                    "data" => $archivedItems
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $e->getMessage(),
                    "data" => []
                ]);
            }
            break;

        case 'restore_archived_item':
            try {
                $archive_id = $data['id'] ?? 0;
                
                if (!$archive_id) {
                    echo json_encode(["success" => false, "message" => "Archive ID is required"]);
                    break;
                }

                // Get archived item details
                $stmt = $conn->prepare("SELECT * FROM tbl_archive WHERE archive_id = ?");
                $stmt->execute([$archive_id]);
                $archivedItem = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$archivedItem) {
                    echo json_encode(["success" => false, "message" => "Archived item not found"]);
                    break;
                }

                $conn->beginTransaction();

                try {
                    // Restore based on item type
                    switch ($archivedItem['item_type']) {
                        case 'Product':
                            // Restore product
                            $stmt = $conn->prepare("UPDATE tbl_product SET status = 'active' WHERE product_id = ?");
                            $stmt->execute([$archivedItem['item_id']]);
                            break;
                        case 'Supplier':
                            // Restore supplier
                            $stmt = $conn->prepare("UPDATE tbl_supplier SET status = 'active' WHERE supplier_id = ?");
                            $stmt->execute([$archivedItem['item_id']]);
                            break;
                        case 'Category':
                            // Restore category
                            $stmt = $conn->prepare("UPDATE tbl_category SET status = 'active' WHERE category_id = ?");
                            $stmt->execute([$archivedItem['item_id']]);
                            break;
                    }

                    // Update archive status
                    $stmt = $conn->prepare("UPDATE tbl_archive SET status = 'Restored' WHERE archive_id = ?");
                    $stmt->execute([$archive_id]);

                    $conn->commit();
                    echo json_encode(["success" => true, "message" => "Item restored successfully"]);
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
            } catch (Exception $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;

        case 'delete_archived_item':
            try {
                $archive_id = $data['id'] ?? 0;
                
                if (!$archive_id) {
                    echo json_encode(["success" => false, "message" => "Archive ID is required"]);
                    break;
                }

                // Update archive status to deleted
                $stmt = $conn->prepare("UPDATE tbl_archive SET status = 'Deleted' WHERE archive_id = ?");
                $stmt->execute([$archive_id]);
                
                echo json_encode(["success" => true, "message" => "Item permanently deleted"]);
            } catch (Exception $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;

        case 'get_transfer_log':
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        tl.transfer_id,
                        tl.product_id,
                        p.product_name,
                        tl.from_location,
                        tl.to_location,
                        tl.quantity,
                        tl.transfer_date,
                        tl.created_at
                    FROM tbl_transfer_log tl
                    LEFT JOIN tbl_product p ON tl.product_id = p.product_id
                    ORDER BY tl.created_at DESC
                ");
                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "success" => true,
                    "data" => $logs
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $e->getMessage(),
                    "data" => []
                ]);
            }
            break;

        case 'get_current_user':
            try {
                session_start();
                
                if (isset($_SESSION['user_id']) && isset($_SESSION['full_name'])) {
                    echo json_encode([
                        "success" => true,
                        "data" => [
                            "user_id" => $_SESSION['user_id'],
                            "username" => $_SESSION['username'] ?? '',
                            "full_name" => $_SESSION['full_name'],
                            "role" => $_SESSION['role'] ?? ''
                        ]
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "No active session found"
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Session error: " . $e->getMessage()
                ]);
            }
            break;
         
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}

// Flush the output buffer to ensure clean JSON response
ob_end_flush();
?>  