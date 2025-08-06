<?php
session_start();
include 'db.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deposit_id'], $_POST['action'])) {
    $deposit_id = intval($_POST['deposit_id']);
    $action = $_POST['action'];

    // Fetch deposit and user info
    $stmt = $conn->prepare("SELECT user_id, amount FROM deposits WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $amount = $row['amount'];

        if ($action === 'approve') {
            // Approve & update user balance
            $conn->begin_transaction();

            $updateDeposit = $conn->prepare("UPDATE deposits SET status = 'approved' WHERE id = ?");
            $updateDeposit->bind_param("i", $deposit_id);
            $updateDeposit->execute();

            $updateBalance = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $updateBalance->bind_param("di", $amount, $user_id);
            $updateBalance->execute();

            $conn->commit();
        } elseif ($action === 'reject') {
            // Just reject
            $update = $conn->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ?");
            $update->bind_param("i", $deposit_id);
            $update->execute();
        }
    }
    $stmt->close();
}

// Fetch all pending deposits
$query = "SELECT d.id, u.username, d.amount, d.method, d.trx_id, d.created_at
          FROM deposits d
          JOIN users u ON d.user_id = u.id
          WHERE d.status = 'pending'
          ORDER BY d.created_at DESC";
$deposits = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Deposits</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }
        .approve { background-color: #28a745; }
        .reject { background-color: #dc3545; }
    </style>
</head>
<body>
<div class="container">
    <h2>Pending Deposit Requests</h2>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Trx ID</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($deposits->num_rows > 0): ?>
                <?php while ($dep = $deposits->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dep['username']); ?></td>
                        <td><?php echo htmlspecialchars($dep['amount']); ?></td>
                        <td><?php echo htmlspecialchars($dep['method']); ?></td>
                        <td><?php echo htmlspecialchars($dep['trx_id']); ?></td>
                        <td><?php echo htmlspecialchars($dep['created_at']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="deposit_id" value="<?php echo $dep['id']; ?>">
                                <button class="btn approve" name="action" value="approve">Approve</button>
                                <button class="btn reject" name="action" value="reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No pending deposits.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>