<?php
session_start();
if (!isset($_SESSION['loggedin'])) { exit; }
require_once('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gekozen_jaar = (int)$_POST['jaar'];
    $username = $_SESSION['user'];
    
    // 1. Haal de live status en het juiste nummer op
    $status = $db->query("SELECT * FROM game_status WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($status && $status['round_active'] == 1) {
        $song_id = $status['current_song_id'];
        $start_time = $status['start_time'];
        $huidige_tijd = microtime(true);
        
        // Bereken de reactiesnelheid in seconden
        $reactie_snelheid = $huidige_tijd - $start_time;
        
        // 2. Controleer of het antwoord klopt
        $stmt = $db->prepare("SELECT year FROM game_songs WHERE id = ?");
        $stmt->execute([$song_id]);
        $echt_jaar = (int)$stmt->fetchColumn();
        
        // We slaan ALTIJD het gekozen jaartal op, zodat de admin het direct ziet! [INDEX]
        $stmt = $db->prepare("INSERT INTO scores (username, points, gekozen_jaar) VALUES (?, 0, ?) 
                              ON CONFLICT(username) DO UPDATE SET gekozen_jaar = ?");
        $stmt->execute([$username, $gekozen_jaar, $gekozen_jaar]);

        if ($gekozen_jaar === $echt_jaar) {
            $bonus = max(0, (10 - $reactie_snelheid) * 5);
            $punten_erbij = round(50 + $bonus);
            
            $stmt = $db->prepare("UPDATE scores SET points = points + ? WHERE username = ?");
            $stmt->execute([$punten_erbij, $username]);
            
            echo json_encode(['status' => 'correct', 'points' => $punten_erbij]);
        } else {
            echo json_encode(['status' => 'wrong', 'correct_year' => $echt_jaar]);
        }

    }
}
if (isset($_GET['sluit_ronde'])) {
    $db->exec("UPDATE game_status SET round_active = 0, music_started = 0 WHERE id = 1");
}
?>
