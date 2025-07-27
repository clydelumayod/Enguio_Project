<?php
require_once 'Api/conn.php';

/**
 * Performs FIFO-based product transfer between locations
 * 
 * @param int $product_barcode - The barcode of the product to transfer
 * @param int $source_location_id - Source location ID
 * @param int $destination_location_id - Destination location ID  
 * @param int $requested_quantity - Quantity to transfer
 * @param int $employee_id - Employee performing the transfer
 * @return array - Result with success status and details
 */
function performFifoTransfer($product_barcode, $source_location_id, $destination_location_id, $requested_quantity, $employee_id) {
    global $conn;
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Step 1: Validate input parameters
        if ($requested_quantity <= 0) {
            throw new Exception("Requested quantity must be greater than 0");
        }
        
        if ($source_location_id == $destination_location_id) {
            throw new Exception("Source and destination locations cannot be the same");
        }
        
        // Step 2: Get available product batches from source location ordered by FIFO (oldest first)
        $stmt = $conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.quantity as available_quantity,
                p.unit_price,
                p.batch_id,
                p.expiration,
                b.entry_date,
                b.batch_reference
            FROM tbl_product p
            INNER JOIN tbl_batch b ON p.batch_id = b.batch_id
            WHERE p.barcode = :barcode 
                AND p.location_id = :source_location_id 
                AND p.status = 'active'
                AND p.quantity > 0
            ORDER BY b.entry_date ASC, p.product_id ASC
        ");
        
        $stmt->bindParam(':barcode', $product_barcode);
        $stmt->bindParam(':source_location_id', $source_location_id);
        $stmt->execute();
        
        $available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($available_batches)) {
            throw new Exception("No available stock found for this product in the source location");
        }
        
        // Step 3: Check if total available quantity is sufficient
        $total_available = array_sum(array_column($available_batches, 'available_quantity'));
        
        if ($total_available < $requested_quantity) {
            throw new Exception("Insufficient stock. Available: {$total_available}, Requested: {$requested_quantity}");
        }
        
        // Step 4: Create transfer header record
        $stmt = $conn->prepare("
            INSERT INTO tbl_transfer_header (date, source_location_id, destination_location_id, employee_id, status) 
            VALUES (CURDATE(), :source_location_id, :destination_location_id, :employee_id, 'approved')
        ");
        
        $stmt->bindParam(':source_location_id', $source_location_id);
        $stmt->bindParam(':destination_location_id', $destination_location_id);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();
        
        $transfer_header_id = $conn->lastInsertId();
        
        // Step 5: Process FIFO transfer
        $remaining_quantity = $requested_quantity;
        $transfer_details = [];
        $updated_products = [];
        
        foreach ($available_batches as $batch) {
            if ($remaining_quantity <= 0) {
                break;
            }
            
            $quantity_from_this_batch = min($remaining_quantity, $batch['available_quantity']);
            
            // Insert transfer detail record
            $stmt = $conn->prepare("
                INSERT INTO tbl_transfer_dtl (transfer_header_id, product_id, qty) 
                VALUES (:transfer_header_id, :product_id, :qty)
            ");
            
            $stmt->bindParam(':transfer_header_id', $transfer_header_id);
            $stmt->bindParam(':product_id', $batch['product_id']);
            $stmt->bindParam(':qty', $quantity_from_this_batch);
            $stmt->execute();
            
            // Update source product quantity
            $new_source_quantity = $batch['available_quantity'] - $quantity_from_this_batch;
            
            $stmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = :new_quantity,
                    stock_status = CASE 
                        WHEN :new_quantity = 0 THEN 'out of stock'
                        WHEN :new_quantity <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                WHERE product_id = :product_id
            ");
            
            $stmt->bindParam(':new_quantity', $new_source_quantity);
            $stmt->bindParam(':product_id', $batch['product_id']);
            $stmt->execute();
            
            // Check if product exists in destination location with same batch
            $stmt = $conn->prepare("
                SELECT product_id, quantity 
                FROM tbl_product 
                WHERE barcode = :barcode 
                    AND location_id = :destination_location_id 
                    AND batch_id = :batch_id
                    AND status = 'active'
            ");
            
            $stmt->bindParam(':barcode', $product_barcode);
            $stmt->bindParam(':destination_location_id', $destination_location_id);
            $stmt->bindParam(':batch_id', $batch['batch_id']);
            $stmt->execute();
            
            $existing_destination_product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_destination_product) {
                // Update existing product in destination
                $new_dest_quantity = $existing_destination_product['quantity'] + $quantity_from_this_batch;
                
                $stmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = :new_quantity,
                        stock_status = CASE 
                            WHEN :new_quantity = 0 THEN 'out of stock'
                            WHEN :new_quantity <= 10 THEN 'low stock'
                            ELSE 'in stock'
                        END
                    WHERE product_id = :product_id
                ");
                
                $stmt->bindParam(':new_quantity', $new_dest_quantity);
                $stmt->bindParam(':product_id', $existing_destination_product['product_id']);
                $stmt->execute();
                
            } else {
                // Create new product record in destination location
                $stmt = $conn->prepare("
                    INSERT INTO tbl_product (
                        product_name, category, barcode, description, prescription, bulk,
                        expiration, quantity, unit_price, brand_id, supplier_id, 
                        location_id, batch_id, status, Variation, stock_status, date_added
                    ) SELECT 
                        product_name, category, barcode, description, prescription, bulk,
                        expiration, :quantity, unit_price, brand_id, supplier_id,
                        :destination_location_id, batch_id, status, Variation,
                        CASE 
                            WHEN :quantity = 0 THEN 'out of stock'
                            WHEN :quantity <= 10 THEN 'low stock'
                            ELSE 'in stock'
                        END,
                        CURDATE()
                    FROM tbl_product 
                    WHERE product_id = :source_product_id
                ");
                
                $stmt->bindParam(':quantity', $quantity_from_this_batch);
                $stmt->bindParam(':destination_location_id', $destination_location_id);
                $stmt->bindParam(':source_product_id', $batch['product_id']);
                $stmt->execute();
            }
            
            // Record transfer details for response
            $transfer_details[] = [
                'batch_id' => $batch['batch_id'],
                'batch_reference' => $batch['batch_reference'],
                'entry_date' => $batch['entry_date'],
                'quantity_transferred' => $quantity_from_this_batch,
                'remaining_in_batch' => $new_source_quantity
            ];
            
            $updated_products[] = [
                'product_id' => $batch['product_id'],
                'product_name' => $batch['product_name'],
                'old_quantity' => $batch['available_quantity'],
                'new_quantity' => $new_source_quantity,
                'transferred' => $quantity_from_this_batch
            ];
            
            $remaining_quantity -= $quantity_from_this_batch;
        }
        
        // Commit transaction
        $conn->commit();
        
        // Get location names for response
        $stmt = $conn->prepare("SELECT location_name FROM tbl_location WHERE location_id = :location_id");
        
        $stmt->bindParam(':location_id', $source_location_id);
        $stmt->execute();
        $source_location_name = $stmt->fetchColumn();
        
        $stmt->bindParam(':location_id', $destination_location_id);
        $stmt->execute();
        $destination_location_name = $stmt->fetchColumn();
        
        return [
            'success' => true,
            'message' => 'FIFO transfer completed successfully',
            'transfer_header_id' => $transfer_header_id,
            'details' => [
                'product_barcode' => $product_barcode,
                'source_location' => $source_location_name,
                'destination_location' => $destination_location_name,
                'total_quantity_transferred' => $requested_quantity,
                'batches_used' => count($transfer_details),
                'transfer_breakdown' => $transfer_details,
                'updated_products' => $updated_products
            ]
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        
        return [
            'success' => false,
            'message' => 'Transfer failed: ' . $e->getMessage(),
            'error_details' => [
                'product_barcode' => $product_barcode ?? null,
                'source_location_id' => $source_location_id ?? null,
                'destination_location_id' => $destination_location_id ?? null,
                'requested_quantity' => $requested_quantity ?? null
            ]
        ];
    }
}

/**
 * Get available stock for a product in a specific location
 * 
 * @param int $product_barcode - Product barcode
 * @param int $location_id - Location ID
 * @return array - Available stock details
 */
function getAvailableStock($product_barcode, $location_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.quantity,
                p.unit_price,
                p.expiration,
                p.stock_status,
                b.batch_reference,
                b.entry_date,
                l.location_name
            FROM tbl_product p
            INNER JOIN tbl_batch b ON p.batch_id = b.batch_id
            INNER JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.barcode = :barcode 
                AND p.location_id = :location_id 
                AND p.status = 'active'
                AND p.quantity > 0
            ORDER BY b.entry_date ASC
        ");
        
        $stmt->bindParam(':barcode', $product_barcode);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->execute();
        
        $stock_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_quantity = array_sum(array_column($stock_details, 'quantity'));
        
        return [
            'success' => true,
            'total_available' => $total_quantity,
            'batches' => $stock_details
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error retrieving stock: ' . $e->getMessage()
        ];
    }
}

/**
 * Example usage function
 */
function exampleUsage() {
    // Example: Transfer 50 units of product with barcode 1000000000015 
    // from warehouse (location_id: 2) to convenience store (location_id: 4)
    // performed by employee with ID 21
    
    $result = performFifoTransfer(
        1000000000015,  // product barcode (C2 Apple)
        2,              // source location (warehouse)
        4,              // destination location (convenience)
        50,             // quantity to transfer
        21              // employee ID
    );
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}

// Uncomment the line below to test the function
// exampleUsage();
?> 