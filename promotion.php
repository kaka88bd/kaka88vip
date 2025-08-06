<html lang="en">
 <head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>Promotions - Kaka88BD</title> 
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #000000;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #fff;
      padding-bottom: 80px; /* Added to make space for the fixed bottom nav */
    }

    .promo-section {
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
      background: linear-gradient(145deg, #111, #222);
      border-radius: 12px;
      box-shadow: 0 0 25px rgba(255, 215, 0, 0.2);
      border: 2px solid gold;
    }

    .promo-title {
      text-align: center;
      font-size: 36px;
      color: gold;
      margin-bottom: 30px;
      text-shadow: 1px 1px 5px rgba(255,215,0,0.7);
    }

    .promo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
    }

    .promo-item {
      background-color: #1c1c1c;
      border: 1px solid #444;
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s ease;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.2);
    }

    .promo-item:hover {
      transform: scale(1.05);
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    }

    .promo-item img {
      width: 100%;
      height: auto;
      display: block;
      border-bottom: 1px solid #333;
    }

    .promo-caption {
      padding: 12px;
      font-size: 17px;
      text-align: center;
      color: #ffcc00;
    }

    /* --- Bottom Navigation Bar Styles --- */
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

    .nav-item.active::before {
        content: '';
        position: absolute;
        top: -16px;
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

    /* Responsive adjustments for bottom nav */
    @media (max-width: 414px) {
        body {
            padding-bottom: 70px; /* Adjust padding for smaller screens if needed */
        }
        .bottom-nav {
            padding: 12px 0;
        }
        .nav-item {
            font-size: 10px;
            min-width: 50px;
            padding: 6px;
        }
        .nav-icon {
            width: 24px;
            height: 24px;
            font-size: 18px;
        }
        .nav-item.active::before {
            top: -12px; /* Adjust dot position */
        }
    }
  </style> 
 </head> 
 <body> 
  <div class="promo-section"> 
   <h1 class="promo-title">💎 Promotions 💎</h1> 
   <div class="promo-grid"> 
    <div class="promo-item"> 
     <img src="promo/join.jpg" alt="Join KAKA88"> 
     <div class="promo-caption">
       🎁 JOIN KAKA88 
     </div> 
    </div> 
    <div class="promo-item"> 
     <img src="promo/jackpot.jpg" alt="Jackpot"> 
     <div class="promo-caption">
       ⭐ WIN BIG JACKPOT 
     </div> 
    </div> 
    <div class="promo-item"> 
     <img src="promo/welcome.jpg" alt="Welcome Bonuses"> 
     <div class="promo-caption">
       🎡 WELCOME BONUS 
     </div> 
    </div> 
    <div class="promo-item"> 
     <img src="promo/deposit.jpg" alt="Deposit Bonuses"> 
     <div class="promo-caption">
       💵 FIRST DEPOSIT BONUS 
     </div> 
    </div> 
    <div class="promo-item"> 
     <img src="promo/refer.jpg" alt="Referral Bonuses"> 
     <div class="promo-caption">
       💰 INVITE &amp; EARN 
     </div> 
    </div> 
    <div class="promo-item"> 
     <img src="promo/tournament.jpg" alt="Tournament"> 
     <div class="promo-caption">
       🎉 Participate Tournament 
     </div> 
    </div> 
   </div> 
  </div> 

  <?php $current_page = 'promotion'; // Set the current page for active state ?>
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
    // This script block is for handling the active state of the bottom navigation.
    // It is purely client-side logic and does not require PHP session_start() or database includes.
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-item');
        // This 'currentPage' variable is set by PHP above this script, based on the file name.
        const currentPage = '<?php echo $current_page; ?>'; 
        
        navItems.forEach(item => {
            const page = item.getAttribute('data-page');
            if (page === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        // Optional: Add click listener for navigation (if using client-side routing,
        // otherwise default browser link behavior is fine)
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // e.preventDefault(); // Uncomment if you want to handle navigation entirely with JS
                
                // Add a small visual feedback for click
                this.style.opacity = '0.7';
                
                // Reset opacity after a short delay (or upon page load for direct navigation)
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 300);
            });
        });
    });
  </script>

 </body>
</html>
