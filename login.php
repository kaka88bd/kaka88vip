<?php
session_start();
include 'db.php';
include 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "User not found.";
    }
}
?>
<html>
    <head> 
  <title>Login - KAKA88 BD</title> 
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
        
banner {
            width: 100%;          
            height: 150px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('images/logo.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative; 
            
            }
            
        form {
            background: #222;
            padding: 15px;
            border-radius: 10px;
            width: 85%;
            max-width: 300px;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: #333;
            color: #fff;
            font-size: 16px;
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
        <img src="assets/logo.png" alt="KAKA88 Logo">
    </div></div>
    <h2>Login</h2>

    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Register</a><br>
        <a href="forgot_password.php">Forgot password?</a>
    </div>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

</body>
</html>