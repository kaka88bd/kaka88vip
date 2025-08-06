<?php
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        die('Invalid or expired token.');
    }
} else {
    die('No token provided.');
}

if (isset($_POST['submit'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        echo "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        echo "Password must be at least 6 characters.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        echo "Password reset successful! <a href='login.php'>Login Now</a>";
    }
}
?>

<html>
<head>
    <title>Reset Password - KAKA88</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="banner">
    <div class="logo">
        <img src="images/logo.png" alt="KAKA88 Logo">
    </div>
</div>

<h2>Reset Password</h2>
<form method="post">
    <input type="password" name="password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
    <button type="submit" name="submit">Reset Password</button>
</form>
</body>
</html>