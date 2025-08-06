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

    case 'create_fifo_transfer':
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
            
            $totalProductsTransferred = 0;
            
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $requested_qty = $product['quantity'];
                $remaining_qty = $requested_qty;
                
                // Get FIFO stock for this product at source location
                $fifoStmt = $conn->prepare("
                    SELECT 
                        ss.summary_id,
                        ss.batch_id,
                        ss.available_quantity,
                        ss.unit_cost,
                        ss.expiration_date,
                        b.entry_date,
                        b.batch_reference
                    FROM tbl_stock_summary ss
                    JOIN tbl_batch b ON ss.batch_id = b.batch_id
                    WHERE ss.product_id = ? 
                    AND ss.available_quantity > 0
                    ORDER BY b.entry_date ASC, ss.summary_id ASC
                ");
                $fifoStmt->bind_param("i", $product_id);
                $fifoStmt->execute();
                $fifoResult = $fifoStmt->get_result();
                
                $transferredFromBatches = [];
                
                // Process FIFO transfer
                while ($fifoRow = $fifoResult->fetch_assoc() && $remaining_qty > 0) {
                    $batch_available = $fifoRow['available_quantity'];
                    $transfer_from_batch = min($remaining_qty, $batch_available);
                    
                    if ($transfer_from_batch > 0) {
                        // Update stock summary - reduce from source
                        $updateStmt = $conn->prepare("
                            UPDATE tbl_stock_summary 
                            SET available_quantity = available_quantity - ? 
                            WHERE summary_id = ?
                        ");
                        $updateStmt->bind_param("ii", $transfer_from_batch, $fifoRow['summary_id']);
                        $updateStmt->execute();
                        
                        // Check if destination location already has this batch
                        $checkDestStmt = $conn->prepare("
                            SELECT summary_id, available_quantity 
                            FROM tbl_stock_summary 
                            WHERE product_id = ? AND batch_id = ?
                        ");
                        $checkDestStmt->bind_param("ii", $product_id, $fifoRow['batch_id']);
                        $checkDestStmt->execute();
                        $destResult = $checkDestStmt->get_result();
                        
                        if ($destResult->num_rows > 0) {
                            // Update existing batch at destination
                            $destRow = $destResult->fetch_assoc();
                            $updateDestStmt = $conn->prepare("
                                UPDATE tbl_stock_summary 
                                SET available_quantity = available_quantity + ? 
                                WHERE summary_id = ?
                            ");
                            $updateDestStmt->bind_param("ii", $transfer_from_batch, $destRow['summary_id']);
                            $updateDestStmt->execute();
                        } else {
                            // Create new batch record at destination
                            $insertDestStmt = $conn->prepare("
                                INSERT INTO tbl_stock_summary (
                                    product_id, batch_id, available_quantity, unit_cost, 
                                    expiration_date, batch_reference
                                ) VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $insertDestStmt->bind_param("iiddss", 
                                $product_id, 
                                $fifoRow['batch_id'], 
                                $transfer_from_batch, 
                                $fifoRow['unit_cost'], 
                                $fifoRow['expiration_date'], 
                                $fifoRow['batch_reference']
                            );
                            $insertDestStmt->execute();
                        }
                        
                        $transferredFromBatches[] = [
                            'batch_reference' => $fifoRow['batch_reference'],
                            'quantity' => $transfer_from_batch,
                            'entry_date' => $fifoRow['entry_date']
                        ];
                        
                        $remaining_qty -= $transfer_from_batch;
                    }
                }
                
                if ($remaining_qty > 0) {
                    // Rollback transaction if insufficient stock
                    $conn->rollback();
                    echo json_encode([
                        "success" => false,
                        "message" => "Insufficient stock for product ID: $product_id. Requested: $requested_qty, Available: " . ($requested_qty - $remaining_qty)
                    ]);
                    break;
                }
                
                // Insert transfer detail
                $stmt2->bind_param("iii", $transfer_header_id, $product_id, $requested_qty);
                $stmt2->execute();
                
                // Log the transfer
                $logStmt->bind_param("iissi", 
                    $transfer_header_id, 
                    $product_id, 
                    $sourceLocation['location_name'], 
                    $destLocation['location_name'], 
                    $requested_qty
                );
                $logStmt->execute();
                
                $totalProductsTransferred++;
            }
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "FIFO transfer completed successfully",
                "transfer_id" => $transfer_header_id,
                "products_transferred" => $totalProductsTransferred,
                "source_location" => $sourceLocation['location_name'],
                "destination_location" => $destLocation['location_name']
            ]);
            
        } catch (Exception $e) {
            if ($conn->connect_errno === 0) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'enhanced_fifo_transfer':
        try {
            // Include the enhanced FIFO transfer system
            require_once '../enhanced_fifo_transfer_system.php';
            
            $product_id = $data['product_id'] ?? 0;
            $quantity = $data['quantity'] ?? 0;
            $source_location_id = $data['source_location_id'] ?? 0;
            $destination_location_id = $data['destination_location_id'] ?? 0;
            $employee_id = $data['employee_id'] ?? null;
            $products = $data['products'] ?? [];
            
            // Handle both single product and multiple products
            if (!empty($products)) {
                // Multiple products transfer
                $conn->autocommit(false);
                $transfer_results = [];
                
                // Create main transfer header
                $stmt = $conn->prepare("
                    INSERT INTO tbl_transfer_header (
                        source_location_id, destination_location_id, employee_id, 
                        status, date
                    ) VALUES (?, ?, ?, 'approved', CURDATE())
                ");
                $stmt->bind_param("iii", $source_location_id, $destination_location_id, $employee_id);
                $stmt->execute();
                $main_transfer_id = $conn->insert_id;
                
                $fifoSystem = new EnhancedFifoTransferSystem($conn);
                $total_products_transferred = 0;
                
                foreach ($products as $product) {
                    $result = $fifoSystem->performFifoTransfer(
                        $product['product_id'],
                        $product['quantity'],
                        $source_location_id,
                        $destination_location_id,
                        $employee_id
                    );
                    
                    if (!$result['success']) {
                        $conn->rollback();
                        echo json_encode([
                            "success" => false,
                            "message" => "Transfer failed for product ID {$product['product_id']}: " . $result['message']
                        ]);
                        return;
                    }
                    
                    $transfer_results[] = $result['data'];
                    $total_products_transferred++;
                }
                
                $conn->commit();
                
                // Get location names
                $sourceLocStmt = $conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = ?");
                $sourceLocStmt->bind_param("i", $source_location_id);
                $sourceLocStmt->execute();
                $sourceLocation = $sourceLocStmt->get_result()->fetch_assoc();
                
                $destLocStmt = $conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = ?");
                $destLocStmt->bind_param("i", $destination_location_id);
                $destLocStmt->execute();
                $destLocation = $destLocStmt->get_result()->fetch_assoc();
                
                echo json_encode([
                    "success" => true,
                    "message" => "Enhanced FIFO transfer completed successfully",
                    "transfer_id" => $main_transfer_id,
                    "products_transferred" => $total_products_transferred,
                    "source_location" => $sourceLocation['location_name'],
                    "destination_location" => $destLocation['location_name'],
                    "detailed_results" => $transfer_results
                ]);
                
            } else {
                // Single product transfer
                $fifoSystem = new EnhancedFifoTransferSystem($conn);
                $result = $fifoSystem->performFifoTransfer(
                    $product_id,
                    $quantity,
                    $source_location_id,
                    $destination_location_id,
                    $employee_id
                );
                
                echo json_encode($result);
            }
            
        } catch (Exception $e) {
            if (isset($conn) && $conn->connect_errno === 0) {
                $conn->rollback();
            }
            echo json_encode([
                "success" => false,
                "message" => "Enhanced FIFO Transfer Error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_fifo_stock_status':
        try {
            require_once '../enhanced_fifo_transfer_system.php';
            
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

    case 'get_products_oldest_batch_for_transfer':
        try {
            $location_id = $data['location_id'] ?? null;
            
            $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
            $params = [];
            $types = "";
            
            if ($location_id) {
                $whereClause .= " AND p.location_id = ?";
                $params[] = $location_id;
                $types .= "i";
            }
            
            // Query to get products with oldest batch information for transfer
            $sql = "
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
                    -- Oldest batch information
                    oldest_batch.batch_id,
                    oldest_batch.batch_reference,
                    oldest_batch.entry_date,
                    oldest_batch.expiration_date,
                    oldest_batch.quantity as oldest_batch_quantity,
                    oldest_batch.unit_cost,
                    -- Total quantity across all batches
                    total_qty.total_quantity,
                    -- Count of total batches
                    total_qty.total_batches
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
                AND oldest_batch.quantity > 0
                ORDER BY p.product_name ASC
            ";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
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
            $types = "";
            
            if ($location_id) {
                $whereClause .= " AND p.location_id = ?";
                $params[] = $location_id;
                $types .= "i";
            }
            
            // Query to get products with oldest batch information for warehouse display
            $sql = "
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
            ";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
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

    case 'check_fifo_availability':
        try {
            $product_id = $data['product_id'] ?? 0;
            $location_id = $data['location_id'] ?? 0;
            $requested_quantity = $data['requested_quantity'] ?? 0;
            
            // First, let's check if the product exists and has stock
            $checkProductStmt = $conn->prepare("
                SELECT p.product_id, p.product_name, p.quantity, p.location_id
                FROM tbl_product p
                WHERE p.product_id = ?
            ");
            $checkProductStmt->bind_param("i", $product_id);
            $checkProductStmt->execute();
            $productResult = $checkProductStmt->get_result();
            $productData = $productResult->fetch_assoc();
            
            // Check stock summary directly
            $checkStockStmt = $conn->prepare("
                SELECT ss.summary_id, ss.product_id, ss.available_quantity, ss.batch_reference
                FROM tbl_stock_summary ss
                WHERE ss.product_id = ?
            ");
            $checkStockStmt->bind_param("i", $product_id);
            $checkStockStmt->execute();
            $stockResult = $checkStockStmt->get_result();
            $stockData = [];
            while ($row = $stockResult->fetch_assoc()) {
                $stockData[] = $row;
            }
            
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
            
            $stmt->bind_param("ii", $product_id, $location_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $total_available = 0;
            $batches = [];
            
            while ($row = $result->fetch_assoc()) {
                $batches[] = $row;
                $total_available += $row['available_quantity'];
            }
            
            $is_available = $total_available >= $requested_quantity;
            
            echo json_encode([
                "success" => true,
                "is_available" => $is_available,
                "total_available" => $total_available,
                "requested_quantity" => $requested_quantity,
                "batches_count" => count($batches),
                "next_batches" => array_slice($batches, 0, 3), // Show first 3 batches that would be used
                "debug_info" => [
                    "product_data" => $productData,
                    "stock_summary_data" => $stockData,
                    "location_id_used" => $location_id
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Error checking FIFO availability: " . $e->getMessage()
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
            
            // If this is for transfer, use FIFO logic to show only oldest batch per product
            if ($for_transfer) {
                // Use CTE (Common Table Expression) for FIFO logic
                $sql = "
                    WITH ranked_batches AS (
                        SELECT 
                            p.product_id,
                            p.product_name,
                            p.barcode,
                            p.category,
                            p.description,
                            COALESCE(p.Variation, '') as variation,
                            COALESCE(b_brand.brand, '') as brand,
                            COALESCE(s.supplier_name, '') as supplier_name,
                            COALESCE(p.srp, p.unit_price) as srp,
                            ss.available_quantity as total_quantity,
                            ss.available_quantity as quantity,
                            ss.batch_id,
                            COALESCE(bt.batch, '') as batch_reference,
                            bt.entry_date,
                            ss.expiration_date,
                            ss.location_id,
                            ss.available_quantity as old_quantity,
                            -- Use ROW_NUMBER() to rank batches by entry_date (oldest first)
                            ROW_NUMBER() OVER (
                                PARTITION BY p.product_id, p.location_id 
                                ORDER BY bt.entry_date ASC, bt.batch_id ASC
                            ) as batch_rank
                        FROM tbl_product p
                        INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
                        INNER JOIN tbl_batch bt ON ss.batch_id = bt.batch_id
                        LEFT JOIN tbl_brand b_brand ON p.brand_id = b_brand.brand_id
                        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                        WHERE ss.available_quantity > 0
                        AND (p.status IS NULL OR p.status = 'active')
                ";
                
                if ($location_id) {
                    $sql .= " AND p.location_id = " . intval($location_id);
                }
                
                $sql .= "
                    )
                    SELECT 
                        product_id,
                        product_name,
                        barcode,
                        category,
                        description,
                        variation,
                        brand,
                        supplier_name,
                        srp,
                        total_quantity,
                        quantity,
                        batch_id,
                        batch_reference,
                        entry_date,
                        expiration_date,
                        location_id,
                        old_quantity
                    FROM ranked_batches
                    WHERE batch_rank = 1
                    ORDER BY product_name ASC
                ";
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
                        COALESCE(b.brand, '') as brand,
                        COALESCE(s.supplier_name, '') as supplier_name
                    FROM tbl_product p
                    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                    WHERE (p.status IS NULL OR p.status = 'active')
                ";
                
                if ($location_id) {
                    $sql .= " AND p.location_id = " . intval($location_id);
                }
                
                $sql .= " ORDER BY p.product_name";
            }
            
            $result = $conn->query($sql);
            $products = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
            echo json_encode([
                "success" => true,
                "data" => $products,
                "fifo_enabled" => $for_transfer,
                "location_id" => $location_id,
                "products_count" => count($products)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => [],
                "sql_error" => $e->getTraceAsString()
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
        
    case 'get_available_products':
        try {
            $location_id = $data['location_id'] ?? null;
            $category = $data['category'] ?? 'All Product Category';
            $supplier = $data['supplier'] ?? 'All Suppliers';
            $search = $data['search'] ?? '';
            
            $sql = "
                SELECT DISTINCT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    p.prescription,
                    p.bulk,
                    p.expiration,
                    p.quantity,
                    p.unit_price,
                    p.srp,
                    p.brand_id,
                    p.supplier_id,
                    p.location_id,
                    p.batch_id,
                    p.status,
                    p.Variation,
                    p.stock_status,
                    p.date_added,
                    b.brand,
                    s.supplier_name,
                    l.location_name
                FROM tbl_product p
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                WHERE p.status = 'active'
            ";
            
            $params = [];
            $types = "";
            
            if ($location_id) {
                $sql .= " AND p.location_id = ?";
                $params[] = $location_id;
                $types .= "i";
            }
            
            if ($category !== 'All Product Category') {
                $sql .= " AND p.category = ?";
                $params[] = $category;
                $types .= "s";
            }
            
            if ($supplier !== 'All Suppliers') {
                $sql .= " AND s.supplier_name = ?";
                $params[] = $supplier;
                $types .= "s";
            }
            
            if (!empty($search)) {
                $sql .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.description LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $sql .= " ORDER BY p.product_name";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $products = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
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

    case 'get_fifo_stock':
        try {
            $location_id = $data['location_id'] ?? null;
            $category = $data['category'] ?? 'All Product Category';
            $supplier = $data['supplier'] ?? 'All Suppliers';
            $search = $data['search'] ?? '';
            
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    p.prescription,
                    p.bulk,
                    p.status,
                    p.Variation,
                    p.stock_status,
                    p.date_added,
                    b.brand,
                    s.supplier_name,
                    l.location_name,
                    ss.batch_id,
                    ss.batch_reference,
                    ss.available_quantity,
                    ss.unit_cost,
                    ss.expiration_date,
                    b2.entry_date as batch_entry_date,
                    ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY b2.entry_date ASC, ss.summary_id ASC) as fifo_order
                FROM tbl_product p
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
                INNER JOIN tbl_batch b2 ON ss.batch_id = b2.batch_id
                WHERE p.status = 'active' AND ss.available_quantity > 0
            ";
            
            $params = [];
            $types = "";
            
            if ($location_id) {
                $sql .= " AND p.location_id = ?";
                $params[] = $location_id;
                $types .= "i";
            }
            
            if ($category !== 'All Product Category') {
                $sql .= " AND p.category = ?";
                $params[] = $category;
                $types .= "s";
            }
            
            if ($supplier !== 'All Suppliers') {
                $sql .= " AND s.supplier_name = ?";
                $params[] = $supplier;
                $types .= "s";
            }
            
            if (!empty($search)) {
                $sql .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.description LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $sql .= " ORDER BY p.product_id, fifo_order";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $products = [];
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
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
        
    case 'consume_stock_fifo':
        try {
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $quantity_needed = isset($data['quantity']) ? intval($data['quantity']) : 0;
            $reference_no = isset($data['reference_no']) ? trim($data['reference_no']) : '';
            $notes = isset($data['notes']) ? trim($data['notes']) : '';
            $created_by = isset($data['created_by']) ? trim($data['created_by']) : 'admin';
            
            if ($product_id <= 0 || $quantity_needed <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID or quantity"
                ]);
                break;
            }
            
            // Start transaction
            $conn->autocommit(false);
            
            // Get FIFO stock levels for consumption
            $stmt = $conn->prepare("
                SELECT 
                    ss.summary_id,
                    ss.batch_id,
                    ss.available_quantity,
                    ss.unit_cost,
                    ss.expiration_date,
                    ss.batch_reference
                FROM tbl_stock_summary ss
                JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE ss.product_id = ? AND ss.available_quantity > 0
                ORDER BY b.entry_date ASC, ss.summary_id ASC
            ");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $available_stock = [];
            
            while ($row = $result->fetch_assoc()) {
                $available_stock[] = $row;
            }
            
            if (empty($available_stock)) {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "No stock available for this product"
                ]);
                break;
            }
            
            $remaining_quantity = $quantity_needed;
            $consumed_batches = [];
            $total_consumed = 0;
            
            // Consume stock from FIFO order
            foreach ($available_stock as $batch) {
                if ($remaining_quantity <= 0) break;
                
                $batch_quantity = min($remaining_quantity, $batch['available_quantity']);
                
                // Update stock summary
                $updateStmt = $conn->prepare("
                    UPDATE tbl_stock_summary 
                    SET available_quantity = available_quantity - ?
                    WHERE summary_id = ?
                ");
                $updateStmt->bind_param("ii", $batch_quantity, $batch['summary_id']);
                $updateStmt->execute();
                
                // Log the consumption
                $logStmt = $conn->prepare("
                    INSERT INTO tbl_stock_movement (
                        product_id, batch_id, movement_type, quantity, 
                        unit_cost, reference_no, notes, created_by, created_at
                    ) VALUES (?, ?, 'consumption', ?, ?, ?, ?, ?, NOW())
                ");
                $logStmt->bind_param("iiidsss", 
                    $product_id, 
                    $batch['batch_id'], 
                    $batch_quantity, 
                    $batch['unit_cost'], 
                    $reference_no, 
                    $notes, 
                    $created_by
                );
                $logStmt->execute();
                
                $consumed_batches[] = [
                    'batch_reference' => $batch['batch_reference'],
                    'quantity' => $batch_quantity,
                    'unit_cost' => $batch['unit_cost'],
                    'total_value' => $batch_quantity * $batch['unit_cost']
                ];
                
                $total_consumed += $batch_quantity;
                $remaining_quantity -= $batch_quantity;
            }
            
            if ($remaining_quantity > 0) {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "Insufficient stock. Only " . $total_consumed . " units available"
                ]);
                break;
            }
            
            // Update main product quantity
            $updateProductStmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = quantity - ?,
                    stock_status = CASE 
                        WHEN (quantity - ?) <= 0 THEN 'out of stock'
                        WHEN (quantity - ?) <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                WHERE product_id = ?
            ");
            $updateProductStmt->bind_param("iiii", $total_consumed, $total_consumed, $total_consumed, $product_id);
            $updateProductStmt->execute();
            
            $conn->commit();
            
            // Calculate total transfer value
            $total_value = 0;
            foreach ($consumed_batches as $batch) {
                $total_value += $batch['total_value'];
            }
            
            echo json_encode([
                "success" => true,
                "message" => "Stock consumed using FIFO method",
                "quantity_consumed" => $total_consumed,
                "total_value" => $total_value,
                "consumed_batches" => $consumed_batches,
                "transfer_summary" => [
                    "total_quantity" => $total_consumed,
                    "total_value" => $total_value,
                    "average_unit_cost" => $total_consumed > 0 ? $total_value / $total_consumed : 0
                ]
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
        
    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid action: " . $action
        ]);
        break;
}

$conn->close();
?> 