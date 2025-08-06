<?php
// db.php

$host = "sql104.infinityfree.com";
$dbname = "if0_39535416_kaka88";
$username = "if0_39535416";
$password = "Kirebhai121377";

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    ?>