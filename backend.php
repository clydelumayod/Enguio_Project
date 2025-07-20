<?php
// Start output buffering to prevent unwanted output
ob_start();

session_start();

// CORS and content-type headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Show PHP errors for debugging (optional - remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log errors to a file for debugging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');


include 'Api/index.php';
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

// Check if database connection is working
if (!isset($conn) || !$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "action" => $action
    ]);
    exit;
}

switch ($action) {
    case 'add_employee':
        try {
            // Extract and sanitize input data
            $fname = isset($data['Fname'])&& !empty($data['Fname']) ? trim($data['Fname']) : '';
            $mname = isset($data['Mname']) && !empty($data['Mname'])? trim($data['Mname']) : '';
            $lname = isset($data['Lname']) && !empty($data['Lname'])? trim($data['Lname']) : '';
            $email = isset($data['email']) ? trim($data['email']) : '';
            $contact = isset($data['contact_num']) ? trim($data['contact_num']) : '';
            $role_id = isset($data['role_id']) ? trim($data['role_id']) : '';
            $shift_id = isset($data['shift_id']) ? trim($data['shift_id']) : '';
            $username = isset($data['username']) ? trim($data['username']) : '';
            $password = isset($data['password']) ? trim($data['password']) : '';
            $age = isset($data['age']) ? trim($data['age']) : '';
            $address = isset($data['address']) ? trim($data['address']) : '';
            $status = isset($data['status']) ? trim($data['status']) : 'Active'; // ✅ Default status

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare the SQL statement
            $stmt = $conn->prepare("
                INSERT INTO tbl_employee (
                    Fname, Mname, Lname, email, contact_num, role_id, shift_id,
                    username, password, age, address, status
                ) VALUES (
                    :Fname, :Mname, :Lname, :email, :contact_num, :role_id, :shift_id,
                    :username, :password, :age, :address, :status
                )
            ");

            // Bind parameters
            $stmt->bindParam(":Fname", $fname, PDO::PARAM_STR);
            $stmt->bindParam(":Mname", $mname, PDO::PARAM_STR);
            $stmt->bindParam(":Lname", $lname, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":contact_num", $contact, PDO::PARAM_STR);
            $stmt->bindParam(":role_id", $role_id, PDO::PARAM_INT);
            $stmt->bindParam(":shift_id", $shift_id, PDO::PARAM_INT);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(":age", $age, PDO::PARAM_INT);
            $stmt->bindParam(":address", $address, PDO::PARAM_STR);
            $stmt->bindParam(":status", $status, PDO::PARAM_STR); // ✅ Status field

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

    case 'display_employee':
        try {
            $stmt = $conn->prepare("SELECT emp_id,Fname,Mname,Lname,email,contact_num,role_id,shift_id,username,age,address,status FROM tbl_employee");
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
            $stmt = $conn->prepare("SELECT brand_id,brand FROM tbl_brand");
            $stmt->execute();
            $brand= $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($brand) {
                echo json_encode([
                    "success" => true,
                    "brand" => $brand
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "brand" => [],
                    "message" => "No employees found"
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
            $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : 0;
            $brand_id = isset($data['brand_id']) ? intval($data['brand_id']) : 30; // Default to first brand (30)
            $expiration = isset($data['expiration']) ? trim($data['expiration']) : null;
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
                    expiration, quantity, unit_price, brand_id, supplier_id,
                    location_id, batch_id, status, Variation, stock_status
                ) VALUES (
                    :product_name, :category, :barcode, :description, :prescription, :bulk,
                    :expiration, :quantity, :unit_price, :brand_id, :supplier_id,
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
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':unit_price', $unit_price);
            $stmt->bindParam(':brand_id', $brand_id);
            $stmt->bindParam(':supplier_id', $supplier_id);
            $stmt->bindParam(':location_id', $location_id);
            $stmt->bindParam(':batch_id', $batch_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':variation', $variation);
            $stmt->bindParam(':stock_status', $stock_status);
    
            if ($stmt->execute()) {
                $product_id = $conn->lastInsertId();
                
                // FIFO: Create stock movement record for new stock
                if ($batch_id && $quantity > 0) {
                    $movementStmt = $conn->prepare("
                        INSERT INTO tbl_stock_movements (
                            product_id, batch_id, movement_type, quantity, remaining_quantity,
                            unit_cost, expiration_date, reference_no, created_by
                        ) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, ?)
                    ");
                    $movementStmt->execute([
                        $product_id, $batch_id, $quantity, $quantity, 
                        $unit_price, $expiration, $reference, $entry_by
                    ]);
                    
                    // Create stock summary record
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
                    $summaryStmt->execute([
                        $product_id, $batch_id, $quantity, $unit_price, 
                        $expiration, $reference, $quantity
                    ]);
                }
                
                $conn->commit();
                echo json_encode(["success" => true, "message" => "Product added successfully with FIFO tracking"]);
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

    case 'check_barcode':
        try {
            $barcode = isset($data['barcode']) ? trim($data['barcode']) : '';
            
            if (empty($barcode)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Barcode is required"
                ]);
                break;
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
                WHERE p.barcode = ? AND (p.status IS NULL OR p.status <> 'archived')
                LIMIT 1
            ");
            $stmt->execute([$barcode]);
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
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $new_quantity = isset($data['new_quantity']) ? intval($data['new_quantity']) : 0;
            $batch_reference = isset($data['batch_reference']) ? trim($data['batch_reference']) : '';
            $expiration_date = isset($data['expiration_date']) ? trim($data['expiration_date']) : null;
            $unit_cost = isset($data['unit_cost']) ? floatval($data['unit_cost']) : 0;
            $entry_by = isset($data['entry_by']) ? trim($data['entry_by']) : 'admin';
            
            if ($product_id <= 0 || $new_quantity <= 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid product ID or quantity"
                ]);
                break;
            }
            
            // Get current product details
            $stmt = $conn->prepare("SELECT quantity, unit_price FROM tbl_product WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo json_encode([
                    "success" => false,
                    "message" => "Product not found"
                ]);
                break;
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            // Create new batch record for the additional stock
            $batch_id = null;
            if ($batch_reference) {
                $batchStmt = $conn->prepare("
                    INSERT INTO tbl_batch (
                        batch, supplier_id, location_id, entry_date, entry_time, 
                        entry_by, order_no
                    ) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)
                ");
                $batchStmt->execute([$batch_reference, 13, 2, $entry_by, '']);
                $batch_id = $conn->lastInsertId();
            }
            
            // Calculate new total quantity
            $current_quantity = intval($product['quantity']);
            $total_quantity = $current_quantity + $new_quantity;
            
            // Update product quantity
            $updateStmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = ?,
                    stock_status = CASE 
                        WHEN ? <= 0 THEN 'out of stock'
                        WHEN ? <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                WHERE product_id = ?
            ");
            
            if ($updateStmt->execute([$total_quantity, $total_quantity, $total_quantity, $product_id])) {
                
                // FIFO: Create stock movement record for additional stock
                if ($batch_id && $new_quantity > 0) {
                    $movementStmt = $conn->prepare("
                        INSERT INTO tbl_stock_movements (
                            product_id, batch_id, movement_type, quantity, remaining_quantity,
                            unit_cost, expiration_date, reference_no, created_by
                        ) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, ?)
                    ");
                    $movementStmt->execute([
                        $product_id, $batch_id, $new_quantity, $new_quantity, 
                        $unit_cost ?: $product['unit_price'], $expiration_date, $batch_reference, $entry_by
                    ]);
                    
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
                    $summaryStmt->execute([
                        $product_id, $batch_id, $new_quantity, 
                        $unit_cost ?: $product['unit_price'], $expiration_date, 
                        $batch_reference, $new_quantity
                    ]);
                }
                
                $conn->commit();
                echo json_encode([
                    "success" => true,
                    "message" => "Stock updated successfully with FIFO tracking",
                    "old_quantity" => $current_quantity,
                    "new_quantity" => $total_quantity,
                    "added_quantity" => $new_quantity,
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

   case 'get_products':
    try {
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
            ORDER BY p.product_id DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Optional: Debug log
        // error_log(json_encode($products));

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
            $stmt = $conn->prepare("
                SELECT category_id, category_name 
                FROM tbl_category 
                ORDER BY category_name
            ");
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
            
            // Get FIFO stock levels for the product
            $stmt = $conn->prepare("
                SELECT 
                    ss.summary_id,
                    ss.batch_id,
                    ss.batch_reference,
                    ss.available_quantity,
                    ss.unit_cost,
                    ss.expiration_date,
                    ss.total_quantity,
                    b.entry_date as batch_date,
                    b.entry_time as batch_time,
                    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry
                FROM tbl_stock_summary ss
                JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE ss.product_id = ? AND ss.available_quantity > 0
                ORDER BY b.entry_date ASC, ss.summary_id ASC
            ");
            $stmt->execute([$product_id]);
            $fifo_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $fifo_stock
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'get_expiring_products':
        try {
            $days_threshold = isset($data['days_threshold']) ? intval($data['days_threshold']) : 30;
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.barcode,
                    p.category,
                    ss.available_quantity,
                    ss.expiration_date,
                    ss.batch_reference,
                    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry,
                    b.entry_date as batch_date
                FROM tbl_product p
                JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
                JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE p.status = 'active' 
                    AND ss.available_quantity > 0 
                    AND ss.expiration_date IS NOT NULL
                    AND ss.expiration_date >= CURDATE()
                    AND DATEDIFF(ss.expiration_date, CURDATE()) <= ?
                ORDER BY ss.expiration_date ASC, b.entry_date ASC
            ");
            $stmt->execute([$days_threshold]);
            $expiring_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $expiring_products
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
            $conn->beginTransaction();
            
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
            $stmt->execute([$product_id]);
            $available_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            foreach ($available_stock as $stock) {
                if ($remaining_quantity <= 0) break;
                
                $batch_quantity = min($remaining_quantity, $stock['available_quantity']);
                $new_available = $stock['available_quantity'] - $batch_quantity;
                
                // Create movement record for consumption
                $movementStmt = $conn->prepare("
                    INSERT INTO tbl_stock_movements (
                        product_id, batch_id, movement_type, quantity, remaining_quantity,
                        unit_cost, expiration_date, reference_no, notes, created_by
                    ) VALUES (?, ?, 'OUT', ?, ?, ?, ?, ?, ?, ?)
                ");
                $movementStmt->execute([
                    $product_id, $stock['batch_id'], $batch_quantity, $new_available,
                    $stock['unit_cost'], $stock['expiration_date'], $reference_no, $notes, $created_by
                ]);
                
                // Update stock summary
                $updateStmt = $conn->prepare("
                    UPDATE tbl_stock_summary 
                    SET available_quantity = ?, last_updated = CURRENT_TIMESTAMP
                    WHERE summary_id = ?
                ");
                $updateStmt->execute([$new_available, $stock['summary_id']]);
                
                $consumed_batches[] = [
                    'batch_reference' => $stock['batch_reference'],
                    'quantity_consumed' => $batch_quantity,
                    'unit_cost' => $stock['unit_cost']
                ];
                
                $remaining_quantity -= $batch_quantity;
            }
            
            if ($remaining_quantity > 0) {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "Insufficient stock. Only " . ($quantity_needed - $remaining_quantity) . " units available"
                ]);
                break;
            }
            
            // Update main product quantity
            $total_consumed = $quantity_needed - $remaining_quantity;
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
            $updateProductStmt->execute([$total_consumed, $total_consumed, $total_consumed, $product_id]);
            
            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Stock consumed using FIFO method",
                "quantity_consumed" => $total_consumed,
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
            $status = $data['status'] ?? 'approved'; // Use 'approved' instead of 'Completed' to match DB enum
            $products = $data['products'] ?? [];
            
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
                
                // Check current quantity in source location
                $checkStmt = $conn->prepare("
                    SELECT quantity, product_name, location_id 
                    FROM tbl_product 
                    WHERE product_id = ? AND location_id = ?
                ");
                $checkStmt->execute([$product_id, $source_location_id]);
                $currentProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentProduct) {
                    throw new Exception("Product not found in source location - Product ID: " . $product_id);
                }
                
                if ($currentProduct['quantity'] < $transfer_qty) {
                    throw new Exception("Insufficient quantity for product: " . $currentProduct['product_name'] . 
                                     ". Available: " . $currentProduct['quantity'] . ", Requested: " . $transfer_qty);
                }
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
            
            // Insert transfer details and update product quantities
            $stmt2 = $conn->prepare("
                INSERT INTO tbl_transfer_dtl (
                    transfer_header_id, product_id, qty
                ) VALUES (?, ?, ?)
            ");
            
            $updateStmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = quantity - ? 
                WHERE product_id = ? AND location_id = ?
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
                
                // Update product quantity (decrease) in source location
                $updateStmt->execute([$transfer_qty, $product_id, $source_location_id]);
                
                // Update stock status based on new quantity in source location
                $updateStockStatusStmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET stock_status = CASE 
                        WHEN quantity <= 0 THEN 'out of stock'
                        WHEN quantity <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                    WHERE product_id = ? AND location_id = ?
                ");
                $updateStockStatusStmt->execute([$product_id, $source_location_id]);
                
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
                    // Check if product exists in destination location
                    $checkDestStmt = $conn->prepare("
                        SELECT product_id, quantity 
                        FROM tbl_product 
                        WHERE product_id = ? AND location_id = ?
                    ");
                    $checkDestStmt->execute([$product_id, $destination_location_id]);
                    $destProduct = $checkDestStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($destProduct) {
                        // Update existing product quantity in destination
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
                        $updateDestStmt->execute([$transfer_qty, $transfer_qty, $transfer_qty, $product_id, $destination_location_id]);
                    }
                    // If product doesn't exist in destination, we don't create it - just track the transfer
                }
            }
            
            $conn->commit();
            echo json_encode([
                "success" => true, 
                "message" => "Transfer created successfully. Products immediately added to destination location."
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
            
            $stmt = $conn->prepare("UPDATE tbl_supplier SET status = 'archived' WHERE supplier_id = ?");
            $stmt->execute([$supplier_id]);
            
            echo json_encode(["success" => true, "message" => "Supplier archived successfully"]);
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete_product':
        try {
            $product_id = $data['product_id'] ?? 0;
            
            $stmt = $conn->prepare("UPDATE tbl_product SET status = 'archived' WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            echo json_encode(["success" => true, "message" => "Product archived successfully"]);
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete_all_products':
        try {
            // Archive all products by setting their status to 'archived'
            $stmt = $conn->prepare("UPDATE tbl_product SET status = 'archived' WHERE status != 'archived'");
            $result = $stmt->execute();
            
            if ($result) {
                $affectedRows = $stmt->rowCount();
                echo json_encode([
                    "success" => true, 
                    "message" => "All products archived successfully. {$affectedRows} products affected."
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to archive products"]);
            }
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'clear_all_data':
        try {
            // Disable foreign key checks temporarily
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Tables to clear (in order to avoid foreign key constraint issues)
            $tables = [
                'tbl_transfer_log',
                'tbl_transfer_dtl', 
                'tbl_transfer_header',
                'tbl_purchase_return_dtl',
                'tbl_purchase_return_header',
                'tbl_purchase_order_dtl',
                'tbl_purchase_order_header',
                'tbl_pos_sales_details',
                'tbl_pos_sales_header',
                'tbl_pos_transaction',
                'tbl_pos_terminal',
                'tbl_adjustment_details',
                'tbl_adjustment_header',
                'tbl_product',
                'tbl_batch',
                'tbl_supplier',
                'tbl_employee',
                'tbl_brand',
                'tbl_discount'
            ];
            
            $totalDeleted = 0;
            
            foreach ($tables as $table) {
                $stmt = $conn->prepare("DELETE FROM $table");
                $stmt->execute();
                $deletedRows = $stmt->rowCount();
                $totalDeleted += $deletedRows;
                
                // Reset auto-increment
                $conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            }
            
            // Re-enable foreign key checks
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo json_encode([
                "success" => true, 
                "message" => "All database data cleared successfully. {$totalDeleted} total records deleted."
            ]);
            
        } catch (Exception $e) {
            // Re-enable foreign key checks in case of error
            if (isset($conn)) {
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
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
                            // Check if a product with this barcode exists anywhere in the database
                            $checkGlobalStmt = $conn->prepare("
                                SELECT product_id, location_id 
                                FROM tbl_product 
                                WHERE barcode = ?
                            ");
                            $checkGlobalStmt->execute([$productDetails['barcode']]);
                            $globalProduct = $checkGlobalStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($globalProduct) {
                                // A product with this barcode exists elsewhere, so we need to create a unique barcode
                                // Generate a truly unique barcode for the destination location
                                $microtime = microtime(true);
                                $random = mt_rand(1000, 9999);
                                $uniqueBarcode = $productDetails['barcode'] . '_' . $destination_location_id . '_' . $microtime . '_' . $random;
                            } else {
                                // No product with this barcode exists anywhere, so we can use the original barcode
                                $uniqueBarcode = $productDetails['barcode'];
                            }
                            
                            // Create new product entry in destination location with retry mechanism
                            $maxRetries = 5;
                            $retryCount = 0;
                            $insertSuccess = false;
                            $currentBarcode = $uniqueBarcode; // Store the current barcode to use
                            
                            while (!$insertSuccess && $retryCount < $maxRetries) {
                                try {
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
                                        $currentBarcode, // Use the current barcode variable
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
                                    $insertSuccess = true;
                                } catch (Exception $e) {
                                    $retryCount++;
                                    if ($retryCount < $maxRetries) {
                                        // Generate a new unique barcode
                                        $microtime = microtime(true);
                                        $random = mt_rand(1000, 9999);
                                        $currentBarcode = $productDetails['barcode'] . '_' . $destination_location_id . '_' . $microtime . '_' . $random;
                                    } else {
                                        throw new Exception("Failed to create unique barcode after $maxRetries attempts");
                                    }
                                }
                            }
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

    case 'create_notification':
        try {
            $location_id = $data['location_id'] ?? 0;
            $transfer_id = $data['transfer_id'] ?? 0;
            $notification_type = $data['notification_type'] ?? 'transfer';
            $message = $data['message'] ?? '';
            $status = $data['status'] ?? 'unread';
            
            $stmt = $conn->prepare("
                INSERT INTO tbl_notifications (
                    location_id, transfer_id, notification_type, message, status, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$location_id, $transfer_id, $notification_type, $message, $status]);
            
            echo json_encode([
                "success" => true,
                "message" => "Notification created successfully",
                "notification_id" => $conn->lastInsertId()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_notifications':
        try {
            $location_id = $data['location_id'] ?? 0;
            $status = $data['status'] ?? 'all';
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($location_id > 0) {
                $whereClause .= " AND location_id = ?";
                $params[] = $location_id;
            }
            
            if ($status !== 'all') {
                $whereClause .= " AND status = ?";
                $params[] = $status;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    n.notification_id,
                    n.location_id,
                    n.transfer_id,
                    n.notification_type,
                    n.message,
                    n.status,
                    n.created_at,
                    l.location_name,
                    th.source_location_id,
                    th.destination_location_id,
                    sl.location_name as source_location_name,
                    dl.location_name as destination_location_name,
                    e.Fname as employee_name
                FROM tbl_notifications n
                LEFT JOIN tbl_location l ON n.location_id = l.location_id
                LEFT JOIN tbl_transfer_header th ON n.transfer_id = th.transfer_header_id
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
                LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
                $whereClause
                ORDER BY n.created_at DESC
            ");
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $notifications
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;

    case 'mark_notification_read':
        try {
            $notification_id = $data['notification_id'] ?? 0;
            
            if (!$notification_id) {
                echo json_encode(["success" => false, "message" => "Notification ID is required"]);
                break;
            }
            
            $stmt = $conn->prepare("
                UPDATE tbl_notifications 
                SET status = 'read' 
                WHERE notification_id = ?
            ");
            $stmt->execute([$notification_id]);
            
            echo json_encode([
                "success" => true,
                "message" => "Notification marked as read"
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'get_location_products':
        try {
            $location_id = $data['location_id'] ?? 0;
            $search = $data['search'] ?? '';
            $category = $data['category'] ?? 'all';
            
            // Get regular products in the location
            $whereClause = "WHERE p.location_id = ?";
            $params = [$location_id];
            
            if ($search) {
                $whereClause .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.category LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($category !== 'all') {
                $whereClause .= " AND p.category = ?";
                $params[] = $category;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    p.quantity,
                    p.unit_price,
                    p.stock_status,
                    p.Variation,
                    p.brand_id,
                    p.supplier_id,
                    p.location_id,
                    b.brand,
                    s.supplier_name,
                    l.location_name,
                    'Regular' as product_type,
                    NULL as transfer_date,
                    NULL as source_location,
                    NULL as transferred_by,
                    NULL as transfer_status
                FROM tbl_product p
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
            ");
            $stmt->execute($params);
            $regularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get transferred products to this location
            $transferWhereClause = "WHERE th.destination_location_id = ?";
            $transferParams = [$location_id];
            
            if ($search) {
                $transferWhereClause .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.category LIKE ?)";
                $searchTerm = "%$search%";
                $transferParams[] = $searchTerm;
                $transferParams[] = $searchTerm;
                $transferParams[] = $searchTerm;
            }
            
            if ($category !== 'all') {
                $transferWhereClause .= " AND p.category = ?";
                $transferParams[] = $category;
            }
            
            $stmt2 = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    td.qty as quantity,
                    p.unit_price,
                    CASE 
                        WHEN td.qty <= 0 THEN 'out of stock'
                        WHEN td.qty <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END as stock_status,
                    p.Variation,
                    p.brand_id,
                    p.supplier_id,
                    th.destination_location_id as location_id,
                    b.brand,
                    s.supplier_name,
                    l.location_name,
                    'Transferred' as product_type,
                    th.date as transfer_date,
                    sl.location_name as source_location,
                    CONCAT(e.Fname, ' ', e.Lname) as transferred_by,
                    CASE 
                        WHEN th.status = '' OR th.status IS NULL THEN 'Completed'
                        WHEN th.status = 'pending' THEN 'Pending'
                        WHEN th.status = 'approved' THEN 'Completed'
                        WHEN th.status = 'rejected' THEN 'Cancelled'
                        ELSE th.status
                    END as transfer_status
                FROM tbl_transfer_header th
                JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                JOIN tbl_product p ON td.product_id = p.product_id
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON th.destination_location_id = l.location_id
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
                $transferWhereClause
            ");
            $stmt2->execute($transferParams);
            $transferredProducts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine both regular and transferred products
            $allProducts = array_merge($regularProducts, $transferredProducts);
            
            // Sort by product name
            usort($allProducts, function($a, $b) {
                return strcmp($a['product_name'], $b['product_name']);
            });
            
            echo json_encode([
                "success" => true,
                "data" => $allProducts
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

    case 'get_transferred_products':
        try {
            $location_id = $data['location_id'] ?? 0;
            $search = $data['search'] ?? '';
            $category = $data['category'] ?? 'all';
            
            $whereClause = "WHERE th.destination_location_id = ?";
            $params = [$location_id];
            
            if ($search) {
                $whereClause .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.category LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($category !== 'all') {
                $whereClause .= " AND p.category = ?";
                $params[] = $category;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    td.qty as transferred_quantity,
                    p.unit_price,
                    p.stock_status,
                    p.Variation,
                    p.brand_id,
                    p.supplier_id,
                    p.location_id,
                    b.brand,
                    s.supplier_name,
                    l.location_name,
                    th.transfer_header_id,
                    th.date as transfer_date,
                    sl.location_name as source_location,
                    CONCAT(e.Fname, ' ', e.Lname) as transferred_by,
                    CASE 
                        WHEN th.status = '' OR th.status IS NULL THEN 'Completed'
                        WHEN th.status = 'pending' THEN 'Pending'
                        WHEN th.status = 'approved' THEN 'Completed'
                        WHEN th.status = 'rejected' THEN 'Cancelled'
                        ELSE th.status
                    END as transfer_status
                FROM tbl_transfer_header th
                JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
                JOIN tbl_product p ON td.product_id = p.product_id
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
                LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
                $whereClause
                ORDER BY th.date DESC, th.transfer_header_id DESC
            ");
            $stmt->execute($params);
            $transferredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $transferredProducts
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage(),
                "data" => []
            ]);
        }
        break;
    
    case 'test_connection':
        echo json_encode([
            "success" => true,
            "message" => "PHP backend is working",
            "database" => "Connected",
            "timestamp" => date('Y-m-d H:i:s')
        ]);
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Invalid action: " . $action]);
        break;
}

// Flush the output buffer to ensure clean JSON response
ob_end_flush();
?>  