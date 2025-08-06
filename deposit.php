<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $trx_id = $_POST['trx_id'];

    if ($amount > 0 && isset($_FILES['screenshot'])) {
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $screenshot_name = basename($_FILES["screenshot"]["name"]);
        $screenshot_path = $upload_dir . time() . "_" . $screenshot_name;

        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $screenshot_path)) {
            $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, method, trx_id, screenshot, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("idsss", $user_id, $amount, $method, $trx_id, $screenshot_path);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Deposit request failed!";
            }
            $stmt->close();
        } else {
            $error = "Screenshot upload failed!";
        }
    } else {
        $error = "Invalid deposit data!";
    }
}
?>

<html lang="en">
 <head> 
  
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>Deposit Wallet</title> 
  <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success { background: #e0ffe6; color: #2e7d32; }
        .error { background: #ffe0e0; color: #c62828; }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        input[type="number"],
        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .payment-info {
            background: #f0f0f0;
            padding: 10px 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .payment-method {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .copy-btn:hover {
            background: #0056b3;
        }
        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #0056b3;
        }
   .popup {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      display: flex;
      justify-content: center;
      align-items: center;
      visibility: hidden;
      opacity: 0;
      transition: all 0.3s ease;
      z-index: 999;
    }

    .popup.show {
      visibility: visible;
      opacity: 1;
    }

    .popup-content {
      background: white;
      padding: 25px 30px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      animation: scaleUp 0.4s ease;
    }

    @keyframes scaleUp {
      from { transform: scale(0.7); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .popup-content h3 {
      color: #4e4376;
      margin-bottom: 10px;
    }

    .popup-content button {
      background: #4e4376;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 15px;
    }
    </style> 
 </head> 
 <body> 
  <div class="container"> 
   <h2>Deposit to Wallet</h2> 

   <?php if ($success): ?>
    <script>
        window.onload = () => {
            document.getElementById('success_popup').classList.add('show');
        };
    </script>
   <?php elseif (!empty($error)): ?>
    <div class="message error"><?php echo $error; ?></div>
   <?php endif; ?>

   <form id="depositForm" action="deposit.php" method="POST" enctype="multipart/form-data"> 
    <label>Amount</label> 
    <input type="number" step="0.01" name="amount" required> 

    <label>Payment Method</label> 
    <select name="method" required>
        <option value="bKash">bKash</option>
        <option value="Nagad">Nagad</option>
    </select> 

    <div class="payment-info"> 
     <div class="payment-method"> 
      <span><strong>bKash:</strong> <span id="bkashNum">01954006631</span></span> 
      <button type="button" class="copy-btn" onclick="copyToClipboard('bkashNum')">Copy</button> 
     </div> 
     <div class="payment-method"> 
      <span><strong>Nagad:</strong> <span id="nagadNum">01933422819</span></span> 
      <button type="button" class="copy-btn" onclick="copyToClipboard('nagadNum')">Copy</button> 
     </div> 
    </div> 

    <label>Transaction ID</label> 
    <input type="text" name="trx_id" required> 

    <label>Upload Screenshot</label> 
    <input type="file" name="screenshot" accept="image/*" required> 

    <button class="submit-btn" type="submit">Submit Deposit</button> 
   </form> 
  </div> 

  <div class="popup" id="success_popup"> 
   <div class="popup-content"> 
    <h3>Deposit Submitted!</h3> 
    <p>Your Balance will be Added Within 2 Minutes </p> 
    <button onclick="closePopup()">OK</button> 
   </div> 
  </div> 

  <script>
    function copyToClipboard(id) {
        const text = document.getElementById(id).innerText;
        navigator.clipboard.writeText(text);
        alert("Copied: " + text);
    }

    function closePopup() {
        document.getElementById('success_popup').classList.remove('show');
    }
  </script> 
 </body>
</html>