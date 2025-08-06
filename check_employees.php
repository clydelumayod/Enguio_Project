<?php
// Check available employees
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "=== Available Employees ===\n";

$sql = "SELECT emp_id, Fname, Lname, username, status FROM tbl_employee WHERE status = 'Active' ORDER BY emp_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["emp_id"] . " | Name: " . $row["Fname"] . " " . $row["Lname"] . " | Username: " . $row["username"] . " | Status: " . $row["status"] . "\n";
    }
} else {
    echo "No active employees found\n";
}

echo "\n=== Total Employees (including inactive) ===\n";
$sql2 = "SELECT emp_id, Fname, Lname, username, status FROM tbl_employee ORDER BY emp_id";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    while($row = $result2->fetch_assoc()) {
        echo "ID: " . $row["emp_id"] . " | Name: " . $row["Fname"] . " " . $row["Lname"] . " | Username: " . $row["username"] . " | Status: " . $row["status"] . "\n";
    }
} else {
    echo "No employees found\n";
}

$conn->close();
?> 