<?php
session_start();
include 'db.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- START: MODIFIED SECTION FOR FETCHING USER DATA ---
// Added 'full_name', 'phone', 'password' (for $password_hash), and 'transaction_password'
// Ensure these columns exist in your 'users' table.
$stmt = $conn->prepare("SELECT username, balance, email, created_at, referral_code, full_name, phone, password, transaction_password FROM users WHERE id = ?");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $balance, $email, $created_at, $referral_code, $full_name, $phone, $password_hash, $transaction_password);

$stmt->fetch();
$stmt->close();

// Initialize variables that might not be set if columns don't exist in DB
// If you added these columns, these initializations will be overwritten by fetched data.
// If not, they prevent 'Undefined variable' errors.
if (!isset($full_name)) $full_name = '';
if (!isset($phone)) $phone = '';
if (!isset($password_hash)) $password_hash = ''; // Crucial for password_verify
if (!isset($transaction_password)) $transaction_password = '';

// Removed last_login_ip and last_login_time as requested
// Defaulting them to empty/placeholder values as they are not fetched
$last_login_ip = ''; // No longer fetched from DB
$last_login_time = ''; // No longer fetched from DB
// --- END: MODIFIED SECTION FOR FETCHING USER DATA ---

// Get wallet information
// This assumes 'user_wallets' table exists as previously discussed
$stmt = $conn->prepare("SELECT wallet_type, wallet_number FROM user_wallets WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate security percentage
$security_score = 0;
// Total checks should match the number of 'if' conditions below that contribute to the score.
// Let's re-evaluate based on the code's checks:
// 1. Personal Info (full_name, email, phone)
// 2. Wallets
// 3. Login Password (always complete)
// 4. Transaction Password
$total_checks = 4;

if (!empty($full_name) && !empty($email) && !empty($phone)) $security_score++;
if (count($wallets) > 0) $security_score++;
// Login password is always considered complete since user can login
$security_score++; // Always add 1 for login password
if (!empty($transaction_password)) $security_score++;

$security_percentage = round(($security_score / $total_checks) * 100);
$security_level = $security_percentage >= 80 ? 'High' : ($security_percentage >= 60 ? 'Medium' : 'Low');

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_personal_info':
                $new_full_name = trim($_POST['full_name']);
                $new_email = trim($_POST['email']);
                $new_phone = trim($_POST['phone']);
                
                if (!empty($new_full_name) && !empty($new_email) && !empty($new_phone)) {
                    // --- START: MODIFIED UPDATE STATEMENT ---
                    // Included full_name, email, phone to match the form fields
                    // Ensure 'full_name' and 'phone' columns exist in your 'users' table
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $new_full_name, $new_email, $new_phone, $user_id);
                    // --- END: MODIFIED UPDATE STATEMENT ---
                    
                    if ($stmt->execute()) {
                        $message = "Personal information updated successfully!";
                        $message_type = "success";
                        // Update local variables to reflect changes without re-fetching from DB
                        $full_name = $new_full_name;
                        $email = $new_email;
                        $phone = $new_phone;
                    } else {
                        $message = "Failed to update personal information.";
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "All fields are required.";
                    $message_type = "error";
                }
                break;
                
            case 'add_wallet':
                $wallet_type = $_POST['wallet_type'];
                $wallet_number = trim($_POST['wallet_number']);
                
                if (!empty($wallet_type) && !empty($wallet_number)) {
                    // Check if user already has 2 wallets
                    if (count($wallets) < 2) {
                        $stmt = $conn->prepare("INSERT INTO user_wallets (user_id, wallet_type, wallet_number) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user_id, $wallet_type, $wallet_number);
                        
                        if ($stmt->execute()) {
                            $message = "Wallet added successfully!";
                            $message_type = "success";
                            // Re-fetch wallets to update display if needed, or simply add to $wallets array
                            // For simplicity, you might want to redirect to refresh the page or manually update $wallets array
                            // For now, it will update on next page load or if page refreshed.
                        } else {
                            $message = "Failed to add wallet.";
                            $message_type = "error";
                        }
                        $stmt->close();
                    } else {
                        $message = "Maximum 2 wallets allowed.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Please fill all wallet details.";
                    $message_type = "error";
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // --- START: ORIGINAL CHECK REQUIRING $password_hash ---
                // This 'password_hash' variable is now correctly fetched from the DB at the top
                if (password_verify($current_password, $password_hash)) {
                    if (strlen($new_password) >= 6) {
                        if ($new_password === $confirm_password) {
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->bind_param("si", $new_password_hash, $user_id);
                            
                            if ($stmt->execute()) {
                                $message = "Password changed successfully!";
                                $message_type = "success";
                                $password_hash = $new_password_hash; // Update local variable
                            } else {
                                $message = "Failed to change password.";
                                $message_type = "error";
                            }
                            $stmt->close();
                        } else {
                            $message = "New passwords do not match.";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Password must be at least 6 characters.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Current password is incorrect.";
                    $message_type = "error";
                }
                // --- END: ORIGINAL CHECK REQUIRING $password_hash ---
                break;
                
            case 'set_transaction_password':
                $new_transaction_password = $_POST['transaction_password'];
                $confirm_transaction_password = $_POST['confirm_transaction_password'];
                
                if (strlen($new_transaction_password) === 6 && is_numeric($new_transaction_password)) {
                    if ($new_transaction_password === $confirm_transaction_password) {
                        $transaction_password_hash_to_save = password_hash($new_transaction_password, PASSWORD_DEFAULT);
                        // --- START: MODIFIED UPDATE STATEMENT ---
                        // Ensure 'transaction_password' column exists in your 'users' table
                        $stmt = $conn->prepare("UPDATE users SET transaction_password = ? WHERE id = ?");
                        $stmt->bind_param("si", $transaction_password_hash_to_save, $user_id);
                        // --- END: MODIFIED UPDATE STATEMENT ---
                        
                        if ($stmt->execute()) {
                            $message = "Transaction password set successfully!";
                            $message_type = "success";
                            $transaction_password = $transaction_password_hash_to_save; // Update local variable
                        } else {
                            $message = "Failed to set transaction password.";
                            $message_type = "error";
                        }
                        $stmt->close();
                    } else {
                        $message = "Transaction passwords do not match.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Transaction password must be exactly 6 digits.";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
    <meta name="description" content="KAKA88 Security Center - Manage your account security settings">
    <meta charset="UTF-8">
    <title>KAKA88 - Security Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #581c87 50%, #0f172a 100%);
            color: #fff;
            min-height: 100vh;
            display: flex; /* Use flexbox for layout */
            flex-direction: column; /* Stack children vertically */
            padding-bottom: 80px; /* Space for bottom nav */
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e293b, #374151);
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 3px solid #fbbf24;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .back-btn {
            background: rgba(251, 191, 36, 0.2);
            border: 2px solid #fbbf24;
            color: #fbbf24;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-right: 20px;
        }

        .back-btn:hover {
            background: #fbbf24;
            color: #000;
            transform: scale(1.1);
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #fbbf24;
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.5);
        }

        /* Security Dashboard */
        .security-dashboard {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
            margin: 20px;
            border-radius: 20px;
            padding: 30px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        .security-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(#10b981 0deg, #10b981 <?php echo $security_percentage * 3.6; ?>deg, #374151 <?php echo $security_percentage * 3.6; ?>deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .security-circle::before {
            content: '';
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #1e293b, #374151);
            border-radius: 50%;
            position: absolute;
        }

        .security-percentage {
            font-size: 28px;
            font-weight: bold;
            color: #10b981;
            z-index: 1;
        }

        .security-level {
            font-size: 18px;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 15px;
        }

        .security-icons {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .security-icon {
            color: #fbbf24;
            font-size: 20px;
        }

        /* Removed .login-info section as requested */
        /*
        .login-info {
            text-align: left;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .login-info p {
            color: #d1d5db;
            margin-bottom: 8px;
            font-size: 14px;
        }
        */

        /* Security Warning */
        .security-warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 15px;
            border-radius: 12px;
            margin: 20px;
            text-align: center;
            font-weight: bold;
        }

        /* Security Options */
        .security-options {
            margin: 20px;
            flex-grow: 1; /* Allows this section to take up available space */
        }

        .security-option {
            background: linear-gradient(135deg, #374151, #4b5563);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }

        .security-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.2);
            border: 2px solid rgba(251, 191, 36, 0.3);
        }

        .option-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #000;
            margin-right: 20px;
        }

        .option-content {
            flex: 1;
        }

        .option-title {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .option-description {
            font-size: 14px;
            color: #d1d5db;
        }

        .option-status {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .status-complete {
            background: #10b981;
            color: white;
        }

        .status-incomplete {
            background: #ef4444;
            color: white;
        }

        .arrow-icon {
            color: #9ca3af;
            font-size: 18px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            overflow-y: auto; /* Allow scrolling for long forms */
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b, #374151);
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            border: 2px solid #fbbf24;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: bold;
            color: #fbbf24;
        }

        .close {
            color: #9ca3af;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #ef4444;
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #fbbf24;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.3);
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 16px;
        }

        .btn {
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(45deg, #dc2626, #b91c1c);
        }

        /* Message Styles */
        .message {
            padding: 15px;
            border-radius: 10px;
            margin: 20px;
            font-weight: bold;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- START: Bottom Navigation CSS (From provided index.php) --- */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: linear-gradient(135deg, #1e293b, #374151);
            padding: 16px 0;
            border-top: 3px solid #fbbf24;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 414px;
            margin: 0 auto;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: white;
            font-size: 12px;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 12px;
            min-width: 60px;
            cursor: pointer;
        }

        .nav-item:hover {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.15);
            transform: translateY(-2px);
        }

        .nav-item.active {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.2);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }

        .nav-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 2px;
            transition: all 0.3s ease;
        }

        .nav-item:hover .nav-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 0 8px #fbbf24);
        }
        /* --- END: Bottom Navigation CSS --- */

        /* Responsive Design */
        @media (max-width: 414px) {
            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 20px;
            }

            .security-option {
                padding: 15px;
            }

            .option-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
                margin-right: 15px;
            }

            .option-title {
                font-size: 16px;
            }

            .option-description {
                font-size: 13px;
            }

            /* Adjust body for sticky footer on small screens */
            body {
                padding-bottom: 70px; /* Adjust based on your bottom nav height */
            }
            .bottom-nav {
                padding: 10px 0; /* Adjust padding if needed */
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="profile.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Security Center</h1>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="security-dashboard">
        <div class="security-circle">
            <div class="security-percentage"><?php echo $security_percentage; ?>%</div>
        </div>
        
        <div class="security-level">Safety percentage: <?php echo $security_level; ?></div>
        
        <div class="security-icons">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <i class="fas fa-bolt security-icon" style="opacity: <?php echo $i < $security_score ? '1' : '0.3'; ?>"></i>
            <?php endfor; ?>
        </div>
    </div>

    <?php if ($security_percentage < 80): ?>
        <div class="security-warning">
            Your account security level is <?php echo $security_level; ?>. Please improve your safety information
        </div>
    <?php endif; ?>

    <div class="security-options">
        <div class="security-option" onclick="openModal('personalInfoModal')">
            <div class="option-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="option-content">
                <div class="option-title">
                    Personal information
                    <?php if (!empty($full_name) && !empty($email) && !empty($phone)): ?>
                        <div class="status-icon status-complete">✓</div>
                    <?php else: ?>
                        <div class="status-icon status-incomplete">!</div>
                    <?php endif; ?>
                </div>
                <div class="option-description">Complete personal information.</div>
            </div>
            <div class="option-status">
                <i class="fas fa-edit" style="color: #9ca3af;"></i>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>

        <div class="security-option" onclick="openModal('walletModal')">
            <div class="option-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="option-content">
                <div class="option-title">
                    Bind E-wallet
                    <?php if (count($wallets) > 0): ?>
                        <div class="status-icon status-complete">✓</div>
                    <?php else: ?>
                        <div class="status-icon status-incomplete">!</div>
                    <?php endif; ?>
                </div>
                <div class="option-description">Bind E-wallet for withdrawal.</div>
            </div>
            <div class="option-status">
                <i class="fas fa-edit" style="color: #9ca3af;"></i>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>

        <div class="security-option" onclick="openModal('passwordModal')">
            <div class="option-icon">
                <i class="fas fa-lock"></i>
            </div>
            <div class="option-content">
                <div class="option-title">
                    Change login password
                    <div class="status-icon status-complete">✓</div>
                </div>
                <div class="option-description">Recommended letter and number combination</div>
            </div>
            <div class="option-status">
                <i class="fas fa-edit" style="color: #9ca3af;"></i>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>

        <div class="security-option" onclick="openModal('transactionPasswordModal')">
            <div class="option-icon">
                <i class="fas fa-key"></i>
            </div>
            <div class="option-content">
                <div class="option-title">
                    Transaction Password
                    <?php if (!empty($transaction_password)): ?>
                        <div class="status-icon status-complete">✓</div>
                    <?php else: ?>
                        <div class="status-icon status-incomplete">!</div>
                    <?php endif; ?>
                </div>
                <div class="option-description">Set a fund password to improve the security of fund operations</div>
            </div>
            <div class="option-status">
                <i class="fas fa-edit" style="color: #9ca3af;"></i>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>

        <div class="security-option" onclick="confirmLogout()">
            <div class="option-icon">
                <i class="fas fa-power-off"></i>
            </div>
            <div class="option-content">
                <div class="option-title">Logout</div>
                <div class="option-description">Logout safely</div>
            </div>
            <div class="option-status">
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>
    </div>

    <div id="personalInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Personal Information</h2>
                <span class="close" onclick="closeModal('personalInfoModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_personal_info">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
                <button type="submit" class="btn">Update Information</button>
            </form>
        </div>
    </div>

    <div id="walletModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Bind E-wallet</h2>
                <span class="close" onclick="closeModal('walletModal')">&times;</span>
            </div>
            
            <?php if (!empty($wallets)): ?>
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #10b981; margin-bottom: 10px;">Current Wallets:</h3>
                    <?php foreach ($wallets as $wallet): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 8px; border: 1px solid #10b981;">
                            <strong><?php echo ucfirst($wallet['wallet_type']); ?>:</strong> <?php echo $wallet['wallet_number']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (count($wallets) < 2): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add_wallet">
                    <div class="form-group">
                        <label class="form-label">Wallet Type</label>
                        <select name="wallet_type" class="form-select" required>
                            <option value="">Select Wallet Type</option>
                            <option value="bkash">Bkash</option>
                            <option value="nagad">Nagad</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wallet Number</label>
                        <input type="text" name="wallet_number" class="form-input" placeholder="Enter wallet number" required>
                    </div>
                    <button type="submit" class="btn">Add Wallet</button>
                </form>
            <?php else: ?>
                <p style="color: #ef4444; text-align: center;">Maximum 2 wallets allowed.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Change Login Password</h2>
                <span class="close" onclick="closeModal('passwordModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password (min 6 characters)</label>
                    <input type="password" name="new_password" class="form-input" minlength="6" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" minlength="6" required>
                </div>
                <button type="submit" class="btn">Change Password</button>
            </form>
        </div>
    </div>

    <div id="transactionPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Transaction Password</h2>
                <span class="close" onclick="closeModal('transactionPasswordModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="set_transaction_password">
                <div class="form-group">
                    <label class="form-label">6-Digit Transaction Password</label>
                    <input type="password" name="transaction_password" class="form-input" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6 digits" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Transaction Password</label>
                    <input type="password" name="confirm_transaction_password" class="form-input" maxlength="6" pattern="[0-9]{6}" placeholder="Confirm 6 digits" required>
                </div>
                <button type="submit" class="btn"><?php echo !empty($transaction_password) ? 'Update' : 'Set'; ?> Transaction Password</button>
            </form>
        </div>
    </div>

    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="dashboard.php" class="nav-item" data-page="home">
                <div class="nav-icon">🏠</div>
                <span>Home</span>
            </a>
            <a href="promotion.php" class="nav-item" data-page="promotion">
                <div class="nav-icon">🎁</div>
                <span>Promotions</span>
            </a>
            <a href="invite.php" class="nav-item" data-page="invite">
                <div class="nav-icon">👥</div>
                <span>Invite</span>
            </a>
            <a href="/rewards.php" class="nav-item" data-page="reward">
                <div class="nav-icon">🏆</div>
                <span>Rewards</span>
            </a>
            <a href="profile.php" class="nav-item active" data-page="profile">
                <div class="nav-icon">👤</div>
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'security.php?logout=1';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);

        // Bottom navigation functionality (from provided code)
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            
            // Set the "Security" item as active initially
            // Since this is security.php, we want "Profile" to be active, not "Security".
            // The provided dashboard HTML has "Profile" as the last item, which is usually where Security would be placed in some apps.
            // I'm assuming 'profile' is intended to be the active one for security.php, as it's the category where security settings would fall under in many apps.
            // If you want "Security" to be an independent active item, you'd need to add it to the nav-bar and adjust.
            // For now, I'm setting the profile item as active in this "Security" page, as that's generally how it's linked.
            navItems.forEach(item => {
                if (item.getAttribute('href') === 'profile.php') { // Changed from 'security.php' to 'profile.php'
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });


            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // This part isn't strictly necessary if page navigation reloads the page,
                    // but good for SPA-like behavior or visual feedback before redirect.
                    // e.preventDefault(); // Uncomment if you want to handle navigation via JS only
                    
                    // Remove active class from all items
                    navItems.forEach(nav => nav.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    const page = this.getAttribute('data-page');
                    console.log(`Navigating to ${page} page`);
                    
                    // If e.preventDefault() is active, you'd do window.location.href = this.href; here
                });
            });
            
            // Optional: Add touch feedback for mobile
            navItems.forEach(item => {
                item.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                item.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
