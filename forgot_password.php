<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
include 'db.php'; 

if (isset($_POST['submit'])) {
    $email = $_POST['email'];

   
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(50)); // 100 character token

       
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $stmt->execute([$token, $email]);

        
        $mail = new PHPMailer(true);

        try {
            
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'today888bd@gmail.com';
            $mail->Password = 'epvedhepkiboajbi'; // No spaces
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('today888bd@gmail.com', 'KAKA88 Support');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - KAKA88';
            $mail->Body = "
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password:</p>
                <a href='https://kaka88.free.nf/reset_password.php?token=$token'>Reset Password</a>
                <p>This link will expire in 1 hour.</p>
            ";

            $mail->send();
            echo "Reset link has been sent to your email.";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        echo "No account found with that email address.";
    }
}
?>

<html>
<head>
    <title>Forgot Password - KAKA88</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="banner">
    <div class="logo">
        <img src="images/logo.png" alt="KAKA88 Logo">
    </div>
</div>

<h2>Forgot Password</h2>
<form method="post">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit" name="submit">Send Reset Link</button>
</form>

<div class="login-link">
    Remembered? <a href="login.php">Login</a>
</div>
</body>
</html>