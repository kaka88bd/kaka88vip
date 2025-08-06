
<?php
// Sample array of slot games (you can replace it with DB data later)
$slot_games = [
    [
        'name' => 'Starburst',
        'image' => 'https://example.com/images/starburst.jpg',
        'link' => 'https://example.com/games/starburst'
    ],
    [
        'name' => 'Gonzo's Quest',
        'image' => 'https://example.com/images/gonzos-quest.jpg',
        'link' => 'https://example.com/games/gonzos-quest'
    ],
    [
        'name' => 'Mega Moolah',
        'image' => 'https://example.com/images/mega-moolah.jpg',
        'link' => 'https://example.com/games/mega-moolah'
    ],
    [
        'name' => 'Book of Dead',
        'image' => 'https://example.com/images/book-of-dead.jpg',
        'link' => 'https://example.com/games/book-of-dead'
    ]
];
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
            <a href="<?php echo htmlspecialchars($game['link']); ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" />
                <div class="game-name"><?php echo htmlspecialchars($game['name']); ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
