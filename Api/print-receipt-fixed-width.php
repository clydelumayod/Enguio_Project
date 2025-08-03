<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function returnJson($success, $message, $data = []) {
    ob_end_clean();
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJson(false, 'Method not allowed');
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        returnJson(false, 'Invalid JSON input');
    }

    $receiptWidth = 32;

    function formatPriceLine($label, $amount, $width) {
        $amountStr = number_format($amount, 2);
        $spaces = $width - strlen($label) - strlen($amountStr);
        return $label . str_repeat(' ', max(0, $spaces)) . $amountStr;
    }

    // Build receipt
    $receipt = "";
    $receipt .= str_repeat("=", $receiptWidth) . "\n";
    $receipt .= str_pad("ENGUIO'S PHARMACY", $receiptWidth, " ", STR_PAD_BOTH) . "\n";
    $receipt .= str_repeat("=", $receiptWidth) . "\n";
    $receipt .= "Date: " . ($input['date'] ?? date('Y-m-d')) . "\n";
    $receipt .= "Time: " . ($input['time'] ?? date('H:i:s')) . "\n";
    $receipt .= "TXN ID: " . ($input['transactionId'] ?? 'N/A') . "\n";
    $receipt .= "Cashier: " . ($input['cashier'] ?? 'Admin') . "\n";
    $receipt .= str_repeat("-", $receiptWidth) . "\n";

    // Items header
    $receipt .= str_pad("QTY", 4) .
                str_pad("ITEM", 14) .
                str_pad("PRICE", 7, ' ', STR_PAD_LEFT) .
                str_pad("TOTAL", 7, ' ', STR_PAD_LEFT) . "\n";
    $receipt .= str_repeat("-", $receiptWidth) . "\n";

    // Items
    if (!empty($input['items'])) {
        foreach ($input['items'] as $item) {
            $name = $item['name'] ?? 'Unknown';
            $qty = $item['quantity'] ?? 1;
            $price = $item['price'] ?? 0;
            $total = $qty * $price;

            if (strlen($name) > 14) {
                $name = substr($name, 0, 11) . "...";
            }

            $receipt .= str_pad($qty, 4) .
                        str_pad($name, 14) .
                        str_pad(number_format($price, 2), 7, ' ', STR_PAD_LEFT) .
                        str_pad(number_format($total, 2), 7, ' ', STR_PAD_LEFT) . "\n";
        }
    } else {
        $receipt .= "No items found\n";
    }

    $receipt .= str_repeat("-", $receiptWidth) . "\n";

    // Totals
    $receipt .= formatPriceLine("TOTAL AMOUNT:", $input['total'] ?? 0, $receiptWidth) . "\n";
    $receipt .= "PAYMENT: " . strtoupper($input['paymentMethod'] ?? 'Unknown') . "\n";

    if (($input['paymentMethod'] ?? '') === 'Cash') {
        $receipt .= formatPriceLine("CASH:", $input['amountPaid'] ?? 0, $receiptWidth) . "\n";
        $receipt .= formatPriceLine("CHANGE:", $input['change'] ?? 0, $receiptWidth) . "\n";
    } elseif (($input['paymentMethod'] ?? '') === 'GCash') {
        if (!empty($input['gcashRef'])) {
            $receipt .= "GCASH REF: " . $input['gcashRef'] . "\n";
        }
    }

    $receipt .= str_repeat("=", $receiptWidth) . "\n";
    $receipt .= str_pad("Thank you!", $receiptWidth, " ", STR_PAD_BOTH) . "\n";
    $receipt .= str_pad("This is your official receipt", $receiptWidth, " ", STR_PAD_BOTH) . "\n";

    // Feed lines
    $receipt .= "\n\n\n\n\n";

    // Windows line endings
    $formattedReceipt = str_replace("\n", "\r\n", $receipt);
    $temp_file = sys_get_temp_dir() . '\\receipt_' . time() . '.txt';
    file_put_contents($temp_file, $formattedReceipt);

    // Send to printer
    $printerName = "XP-58";
    $command = "copy /b \"$temp_file\" \"\\\\localhost\\$printerName\"";
    $output = shell_exec($command);
    $success = (strpos(strtolower($output), 'error') === false && strpos(strtolower($output), 'denied') === false);

    unlink($temp_file); // Cleanup

    if ($success) {
        returnJson(true, 'Receipt printed successfully!', [
            'method' => 'copy_raw',
            'transactionId' => $input['transactionId']
        ]);
    } else {
        returnJson(false, 'Printing failed: ' . $output, [
            'method' => 'copy_raw',
            'transactionId' => $input['transactionId']
        ]);
    }

} catch (Exception $e) {
    returnJson(false, 'Error: ' . $e->getMessage());
}
?>
