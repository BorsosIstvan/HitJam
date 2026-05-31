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
        
        if ($gekozen_jaar === $echt_jaar) {
            // 🎉 GOED ANTWOORD! Bereken score (Snelheid loont!)
            // Basis 50 punten + maximaal 50 bonuspunten als je binnen 10 seconden drukt
            $bonus = max(0, (10 - $reactie_snelheid) * 5);
            $punten_erbij = round(50 + $bonus);
            
            // Sla de score op in SQLite
            $stmt = $db->prepare("INSERT INTO scores (username, points) VALUES (?, ?) 
                                  ON CONFLICT(username) DO UPDATE SET points = points + ?");
            $stmt->execute([$username, $punten_erbij, $punten_erbij]);
            
            echo json_encode(['status' => 'correct', 'points' => $punten_erbij]);
        } else {
            // ❌ FOUT ANTWOORD
            echo json_encode(['status' => 'wrong', 'correct_year' => $echt_jaar]);
        }
    }
}
?>
