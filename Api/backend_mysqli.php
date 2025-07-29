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
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
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
            
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category,
                    p.barcode,
                    p.description,
                    p.quantity,
                    p.unit_price,
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
            
            $result = $conn->query($sql);
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
        
    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid action: " . $action
        ]);
        break;
}

$conn->close();
?> 