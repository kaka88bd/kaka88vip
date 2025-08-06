<?php
session_start(); // This must be the very first line of your PHP file

// Include database connection and configuration files
include 'db.php';
include 'config.php';

// Check if the user is logged in
// IMPORTANT FIX: Using $_SESSION['user_id'] to match login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); // Always exit after a header redirect
}

// Get the user ID from the session
// IMPORTANT FIX: Using $_SESSION['user_id'] to match login.php
$userid = $_SESSION['user_id']; 

// --- Fetch User Information ---
// This query retrieves the username, balance, email, account creation date,
// and referral code for the logged-in user.
// Ensure your 'users' table has columns named 'username', 'balance', 'email', 'created_at', and 'referral_code'.
$stmt = $conn->prepare("SELECT username, balance, email, created_at, referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $userid); // 'i' for integer (user ID)
$stmt->execute();
$stmt->bind_result($username, $balance, $email, $created_at, $referral_code);
$stmt->fetch();
$stmt->close();

// --- Generate Unique Referral Code if it doesn't exist ---
// If the 'referral_code' column for the current user is empty, generate a new unique one.
if (empty($referral_code)) {
    do {
        $letters = '';
        $numbers = '';
        
        // Generate 3-4 random uppercase letters
        $letter_count = rand(3, 4);
        for ($i = 0; $i < $letter_count; $i++) {
            $letters .= chr(rand(65, 90)); // ASCII for A-Z
        }
        
        // Generate 2-3 random digits
        $number_count = rand(2, 3);
        for ($i = 0; $i < $number_count; ++$i) {
            $numbers .= rand(0, 9);
        }
        
        $referral_code = $letters . $numbers;
        
        // Check if this newly generated code already exists in the database
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $check_stmt->bind_param("s", $referral_code); // 's' for string
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->num_rows > 0; // Check if any rows were returned
        $check_stmt->close();
        
    } while ($exists); // Repeat if the generated code already exists
    
    // Update the user's record with the new unique referral code
    $stmt = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $stmt->bind_param("si", $referral_code, $userid); // 's' for string, 'i' for integer
    $stmt->execute();
    $stmt->close();
}

// --- Fetch Referred Users Information ---
// This query gets details of users who registered using this user's referral code.
// It also calculates their total deposit amount and count of deposits.
// Ensure your 'users' table has a 'referred_by_code' column and 'transactions' table has 'user_id', 'type', 'amount', 'created_at'.
$stmt = $conn->prepare("SELECT u.username, u.created_at, u.id, 
                       COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) as total_deposits,
                       COUNT(CASE WHEN t.type = 'deposit' THEN 1 END) as deposit_count
                       FROM users u 
                       LEFT JOIN transactions t ON u.id = t.user_id 
                       WHERE u.referred_by_code = ? 
                       GROUP BY u.id, u.username, u.created_at
                       ORDER BY u.created_at DESC");
$stmt->bind_param("s", $referral_code); // 's' for string (referral code)
$stmt->execute();
$result = $stmt->get_result();
$referredUsers = [];
while ($row = $result->fetch_assoc()) {
    $referredUsers[] = $row;
}
$stmt->close();

// --- Calculate Referral Earnings ---
// This query fetches the total referral earnings and this month's earnings for the user.
// Ensure your 'referral_earnings' table has 'referrer_id', 'amount', 'created_at'.
$total_referral_earnings = 0;
$this_month_earnings = 0;

$stmt = $conn->prepare("SELECT 
                       COALESCE(SUM(amount), 0) as total, 
                       COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN amount ELSE 0 END), 0) as this_month
                       FROM referral_earnings WHERE referrer_id = ?");
$stmt->bind_param("i", $userid); // 'i' for integer (referrer ID)
$stmt->execute();
$stmt->bind_result($total_referral_earnings, $this_month_earnings);
$stmt->fetch();
$stmt->close();

// --- Generate Referral Link ---
// The link format assumes new users register via 'register.php' and pass the referral code.
$referral_link = "https://kaka88bd.free.nf/register.php?ref=" . $referral_code;

// --- Get Referral Statistics ---
$total_referred = count($referredUsers);
// Active referrals are defined as users who have made at least one deposit.
$active_referrals = count(array_filter($referredUsers, function($user) {
    return $user['deposit_count'] > 0;
}));

