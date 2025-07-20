<?php
include 'index.php';
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Extension: " . (extension_loaded('pdo') ? 'Loaded' : 'Not Loaded') . "\n";
echo "PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Loaded') . "\n";

if (extension_loaded('pdo')) {
    echo "Available PDO drivers:\n";
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO extension is not loaded!\n";
}

// Check php.ini location
echo "\nPHP INI location: " . php_ini_loaded_file() . "\n";
?> 