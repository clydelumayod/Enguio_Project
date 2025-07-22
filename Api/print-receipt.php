<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3001');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate input
        $input = file_get_contents('php://input');
        if (!$input) {
            throw new Exception("No data received");
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }

        // Create receipt content - simplified for speed
        $receipt = $data['store']['name'] . "\n";
        $receipt .= $data['store']['receipt'] . "\n\n";
        $receipt .= "Date: " . $data['date'] . "\n";
        $receipt .= "Time: " . $data['time'] . "\n";
        $receipt .= "Transaction #: " . $data['transactionId'] . "\n";
        $receipt .= "--------------------------------\n";

        foreach ($data['items'] as $item) {
            $receipt .= $item['name'] . "\n";
            $receipt .= $item['quantity'] . " x " . $item['price'] . " = " . ($item['quantity'] * $item['price']) . "\n";
        }
        
        $receipt .= "--------------------------------\n";
        $receipt .= "Total: " . $data['total'] . "\n";
        $receipt .= "Payment: " . $data['payment']['method'] . "\n";
        $receipt .= "Amount: " . $data['payment']['amount'] . "\n";
        $receipt .= "Change: " . $data['payment']['change'] . "\n";
        
        if ($data['payment']['method'] === 'GCASH' && $data['payment']['reference']) {
            $receipt .= "GCash Ref: " . $data['payment']['reference'] . "\n";
        }
        
        $receipt .= "--------------------------------\n";
        $receipt .= "Thank you for shopping!\n";
        $receipt .= "Please come again!\n";
        $receipt .= "\n\n\n\n\n";  // Feed paper

        // Try direct printer write first
        $handle = fopen("//./PRN", "w");
        
        if ($handle === false) {
            // Fallback to network printer
            $handle = fopen("//localhost/POS58 Printer(2)", "w");
            
            if ($handle === false) {
                throw new Exception("Could not connect to printer");
            }
        }

        // Write in small chunks for better performance
        $chunkSize = 256;
        $length = strlen($receipt);
        for ($i = 0; $i < $length; $i += $chunkSize) {
            $chunk = substr($receipt, $i, $chunkSize);
            fwrite($handle, $chunk);
            fflush($handle);
        }

        fclose($handle);
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        error_log("Print error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} 