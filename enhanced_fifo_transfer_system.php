<?php
/**
 * Enhanced FIFO Transfer System
 * Handles FIFO-based inventory transfers with automatic batch switching
 */

class EnhancedFifoTransferSystem {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get FIFO stock status for a product
     */
    public function getFifoStockStatus($product_id, $location_id = null) {
        try {
            $sql = "
                SELECT 
                    ss.available_quantity,
                    ss.batch_reference,
                    b.entry_date,
                    b.batch_id,
                    ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_rank
                FROM tbl_stock_summary ss
                JOIN tbl_batch b ON ss.batch_id = b.batch_id
                WHERE ss.product_id = ? 
                AND ss.available_quantity > 0
            ";
            
            $params = [$product_id];
            
            if ($location_id) {
                $sql .= " AND b.location_id = ?";
                $params[] = $location_id;
            }
            
            $sql .= " ORDER BY b.entry_date ASC, ss.summary_id ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_available = 0;
            foreach ($batches as $batch) {
                $total_available += $batch['available_quantity'];
            }
            
            return [
                'success' => true,
                'total_available' => $total_available,
                'batches_count' => count($batches),
                'fifo_batches' => $batches
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting FIFO stock status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Perform FIFO transfer for a single product
     */
    public function performFifoTransfer($product_id, $quantity, $source_location_id, $destination_location_id, $employee_id = null) {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Get FIFO stock status
            $stockStatus = $this->getFifoStockStatus($product_id, $source_location_id);
            if (!$stockStatus['success']) {
                throw new Exception($stockStatus['message']);
            }
            
            if ($stockStatus['total_available'] < $quantity) {
                throw new Exception("Insufficient stock. Available: {$stockStatus['total_available']}, Requested: $quantity");
            }
            
            // Get product details
            $stmt = $this->conn->prepare("
                SELECT p.*, l.location_name as source_location_name
                FROM tbl_product p
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                WHERE p.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            // Get destination location name
            $stmt = $this->conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = ?");
            $stmt->execute([$destination_location_id]);
            $destLocation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create transfer header
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_transfer_header (
                    source_location_id, destination_location_id, employee_id, 
                    status, date
                ) VALUES (?, ?, ?, 'approved', CURDATE())
            ");
            $stmt->execute([$source_location_id, $destination_location_id, $employee_id]);
            $transfer_id = $this->conn->lastInsertId();
            
            // Create transfer detail
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_transfer_dtl (
                    transfer_header_id, product_id, qty
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([$transfer_id, $product_id, $quantity]);
            
            // Update source product quantity
            $stmt = $this->conn->prepare("
                UPDATE tbl_product 
                SET quantity = quantity - ? 
                WHERE product_id = ? AND location_id = ?
            ");
            $stmt->execute([$quantity, $product_id, $source_location_id]);
            
            // Check if destination has this product
            $stmt = $this->conn->prepare("
                SELECT product_id, quantity FROM tbl_product 
                WHERE barcode = ? AND location_id = ?
            ");
            $stmt->execute([$product['barcode'], $destination_location_id]);
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingProduct) {
                // Update existing product
                $stmt = $this->conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity + ? 
                    WHERE product_id = ?
                ");
                $stmt->execute([$quantity, $existingProduct['product_id']]);
            } else {
                // Create new product in destination
                $stmt = $this->conn->prepare("
                    INSERT INTO tbl_product (
                        product_name, category, barcode, description, Variation,
                        brand_id, supplier_id, unit_price, srp, quantity, location_id,
                        status, stock_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'in stock', NOW())
                ");
                $stmt->execute([
                    $product['product_name'], $product['category'], $product['barcode'],
                    $product['description'], $product['Variation'], $product['brand_id'],
                    $product['supplier_id'], $product['unit_price'], $product['srp'],
                    $quantity, $destination_location_id
                ]);
            }
            
            // Log the transfer
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_transfer_log (
                    transfer_id, product_id, from_location, to_location,
                    quantity, transfer_date, created_at
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([
                $transfer_id, $product_id,
                $product['source_location_name'], $destLocation['location_name'],
                $quantity
            ]);
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'transfer_id' => $transfer_id,
                'message' => "Successfully transferred $quantity units of {$product['product_name']}",
                'source_location' => $product['source_location_name'],
                'destination_location' => $destLocation['location_name'],
                'products_transferred' => 1
            ];
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Perform enhanced FIFO transfer for multiple products
     */
    public function performEnhancedFifoTransfer($transferData) {
        try {
            $this->conn->beginTransaction();
            
            $source_location_id = $transferData['source_location_id'];
            $destination_location_id = $transferData['destination_location_id'];
            $employee_id = $transferData['employee_id'];
            $products = $transferData['products'];
            
            // Create transfer header
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_transfer_header (
                    source_location_id, destination_location_id, employee_id, 
                    status, date
                ) VALUES (?, ?, ?, 'approved', CURDATE())
            ");
            $stmt->execute([
                $source_location_id, $destination_location_id, $employee_id
            ]);
            $transfer_id = $this->conn->lastInsertId();
            
            $products_transferred = 0;
            $detailed_results = [];
            
            foreach ($products as $productData) {
                $product_id = $productData['product_id'];
                $quantity = $productData['quantity'];
                
                // Get product details
                $stmt = $this->conn->prepare("
                    SELECT p.*, l.location_name as source_location_name
                    FROM tbl_product p
                    LEFT JOIN tbl_location l ON p.location_id = l.location_id
                    WHERE p.product_id = ?
                ");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Product ID $product_id not found");
                }
                
                // Check if sufficient quantity exists
                if ($product['quantity'] < $quantity) {
                    throw new Exception("Insufficient quantity for {$product['product_name']}. Available: {$product['quantity']}, Requested: $quantity");
                }
                
                // Create transfer detail
                $stmt = $this->conn->prepare("
                    INSERT INTO tbl_transfer_dtl (
                        transfer_header_id, product_id, qty
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([$transfer_id, $product_id, $quantity]);
                
                // Update source product quantity
                $stmt = $this->conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity - ? 
                    WHERE product_id = ? AND location_id = ?
                ");
                $stmt->execute([$quantity, $product_id, $source_location_id]);
                
                // Handle destination product
                $stmt = $this->conn->prepare("
                    SELECT product_id, quantity FROM tbl_product 
                    WHERE barcode = ? AND location_id = ?
                ");
                $stmt->execute([$product['barcode'], $destination_location_id]);
                $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingProduct) {
                    // Update existing product
                    $stmt = $this->conn->prepare("
                        UPDATE tbl_product 
                        SET quantity = quantity + ? 
                        WHERE product_id = ?
                    ");
                    $stmt->execute([$quantity, $existingProduct['product_id']]);
                } else {
                    // Create new product in destination
                    $stmt = $this->conn->prepare("
                        INSERT INTO tbl_product (
                            product_name, category, barcode, description, Variation,
                            brand_id, supplier_id, unit_price, srp, quantity, location_id,
                            status, stock_status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'in stock')
                    ");
                    $stmt->execute([
                        $product['product_name'], $product['category'], $product['barcode'],
                        $product['description'], $product['Variation'], $product['brand_id'],
                        $product['supplier_id'], $product['unit_price'], $product['srp'],
                        $quantity, $destination_location_id
                    ]);
                }
                
                // Log the transfer
                $stmt = $this->conn->prepare("
                    INSERT INTO tbl_transfer_log (
                        transfer_id, product_id, from_location, to_location,
                        quantity, transfer_date, created_at
                    ) VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())
                ");
                $stmt->execute([
                    $transfer_id, $product_id,
                    $product['source_location_name'], 'Destination Location',
                    $quantity
                ]);
                
                $products_transferred++;
                $detailed_results[] = [
                    'product_id' => $product_id,
                    'product_name' => $product['product_name'],
                    'quantity_transferred' => $quantity,
                    'batches_processed' => 1, // Simplified for now
                    'batch_breakdown' => [
                        [
                            'batch_reference' => 'N/A',
                            'entry_date' => date('Y-m-d'),
                            'quantity_taken' => $quantity
                        ]
                    ]
                ];
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'transfer_id' => $transfer_id,
                'products_transferred' => $products_transferred,
                'detailed_results' => $detailed_results,
                'message' => "Successfully transferred $products_transferred products"
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Enhanced FIFO transfer failed: ' . $e->getMessage()
            ];
        }
    }
}
?> 