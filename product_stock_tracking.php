<?php
/**
 * Product Stock Tracking System
 * Tracks when products were added or restocked with batch information
 * For inventory auditing purposes
 */

// Database connection
require_once 'Api/conn.php';

/**
 * Get Product Stock History with Batch Information
 * 
 * @param int|null $product_id Optional: Filter by specific product
 * @param int|null $location_id Optional: Filter by specific location
 * @param string|null $date_from Optional: Start date (YYYY-MM-DD)
 * @param string|null $date_to Optional: End date (YYYY-MM-DD)
 * @param string|null $batch_reference Optional: Filter by batch reference
 * @return array Array of stock history records
 */
function getProductStockHistory($product_id = null, $location_id = null, $date_from = null, $date_to = null, $batch_reference = null) {
    global $conn;
    
    try {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                b.batch_id,
                b.batch_reference,
                b.entry_date as date_received,
                b.entry_time as time_received,
                b.entry_by,
                b.order_no,
                b.order_ref,
                s.supplier_name,
                l.location_name,
                p.quantity as current_quantity,
                p.unit_price,
                p.expiration,
                p.date_added,
                p.stock_status,
                CASE 
                    WHEN p.date_added = b.entry_date THEN 'New Entry'
                    WHEN p.date_added != b.entry_date THEN 'Restocked'
                    ELSE 'Unknown'
                END as action_type,
                CONCAT(
                    'Product: ', p.product_name, ' | ',
                    'Batch: ', COALESCE(b.batch_reference, 'N/A'), ' | ',
                    'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
                    'Qty: ', p.quantity, ' | ',
                    'Location: ', l.location_name
                ) as audit_summary
            FROM tbl_product p
            LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
            LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        // Add filters
        if ($product_id) {
            $sql .= " AND p.product_id = ?";
            $params[] = $product_id;
        }
        
        if ($location_id) {
            $sql .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        if ($date_from) {
            $sql .= " AND b.entry_date >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND b.entry_date <= ?";
            $params[] = $date_to;
        }
        
        if ($batch_reference) {
            $sql .= " AND b.batch_reference LIKE ?";
            $params[] = "%$batch_reference%";
        }
        
        $sql .= " ORDER BY b.entry_date DESC, b.entry_time DESC, p.product_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getProductStockHistory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Detailed Stock Movement History
 * Uses the existing tbl_stock_movements table for comprehensive tracking
 * 
 * @param int|null $product_id Optional: Filter by specific product
 * @param int|null $location_id Optional: Filter by specific location
 * @param string|null $date_from Optional: Start date (YYYY-MM-DD)
 * @param string|null $date_to Optional: End date (YYYY-MM-DD)
 * @param string $movement_type Optional: Filter by movement type (IN, OUT, ADJUSTMENT)
 * @return array Array of movement history records
 */
function getStockMovementHistory($product_id = null, $location_id = null, $date_from = null, $date_to = null, $movement_type = null) {
    global $conn;
    
    try {
        $sql = "
            SELECT 
                sm.movement_id,
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                b.batch_id,
                b.batch_reference,
                b.entry_date as batch_date_received,
                sm.movement_type,
                sm.quantity,
                sm.remaining_quantity,
                sm.unit_cost,
                sm.expiration_date,
                sm.movement_date,
                sm.reference_no,
                sm.notes,
                sm.created_by,
                s.supplier_name,
                l.location_name,
                CASE 
                    WHEN sm.movement_type = 'IN' THEN 'Stock Added'
                    WHEN sm.movement_type = 'OUT' THEN 'Stock Consumed'
                    WHEN sm.movement_type = 'ADJUSTMENT' THEN 'Stock Adjusted'
                    ELSE 'Unknown'
                END as action_description,
                CONCAT(
                    p.product_name, ' - ',
                    CASE 
                        WHEN sm.movement_type = 'IN' THEN 'Added'
                        WHEN sm.movement_type = 'OUT' THEN 'Consumed'
                        WHEN sm.movement_type = 'ADJUSTMENT' THEN 'Adjusted'
                    END,
                    ' ', sm.quantity, ' units',
                    ' (Batch: ', COALESCE(b.batch_reference, 'N/A'), ')'
                ) as movement_summary
            FROM tbl_stock_movements sm
            JOIN tbl_product p ON sm.product_id = p.product_id
            LEFT JOIN tbl_batch b ON sm.batch_id = b.batch_id
            LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        // Add filters
        if ($product_id) {
            $sql .= " AND p.product_id = ?";
            $params[] = $product_id;
        }
        
        if ($location_id) {
            $sql .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        if ($date_from) {
            $sql .= " AND DATE(sm.movement_date) >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND DATE(sm.movement_date) <= ?";
            $params[] = $date_to;
        }
        
        if ($movement_type) {
            $sql .= " AND sm.movement_type = ?";
            $params[] = $movement_type;
        }
        
        $sql .= " ORDER BY sm.movement_date DESC, p.product_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getStockMovementHistory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Product Stock Summary by Location
 * Shows current stock levels with batch information
 * 
 * @param int|null $location_id Optional: Filter by specific location
 * @return array Array of stock summary records
 */
function getProductStockSummary($location_id = null) {
    global $conn;
    
    try {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                p.quantity as current_stock,
                p.unit_price,
                p.expiration,
                p.date_added,
                p.stock_status,
                b.batch_id,
                b.batch_reference,
                b.entry_date as last_batch_date,
                b.entry_by as last_added_by,
                s.supplier_name,
                l.location_name,
                CONCAT(
                    p.product_name, ' | ',
                    'Stock: ', p.quantity, ' | ',
                    'Last Batch: ', COALESCE(DATE_FORMAT(b.entry_date, '%M %d, %Y'), 'N/A'), ' | ',
                    'Location: ', l.location_name
                ) as stock_summary
            FROM tbl_product p
            LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
            LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        if ($location_id) {
            $sql .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        $sql .= " ORDER BY l.location_name, p.product_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getProductStockSummary: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Products by Batch Reference
 * Useful for tracking specific batches across products
 * 
 * @param string $batch_reference The batch reference to search for
 * @return array Array of products in the specified batch
 */
function getProductsByBatch($batch_reference) {
    global $conn;
    
    try {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                p.quantity,
                p.unit_price,
                p.expiration,
                p.date_added,
                b.batch_id,
                b.batch_reference,
                b.entry_date,
                b.entry_time,
                b.entry_by,
                b.order_no,
                s.supplier_name,
                l.location_name,
                CONCAT(
                    'Batch: ', b.batch_reference, ' | ',
                    'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
                    'Products: ', COUNT(*) OVER(), ' items'
                ) as batch_summary
            FROM tbl_product p
            JOIN tbl_batch b ON p.batch_id = b.batch_id
            LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE b.batch_reference = ? AND p.status = 'active'
            ORDER BY p.product_name
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$batch_reference]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getProductsByBatch: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Stock Audit Report
 * Comprehensive report for inventory auditing
 * 
 * @param string|null $date_from Optional: Start date
 * @param string|null $date_to Optional: End date
 * @param int|null $location_id Optional: Filter by location
 * @return array Audit report data
 */
function getStockAuditReport($date_from = null, $date_to = null, $location_id = null) {
    global $conn;
    
    try {
        $sql = "
            SELECT 
                -- Product Information
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                p.quantity as current_stock,
                p.unit_price,
                p.expiration,
                p.date_added,
                p.stock_status,
                
                -- Batch Information
                b.batch_id,
                b.batch_reference,
                b.entry_date as batch_received_date,
                b.entry_time as batch_received_time,
                b.entry_by as batch_added_by,
                b.order_no,
                b.order_ref,
                
                -- Supplier Information
                s.supplier_name,
                s.supplier_contact,
                
                -- Location Information
                l.location_name,
                
                -- Movement Information (latest)
                (
                    SELECT sm.movement_date 
                    FROM tbl_stock_movements sm 
                    WHERE sm.product_id = p.product_id 
                    ORDER BY sm.movement_date DESC 
                    LIMIT 1
                ) as last_movement_date,
                
                -- Audit Information
                CASE 
                    WHEN p.date_added = b.entry_date THEN 'New Entry'
                    WHEN p.date_added != b.entry_date THEN 'Restocked'
                    ELSE 'Unknown'
                END as entry_type,
                
                CONCAT(
                    'Product: ', p.product_name, ' | ',
                    'Batch: ', COALESCE(b.batch_reference, 'N/A'), ' | ',
                    'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
                    'Current Stock: ', p.quantity, ' | ',
                    'Location: ', l.location_name, ' | ',
                    'Supplier: ', COALESCE(s.supplier_name, 'N/A')
                ) as audit_summary
                
            FROM tbl_product p
            LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
            LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        if ($date_from) {
            $sql .= " AND b.entry_date >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND b.entry_date <= ?";
            $params[] = $date_to;
        }
        
        if ($location_id) {
            $sql .= " AND p.location_id = ?";
            $params[] = $location_id;
        }
        
        $sql .= " ORDER BY l.location_name, b.entry_date DESC, p.product_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getStockAuditReport: " . $e->getMessage());
        return [];
    }
}

// Example usage and API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'data' => [], 'message' => ''];
    
    try {
        switch ($action) {
            case 'stock_history':
                $product_id = $_GET['product_id'] ?? null;
                $location_id = $_GET['location_id'] ?? null;
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                $batch_reference = $_GET['batch_reference'] ?? null;
                
                $data = getProductStockHistory($product_id, $location_id, $date_from, $date_to, $batch_reference);
                $response = ['success' => true, 'data' => $data, 'message' => 'Stock history retrieved successfully'];
                break;
                
            case 'movement_history':
                $product_id = $_GET['product_id'] ?? null;
                $location_id = $_GET['location_id'] ?? null;
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                $movement_type = $_GET['movement_type'] ?? null;
                
                $data = getStockMovementHistory($product_id, $location_id, $date_from, $date_to, $movement_type);
                $response = ['success' => true, 'data' => $data, 'message' => 'Movement history retrieved successfully'];
                break;
                
            case 'stock_summary':
                $location_id = $_GET['location_id'] ?? null;
                
                $data = getProductStockSummary($location_id);
                $response = ['success' => true, 'data' => $data, 'message' => 'Stock summary retrieved successfully'];
                break;
                
            case 'products_by_batch':
                $batch_reference = $_GET['batch_reference'] ?? '';
                
                if (empty($batch_reference)) {
                    $response = ['success' => false, 'message' => 'Batch reference is required'];
                } else {
                    $data = getProductsByBatch($batch_reference);
                    $response = ['success' => true, 'data' => $data, 'message' => 'Products by batch retrieved successfully'];
                }
                break;
                
            case 'audit_report':
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                $location_id = $_GET['location_id'] ?? null;
                
                $data = getStockAuditReport($date_from, $date_to, $location_id);
                $response = ['success' => true, 'data' => $data, 'message' => 'Audit report generated successfully'];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Invalid action specified'];
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Example usage functions for direct PHP calls
function exampleUsage() {
    echo "<h2>Product Stock Tracking Examples</h2>\n";
    
    // Example 1: Get stock history for all products
    echo "<h3>1. All Product Stock History</h3>\n";
    $history = getProductStockHistory();
    echo "Found " . count($history) . " records\n";
    
    // Example 2: Get stock history for specific product
    echo "<h3>2. Stock History for Product ID 1</h3>\n";
    $productHistory = getProductStockHistory(1);
    echo "Found " . count($productHistory) . " records for product ID 1\n";
    
    // Example 3: Get movement history
    echo "<h3>3. Stock Movement History</h3>\n";
    $movements = getStockMovementHistory();
    echo "Found " . count($movements) . " movement records\n";
    
    // Example 4: Get stock summary by location
    echo "<h3>4. Stock Summary by Location</h3>\n";
    $summary = getProductStockSummary();
    echo "Found " . count($summary) . " stock summary records\n";
    
    // Example 5: Get audit report
    echo "<h3>5. Stock Audit Report</h3>\n";
    $audit = getStockAuditReport();
    echo "Found " . count($audit) . " audit records\n";
}

// Uncomment the line below to run examples
// exampleUsage();
?>

<!-- HTML Interface for Testing -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Stock Tracking System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .results { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Product Stock Tracking System</h1>
        
        <div class="section">
            <h2>Stock History</h2>
            <form id="stockHistoryForm">
                <div class="form-group">
                    <label for="product_id">Product ID (optional):</label>
                    <input type="number" id="product_id" name="product_id">
                </div>
                <div class="form-group">
                    <label for="location_id">Location ID (optional):</label>
                    <input type="number" id="location_id" name="location_id">
                </div>
                <div class="form-group">
                    <label for="date_from">Date From (YYYY-MM-DD):</label>
                    <input type="date" id="date_from" name="date_from">
                </div>
                <div class="form-group">
                    <label for="date_to">Date To (YYYY-MM-DD):</label>
                    <input type="date" id="date_to" name="date_to">
                </div>
                <button type="submit">Get Stock History</button>
            </form>
            <div id="stockHistoryResults" class="results"></div>
        </div>
        
        <div class="section">
            <h2>Movement History</h2>
            <form id="movementHistoryForm">
                <div class="form-group">
                    <label for="movement_product_id">Product ID (optional):</label>
                    <input type="number" id="movement_product_id" name="product_id">
                </div>
                <div class="form-group">
                    <label for="movement_type">Movement Type:</label>
                    <select id="movement_type" name="movement_type">
                        <option value="">All</option>
                        <option value="IN">IN</option>
                        <option value="OUT">OUT</option>
                        <option value="ADJUSTMENT">ADJUSTMENT</option>
                    </select>
                </div>
                <button type="submit">Get Movement History</button>
            </form>
            <div id="movementHistoryResults" class="results"></div>
        </div>
        
        <div class="section">
            <h2>Stock Summary</h2>
            <form id="stockSummaryForm">
                <div class="form-group">
                    <label for="summary_location_id">Location ID (optional):</label>
                    <input type="number" id="summary_location_id" name="location_id">
                </div>
                <button type="submit">Get Stock Summary</button>
            </form>
            <div id="stockSummaryResults" class="results"></div>
        </div>
        
        <div class="section">
            <h2>Products by Batch</h2>
            <form id="batchForm">
                <div class="form-group">
                    <label for="batch_reference">Batch Reference:</label>
                    <input type="text" id="batch_reference" name="batch_reference" required>
                </div>
                <button type="submit">Get Products by Batch</button>
            </form>
            <div id="batchResults" class="results"></div>
        </div>
        
        <div class="section">
            <h2>Audit Report</h2>
            <form id="auditForm">
                <div class="form-group">
                    <label for="audit_date_from">Date From (YYYY-MM-DD):</label>
                    <input type="date" id="audit_date_from" name="date_from">
                </div>
                <div class="form-group">
                    <label for="audit_date_to">Date To (YYYY-MM-DD):</label>
                    <input type="date" id="audit_date_to" name="date_to">
                </div>
                <div class="form-group">
                    <label for="audit_location_id">Location ID (optional):</label>
                    <input type="number" id="audit_location_id" name="location_id">
                </div>
                <button type="submit">Generate Audit Report</button>
            </form>
            <div id="auditResults" class="results"></div>
        </div>
    </div>

    <script>
        // JavaScript for form handling
        document.addEventListener('DOMContentLoaded', function() {
            // Stock History Form
            document.getElementById('stockHistoryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('action', 'stock_history');
                
                fetch('product_stock_tracking.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        displayResults('stockHistoryResults', data);
                    });
            });
            
            // Movement History Form
            document.getElementById('movementHistoryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('action', 'movement_history');
                
                fetch('product_stock_tracking.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        displayResults('movementHistoryResults', data);
                    });
            });
            
            // Stock Summary Form
            document.getElementById('stockSummaryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('action', 'stock_summary');
                
                fetch('product_stock_tracking.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        displayResults('stockSummaryResults', data);
                    });
            });
            
            // Batch Form
            document.getElementById('batchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('action', 'products_by_batch');
                
                fetch('product_stock_tracking.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        displayResults('batchResults', data);
                    });
            });
            
            // Audit Form
            document.getElementById('auditForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('action', 'audit_report');
                
                fetch('product_stock_tracking.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        displayResults('auditResults', data);
                    });
            });
        });
        
        function displayResults(elementId, data) {
            const element = document.getElementById(elementId);
            
            if (!data.success) {
                element.innerHTML = '<p style="color: red;">Error: ' + data.message + '</p>';
                return;
            }
            
            if (data.data.length === 0) {
                element.innerHTML = '<p>No data found.</p>';
                return;
            }
            
            let html = '<h3>Results (' + data.data.length + ' records)</h3>';
            html += '<table><thead><tr>';
            
            // Create table headers
            const headers = Object.keys(data.data[0]);
            headers.forEach(header => {
                html += '<th>' + header.replace(/_/g, ' ').toUpperCase() + '</th>';
            });
            html += '</tr></thead><tbody>';
            
            // Create table rows
            data.data.forEach(row => {
                html += '<tr>';
                headers.forEach(header => {
                    html += '<td>' + (row[header] || 'N/A') + '</td>';
                });
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            element.innerHTML = html;
        }
    </script>
</body>
</html> 