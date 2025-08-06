<?php
session_start();
include("db.php");

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT balance FROM users WHERE id='$user_id'");
$row = $result->fetch_assoc();

echo $row['balance'];
?>