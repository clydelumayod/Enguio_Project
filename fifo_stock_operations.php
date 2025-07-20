<?php
/**
 * FIFO Stock Operations
 * Handles First-In-First-Out stock consumption for inventory management
 * 
 * Features:
 * - FIFO stock consumption with batch tracking
 * - Detailed movement logging
 * - Stock transfer support
 * - Sales order processing
 * - Expiration date tracking
 * - Comprehensive error handling
 */

class FIFOStockOperations {
    private $conn;
    private $debug = false;
    
    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }
    
    /**
     * Enable/disable debug logging
     */
    public function setDebug($enabled = true) {
        $this->debug = $enabled;
    }
    
    /**
     * Log debug messages
     */
    private function logDebug($message) {
        if ($this->debug) {
            error_log("[FIFO DEBUG] " . $message);
        }
    }
    
    /**
     * Consume stock using FIFO method
     * 
     * @param int $product_id Product ID
     * @param int $quantity_needed Quantity to consume
     * @param string $reference_no Reference number (PO, SO, etc.)
     * @param string $movement_type Type of movement (SALE, TRANSFER, ADJUSTMENT, etc.)
     * @param string $notes Additional notes
     * @param string $created_by User who initiated the operation
     * @param array $options Additional options
     * @return array Result with success status and details
     */
    public function consumeStockFIFO($product_id, $quantity_needed, $reference_no = '', $movement_type = 'OUT', $notes = '', $created_by = 'admin', $options = []) {
        try {
            $this->logDebug("Starting FIFO consumption for product_id: $product_id, quantity: $quantity_needed");
            
            // Validate inputs
            if ($product_id <= 0 || $quantity_needed <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid product ID or quantity'
                ];
            }
            
            // Start transaction
            $this->conn->beginTransaction();
            
            // Get product information
            $product = $this->getProductInfo($product_id);
            if (!$product) {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
            
            // Check if sufficient stock exists
            $total_available = $this->getTotalAvailableStock($product_id);
            if ($total_available < $quantity_needed) {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => "Insufficient stock. Available: $total_available, Requested: $quantity_needed"
                ];
            }
            
            // Get FIFO stock levels (oldest first)
            $available_stock = $this->getFIFOStockLevels($product_id);
            if (empty($available_stock)) {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => 'No stock available for this product'
                ];
            }
            
            $remaining_quantity = $quantity_needed;
            $consumed_batches = [];
            $total_cost = 0;
            
            // Process each batch in FIFO order
            foreach ($available_stock as $stock) {
                if ($remaining_quantity <= 0) break;
                
                $batch_quantity = min($remaining_quantity, $stock['available_quantity']);
                $new_available = $stock['available_quantity'] - $batch_quantity;
                $batch_cost = $batch_quantity * $stock['unit_cost'];
                
                $this->logDebug("Processing batch {$stock['batch_reference']}: consuming $batch_quantity units");
                
                // Create movement record for consumption
                $movement_id = $this->createMovementRecord([
                    'product_id' => $product_id,
                    'batch_id' => $stock['batch_id'],
                    'movement_type' => $movement_type,
                    'quantity' => $batch_quantity,
                    'remaining_quantity' => $new_available,
                    'unit_cost' => $stock['unit_cost'],
                    'expiration_date' => $stock['expiration_date'],
                    'reference_no' => $reference_no,
                    'notes' => $notes,
                    'created_by' => $created_by
                ]);
                
                // Update stock summary
                $this->updateStockSummary($stock['summary_id'], $new_available);
                
                // Log batch consumption
                $consumed_batches[] = [
                    'batch_reference' => $stock['batch_reference'],
                    'batch_id' => $stock['batch_id'],
                    'quantity_consumed' => $batch_quantity,
                    'unit_cost' => $stock['unit_cost'],
                    'total_cost' => $batch_cost,
                    'movement_id' => $movement_id,
                    'expiration_date' => $stock['expiration_date'],
                    'days_until_expiry' => $stock['days_until_expiry']
                ];
                
                $total_cost += $batch_cost;
                $remaining_quantity -= $batch_quantity;
            }
            
            if ($remaining_quantity > 0) {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => "Insufficient stock. Only " . ($quantity_needed - $remaining_quantity) . " units available"
                ];
            }
            
            // Update main product quantity
            $total_consumed = $quantity_needed - $remaining_quantity;
            $this->updateProductQuantity($product_id, $total_consumed);
            
            // Create consumption summary log
            $this->createConsumptionLog([
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'quantity_consumed' => $total_consumed,
                'total_cost' => $total_cost,
                'reference_no' => $reference_no,
                'movement_type' => $movement_type,
                'consumed_batches' => $consumed_batches,
                'created_by' => $created_by,
                'notes' => $notes
            ]);
            
            $this->conn->commit();
            
            $this->logDebug("FIFO consumption completed successfully. Total consumed: $total_consumed");
            
            return [
                'success' => true,
                'message' => 'Stock consumed using FIFO method',
                'quantity_consumed' => $total_consumed,
                'total_cost' => $total_cost,
                'consumed_batches' => $consumed_batches,
                'product_info' => [
                    'product_id' => $product_id,
                    'product_name' => $product['product_name'],
                    'barcode' => $product['barcode']
                ]
            ];
            
        } catch (Exception $e) {
            if (isset($this->conn)) {
                $this->conn->rollback();
            }
            $this->logDebug("Error in FIFO consumption: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Transfer stock between locations using FIFO
     */
    public function transferStockFIFO($product_id, $quantity, $from_location_id, $to_location_id, $reference_no = '', $notes = '', $created_by = 'admin') {
        try {
            $this->logDebug("Starting FIFO transfer for product_id: $product_id, quantity: $quantity");
            
            // First consume from source location
            $consume_result = $this->consumeStockFIFO(
                $product_id, 
                $quantity, 
                $reference_no, 
                'TRANSFER_OUT', 
                "Transfer from location $from_location_id to $to_location_id. " . $notes,
                $created_by
            );
            
            if (!$consume_result['success']) {
                return $consume_result;
            }
            
            // Add to destination location
            $add_result = $this->addStockToLocation(
                $product_id,
                $quantity,
                $to_location_id,
                $reference_no,
                'TRANSFER_IN',
                "Transfer from location $from_location_id. " . $notes,
                $created_by,
                $consume_result['consumed_batches']
            );
            
            if (!$add_result['success']) {
                // If adding fails, we need to reverse the consumption
                $this->reverseConsumption($product_id, $consume_result['consumed_batches'], $created_by);
                return $add_result;
            }
            
            return [
                'success' => true,
                'message' => 'Stock transferred successfully using FIFO',
                'quantity_transferred' => $quantity,
                'from_location' => $from_location_id,
                'to_location' => $to_location_id,
                'transfer_details' => $consume_result['consumed_batches']
            ];
            
        } catch (Exception $e) {
            $this->logDebug("Error in FIFO transfer: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Transfer error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process sales order with FIFO
     */
    public function processSalesOrderFIFO($order_items, $order_reference, $customer_id = null, $created_by = 'admin') {
        try {
            $this->logDebug("Processing sales order: $order_reference");
            
            $this->conn->beginTransaction();
            
            $processed_items = [];
            $total_order_value = 0;
            
            foreach ($order_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $unit_price = $item['unit_price'];
                
                $consume_result = $this->consumeStockFIFO(
                    $product_id,
                    $quantity,
                    $order_reference,
                    'SALE',
                    "Sales order: $order_reference",
                    $created_by
                );
                
                if (!$consume_result['success']) {
                    $this->conn->rollback();
                    return [
                        'success' => false,
                        'message' => "Failed to process item: " . $consume_result['message']
                    ];
                }
                
                $item_total = $quantity * $unit_price;
                $total_order_value += $item_total;
                
                $processed_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total_price' => $item_total,
                    'fifo_cost' => $consume_result['total_cost'],
                    'profit' => $item_total - $consume_result['total_cost'],
                    'consumed_batches' => $consume_result['consumed_batches']
                ];
            }
            
            // Create sales order record
            $this->createSalesOrderRecord($order_reference, $customer_id, $total_order_value, $created_by);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Sales order processed successfully',
                'order_reference' => $order_reference,
                'total_value' => $total_order_value,
                'processed_items' => $processed_items
            ];
            
        } catch (Exception $e) {
            if (isset($this->conn)) {
                $this->conn->rollback();
            }
            return [
                'success' => false,
                'message' => 'Sales order error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get FIFO stock levels for a product
     */
    private function getFIFOStockLevels($product_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                ss.summary_id,
                ss.batch_id,
                ss.available_quantity,
                ss.unit_cost,
                ss.expiration_date,
                ss.batch_reference,
                b.entry_date,
                DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry
            FROM tbl_stock_summary ss
            JOIN tbl_batch b ON ss.batch_id = b.batch_id
            WHERE ss.product_id = ? AND ss.available_quantity > 0
            ORDER BY b.entry_date ASC, ss.summary_id ASC
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total available stock for a product
     */
    private function getTotalAvailableStock($product_id) {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(available_quantity), 0) as total_available
            FROM tbl_stock_summary
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($result['total_available']);
    }
    
    /**
     * Get product information
     */
    private function getProductInfo($product_id) {
        $stmt = $this->conn->prepare("
            SELECT product_id, product_name, barcode, category, unit_price, quantity
            FROM tbl_product
            WHERE product_id = ? AND status = 'active'
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create movement record
     */
    private function createMovementRecord($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO tbl_stock_movements (
                product_id, batch_id, movement_type, quantity, remaining_quantity,
                unit_cost, expiration_date, reference_no, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['product_id'],
            $data['batch_id'],
            $data['movement_type'],
            $data['quantity'],
            $data['remaining_quantity'],
            $data['unit_cost'],
            $data['expiration_date'],
            $data['reference_no'],
            $data['notes'],
            $data['created_by']
        ]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Update stock summary
     */
    private function updateStockSummary($summary_id, $new_quantity) {
        $stmt = $this->conn->prepare("
            UPDATE tbl_stock_summary 
            SET available_quantity = ?, last_updated = CURRENT_TIMESTAMP
            WHERE summary_id = ?
        ");
        $stmt->execute([$new_quantity, $summary_id]);
    }
    
    /**
     * Update product quantity
     */
    private function updateProductQuantity($product_id, $quantity_to_subtract) {
        $stmt = $this->conn->prepare("
            UPDATE tbl_product 
            SET quantity = quantity - ?,
                stock_status = CASE 
                    WHEN (quantity - ?) <= 0 THEN 'out of stock'
                    WHEN (quantity - ?) <= 10 THEN 'low stock'
                    ELSE 'in stock'
                END
            WHERE product_id = ?
        ");
        $stmt->execute([$quantity_to_subtract, $quantity_to_subtract, $quantity_to_subtract, $product_id]);
    }
    
    /**
     * Create consumption log
     */
    private function createConsumptionLog($data) {
        // This could be extended to create a separate consumption log table
        $this->logDebug("Consumption log: " . json_encode($data));
    }
    
    /**
     * Add stock to location (for transfers)
     */
    private function addStockToLocation($product_id, $quantity, $location_id, $reference_no, $movement_type, $notes, $created_by, $source_batches = []) {
        // This would add stock to the destination location
        // Implementation depends on your specific requirements
        return ['success' => true];
    }
    
    /**
     * Reverse consumption (for error handling)
     */
    private function reverseConsumption($product_id, $consumed_batches, $created_by) {
        // This would reverse the consumption if needed
        $this->logDebug("Reversing consumption for product_id: $product_id");
    }
    
    /**
     * Create sales order record
     */
    private function createSalesOrderRecord($order_reference, $customer_id, $total_value, $created_by) {
        // Implementation depends on your sales order table structure
        $this->logDebug("Creating sales order record: $order_reference");
    }
    
    /**
     * Get FIFO stock report for a product
     */
    public function getFIFOStockReport($product_id) {
        try {
            $stmt = $this->conn->prepare("
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
                    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry,
                    ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order
                FROM tbl_stock_summary ss
                JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE ss.product_id = ? AND ss.available_quantity > 0
                ORDER BY b.entry_date ASC, ss.summary_id ASC
            ");
            $stmt->execute([$product_id]);
            $fifo_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $fifo_stock
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get expiring products report
     */
    public function getExpiringProductsReport($days_threshold = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.barcode,
                    ss.available_quantity,
                    ss.expiration_date,
                    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry,
                    ss.batch_reference,
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
            
            return [
                'success' => true,
                'data' => $expiring_products
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
}

// Example usage and API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // Database connection
    $host = 'localhost';
    $dbname = 'enguio2';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $fifo = new FIFOStockOperations($pdo);
        $fifo->setDebug(true); // Enable debug logging
        
        switch ($action) {
            case 'consume_stock':
                $result = $fifo->consumeStockFIFO(
                    $input['product_id'],
                    $input['quantity'],
                    $input['reference_no'] ?? '',
                    $input['movement_type'] ?? 'OUT',
                    $input['notes'] ?? '',
                    $input['created_by'] ?? 'admin'
                );
                echo json_encode($result);
                break;
                
            case 'transfer_stock':
                $result = $fifo->transferStockFIFO(
                    $input['product_id'],
                    $input['quantity'],
                    $input['from_location_id'],
                    $input['to_location_id'],
                    $input['reference_no'] ?? '',
                    $input['notes'] ?? '',
                    $input['created_by'] ?? 'admin'
                );
                echo json_encode($result);
                break;
                
            case 'process_sales_order':
                $result = $fifo->processSalesOrderFIFO(
                    $input['order_items'],
                    $input['order_reference'],
                    $input['customer_id'] ?? null,
                    $input['created_by'] ?? 'admin'
                );
                echo json_encode($result);
                break;
                
            case 'get_fifo_report':
                $result = $fifo->getFIFOStockReport($input['product_id']);
                echo json_encode($result);
                break;
                
            case 'get_expiring_products':
                $result = $fifo->getExpiringProductsReport($input['days_threshold'] ?? 30);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Unknown action: ' . $action
                ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error: ' . $e->getMessage()
        ]);
    }
}
?> 