<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$user_id = $_SESSION['user_id'];
$sql = $conn->prepare("SELECT username, balance, avatar FROM users WHERE id = ?");
$sql->bind_param("i", $user_id);
$sql->execute();
$sql->store_result();

if ($sql->num_rows == 0) {
    echo "User not found!";
    exit;
}

$sql->bind_result($username, $balance, $avatar);
$sql->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reward Center</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0; 
    padding: 0;
    background: #fff;
    padding-bottom: 80px; /* Space for bottom nav */
}

.header {
    background: linear-gradient(135deg, #ff7e5f, #feb47b);
    color: #fff;
    padding: 15px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
}

.card {
    margin: 20px;
    padding: 15px;
    background: #ffe4b5;
    border-radius: 10px;
    display: flex;
    align-items: center;
}

.card img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.card .info {
    flex-grow: 1;
}

.card .info .name {
    font-weight: bold;
    font-size: 18px;
}

.card .info .nickname {
    color: #555;
    font-size: 14px;
}

.card .info .balance {
    margin-top: 5px;
    font-size: 16px;
    color: #000;
}

.vip {
    margin: 0 20px 20px 20px;
    font-size: 14px;
    color: #666;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 0 20px 20px 20px;
}

.grid .item {
    background: #f2f2f2;
    padding: 20px;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s;
}

.grid .item:hover {
    background: #ddd;
}

.grid .item i {
    display: block;
    font-size: 24px;
    margin-bottom: 8px;
}

/* Enhanced Bottom Navigation - Matching Dashboard.php Exactly */
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

/* Responsive Design */
@media (max-width: 414px) {
    .nav-item {
        font-size: 11px;
        min-width: 50px;
    }
    
    .nav-icon {
        font-size: 16px;
    }
}
</style>
</head>
<body>
<div class="header">Reward Center</div>

<div class="card">
    <img src="assets/avatar.png" alt="Avatar">
    <div class="info">
        <div class="name"><?php echo htmlspecialchars($username); ?></div>
        <div class="nickname">Nickname: <?php echo htmlspecialchars($username); ?></div>
        <div class="balance">৳ <?php echo number_format($balance, 2); ?></div>
    </div>
</div>

<div class="vip">
    <strong>VIP2</strong> &nbsp; Benefits 1/2
</div>

<div class="grid">
    <div class="item" onclick="location.href='#'">
        🎁<br>Bonus
    </div>
    <div class="item" onclick="location.href='#'">
        💰<br>Rescue Fund
    </div>
    <div class="item" onclick="location.href='invite.php'">
        👥<br>Invite Friends
    </div>
    <div class="item" onclick="location.href='#'">
        🎫<br>TEMU Ticket
    </div>
</div>

<!-- Bottom Navigation - Same as Dashboard.php -->
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
        <a href="rewards.php" class="nav-item active" data-page="reward">
            <div class="nav-icon">🏆</div>
            <span>Rewards</span>
        </a>
        <a href="profile.php" class="nav-item" data-page="profile">
            <div class="nav-icon">👤</div>
            <span>Profile</span>
        </a>
    </div>
</nav>

<script>
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
</script>
</body>
</html>