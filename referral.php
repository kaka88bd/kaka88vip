<?php
// This file handles referral link redirects
// Place this in your root directory or configure your .htaccess to handle the routing

session_start();
include 'db.php';

// Get the referral code from the URL
$referral_code = '';
$request_uri = $_SERVER['REQUEST_URI'];

// Extract referral code from URL like https://kaka88.free.nf/ABC123
if (preg_match('/\/([A-Z]{3,4}\d{2,3})$/', $request_uri, $matches)) {
    $referral_code = $matches[1];
}

if (!empty($referral_code)) {
    // Check if referral code exists in database
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE referral_code = ?");
    $stmt->bind_param("s", $referral_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $referrer = $result->fetch_assoc();
        
        // Store referral code in session for registration
        $_SESSION['referral_code'] = $referral_code;
        $_SESSION['referrer_username'] = $referrer['username'];
        
        // Redirect to registration page
        header("Location: register.php?ref=" . $referral_code);
        exit();
    } else {
        // Invalid referral code, redirect to homepage
        header("Location: index.php");
        exit();
    }
} else {
    // No referral code, redirect to homepage
    header("Location: index.php");
    exit();
}
?>