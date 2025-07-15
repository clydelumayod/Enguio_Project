<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        handleGet($pdo, $action);
        break;
    case 'POST':
        handlePost($pdo, $action);
        break;
    case 'PUT':
        handlePut($pdo, $action);
        break;
    case 'DELETE':
        handleDelete($pdo, $action);
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($pdo, $action) {
    switch($action) {
        case 'suppliers':
            getSuppliers($pdo);
            break;
        case 'products':
            getProducts($pdo);
            break;
        case 'purchase_orders':
            getPurchaseOrders($pdo);
            break;
        case 'purchase_order_details':
            $po_id = $_GET['po_id'] ?? null;
            getPurchaseOrderDetails($pdo, $po_id);
            break;
        case 'pending_approvals':
            getPendingApprovals($pdo);
            break;
        case 'delivery_status':
            $po_id = $_GET['po_id'] ?? null;
            getDeliveryStatus($pdo, $po_id);
            break;
        case 'receiving_list':
            getReceivingList($pdo);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($pdo, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'create_purchase_order':
            createPurchaseOrder($pdo, $data);
            break;
        case 'approve_purchase_order':
            approvePurchaseOrder($pdo, $data);
            break;
        case 'update_delivery_status':
            updateDeliveryStatus($pdo, $data);
            break;
        case 'receive_items':
            receiveItems($pdo, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($pdo, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'update_purchase_order':
            updatePurchaseOrder($pdo, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($pdo, $action) {
    switch($action) {
        case 'delete_purchase_order':
            $po_id = $_GET['po_id'] ?? null;
            deletePurchaseOrder($pdo, $po_id);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Get all suppliers
function getSuppliers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM tbl_supplier WHERE status = 'active' ORDER BY supplier_name");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $suppliers]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get all products
function getProducts($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.*, b.brand, s.supplier_name 
                             FROM tbl_product p 
                             LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                             LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                             WHERE p.status = 'active' 
                             ORDER BY p.product_name");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $products]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get all purchase orders
function getPurchaseOrders($pdo) {
    try {
        $stmt = $pdo->query("SELECT poh.*, s.supplier_name, e.Fname, e.Lname,
                             pod.delivery_status, pod.expected_delivery_date,
                             poa.approval_status, poa.approval_date
                             FROM tbl_purchase_order_header poh
                             LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                             LEFT JOIN tbl_employee e ON poh.created_by = e.emp_id
                             LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                             LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                             ORDER BY poh.created_at DESC");
        $purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $purchase_orders]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get purchase order details
function getPurchaseOrderDetails($pdo, $po_id) {
    if (!$po_id) {
        echo json_encode(['error' => 'Purchase order ID required']);
        return;
    }
    
    try {
        // Get header
        $stmt = $pdo->prepare("SELECT poh.*, s.supplier_name, s.supplier_contact, s.supplier_email,
                               e.Fname, e.Lname, pod.delivery_status, pod.expected_delivery_date,
                               poa.approval_status, poa.approval_notes
                               FROM tbl_purchase_order_header poh
                               LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                               LEFT JOIN tbl_employee e ON poh.created_by = e.emp_id
                               LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                               LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                               WHERE poh.purchase_header_id = ?");
        $stmt->execute([$po_id]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get details
        $stmt = $pdo->prepare("SELECT pod.*, p.product_name, p.category, p.unit_price as current_price,
                               b.brand
                               FROM tbl_purchase_order_dtl pod
                               LEFT JOIN tbl_product p ON pod.product_id = p.product_id
                               LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
                               WHERE pod.purchase_header_id = ?");
        $stmt->execute([$po_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'header' => $header, 'details' => $details]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Create purchase order
function createPurchaseOrder($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Generate PO number
        $po_number = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert header
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_order_header 
                               (po_number, date, time, supplier_id, total_amount, expected_delivery_date, status, created_by) 
                               VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, 'pending', ?)");
        $stmt->execute([
            $po_number,
            $data['supplier_id'],
            $data['total_amount'],
            $data['expected_delivery_date'],
            $data['created_by']
        ]);
        
        $po_id = $pdo->lastInsertId();
        
        // Insert details
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_order_dtl 
                               (purchase_header_id, product_id, quantity, price, unit_price) 
                               VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data['products'] as $product) {
            $stmt->execute([
                $po_id,
                $product['product_id'],
                $product['quantity'],
                $product['unit_price'],
                $product['unit_price']
            ]);
        }
        
        // Create delivery record
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_order_delivery 
                               (purchase_header_id, expected_delivery_date, delivery_status) 
                               VALUES (?, ?, 'pending')");
        $stmt->execute([$po_id, $data['expected_delivery_date']]);
        
        // Create approval record
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_order_approval 
                               (purchase_header_id, approval_status) 
                               VALUES (?, 'pending')");
        $stmt->execute([$po_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'po_id' => $po_id, 'po_number' => $po_number]);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Approve purchase order
function approvePurchaseOrder($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Update approval
        $stmt = $pdo->prepare("UPDATE tbl_purchase_order_approval 
                               SET approved_by = ?, approval_date = NOW(), approval_status = ?, approval_notes = ? 
                               WHERE purchase_header_id = ?");
        $stmt->execute([
            $data['approved_by'],
            $data['approval_status'],
            $data['approval_notes'] ?? null,
            $data['purchase_header_id']
        ]);
        
        // Update PO status
        $stmt = $pdo->prepare("UPDATE tbl_purchase_order_header 
                               SET status = ? WHERE purchase_header_id = ?");
        $stmt->execute([$data['approval_status'], $data['purchase_header_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Update delivery status
function updateDeliveryStatus($pdo, $data) {
    try {
        $stmt = $pdo->prepare("UPDATE tbl_purchase_order_delivery 
                               SET delivery_status = ?, actual_delivery_date = ?, delivery_notes = ? 
                               WHERE purchase_header_id = ?");
        $stmt->execute([
            $data['delivery_status'],
            $data['actual_delivery_date'] ?? null,
            $data['delivery_notes'] ?? null,
            $data['purchase_header_id']
        ]);
        
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Receive items
function receiveItems($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Create receiving header
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_receiving_header 
                               (purchase_header_id, receiving_date, receiving_time, received_by, delivery_receipt_no, notes) 
                               VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)");
        $stmt->execute([
            $data['purchase_header_id'],
            $data['received_by'],
            $data['delivery_receipt_no'] ?? null,
            $data['notes'] ?? null
        ]);
        
        $receiving_id = $pdo->lastInsertId();
        
        // Process received items
        foreach ($data['items'] as $item) {
            // Insert receiving detail
            $stmt = $pdo->prepare("INSERT INTO tbl_purchase_receiving_dtl 
                                   (receiving_id, product_id, ordered_qty, received_qty, unit_price, batch_number, expiration_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $receiving_id,
                $item['product_id'],
                $item['ordered_qty'],
                $item['received_qty'],
                $item['unit_price'],
                $item['batch_number'] ?? null,
                $item['expiration_date'] ?? null
            ]);
            
            // Update product quantity
            $stmt = $pdo->prepare("UPDATE tbl_product 
                                   SET quantity = quantity + ? 
                                   WHERE product_id = ? AND location_id = 2"); // Assuming warehouse location_id = 2
            $stmt->execute([$item['received_qty'], $item['product_id']]);
        }
        
        // Update receiving status
        $total_ordered = array_sum(array_column($data['items'], 'ordered_qty'));
        $total_received = array_sum(array_column($data['items'], 'received_qty'));
        $status = ($total_received >= $total_ordered) ? 'completed' : 'partial';
        
        $stmt = $pdo->prepare("UPDATE tbl_purchase_receiving_header SET status = ? WHERE receiving_id = ?");
        $stmt->execute([$status, $receiving_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'receiving_id' => $receiving_id]);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get pending approvals
function getPendingApprovals($pdo) {
    try {
        $stmt = $pdo->query("SELECT poh.*, s.supplier_name, e.Fname, e.Lname,
                             poa.approval_id, poa.approval_notes
                             FROM tbl_purchase_order_header poh
                             LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                             LEFT JOIN tbl_employee e ON poh.created_by = e.emp_id
                             LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                             WHERE poa.approval_status = 'pending'
                             ORDER BY poh.created_at ASC");
        $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $approvals]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get delivery status
function getDeliveryStatus($pdo, $po_id) {
    if (!$po_id) {
        echo json_encode(['error' => 'Purchase order ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_purchase_order_delivery WHERE purchase_header_id = ?");
        $stmt->execute([$po_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $delivery]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Get receiving list
function getReceivingList($pdo) {
    try {
        $stmt = $pdo->query("SELECT poh.*, s.supplier_name, pod.delivery_status,
                             pod.expected_delivery_date, poa.approval_status
                             FROM tbl_purchase_order_header poh
                             LEFT JOIN tbl_supplier s ON poh.supplier_id = s.supplier_id
                             LEFT JOIN tbl_purchase_order_delivery pod ON poh.purchase_header_id = pod.purchase_header_id
                             LEFT JOIN tbl_purchase_order_approval poa ON poh.purchase_header_id = poa.purchase_header_id
                             WHERE poa.approval_status = 'approved' 
                             AND pod.delivery_status IN ('delivered', 'partial')
                             AND poh.status = 'approved'
                             ORDER BY pod.expected_delivery_date ASC");
        $receiving_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $receiving_list]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Update purchase order
function updatePurchaseOrder($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Update header
        $stmt = $pdo->prepare("UPDATE tbl_purchase_order_header 
                               SET supplier_id = ?, total_amount = ?, expected_delivery_date = ? 
                               WHERE purchase_header_id = ?");
        $stmt->execute([
            $data['supplier_id'],
            $data['total_amount'],
            $data['expected_delivery_date'],
            $data['purchase_header_id']
        ]);
        
        // Delete existing details
        $stmt = $pdo->prepare("DELETE FROM tbl_purchase_order_dtl WHERE purchase_header_id = ?");
        $stmt->execute([$data['purchase_header_id']]);
        
        // Insert new details
        $stmt = $pdo->prepare("INSERT INTO tbl_purchase_order_dtl 
                               (purchase_header_id, product_id, quantity, price, unit_price) 
                               VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data['products'] as $product) {
            $stmt->execute([
                $data['purchase_header_id'],
                $product['product_id'],
                $product['quantity'],
                $product['unit_price'],
                $product['unit_price']
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Delete purchase order
function deletePurchaseOrder($pdo, $po_id) {
    if (!$po_id) {
        echo json_encode(['error' => 'Purchase order ID required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete details
        $stmt = $pdo->prepare("DELETE FROM tbl_purchase_order_dtl WHERE purchase_header_id = ?");
        $stmt->execute([$po_id]);
        
        // Delete header
        $stmt = $pdo->prepare("DELETE FROM tbl_purchase_order_header WHERE purchase_header_id = ?");
        $stmt->execute([$po_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 