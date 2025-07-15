<?php
echo "Checking PDO drivers...\n";
print_r(PDO::getAvailableDrivers());

echo "\nChecking if MySQL PDO is available...\n";
if (in_array('mysql', PDO::getAvailableDrivers())) {
    echo "MySQL PDO driver is available\n";
} else {
    echo "MySQL PDO driver is NOT available\n";
}

echo "\nTrying to connect to database...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio", "root", "");
    echo "Database connection successful!\n";
} catch(PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?> 