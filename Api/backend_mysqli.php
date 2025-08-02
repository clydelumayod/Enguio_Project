<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection using mysqli
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'login':
        try {
            $username = isset($data['username']) ? trim($data['username']) : '';
            $password = isset($data['password']) ? trim($data['password']) : '';
            $captcha = isset($data['captcha']) ? trim($data['captcha']) : '';
            $captchaAnswer = isset($data['captchaAnswer']) ? trim($data['captchaAnswer']) : '';

            // Validate inputs
            if (empty($username) || empty($password)) {
                echo json_encode(["success" => false, "message" => "Username and password are required"]);
                break;
            }

            // Verify captcha
            if (empty($captcha) || empty($captchaAnswer) || $captcha !== $captchaAnswer) {
                echo json_encode(["success" => false, "message" => "Invalid captcha"]);
                break;
            }

            // Check if user exists and is active
            $stmt = $conn->prepare("
                SELECT e.emp_id, e.username, e.password, e.status, e.Fname, e.Lname, r.role 
                FROM tbl_employee e 
                JOIN tbl_role r ON e.role_id = r.role_id 
                WHERE e.username = ? AND e.status = 'Active'
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

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

                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "role" => $user['role'],
                    "user_id" => $user['emp_id'],
                    "full_name" => $user['Fname'] . ' ' . $user['Lname']
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid username or password"]);
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
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

    case 'update_product_stock':
        try {
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $new_quantity = isset($data['new_quantity']) ? intval($data['new_quantity']) : 0;
            $batch_reference = isset($data['batch_reference']) ? trim($data['batch_reference']) : '';
            $expiration_date = isset($data['expiration_date']) ? trim($data['expiration_date']) : null;
            $unit_cost = isset($data['unit_cost']) ? floatval($data['unit_cost']) : 0;
            $new_srp = isset($data['new_srp']) ? floatval($data['new_srp']) : null;
            $entry_by = isset($data['entry_by']) ? trim($data['entry_by']) : 'admin';
            
            if ($product_id <= 0 || $new_quantity <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID or quantity"
                ]);
                break;
            }
            
            // Get current product details
            $stmt = $conn->prepare("SELECT quantity, current_quantity, unit_price, location_id FROM tbl_product WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if (!$product) {
                echo json_encode([
                    "success" => false,
                    "message" => "Product not found"
                ]);
                break;
            }
            
            // Start transaction
            $conn->autocommit(false);
            
            // Create new batch record for the additional stock
            $batch_id = null;
            if ($batch_reference) {
                $batchStmt = $conn->prepare("
                    INSERT INTO tbl_batch (
                        batch, supplier_id, location_id, entry_date, entry_time, 
                        entry_by, order_no
                    ) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)
                ");
                $batchStmt->bind_param("siiss", $batch_reference, 13, $product['location_id'], $entry_by, '');
                $batchStmt->execute();
                $batch_id = $conn->insert_id;
            }
            
            // Calculate new current_quantity - new stock goes ONLY to current_quantity
            $existing_quantity = intval($product['quantity']);
            $current_quantity = intval($product['current_quantity'] ?? 0);
            $new_current_quantity = $current_quantity + $new_quantity;
            // Keep the main quantity unchanged - only update current_quantity
            $total_quantity = $existing_quantity; // Don't add to main quantity
            
            // Update product current_quantity and SRP if provided
            if ($new_srp !== null) {
                $updateSql = "UPDATE tbl_product SET current_quantity = ?, stock_status = CASE WHEN ? <= 0 THEN 'out of stock' WHEN ? <= 10 THEN 'low stock' ELSE 'in stock' END, srp = ? WHERE product_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("iiidi", $new_current_quantity, $total_quantity, $total_quantity, $new_srp, $product_id);
            } else {
                $updateSql = "UPDATE tbl_product SET current_quantity = ?, stock_status = CASE WHEN ? <= 0 THEN 'out of stock' WHEN ? <= 10 THEN 'low stock' ELSE 'in stock' END WHERE product_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("iiii", $new_current_quantity, $total_quantity, $total_quantity, $product_id);
            }
            
            if ($updateStmt->execute()) {
                
                // FIFO: Create stock movement record for additional stock
                if ($batch_id && $new_quantity > 0) {
                    $movementStmt = $conn->prepare("
                        INSERT INTO tbl_stock_movements (
                            product_id, batch_id, movement_type, quantity, remaining_quantity,
                            unit_cost, expiration_date, reference_no, created_by
                        ) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, ?)
                    ");
                    $movementStmt->bind_param("iiiddsss", 
                        $product_id, $batch_id, $new_quantity, $new_quantity, 
                        $unit_cost ?: $product['unit_price'], $expiration_date, $batch_reference, $entry_by
                    );
                    $movementStmt->execute();
                    
                    // Update stock summary record
                    $summaryStmt = $conn->prepare("
                        INSERT INTO tbl_stock_summary (
                            product_id, batch_id, available_quantity, unit_cost, 
                            expiration_date, batch_reference, total_quantity
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            available_quantity = available_quantity + VALUES(available_quantity),
                            total_quantity = total_quantity + VALUES(total_quantity),
                            last_updated = CURRENT_TIMESTAMP
                    ");
                    $summaryStmt->bind_param("iiddssi", 
                        $product_id, $batch_id, $new_quantity, 
                        $unit_cost ?: $product['unit_price'], $expiration_date, 
                        $batch_reference, $new_quantity
                    );
                    $summaryStmt->execute();
                }
                
                $conn->commit();
                echo json_encode([
                    "success" => true,
                    "message" => "Stock added to current_quantity successfully with FIFO tracking",
                    "old_current_quantity" => $current_quantity,
                    "new_current_quantity" => $new_current_quantity,
                    "added_quantity" => $new_quantity,
                    "main_quantity" => $total_quantity,
                    "batch_id" => $batch_id
                ]);
            } else {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to update stock"
                ]);
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

    case 'get_transfers_with_details':
        try {
            // Get transfer headers with location and employee info
            $sql = "
                SELECT 
                    th.transfer_header_id,
                    th.date,
                    CASE 
                        WHEN th.status = '' OR th.status IS NULL THEN 'Completed'
                        WHEN th.status = 'pending' THEN 'New'
                        WHEN th.status = 'approved' THEN 'Completed'
                        WHEN th.status = 'rejected' THEN 'Cancelled'
                        ELSE th.status
                    END as status,
                    th.note,
                    sl.location_name as source_location_name,
                    dl.location_name as destination_location_name,
                    CONCAT(e.Fname, ' ', e.Lname) as employee_name,
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
            ";
            
            $result = $conn->query($sql);
            $transfers = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $transfer = $row;
                    
                    // Get products for this transfer
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
                    $stmt2->bind_param("i", $row['transfer_header_id']);
                    $stmt2->execute();
                    $products_result = $stmt2->get_result();
                    
                    $products = [];
                    while ($product = $products_result->fetch_assoc()) {
                        $products[] = $product;
                    }
                    
                    $transfer['products'] = $products;
                    $transfers[] = $transfer;
                }
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
            $status = $data['status'] ?? 'approved';
            $products = $data['products'] ?? [];
            
            if (empty($products)) {
                echo json_encode(["success" => false, "message" => "No products to transfer"]);
                break;
            }
            
            // Start transaction
            $conn->autocommit(false);
            
            // Insert transfer header
            $stmt = $conn->prepare("
                INSERT INTO tbl_transfer_header (
                    source_location_id, destination_location_id, employee_id, 
                    status, date
                ) VALUES (?, ?, ?, ?, CURDATE())
            ");
            $stmt->bind_param("iiis", $source_location_id, $destination_location_id, $employee_id, $status);
            $stmt->execute();
            $transfer_header_id = $conn->insert_id;
            
            // Insert transfer details and log each transfer
            $stmt2 = $conn->prepare("
                INSERT INTO tbl_transfer_dtl (
                    transfer_header_id, product_id, qty
                ) VALUES (?, ?, ?)
            ");
            
            $logStmt = $conn->prepare("
                INSERT INTO tbl_transfer_log (
                    transfer_id, product_id, from_location, to_location, quantity, transfer_date
                ) VALUES (?, ?, ?, ?, ?, CURDATE())
            ");
            
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $transfer_qty = $product['quantity'];
                
                // Insert transfer detail
                $stmt2->bind_param("iii", $transfer_header_id, $product_id, $transfer_qty);
                $stmt2->execute();
                
                // Get location names for logging
                $sourceLocStmt = $conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = ?");
                $sourceLocStmt->bind_param("i", $source_location_id);
                $sourceLocStmt->execute();
                $sourceLocResult = $sourceLocStmt->get_result();
                $sourceLocation = $sourceLocResult->fetch_assoc();
                
                $destLocStmt = $conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = ?");
                $destLocStmt->bind_param("i", $destination_location_id);
                $destLocStmt->execute();
                $destLocResult = $destLocStmt->get_result();
                $destLocation = $destLocResult->fetch_assoc();
                
                // Log the transfer
                $logStmt->bind_param("iissi", 
                    $transfer_header_id, 
                    $product_id, 
                    $sourceLocation['location_name'], 
                    $destLocation['location_name'], 
                    $transfer_qty
                );
                $logStmt->execute();
            }
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Transfer created successfully with logging",
                "transfer_id" => $transfer_header_id,
                "products_transferred" => count($products)
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
            $sql = "SELECT location_id, location_name, status FROM tbl_location WHERE status = 'active' ORDER BY location_name";
            $result = $conn->query($sql);
            $locations = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            
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
            $sql = "SELECT emp_id, CONCAT(Fname, ' ', Lname) as name FROM tbl_employee WHERE status = 'Active' ORDER BY Fname, Lname";
            $result = $conn->query($sql);
            $staff = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $staff[] = $row;
                }
            }
            
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
        
    case 'get_products':
        try {
            $location_id = $data['location_id'] ?? null;
            $for_transfer = $data['for_transfer'] ?? false;
            
            // If this is for transfer, use FIFO logic to prioritize older batches
            if ($for_transfer) {
                $sql = "
                    SELECT 
                        p.product_id,
                        p.product_name,
                        p.category,
                        p.barcode,
                        p.description,
                        p.quantity as total_quantity,
                        p.current_quantity,
                        p.unit_price as srp,
                        p.Variation,
                        p.brand_id,
                        p.supplier_id,
                        p.batch_id,
                        p.expiration,
                        b.brand,
                        s.supplier_name,
                        bt.batch as batch_reference,
                        bt.order_no,
                        -- Get old quantity (first batch quantity)
                        COALESCE((
                            SELECT ss2.available_quantity 
                            FROM tbl_stock_summary ss2 
                            JOIN tbl_batch bt2 ON ss2.batch_id = bt2.batch_id
                            WHERE ss2.product_id = p.product_id 
                            ORDER BY bt2.entry_date ASC
                            LIMIT 1
                        ), 0) as old_quantity
                    FROM tbl_product p
                    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                    LEFT JOIN tbl_batch bt ON p.batch_id = bt.batch_id
                    LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id AND p.batch_id = ss.batch_id
                    WHERE p.status = 'active' AND p.quantity > 0
                ";
                
                if ($location_id) {
                    $sql .= " AND p.location_id = " . intval($location_id);
                }
                
                // Order by FIFO: earliest entry date first, then by expiration date
                $sql .= " ORDER BY 
                    bt.entry_date ASC, 
                    p.expiration ASC,
                    p.product_name ASC";
            } else {
                // Regular product listing (non-transfer)
                $sql = "
                    SELECT 
                        p.product_id,
                        p.product_name,
                        p.category,
                        p.barcode,
                        p.description,
                        p.quantity,
                        p.current_quantity,
                        p.unit_price as srp,
                        p.Variation,
                        p.brand_id,
                        p.supplier_id,
                        p.batch_id,
                        b.brand,
                        s.supplier_name
                    FROM tbl_product p
                    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                    WHERE p.status = 'active'
                ";
                
                if ($location_id) {
                    $sql .= " AND p.location_id = " . intval($location_id);
                }
                
                $sql .= " ORDER BY p.product_name";
            }
            
            $result = $conn->query($sql);
            $products = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
            echo json_encode([
                "success" => true,
                "data" => $products,
                "fifo_enabled" => $for_transfer
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;
        
    case 'get_transfer_log':
        try {
            $sql = "
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
            ";
            
            $result = $conn->query($sql);
            $logs = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $logs[] = $row;
                }
            }
            
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
        
    case 'populate_transfer_log':
        try {
            // Get all existing transfers and populate the log
            $sql = "
                SELECT 
                    th.transfer_header_id,
                    th.date,
                    sl.location_name as source_location,
                    dl.location_name as destination_location,
                    td.product_id,
                    td.qty
                FROM tbl_transfer_header th
                JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
                ORDER BY th.transfer_header_id, td.product_id
            ";
            
            $result = $conn->query($sql);
            $inserted = 0;
            
            if ($result->num_rows > 0) {
                $insertStmt = $conn->prepare("
                    INSERT INTO tbl_transfer_log (
                        transfer_id, product_id, from_location, to_location, quantity, transfer_date
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                while ($row = $result->fetch_assoc()) {
                    $insertStmt->bind_param("iissis", 
                        $row['transfer_header_id'],
                        $row['product_id'],
                        $row['source_location'],
                        $row['destination_location'],
                        $row['qty'],
                        $row['date']
                    );
                    $insertStmt->execute();
                    $inserted++;
                }
            }
            
            echo json_encode([
                "success" => true,
                "message" => "Transfer log populated with $inserted records",
                "records_inserted" => $inserted
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_suppliers':
        try {
            $sql = "SELECT * FROM tbl_supplier WHERE status != 'archived' OR status IS NULL ORDER BY supplier_id DESC";
            $result = $conn->query($sql);
            $suppliers = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $suppliers[] = $row;
                }
            }
            
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

    case 'get_batches':
        try {
            $sql = "SELECT * FROM tbl_batch ORDER BY batch_id DESC";
            $result = $conn->query($sql);
            $batches = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $batches[] = $row;
                }
            }
            
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

    case 'get_brands':
        try {
            $sql = "SELECT * FROM tbl_brand ORDER BY brand_id DESC";
            $result = $conn->query($sql);
            $brands = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $brands[] = $row;
                }
            }
            
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
            $sql = "SELECT * FROM tbl_category ORDER BY category_id DESC";
            $result = $conn->query($sql);
            $categories = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
            }
            
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

    case 'check_barcode':
        try {
            $barcode = $data['barcode'] ?? '';
            
            if (empty($barcode)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Barcode is required"
                ]);
                break;
            }
            
            $stmt = $conn->prepare("SELECT product_id, product_name FROM tbl_product WHERE barcode = ?");
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                echo json_encode([
                    "success" => true,
                    "exists" => true,
                    "product" => $product
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "exists" => false,
                    "product" => null
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'add_product':
        try {
            $product_name = $data['product_name'] ?? '';
            $category = $data['category'] ?? '';
            $barcode = $data['barcode'] ?? '';
            $description = $data['description'] ?? '';
            $unit_price = $data['unit_price'] ?? 0;
            $srp = $data['srp'] ?? $unit_price;
            $brand_id = $data['brand_id'] ?? 30;
            $quantity = $data['quantity'] ?? 0;
            $supplier_id = $data['supplier_id'] ?? 13;
            $expiration = $data['expiration'] ?? null;
            $prescription = $data['prescription'] ?? 0;
            $bulk = $data['bulk'] ?? 0;
            $location = $data['location'] ?? 'warehouse';
            $status = $data['status'] ?? 'active';
            $stock_status = $data['stock_status'] ?? 'in stock';
            $reference = $data['reference'] ?? '';
            $order_no = $data['order_no'] ?? '';
            
            // Get location_id based on location name
            $locationStmt = $conn->prepare("SELECT location_id FROM tbl_location WHERE location_name = ?");
            $locationStmt->bind_param("s", $location);
            $locationStmt->execute();
            $locationResult = $locationStmt->get_result();
            $locationData = $locationResult->fetch_assoc();
            $location_id = $locationData['location_id'] ?? 2; // Default to warehouse
            
            // Create batch record
            $batch_id = null;
            if ($reference) {
                $batchStmt = $conn->prepare("
                    INSERT INTO tbl_batch (
                        batch, supplier_id, location_id, entry_date, entry_time, 
                        entry_by, order_no
                    ) VALUES (?, ?, ?, CURDATE(), CURTIME(), 'admin', ?)
                ");
                $batchStmt->bind_param("siis", $reference, $supplier_id, $location_id, $order_no);
                $batchStmt->execute();
                $batch_id = $conn->insert_id;
            }
            
            // Insert product
            $stmt = $conn->prepare("
                INSERT INTO tbl_product (
                    product_name, category, barcode, description, unit_price, srp,
                    brand_id, quantity, supplier_id, expiration, prescription, bulk,
                    location_id, batch_id, status, stock_status, date_added
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->bind_param("ssssddiiissiiiss", 
                $product_name, $category, $barcode, $description, $unit_price, $srp,
                $brand_id, $quantity, $supplier_id, $expiration, $prescription, $bulk,
                $location_id, $batch_id, $status, $stock_status
            );
            
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                
                // Create stock summary record
                if ($batch_id && $quantity > 0) {
                    $summaryStmt = $conn->prepare("
                        INSERT INTO tbl_stock_summary (
                            product_id, batch_id, available_quantity, unit_cost, 
                            expiration_date, batch_reference, total_quantity
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $summaryStmt->bind_param("iiddssi", 
                        $product_id, $batch_id, $quantity, $unit_price, 
                        $expiration, $reference, $quantity
                    );
                    $summaryStmt->execute();
                }
                
                echo json_encode([
                    "success" => true,
                    "message" => "Product added successfully",
                    "product_id" => $product_id
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to add product"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
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

    case 'get_warehouse_kpis':
        try {
            $location = $data['location'] ?? 'warehouse';
            
            // Get location_id
            $locationStmt = $conn->prepare("SELECT location_id FROM tbl_location WHERE location_name = ?");
            $locationStmt->bind_param("s", $location);
            $locationStmt->execute();
            $locationResult = $locationStmt->get_result();
            $locationData = $locationResult->fetch_assoc();
            $location_id = $locationData['location_id'] ?? 2;
            
            // Calculate KPIs
            $kpiStmt = $conn->prepare("
                SELECT 
                    COUNT(*) as totalProducts,
                    SUM(quantity) as totalQuantity,
                    SUM(quantity * unit_price) as totalValue,
                    COUNT(CASE WHEN quantity <= 10 AND quantity > 0 THEN 1 END) as lowStockItems,
                    COUNT(CASE WHEN quantity = 0 THEN 1 END) as outOfStockItems
                FROM tbl_product 
                WHERE location_id = ? AND status = 'active'
            ");
            $kpiStmt->bind_param("i", $location_id);
            $kpiStmt->execute();
            $kpiResult = $kpiStmt->get_result();
            $kpis = $kpiResult->fetch_assoc();
            
            echo json_encode([
                "success" => true,
                "data" => $kpis
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;
        
    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid action: " . $action
        ]);
        break;
}

$conn->close();
?> 