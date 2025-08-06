<?php
$host = "sql309.infinityfree.com";
$dbname = "if0_38830575_kaka88";
$user = "if0_38830575";
$pass = "Kirebhai121377";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully.<br>";

$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "Table 'users' exists.";
} else {
    echo "Table 'users' does NOT exist.";
}

$conn->close();
?>