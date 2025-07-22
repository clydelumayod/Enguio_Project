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

// Include DB connection
include 'index.php';

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
            $shift_id = isset($data['shift_id']) ? trim($data['shift_id']) : null;
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
            $stmt->bindParam(":shift_id", $shift_id, PDO::PARAM_INT | PDO::PARAM_NULL);
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
                    expiration, date_added, quantity, unit_price, brand_id, supplier_id,
                    location_id, batch_id, status, Variation, stock_status
                ) VALUES (
                    :product_name, :category, :barcode, :description, :prescription, :bulk,
                    :expiration, :date_added, :quantity, :unit_price, :brand_id, :supplier_id,
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
        
        $whereClause = "WHERE (p.status IS NULL OR p.status <> 'archived')";
        $params = [];
        
        if ($location_id) {
            $whereClause .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        // Include products with quantity > 0 or when specifically filtering by location
        if (!$location_id) {
            $whereClause .= " AND p.quantity > 0";
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
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            
            // Get current product details
            $productStmt = $conn->prepare("
                SELECT product_name, category, barcode, description, prescription, bulk,
                       expiration, unit_price, brand_id, supplier_id, location_id, status, Variation
                FROM tbl_product 
                WHERE product_id = ?
                LIMIT 1
            ");
            $productStmt->execute([$product_id]);
            $productDetails = $productStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$productDetails) {
                throw new Exception("Product not found");
            }
            
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
                        product_id, batch_id, batch_reference, quantity, unit_cost,
                        expiration_date, entry_date, entry_by
                    ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)
                ");
                $fifoStmt->execute([
                    $product_id, $batch_id, $batch_reference, $new_quantity, 
                    $unit_cost, $expiration_date, $entry_by
                ]);
            }
            
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
            
            // Query to get FIFO stock data for the product
            $stmt = $conn->prepare("
                SELECT 
                    fs.batch_id,
                    fs.batch_reference,
                    fs.available_quantity,
                    fs.unit_cost,
                    fs.expiration_date,
                    fs.batch_date,
                    fs.fifo_order,
                    CASE 
                        WHEN fs.expiration_date IS NULL THEN NULL
                        ELSE DATEDIFF(fs.expiration_date, CURDATE())
                    END as days_until_expiry
                FROM v_fifo_stock fs
                WHERE fs.product_id = ? AND fs.available_quantity > 0
                ORDER BY fs.fifo_order ASC
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
                FROM v_fifo_stock fs
                WHERE fs.product_id = ? AND fs.available_quantity > 0
                ORDER BY fs.fifo_order ASC
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
            
            // Get warehouse-specific KPIs
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
                LEFT JOIN tbl_transfer t ON p.product_id = t.product_id
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
        
            $stmt = $conn->prepare("UPDATE tbl_supplier SET deleted_at = NOW() WHERE supplier_id = :supplier_id");
            $stmt->bindParam(":supplier_id", $supplier_id, PDO::PARAM_INT);
        
            try {
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Supplier archived"]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Failed to archive supplier",
                        "error" => $stmt->errorInfo()
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "An error occurred: " . $e->getMessage(),
                    "error" => $stmt->errorInfo()
                ]);
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
                $stmt = $conn->prepare("UPDATE tbl_supplier SET deleted_at = NULL WHERE supplier_id = :supplier_id");
                $stmt->execute([":supplier_id" => $supplier_id]);
        
                echo json_encode([
                    "success" => true,
                    "message" => "Supplier restored"
                ]);
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
                echo json_encode(["success" => false, "message" => "Error fetching archived suppliers"]);
            }
            break;
         
}

// Flush the output buffer to ensure clean JSON response
ob_end_flush();
?>  