<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once('db.php');

// 🔥 AUTOMATISCHE EXTRA TABELLEN CHECK FOR THE BATTLE
$db->exec("CREATE TABLE IF NOT EXISTS game_status (
    id INTEGER PRIMARY KEY,
    current_song_id INTEGER DEFAULT 0,
    round_active INTEGER DEFAULT 0,
    start_time REAL DEFAULT 0
)");

$db->exec("CREATE TABLE IF NOT EXISTS scores (
    username TEXT PRIMARY KEY,
    points INTEGER DEFAULT 0
)");

// Zorg dat er altijd 1 status rij is
$checkStatus = $db->query("SELECT COUNT(*) FROM game_status")->fetchColumn();
if ($checkStatus == 0) {
    $db->exec("INSERT INTO game_status (id, current_song_id, round_active) VALUES (1, 0, 0)");
}

$melding = "";

// Actie: Start een nieuwe willekeurige quiz-ronde voor IEDEREEN
if (isset($_POST['start_nieuwe_ronde'])) {
    // Kies een willekeurig liedje uit de database
    $song = $db->query("SELECT id FROM game_songs ORDER BY RANDOM() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($song) {
        $song_id = $song['id'];
        $huidige_tijd = microtime(true); // Tijd in milliseconden voor de snelheid-check
        
        // Update de live status: We zetten music_started op 0! Pas bij de play-knop gaat de quiz los. v1.0.1
		$stmt = $db->prepare("UPDATE game_status SET current_song_id = ?, round_active = 1, music_started = 0, start_time = 0 WHERE id = 1");
		$stmt->execute([$song_id]);
		
		// Reset ook direct de oude gekozen antwoorden van de spelers voor de nieuwe ronde!
		$db->exec("UPDATE scores SET gekozen_jaar = 0");
        
        // Stuur de leider direct door naar de luisterpagina om de muziek te starten op de JBL!
        header("Location: luister.php?id=" . $song_id . "&battle=1");
        exit;
    } else {
        $melding = "❌ Geen liedjes gevonden in de database.";
    }
}

// Actie: Reset alle scores naar 0
if (isset($_POST['reset_scores'])) {
    $db->exec("DELETE FROM scores");
    $db->exec("UPDATE game_status SET current_song_id = 0, round_active = 0 WHERE id = 1");
    $melding = "🔄 Alle scores zijn gereset naar 0!";
}

// Haal de live scores op om te tonen op het dashboard
$ranglijst = $db->query("SELECT username, points FROM scores ORDER BY points DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Spelleider Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #0b0c10; color: #ffffff; display: flex; justify-content: center; min-height: 100vh; }
        .app-container { width: 100%; max-width: 450px; background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%); padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 0 30px rgba(0,0,0,0.5); text-align: center; }
        h2 { font-size: 28px; font-weight: 900; background: linear-gradient(45deg, #007bff, #00ffcc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; margin: 10px 0; }
        .score-box { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); margin: 20px 0; }
        .score-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 16px; }
        .score-row:last-child { border: none; }
        .player-name { font-weight: bold; color: #ff9500; }
        .player-points { font-weight: 900; color: #00ffcc; }
        .btn { width: 100%; padding: 18px; border-radius: 16px; font-size: 18px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; margin-bottom: 15px; display: block; text-decoration: none; text-align: center; box-sizing: border-box; }
        .btn-battle { background: linear-gradient(90deg, #007bff, #00ffcc); color: white; box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3); }
        .btn-secondary { background: #1f2026; color: white; border: 1px solid #33343f; }
        .btn:active { transform: scale(0.96); }
        .alert { color: #00ffcc; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="app-container">
        <div>
            <h2>👑 Quiz Control</h2>
            <p style="color: #aaa; font-size: 14px;">Bedien de live battle en bekijk wie er wint!</p>

            <?php if (!empty($melding)): ?>
                <div class="alert"><?= $melding ?></div>
            <?php endif; ?>

            <!-- Scorebord -->
            <div class="score-box">
                <h3 style="margin-top:0; text-transform:uppercase; font-size:14px; letter-spacing:1px; color:#aaa;">🏆 Live Tussenstand</h3>
                <?php if (empty($ranglijst)): ?>
                    <p style="color:#666; font-size:14px; margin: 10px 0 0 0;">Nog geen punten gescoord.</p>
                <?php else: ?>
                    <?php foreach ($ranglijst as $index => $speler): ?>
                        <div class="score-row">
                            <span><?= $index + 1 ?>. <span class="player-name"><?= htmlspecialchars($speler['username']) ?></span></span>
                            <span class="player-points"><?= $speler['points'] ?> Pnt</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <form method="POST" action="">
                <!-- Start een ronde voor álle telefoons tegelijk -->
                <button type="submit" name="start_nieuwe_ronde" class="btn btn-battle">🚀 Start Live Battle Ronde</button>
                
                <!-- Of gebruik de klassieke camera scan methode -->
                <a href="index.php" class="btn btn-secondary" style="color: #007bff;">📸 Klassiek scannen via camera</a>
                
                <button type="submit" name="reset_scores" class="btn btn-secondary" style="color: #ff2d55; font-size: 14px; padding: 10px;" onclick="return confirm('Weet je zeker dat je alle scores wilt wissen?')">🔄 Reset Scorebord</button>
            </form>
            <!-- Sla dit op in de knoppen-lijst van leider_dashboard.php -->
			<a href="voeg_liedje_toe.php" class="btn btn-secondary" style="color: #00ffcc; border-color: #00ffcc;">➕ Nieuw Liedje Toevoegen</a>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 10px;">⬅️ Hoofdmenu</a>
        </div>
    </div>

</body>
</html>
