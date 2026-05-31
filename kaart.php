<?php
session_start();
// Veiligheid: Alleen ingelogde mensen mogen dit zien
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Koppel de databaseverbinding en de officiële QR-bibliotheek
require_once('db.php');
require_once('../MyApp/phpqrcode/qrlib.php');

try {
    // Haal 1 willekeurig liedje op uit de SQLite database via RANDOM()
    $stmt = $db->query("SELECT id, artist, title, year FROM game_songs ORDER BY RANDOM() LIMIT 1");
    $gekozen_song = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gekozen_song) {
        die("Er staan nog geen liedjes in de SQLite database! Voer eerst import_hitjam.php uit.");
    }
    
    $willekeurig_id = $gekozen_song['id'];

} catch (Exception $e) {
    die("Database fout: " . $e->getMessage());
}

// Bouw de link die de spelleider straks gaat scannen met zijn Samsung-camera
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$scan_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/luister.php?id=" . $willekeurig_id;

// Genereer de QR-code direct in het geheugen via een buffer (Base64)
ob_start();
QRcode::png($scan_url, null, QR_ECLEVEL_L, 6, 2);
$image_data = ob_get_contents();
ob_end_clean();
$base64_qr = 'data:image/png;base64,' . base64_encode($image_data);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Mijn Kaart</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background-color: #0b0c10;
            color: #ffffff;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .app-container {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%);
            padding: 30px 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            text-align: center;
        }

        h2 {
            font-size: 28px;
            font-weight: 900;
            margin: 10px 0;
            background: linear-gradient(45deg, #ff2d55, #ff9500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        .instruction {
            color: #b3b3b3;
            font-size: 14px;
            margin-bottom: 25px;
        }

        /* De fysieke speelkaart op het scherm */
        .game-card {
            background: #121212;
            padding: 25px 20px;
            border-radius: 24px;
            border: 2px solid #ff2d55;
            box-shadow: 0 10px 25px rgba(255, 45, 85, 0.15);
            margin: 20px 0;
        }

        /* Prachtige witte omranding voor de QR code, cruciaal voor de Samsung camera */
        .qr-wrapper {
            background: #ffffff;
            padding: 15px;
            border-radius: 16px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }

        .qr-wrapper img {
            width: 180px;
            height: 180px;
            display: block;
        }

        .card-footer-text {
            color: #4f4f4f;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .btn-back {
            padding: 16px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            background: #1f2026;
            color: #ffffff;
            border: 1px solid #33343f;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-back:active {
            transform: scale(0.96);
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <div>
            <h2>🃏 Jouw Handkaart</h2>
            <div class="instruction">Laat de spelleider deze code scannen met zijn telefoon-camera!</div>
        </div>

        <!-- De interactieve speelkaart -->
        <div class="game-card">
            <div class="qr-wrapper">
                <img src="<?= $base64_qr ?>" alt="HitJam QR Code">
            </div>
            <div class="card-footer-text">Geheime Muziekkaart #<?= $willekeurig_id ?></div>
        </div>

        <!-- Terugknop naar het hoofdmenu -->
        <a href="index.php" class="btn-back">⬅️ Terug naar Menu</a>

    </div>

</body>
</html>
