<?php
echo "Testing PHP permissions...\n\n";

// Check if exec is enabled
if (function_exists('exec')) {
    echo "exec() function is available\n";
} else {
    echo "exec() function is disabled\n";
}

// Check current user and permissions
echo "\nCurrent user: " . get_current_user() . "\n";
echo "PHP running as: " . exec('whoami') . "\n";

// List available printers
echo "\nListing printers:\n";
$output = [];
exec('wmic printer list brief', $output);
foreach ($output as $line) {
    echo $line . "\n";
}

// Check USB port access
echo "\nTrying to access USB port:\n";
$handle = @fopen("\\\\.\\USB004", "w");
if ($handle === false) {
    echo "Could not open USB004 - Error: " . error_get_last()['message'] . "\n";
} else {
    echo "Successfully opened USB004\n";
    fclose($handle);
}

// Check printer status
echo "\nChecking POS58 Printer(2) status:\n";
exec('wmic printer where name="POS58 Printer(2)" get PrinterState,PrinterStatus', $printerStatus);
foreach ($printerStatus as $line) {
    echo $line . "\n";
} 