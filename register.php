<?php
session_start();
include 'db.php';
include 'config.php';

$error = '';
$success = '';
$referral_code = '';
$referrer_username = ''; // This variable will no longer be used for display

// Check for referral code from URL or session
if (isset($_GET['ref'])) {
    $referral_code = $_GET['ref'];
    $_SESSION['referral_code'] = $referral_code;
} elseif (isset($_SESSION['referral_code'])) {
    $referral_code = $_SESSION['referral_code'];
}

// The logic to fetch referrer_username is removed as it's no longer displayed.
// The referral_code is still captured and passed via the hidden input.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordRaw = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $referredBy = $_POST['referral_code']; // This will be the referral code from the hidden input

    // Validation
    if (empty($username) || empty($email) || empty($passwordRaw)) {
        $error = 'All fields are required.';
    } elseif ($passwordRaw !== $confirmPassword) {
        $error = "Passwords do not match!";
    } elseif (strlen($passwordRaw) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
            
            // Generate unique referral code for new user (3-4 letters + 2-3 numbers)
            do {
                $letters = '';
                $numbers = '';
                
                // Generate 3-4 random letters
                $letter_count = rand(3, 4);
                for ($i = 0; $i < $letter_count; $i++) {
                    $letters .= chr(rand(65, 90)); // A-Z
                }
                
                // Generate 2-3 random numbers
                $number_count = rand(2, 3);
                for ($i = 0; $i < $number_count; $i++) {
                    $numbers .= rand(0, 9);
                }
                
                $new_referral_code = $letters . $numbers;
                
                // Check if this code already exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
                $check_stmt->bind_param("s", $new_referral_code);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $exists = $check_result->num_rows > 0;
                $check_stmt->close();
                
            } while ($exists);

            // Create tables if they don't exist (ensure these are run at least once, e.g., on first setup or via a separate script)
            $conn->query("CREATE TABLE IF NOT EXISTS referral_earnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                referrer_id INT NOT NULL,
                referred_user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referrer_id) REFERENCES users(id),
                FOREIGN KEY (referred_user_id) REFERENCES users(id)
            )");

            $conn->query("CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type ENUM('deposit', 'withdraw', 'referral_bonus', 'commission') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");

            // Insert new user with proper column names
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, referral_code, referred_by_code, balance, created_at) VALUES (?, ?, ?, ?, ?, 0.00, NOW())");
            $stmt->bind_param("sssss", $username, $password, $email, $new_referral_code, $referredBy);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // If user was referred, add bonus to referrer
                if (!empty($referredBy)) {
                    // Get referrer ID
                    $ref_stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $ref_stmt->bind_param("s", $referredBy);
                    $ref_stmt->execute();
                    $ref_result = $ref_stmt->get_result();
                    
                    if ($ref_result->num_rows > 0) {
                        $referrer = $ref_result->fetch_assoc();
                        $referrer_id = $referrer['id'];
                        
                        // Add signup bonus to referrer
                        $signup_bonus = 10.00; // 10 BDT signup bonus
                        $bonus_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $bonus_stmt->bind_param("di", $signup_bonus, $referrer_id);
                        $bonus_stmt->execute();
                        $bonus_stmt->close();
                        
                        // Record referral earning
                        $earning_stmt = $conn->prepare("INSERT INTO referral_earnings (referrer_id, referred_user_id, amount, created_at) VALUES (?, ?, ?, NOW())");
                        $earning_stmt->bind_param("iid", $referrer_id, $user_id, $signup_bonus);
                        $earning_stmt->execute();
                        $earning_stmt->close();
                        
                        // Add transaction record for referrer
                        $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, created_at) VALUES (?, 'referral_bonus', ?, NOW())");
                        $trans_stmt->bind_param("id", $referrer_id, $signup_bonus);
                        $trans_stmt->execute();
                        $trans_stmt->close();
                    }
                    $ref_stmt->close();
                }
                
                // Clear referral session data
                unset($_SESSION['referral_code']);
                unset($_SESSION['referrer_username']);

                echo <<<HTML
                <html>
                <head>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Registration Successful!',
                            text: 'You can now login.',
                            confirmButtonText: 'Login Now',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'login.php';
                            }
                        });
                    </script>
                </body>
                </html>
                HTML;
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<html>
<head> <title>Register - KAKA88 Casino</title>
<meta name="description" content="Create a KAKA88 account to start playing online casino games, Aviator and more.">
<meta name="robots" content="index, follow">
    <title>Register - KAKA88</title> 
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 60px;
            margin: 0;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        
    
        form {
            background: #222;
            padding: 15px;
            border-radius: 15px;
            width: 90%;
            max-width: 300px;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
        }

        input[type="text"],
        input[type="username"],
        input[type="email"],
        input[type="password"],
        input[type="confirm_password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: #333;
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #ff4500;
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #e03e00;
        }

        .logo {
            text-align: center;
        }
        
        .logo img {
            max-height: 60px;
        }

        .message {
            margin-top: 15px;
            font-size: 14px;
            color: #ffcc00;
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        /* Referral info section is removed from display */
        /* .referral-info {
            background: rgba(0, 191, 255, 0.1);
            border: 1px solid #00bfff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }

        .referral-info h3 {
            color: #00bfff;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .referral-info p {
            color: #ccc;
            font-size: 14px;
            margin: 5px 0;
        } */

        .login-link {
            margin-top: 15px;
            font-size: 14px;
            color: #ccc; 
        }

        .login-link a {
            color: #00bfff;
            text-decoration: none; 
        }

        .login-link a:hover {
            text-decoration: underline; 
        }

        .register-link {
            margin-top: 15px;
            font-size: 14px;
            color: #ccc;
        }

        .register-link a {
            color: #00bfff;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style> 
</head> 
<body> 
    <div class="banner"> 
        <div class="logo"> 
            <img src="images/logo.png" alt="KAKA88 Logo"> 
        </div> 
    </div> 
    <h2>Register</h2> 

    <?php /* The referral-info block is commented out to hide it
    if (!empty($referrer_username)): ?>
    <div class="referral-info">
        <h3>🎉 Referred by</h3>
        <p><strong><?php echo htmlspecialchars($referrer_username); ?></strong></p>
        <p>You'll get bonus rewards!</p>
    </div>
    <?php endif; */ ?>

    <?php if ($error): ?>
        <div class="error">
            ⚠️ <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="username" name="username" placeholder="Username" required> 
        <input type="email" name="email" placeholder="Email" required> 
        <input type="password" name="password" placeholder="Password (min 6 chars)" required> 
        <input type="password" name="confirm_password" placeholder="Confirm Password (min 6 chars)" required> 
        <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($referral_code); ?>"> 
        <button type="submit">Register</button> 
    </form> 
    
    <div class="login-link">
        Already have an account? 
        <a href="login.php">Login</a> 
    </div> 
</body>
</html>