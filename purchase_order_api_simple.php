<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection using mysqli
$host = 'localhost';
$dbname = 'enguio';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    echo json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        handleGet($conn, $action);
        break;
    case 'POST':
        handlePost($conn, $action);
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($conn, $action) {
    switch($action) {
        case 'suppliers':
            getSuppliers($conn);
            break;
        case 'products':
            getProducts($conn);
            break;
        case 'purchase_orders':
            getPurchaseOrders($conn);
            break;
        case 'purchase_order_details':
            $po_id = $_GET['po_id'] ?? null;
            getPurchaseOrderDetails($conn, $po_id);
            break;
        case 'receiving_list':
            getReceivingList($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($conn, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'create_purchase_order':
            createPurchaseOrder($conn, $data);
            break;
        case 'approve_purchase_order':
            approvePurchaseOrder($conn, $data);
            break;
        case 'update_delivery_status':
            updateDeliveryStatus($conn, $data);
            break;
        case 'receive_items':
            receiveItems($conn, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Get all suppliers
function getSuppliers($conn) {
    try {
        $query = "SELECT * FROM tbl_supplier WHERE status = 'active' ORDER BY supplier_name";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $suppliers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $suppliers[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $suppliers]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get all products
function getProducts($conn) {
    try {
        $query = "SELECT p.*, b.brand, s.supplier_name 
                 FROM tbl_product p 
                 LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                 LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                 WHERE p.status = 'active' 
                 ORDER BY p.product_name";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $products]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get all purchase orders
function getPurchaseOrders($conn) {
    try {
        $query = "SELECT poh.*, s.supplier_name, e.Fname, e.Lname,
                 COALESCE(pod.delivery_status, 'pending') as delivery_status, 
                 pod.expected_delivery_date,
                 COALESCE(poa.approval_status, 'pending') as approval_status, 
                 poa.approval_date
                 FROM tbl_purchase_order_header poh
                 LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                 LEFT JOIN tbl_employee e ON poh.created_by = e.emp_id
                 LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                 LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                 ORDER BY poh.created_at DESC";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $purchase_orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $purchase_orders[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $purchase_orders]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get purchase order details
function getPurchaseOrderDetails($conn, $po_id) {
    if (!$po_id) {
        echo json_encode(['error' => 'Purchase order ID required']);
        return;
    }
    
    try {
        // Get header
        $stmt = mysqli_prepare($conn, "SELECT poh.*, s.supplier_name, s.supplier_contact, s.supplier_email,
                               e.Fname, e.Lname, pod.delivery_status, pod.expected_delivery_date,
                               poa.approval_status, poa.approval_notes
                               FROM tbl_purchase_order_header poh
                               LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                               LEFT JOIN tbl_employee e ON poh.created_by = e.emp_id
                               LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                               LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                               WHERE poh.purchase_header_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $po_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $header = mysqli_fetch_assoc($result);
        
        // Get details
        $stmt = mysqli_prepare($conn, "SELECT pod.*, p.product_name, p.category, p.unit_price as current_price,
                               b.brand
                               FROM tbl_purchase_order_dtl pod
                               LEFT JOIN tbl_product p ON pod.product_id = p.product_id
                               LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                               WHERE pod.purchase_header_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $po_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $details = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = $row;
        }
        
        echo json_encode(['success' => true, 'header' => $header, 'details' => $details]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Create purchase order
function createPurchaseOrder($conn, $data) {
    try {
        mysqli_begin_transaction($conn);
        
        // Generate PO number
        $po_number = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert header
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_order_header 
                               (po_number, date, time, supplier_id, total_amount, expected_delivery_date, status, created_by) 
                               VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, 'pending', ?)");
        mysqli_stmt_bind_param($stmt, "sids", $po_number, $data['supplier_id'], $data['total_amount'], $data['expected_delivery_date'], $data['created_by']);
        mysqli_stmt_execute($stmt);
        
        $po_id = mysqli_insert_id($conn);
        
        // Insert details
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_order_dtl 
                               (purchase_header_id, product_id, quantity, price, unit_price) 
                               VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data['products'] as $product) {
            mysqli_stmt_bind_param($stmt, "iiidd", $po_id, $product['product_id'], $product['quantity'], $product['unit_price'], $product['unit_price']);
            mysqli_stmt_execute($stmt);
        }
        
        // Create delivery record
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_order_delivery 
                               (purchase_header_id, expected_delivery_date, delivery_status) 
                               VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "is", $po_id, $data['expected_delivery_date']);
        mysqli_stmt_execute($stmt);
        
        // Create approval record
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_order_approval 
                               (purchase_header_id, approval_status) 
                               VALUES (?, 'pending')");
        mysqli_stmt_bind_param($stmt, "is", $po_id, 'pending');
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'po_id' => $po_id, 'po_number' => $po_number]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Approve purchase order
function approvePurchaseOrder($conn, $data) {
    try {
        mysqli_begin_transaction($conn);
        
        // Update approval
        $stmt = mysqli_prepare($conn, "UPDATE tbl_purchase_order_approval 
                               SET approved_by = ?, approval_date = NOW(), approval_status = ?, approval_notes = ? 
                               WHERE purchase_header_id = ?");
        mysqli_stmt_bind_param($stmt, "issi", $data['approved_by'], $data['approval_status'], $data['approval_notes'], $data['purchase_header_id']);
        mysqli_stmt_execute($stmt);
        
        // Update PO status
        $stmt = mysqli_prepare($conn, "UPDATE tbl_purchase_order_header 
                               SET status = ? WHERE purchase_header_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $data['approval_status'], $data['purchase_header_id']);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Update delivery status
function updateDeliveryStatus($conn, $data) {
    try {
        $stmt = mysqli_prepare($conn, "UPDATE tbl_purchase_order_delivery 
                               SET delivery_status = ?, actual_delivery_date = ?, delivery_notes = ? 
                               WHERE purchase_header_id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $data['delivery_status'], $data['actual_delivery_date'], $data['delivery_notes'], $data['purchase_header_id']);
        mysqli_stmt_execute($stmt);
        
        echo json_encode(['success' => true]);
        
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Receive items
function receiveItems($conn, $data) {
    try {
        mysqli_begin_transaction($conn);
        
        // Create receiving header
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_receiving_header 
                               (purchase_header_id, receiving_date, receiving_time, received_by, delivery_receipt_no, notes) 
                               VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $data['purchase_header_id'], $data['received_by'], $data['delivery_receipt_no'], $data['notes']);
        mysqli_stmt_execute($stmt);
        
        $receiving_id = mysqli_insert_id($conn);
        
        // Process received items
        foreach ($data['items'] as $item) {
            // Insert receiving detail
            $stmt = mysqli_prepare($conn, "INSERT INTO tbl_purchase_receiving_dtl 
                                   (receiving_id, product_id, ordered_qty, received_qty, unit_price, batch_number, expiration_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiiidss", $receiving_id, $item['product_id'], $item['ordered_qty'], $item['received_qty'], $item['unit_price'], $item['batch_number'], $item['expiration_date']);
            mysqli_stmt_execute($stmt);
            
            // Update product quantity
            $stmt = mysqli_prepare($conn, "UPDATE tbl_product 
                                   SET quantity = quantity + ? 
                                   WHERE product_id = ? AND location_id = 2");
            mysqli_stmt_bind_param($stmt, "ii", $item['received_qty'], $item['product_id']);
            mysqli_stmt_execute($stmt);
        }
        
        // Update receiving status
        $total_ordered = array_sum(array_column($data['items'], 'ordered_qty'));
        $total_received = array_sum(array_column($data['items'], 'received_qty'));
        $status = ($total_received >= $total_ordered) ? 'completed' : 'partial';
        
        $stmt = mysqli_prepare($conn, "UPDATE tbl_purchase_receiving_header SET status = ? WHERE receiving_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $receiving_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'receiving_id' => $receiving_id]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get receiving list
function getReceivingList($conn) {
    try {
        $query = "SELECT poh.*, s.supplier_name, pod.delivery_status,
                 pod.expected_delivery_date, poa.approval_status
                 FROM tbl_purchase_order_header poh
                 LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                 LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                 LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                 WHERE poa.approval_status = 'approved' 
                 AND pod.delivery_status IN ('delivered', 'partial')
                 AND poh.status = 'approved'
                 ORDER BY pod.expected_delivery_date ASC";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $receiving_list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $receiving_list[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $receiving_list]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

mysqli_close($conn);
?> 