// Set the current page variable for the bottom navigation bar to highlight "Invite"
$current_page = 'invite';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
    <meta name="description" content="KAKA88 - Invite Friends and Earn Rewards">
    <meta charset="UTF-8"> 
    <title>KAKA88 - Invite Friends</title> 
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> 
    <style>
        /* General Body and Reset Styles */
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
            padding-bottom: 80px; /* Space for fixed bottom navigation */
        }

        /* Header Styles */
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

        /* Referral System Section Styles */
        .referral-section {
            margin: 20px;
            background: linear-gradient(135deg, #374151, #4b5563);
            padding: 25px;
            border-radius: 16px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.8s ease-out; /* Animation for section entrance */
        }

        .referral-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
        }

        .referral-title {
            color: #fbbf24;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px; /* Adds space when items wrap */
        }

        .referral-code-badge {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        /* Referral Stats Dashboard Styles */
        .referral-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); /* Responsive grid columns */
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.2);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.2));
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 8px;
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.6);
        }

        .stat-label {
            font-size: 13px;
            color: #d1d5db;
            font-weight: 500;
        }

        /* Referral Link Section Styles */
        .referral-link-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(251, 191, 36, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .referral-link-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .referral-link-title {
            color: #fbbf24;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px; /* Adds space when items wrap */
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center; /* Centers buttons within their container */
        }

        .share-btn {
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid #fbbf24;
            color: #fbbf24;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .share-btn:hover {
            background: #fbbf24;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
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
            flex-wrap: wrap; /* Allows items to wrap */
        }

        .referral-link {
            word-break: break-all; /* Ensures long links break lines */
            font-size: 14px;
            flex: 1; /* Allows the link text to take available space */
            color: #e5e7eb;
            font-family: 'Courier New', monospace;
            padding-right: 10px; /* Space before copy button */
        }

        .copy-link-btn {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap; /* Prevents button text from wrapping */
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .copy-link-btn:hover {
            background: linear-gradient(45deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
        }

        /* Referral Program Info Box Styles */
        .referral-info-box {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .referral-info-box h4 {
            color: #10b981;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        .referral-info-box div {
            font-size: 14px;
            color: #d1d5db;
            line-height: 1.6;
        }

        .referral-info-box div strong {
            color: #fbbf24; /* Highlight strong points */
        }

        .referral-info-box .highlight-text {
            color: #fbbf24;
            font-weight: bold;
            margin-top: 10px;
            text-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
        }
        
        /* Bottom Navigation Bar Styles */
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
            backdrop-filter: blur(10px); /* Adds a subtle blur effect */
        }

        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 414px; /* Max width for mobile-like navigation */
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
            position: relative;
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

        /* Dot indicator for active navigation item */
        .nav-item.active::before {
            content: '';
            position: absolute;
            top: -16px; /* Position above the nav item */
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #fbbf24;
            border-radius: 50%;
            box-shadow: 0 0 10px #fbbf24;
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

        .nav-item.active .nav-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 0 8px #fbbf24);
        }

        /* Success/Error Message Styles (for JS messages) */
        .message {
            padding: 12px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: bold;
            text-align: center;
            animation: slideIn 0.3s ease; /* Animation for messages */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            z-index: 1001; /* Ensure messages are above other content */
            position: relative; /* Allows z-index to work */
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

        /* Keyframe Animations */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design Media Queries */
        @media (max-width: 768px) {
            .referral-header {
                flex-direction: column; /* Stacks title and badge */
                align-items: flex-start;
            }

            .referral-code-badge {
                margin-top: 10px;
                width: 100%; /* Badge takes full width */
                text-align: center;
            }

            .referral-dashboard {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); /* Adjusts grid for smaller screens */
            }

            .referral-link-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .share-buttons {
                margin-top: 10px;
                width: 100%;
                justify-content: flex-start;
            }

            .share-btn {
                width: calc(50% - 5px); /* Two share buttons per row */
            }

            .referral-link-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .referral-link {
                width: 100%;
                padding-right: 0;
                text-align: center;
            }

            .copy-link-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 414px) { /* Targeting typical mobile screen widths */
            .referral-section {
                margin: 15px;
                padding: 20px;
            }
            .referral-title {
                font-size: 18px;
            }
            .referral-code-badge {
                font-size: 13px;
                padding: 6px 12px;
            }
            .stat-number {
                font-size: 24px;
            }
            .stat-label {
                font-size: 11px;
            }
            .referral-link-title {
                font-size: 16px;
            }
            .share-btn {
                font-size: 11px;
                padding: 6px 10px;
                width: 100%; /* Share buttons stack on very small screens */
            }
            .referral-link {
                font-size: 12px;
            }
            .copy-link-btn {
                font-size: 11px;
                padding: 8px 12px;
            }
            .referral-info-box h4 {
                font-size: 16px;
            }
            .referral-info-box div {
                font-size: 12px;
            }

            .bottom-nav {
                padding: 12px 0;
            }
            .nav-item {
                font-size: 10px;
                min-width: 50px;
            }
            .nav-icon {
                width: 24px;
                height: 24px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Invite Friends</h1>
    </div>

    <div class="referral-section">
        <div class="referral-header">
            <div class="referral-title">
                <i class="fas fa-users"></i> Referral Program
            </div>
            <div class="referral-code-badge">
                <?php echo htmlspecialchars($referral_code); ?>
            </div>
        </div>

        <div class="referral-dashboard">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo htmlspecialchars($total_referred); ?></div>
                <div class="stat-label">Total Referred</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-number"><?php echo htmlspecialchars($active_referrals); ?></div>
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

        <div class="referral-link-section">
            <div class="referral-link-header">
                <div class="referral-link-title">
                    <i class="fas fa-link"></i> Your Referral Link
                </div>
                <div class="share-buttons">
                    <a href="whatsapp://send?text=Join KAKA88 using my referral link: <?php echo urlencode($referral_link); ?>" class="share-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://t.me/share/url?url=<?php echo urlencode($referral_link); ?>&text=Join KAKA88 and start earning!" class="share-btn" target="_blank">
                        <i class="fab fa-telegram"></i> Telegram
                    </a>
                </div>
            </div>
            
            <div class="referral-link-container">
                <div class="referral-link" id="referralLink"><?php echo htmlspecialchars($referral_link); ?></div>
                <button class="copy-link-btn" onclick="copyReferralLink()">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>
        </div>

        <div class="referral-info-box">
            <h4>
                <i class="fas fa-info-circle"></i> How Referral Program Works
            </h4>
            <div>
                <div style="margin-bottom: 8px;"><strong>• Share:</strong> Send your unique referral link to friends.</div>
                <div style="margin-bottom: 8px;"><strong>• Earn:</strong> Get 5% commission on their deposits.</div>
                <div style="margin-bottom: 8px;"><strong>• Withdraw:</strong> Commissions are credited instantly.</div>
                <div style="margin-bottom: 8px;"><strong>• No Limit:</strong> Refer unlimited users and earn more.</div>
                <div class="highlight-text">
                    💰 The more they deposit, the more you earn!
                </div>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'home') ? 'active' : ''; ?>" data-page="home">
                <div class="nav-icon">🏠</div>
                <span>Home</span>
            </a>
            <a href="promotion.php" class="nav-item <?php echo ($current_page == 'promotion') ? 'active' : ''; ?>" data-page="promotion">
                <div class="nav-icon">🎁</div>
                <span>Promotions</span>
            </a>
            <a href="invite.php" class="nav-item <?php echo ($current_page == 'invite') ? 'active' : ''; ?>" data-page="invite">
                <div class="nav-icon">👥</div>
                <span>Invite</span>
            </a>
            <a href="rewards.php" class="nav-item <?php echo ($current_page == 'rewards') ? 'active' : ''; ?>" data-page="rewards">
                <div class="nav-icon">🏆</div>
                <span>Rewards</span>
            </a>
            <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" data-page="profile">
                <div class="nav-icon">👤</div>
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <script>
        // JavaScript function to copy the referral link to the clipboard
        function copyReferralLink() {
            const referralLink = document.getElementById('referralLink').innerText; // Get the text content of the link
            navigator.clipboard.writeText(referralLink).then(function() {
                showMessage('Referral link copied to clipboard!', 'success'); // Show success message
                
                // Visual feedback for the copy button
                const btn = event.target.closest('.copy-link-btn');
                const originalText = btn.innerHTML; // Store original button content
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!'; // Change text and icon
                btn.style.background = 'linear-gradient(45deg, #047857, #059669)'; // Change background to indicate success
                
                // Revert button back to original state after 2 seconds
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(45deg, #10b981, #059669)'; // Revert to original gradient
                }, 2000);
            }).catch(function() {
                showMessage('Failed to copy referral link', 'error'); // Show error message
            });
        }

        // JavaScript function to display a temporary message (success/error)
        function showMessage(text, type) {
            // Remove any existing messages to prevent stacking
            const existingMessages = document.querySelectorAll('.message');
            existingMessages.forEach(msg => msg.remove());
            
            // Create a new message div
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`; // Add dynamic class for styling
            messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${text}`;
            
            // Insert the message at the top of the referral section
            const referralSection = document.querySelector('.referral-section');
            referralSection.insertBefore(messageDiv, referralSection.firstChild);
            
            // Remove the message after 4 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 4000);
        }

        // JavaScript for bottom navigation active state (optional, can be done purely with PHP too)
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            const currentPage = '<?php echo $current_page; ?>'; // PHP variable passed to JS
            
            // Loop through nav items and set 'active' class based on current page
            navItems.forEach(item => {
                const page = item.getAttribute('data-page');
                if (page === currentPage) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
            
            // Optional: Add click listener for navigation (if using client-side routing)
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // e.preventDefault(); // Uncomment if you handle navigation purely with JavaScript
                    
                    const page = this.getAttribute('data-page');
                    
                    // Visual feedback for click
                    this.style.opacity = '0.7';
                    
                    // Reset opacity (would typically happen on page load/after JS navigation)
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>
