<?php
session_start();
include 'db.php'; // your DB connection

$user_id = $_SESSION['user_id'] ?? 0;
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_type'], $_POST['wallet_number'])) {
    $wallet_type = $_POST['wallet_type'];
    $wallet_number = trim($_POST['wallet_number']);

    // Check current count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_count);
    $stmt->fetch();
    $stmt->close();

    if ($wallet_count >= 2) {
        $message = "You can only add up to 2 wallets.";
    } else {
        $stmt = $conn->prepare("INSERT INTO wallets (user_id, wallet_type, wallet_number) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $wallet_type, $wallet_number);
        if ($stmt->execute()) {
            $message = "Wallet added successfully.";
        } else {
            $message = "Failed to add wallet.";
        }
        $stmt->close();
    }
}

// Fetch user's wallets
$stmt = $conn->prepare("SELECT wallet_type, wallet_number FROM wallets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>My Wallets</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
      margin: 0;
      padding: 20px;
    }
    .card-container {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px #aaa;
    }
    h2 {
      text-align: center;
      color: #333;
    }
    .message {
      text-align: center;
      color: green;
      margin-bottom: 15px;
    }
    .wallet {
      padding: 15px;
      background: #f9f9f9;
      border: 1px solid #ddd;
      margin-bottom: 10px;
      border-radius: 6px;
    }
    form {
      margin-top: 20px;
    }
    select, input[type="text"] {
      width: 100%;
      padding: 10px;
      margin: 6px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 12px;
      background: #28a745;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
    }
    button:hover {
      background: #218838;
    }
  </style>
</head>
<body>

<div class="card-container">
  <h2>My Wallets</h2>
  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php foreach ($wallets as $wallet): ?>
    <div class="wallet">
      <strong><?= htmlspecialchars($wallet['wallet_type']) ?>:</strong>
      <?= htmlspecialchars($wallet['wallet_number']) ?>
    </div>
  <?php endforeach; ?>

  <?php if (count($wallets) < 2): ?>
    <form method="post">
      <label for="wallet_type">Wallet Type</label>
      <select name="wallet_type" id="wallet_type" required>
        <option value="">-- Select --</option>
        <option value="Bkash">Bkash</option>
        <option value="Nagad">Nagad</option>
      </select>

      <label for="wallet_number">Wallet Number</label>
      <input type="text" name="wallet_number" id="wallet_number" maxlength="20" required pattern="\d{11}" placeholder="11 digit number">

      <button type="submit">Add Wallet</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>