
<?php
$host = "sql309.infinityfree.com";
$dbname = "if0_38830575_kaka88";
$user = "if0_38830575";
$pass = "Kirebhai121377";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$stmt = $pdo->query("SELECT name, image_url, game_link FROM slot_games");
$slot_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Slot Games</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: #121212;
        color: #fff;
    }
    header {
        background: #222;
        padding: 15px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
    }
    .games-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        padding: 15px;
    }
    .game-card {
        background: #1e1e1e;
        border-radius: 8px;
        width: 150px;
        box-shadow: 0 0 10px #000;
        text-align: center;
        overflow: hidden;
        transition: transform 0.2s ease;
    }
    .game-card:hover {
        transform: scale(1.05);
    }
    .game-card img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        display: block;
    }
    .game-name {
        padding: 10px;
        font-size: 16px;
    }
    a {
        color: #1db954;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<header>Slot Games</header>
<div class="games-container">
    <?php foreach ($slot_games as $game): ?>
        <div class="game-card">
            <a href="<?php echo htmlspecialchars($game['game_link']); ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?php echo htmlspecialchars($game['image_url']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" />
                <div class="game-name"><?php echo htmlspecialchars($game['name']); ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
