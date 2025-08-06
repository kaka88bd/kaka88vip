<?php
session_start();
include 'db.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $conn->prepare("SELECT username, balance, email, phone, created_at, referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $balance, $email, $phone, $created_at, $referral_code);
$stmt->fetch();
$stmt->close();

// Generate referral code if not exists
if (empty($referral_code)) {
    $referral_code = strtoupper(substr($username, 0, 3) . rand(1000, 9999));
    $stmt = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $stmt->bind_param("si", $referral_code, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get transaction history
$stmt = $conn->prepare("SELECT type, amount, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get referral information - users who used this user's referral code
$stmt = $conn->prepare("SELECT u.username, u.created_at, u.id, 
                       COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) as total_deposits,
                       COUNT(CASE WHEN t.type = 'deposit' THEN 1 END) as deposit_count
                       FROM users u 
                       LEFT JOIN transactions t ON u.id = t.user_id 
                       WHERE u.referred_by_code = ? 
                       GROUP BY u.id, u.username, u.created_at
                       ORDER BY u.created_at DESC");
$stmt->bind_param("s", $referral_code);
$stmt->execute();
$result = $stmt->get_result();
$referredUsers = [];
while ($row = $result->fetch_assoc()) {
    $referredUsers[] = $row;
}
$stmt->close();

// Calculate referral earnings
$total_referral_earnings = 0;
$this_month_earnings = 0;
$stmt = $conn->prepare("SELECT SUM(amount) as total, 
                       SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN amount ELSE 0 END) as this_month
                       FROM referral_earnings WHERE referrer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_referral_earnings, $this_month_earnings);
$stmt->fetch();
$stmt->close();

$total_referral_earnings = $total_referral_earnings ?? 0;
$this_month_earnings = $this_month_earnings ?? 0;

// Generate referral link
$base_url = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$referral_link = $base_url . "/register.php?ref=" . $referral_code;

// Get referral statistics
$total_referred = count($referredUsers);
$active_referrals = count(array_filter($referredUsers, function($user) {
    return $user['deposit_count'] > 0;
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
    <meta name="description" content="KAKA88 Profile - Manage your account, view transactions, and track referrals">
    <meta charset="UTF-8"> 
    <title>KAKA88 - My Account</title> 
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
            padding-bottom: 80px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e293b, #374151);
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #fbbf24;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #fbbf24;
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.5);
        }

        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
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
        }

        .back-btn:hover {
            background: #fbbf24;
            color: #000;
            transform: translateY(-50%) scale(1.1);
        }

        /* Profile Section */
        .profile-container {
            padding: 30px 20px;
            text-align: center;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
            margin: 20px;
            border-radius: 20px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .avatar-section {
            position: relative;
            margin-bottom: 20px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #000;
            font-weight: bold;
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.4);
            border: 4px solid rgba(251, 191, 36, 0.6);
            overflow: hidden;
            position: relative;
        }

        .avatar-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 48px;
            color: white;
        }

        .vip-badge {
            position: absolute;
            top: -5px;
            right: calc(50% - 80px);
            background: linear-gradient(45deg, #7c3aed, #5b21b6);
            color: #fbbf24;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid #fbbf24;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
        }

        .username-section {
            margin-bottom: 15px;
        }

        .username {
            font-size: 24px;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .copy-btn {
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid #fbbf24;
            color: #fbbf24;
            padding: 4px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .copy-btn:hover {
            background: #fbbf24;
            color: #000;
        }

        .nickname {
            font-size: 16px;
            color: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .edit-btn {
            color: #fbbf24;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            transform: scale(1.2);
        }

        .balance {
            font-size: 32px;
            font-weight: bold;
            color: #fbbf24;
            margin: 20px 0;
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }

        /* Action Buttons - Smaller and in same line */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: nowrap;
        }

        .action-btn {
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
            min-width: 80px;
            flex: 1;
            max-width: 100px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(251, 191, 36, 0.5);
            background: linear-gradient(45deg, #f59e0b, #d97706);
        }

        .action-btn.withdraw {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
        }

        .action-btn.withdraw:hover {
            background: linear-gradient(45deg, #dc2626, #b91c1c);
        }

        .action-btn.card {
            background: linear-gradient(45deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .action-btn.card:hover {
            background: linear-gradient(45deg, #7c3aed, #6d28d9);
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
            margin: 20px 0;
        }

        .feature-item {
            background: linear-gradient(135deg, #374151, #4b5563);
            border-radius: 16px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .feature-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.4);
            border: 2px solid #fbbf24;
            background: linear-gradient(135deg, #4b5563, #6b7280);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            color: #000;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }

        .feature-text {
            font-size: 11px;
            color: white;
            font-weight: bold;
            line-height: 1.2;
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        /* New Referral System */
        .referral-section {
            margin: 20px;
            background: linear-gradient(135deg, #374151, #4b5563);
            padding: 25px;
            border-radius: 16px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .referral-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .referral-title {
            color: #fbbf24;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .referral-code-badge {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Referral Stats Dashboard */
        .referral-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(251, 191, 36, 0.3);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.2);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 8px;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.3);
        }

        .stat-label {
            font-size: 12px;
            color: #d1d5db;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 20px;
            margin-bottom: 10px;
            color: #fbbf24;
        }

        /* Referral Link Section */
        .referral-link-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .referral-link-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .referral-link-title {
            color: #fbbf24;
            font-size: 16px;
            font-weight: bold;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
        }

        .share-btn {
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid #fbbf24;
            color: #fbbf24;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            text-decoration: none;
        }

        .share-btn:hover {
            background: #fbbf24;
            color: #000;
            transform: translateY(-2px);
        }

        .referral-link-container {
            background: rgba(251, 191, 36, 0.1);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(251, 191, 36, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .referral-link {
            word-break: break-all;
            font-size: 13px;
            flex: 1;
            color: #e5e7eb;
            font-family: 'Courier New', monospace;
        }

        .copy-link-btn {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .copy-link-btn:hover {
            background: linear-gradient(45deg, #059669, #047857);
            transform: translateY(-2px);
        }

        /* Referred Users List */
        .referred-users-section {
            margin-top: 25px;
        }

        .referred-users-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .referred-users-title {
            color: #fbbf24;
            font-size: 16px;
            font-weight: bold;
        }

        .referred-count {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .referred-users-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .referred-user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid #fbbf24;
            transition: all 0.3s ease;
        }

        .referred-user-item:hover {
            background: rgba(0, 0, 0, 0.3);
            transform: translateX(5px);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-name {
            font-weight: bold;
            color: #fbbf24;
            font-size: 14px;
        }

        .user-details {
            font-size: 11px;
            color: #9ca3af;
        }

        .user-stats {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }

        .user-deposits {
            font-size: 12px;
            font-weight: bold;
            color: #10b981;
        }

        .user-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .status-inactive {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
            border: 1px solid #9ca3af;
        }

        /* Empty State */
        .empty-referrals {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .empty-description {
            font-size: 14px;
            line-height: 1.5;
        }

        /* Transaction History */
        .transactions {
            margin: 20px;
            background: linear-gradient(135deg, #374151, #4b5563);
            padding: 20px;
            border-radius: 16px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .transactions h3 {
            color: #fbbf24;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .transaction {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: rgba(0, 0, 0, 0.2);
            margin: 8px 0;
            border-radius: 10px;
            border-left: 3px solid #fbbf24;
        }

        .transaction-type {
            font-weight: bold;
            color: #fbbf24;
        }

        .transaction-amount {
            font-weight: bold;
            color: #10b981;
        }

        .transaction-amount.negative {
            color: #ef4444;
        }

        .transaction-date {
            font-size: 12px;
            color: #9ca3af;
        }

        /* Logout Button */
        .logout-section {
            padding: 20px;
            text-align: center;
        }

        .logout-btn {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.5);
            background: linear-gradient(45deg, #dc2626, #b91c1c);
        }

        /* Bottom Navigation */
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

        /* Success/Error Messages */
        .message {
            padding: 12px;
            border-radius: 8px;
            margin: 10px 0;
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

        /* Responsive Design */
        @media (max-width: 414px) {
            .features-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                padding: 15px;
            }

            .feature-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .feature-text {
                font-size: 10px;
            }

            .referral-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }

            .username {
                font-size: 20px;
            }

            .balance {
                font-size: 28px;
            }

            .action-btn {
                font-size: 11px;
                padding: 6px 12px;
                min-width: 70px;
            }

            .share-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .referral-link-container {
                flex-direction: column;
                gap: 10px;
            }

            .copy-link-btn {
                width: 100%;
            }
        }

        @media (max-width: 320px) {
            .features-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                padding: 10px;
            }

            .feature-icon {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .feature-text {
                font-size: 9px;
            }

            .referral-dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>My Account</h1>
    </div>

    <!-- Profile Container -->
    <div class="profile-container">
        <div class="avatar-section">
            <div class="vip-badge">
                <i class="fas fa-crown"></i> VIP1
            </div>
            <div class="avatar">
                <!-- Demo Avatar -->
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <div class="username-section">
            <div class="username">
                <?php echo htmlspecialchars($username); ?>
                <button class="copy-btn" onclick="copyUsername()">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="nickname">
                Nickname: <?php echo htmlspecialchars($username); ?>
                <i class="fas fa-edit edit-btn" onclick="editNickname()"></i>
            </div>
        </div>

        <div class="balance">
            ৳ <?php echo number_format($balance, 2); ?>
        </div>

        <!-- Smaller Action Buttons in Same Line -->
        <div class="action-buttons">
            <a href="deposit.php" class="action-btn">Deposit</a>
            <a href="withdraw.php" class="action-btn withdraw">Withdraw</a>
            <a href="my-card.php" class="action-btn card">My Card</a>
        </div>
    </div>

    <!-- Features Grid -->
    <div class="features-grid">
        <div class="feature-item" onclick="location.href='rewards.php'">
            <div class="feature-icon"><i class="fas fa-trophy"></i></div>
            <div class="feature-text">Reward Center</div>
        </div>
        
        <div class="feature-item" onclick="location.href='betting-record.php'">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <div class="feature-text">Betting Record</div>
        </div>
        
        <div class="feature-item" onclick="location.href='profit-loss.php'">
            <div class="feature-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="feature-text">Profit & Loss</div>
        </div>
        
        <div class="feature-item" onclick="location.href='deposit-record.php'">
            <div class="feature-icon"><i class="fas fa-file-upload"></i></div>
            <div class="feature-text">Deposit Record</div>
        </div>
        
        <div class="feature-item" onclick="location.href='withdrawal-record.php'">
            <div class="feature-icon"><i class="fas fa-file-download"></i></div>
            <div class="feature-text">Withdrawal Record</div>
        </div>
        
        <div class="feature-item" onclick="location.href='account-record.php'">
            <div class="feature-icon"><i class="fas fa-search-dollar"></i></div>
            <div class="feature-text">Account Record</div>
        </div>
        
        <div class="feature-item" onclick="location.href='my-account.php'">
            <div class="feature-icon"><i class="fas fa-user"></i></div>
            <div class="feature-text">My Account</div>
        </div>
        
        <div class="feature-item" onclick="location.href='security.php'">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="feature-text">Security Center</div>
        </div>
        
        <div class="feature-item" onclick="location.href='invite-friends.php'">
            <div class="feature-icon"><i class="fas fa-user-plus"></i></div>
            <div class="feature-text">Invite Friends</div>
        </div>
        
        <div class="feature-item" onclick="location.href='missions.php'">
            <div class="notification-badge">1</div>
            <div class="feature-icon"><i class="fas fa-gift"></i></div>
            <div class="feature-text">Missions</div>
        </div>
        
        <div class="feature-item" onclick="location.href='manual-rebate.php'">
            <div class="feature-icon"><i class="fas fa-coins"></i></div>
            <div class="feature-text">Manual Rebate</div>
        </div>
        
        <div class="feature-item" onclick="location.href='mail.php'">
            <div class="feature-icon"><i class="fas fa-envelope"></i></div>
            <div class="feature-text">Mail</div>
        </div>
        
        <div class="feature-item" onclick="location.href='suggestions.php'">
            <div class="feature-icon"><i class="fas fa-comment-alt"></i></div>
            <div class="feature-text">Suggestions</div>
        </div>
        
        <div class="feature-item" onclick="location.href='customer-service.php'">
            <div class="feature-icon"><i class="fas fa-headset"></i></div>
            <div class="feature-text">Customer Service</div>
        </div>
        
        <div class="feature-item" onclick="location.href='help.php'">
            <div class="feature-icon"><i class="fas fa-question-circle"></i></div>
            <div class="feature-text">Help Center</div>
        </div>
    </div>

    <!-- New Referral System -->
    <div class="referral-section">
        <div class="referral-header">
            <div class="referral-title">
                <i class="fas fa-users"></i> Referral Program
            </div>
            <div class="referral-code-badge">
                <?php echo $referral_code; ?>
            </div>
        </div>

        <!-- Referral Stats Dashboard -->
        <div class="referral-dashboard">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $total_referred; ?></div>
                <div class="stat-label">Total Referred</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-number"><?php echo $active_referrals; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-number">৳<?php echo number_format($total_referral_earnings, 0); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-number">৳<?php echo number_format($this_month_earnings, 0); ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- Referral Link Section -->
        <div class="referral-link-section">
            <div class="referral-link-header">
                <div class="referral-link-title">
                    <i class="fas fa-link"></i> Your Referral Link
                </div>
                <div class="share-buttons">
                    <a href="whatsapp://send?text=Join KAKA88 using my referral link: <?php echo urlencode($referral_link); ?>" class="share-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://t.me/share/url?url=<?php echo urlencode($referral_link); ?>&text=Join KAKA88 and start earning!" class="share-btn">
                        <i class="fab fa-telegram"></i> Telegram
                    </a>
                </div>
            </div>
            
            <div class="referral-link-container">
                <div class="referral-link" id="referralLink"><?php echo $referral_link; ?></div>
                <button class="copy-link-btn" onclick="copyReferralLink()">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>
        </div>

        <!-- Referred Users Section -->
        <div class="referred-users-section">
            <div class="referred-users-header">
                <div class="referred-users-title">
                    <i class="fas fa-list"></i> Referred Users
                </div>
                <div class="referred-count"><?php echo count($referredUsers); ?> Users</div>
            </div>

            <?php if (!empty($referredUsers)): ?>
            <div class="referred-users-list">
                <?php foreach ($referredUsers as $user): ?>
                    <?php 
                    $masked_username = substr($user['username'], 0, 2) . str_repeat("*", max(0, strlen($user['username']) - 4)) . substr($user['username'], -2);
                    $is_active = $user['deposit_count'] > 0;
                    $join_date = date("M d, Y", strtotime($user['created_at']));
                    ?>
                    <div class="referred-user-item">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($masked_username); ?></div>
                            <div class="user-details">
                                Joined: <?php echo $join_date; ?> • 
                                Deposits: <?php echo $user['deposit_count']; ?>
                            </div>
                        </div>
                        <div class="user-stats">
                            <div class="user-deposits">৳<?php echo number_format($user['total_deposits'], 0); ?></div>
                            <div class="user-status <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-referrals">
                <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
                <div class="empty-title">No Referrals Yet</div>
                <div class="empty-description">
                    Share your referral link with friends and family to start earning commissions!<br>
                    <strong>Earn 5% on every deposit they make!</strong>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Referral Program Info -->
        <div style="margin-top: 25px; padding: 20px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1)); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.3);">
            <h4 style="color: #10b981; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i> How Referral Program Works
            </h4>
            <div style="font-size: 13px; color: #d1d5db; line-height: 1.6;">
                <div style="margin-bottom: 8px;"><strong>• Share:</strong> Send your unique referral link to friends</div>
                <div style="margin-bottom: 8px;"><strong>• Earn:</strong> Get 5% commission on their deposits</div>
                <div style="margin-bottom: 8px;"><strong>• Withdraw:</strong> Commissions are credited instantly</div>
                <div style="margin-bottom: 8px;"><strong>• No Limit:</strong> Refer unlimited users and earn more</div>
                <div style="color: #fbbf24; font-weight: bold; margin-top: 10px;">
                    💰 The more they deposit, the more you earn!
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="transactions">
        <h3><i class="fas fa-history"></i> Transaction History</h3>
        <?php if (empty($transactions)): ?>
            <p style="text-align: center; color: #9ca3af; padding: 20px;">No transactions found.</p>
        <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
            <div class="transaction">
                <div>
                    <div class="transaction-type"><?php echo ucfirst($tx['type']); ?></div>
                    <div class="transaction-date"><?php echo date("M d, Y", strtotime($tx['created_at'])); ?></div>
                </div>
                <div class="transaction-amount <?php echo ($tx['type'] == 'withdraw') ? 'negative' : ''; ?>">
                    <?php echo ($tx['type'] == 'withdraw') ? '-' : '+'; ?>৳ <?php echo number_format($tx['amount'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Logout Section -->
    <div class="logout-section">
        <button class="logout-btn" onclick="confirmLogout()">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>

    <!-- Bottom Navigation -->
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
            <a href="rewards.php" class="nav-item" data-page="rewards">
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
        // Copy username function
        function copyUsername() {
            const username = "<?php echo $username; ?>";
            navigator.clipboard.writeText(username).then(function() {
                showMessage('Username copied to clipboard!', 'success');
            }).catch(function() {
                showMessage('Failed to copy username', 'error');
            });
        }

        // Copy referral link function
        function copyReferralLink() {
            const referralLink = "<?php echo $referral_link; ?>";
            navigator.clipboard.writeText(referralLink).then(function() {
                showMessage('Referral link copied to clipboard!', 'success');
                
                // Add visual feedback to button
                const btn = event.target.closest('.copy-link-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = 'linear-gradient(45deg, #10b981, #059669)';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(45deg, #10b981, #059669)';
                }, 2000);
            }).catch(function() {
                showMessage('Failed to copy referral link', 'error');
            });
        }

        // Show message function
        function showMessage(text, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.message');
            existingMessages.forEach(msg => msg.remove());
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${text}`;
            
            const referralSection = document.querySelector('.referral-section');
            referralSection.insertBefore(messageDiv, referralSection.firstChild);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 4000);
        }

        // Edit nickname function
        function editNickname() {
            const newNickname = prompt('Enter new nickname:', "<?php echo $username; ?>");
            if (newNickname && newNickname.trim() !== '') {
                // Here you would send an AJAX request to update the nickname
                showMessage('Nickname updated successfully!', 'success');
            }
        }

        // Confirm logout
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Bottom navigation functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Remove active class from all items
                    navItems.forEach(nav => nav.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Get the page data attribute
                    const page = this.getAttribute('data-page');
                    console.log(`Navigating to ${page} page`);
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

        // Auto-refresh referral stats every 30 seconds
        setInterval(function() {
            // You can add AJAX call here to refresh stats without page reload
            console.log('Refreshing referral stats...');
        }, 30000);
    </script>
</body>
</html>