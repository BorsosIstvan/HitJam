<?php
session_start();
if (!isset($_SESSION['loggedin'])) { exit; }
require_once('db.php');

// Als de admin de ronde sluit via luister.php
if (isset($_GET['sluit_ronde'])) {
    $db->exec("UPDATE game_status SET round_active = 0, music_started = 0 WHERE id = 1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gekozen_jaar = (int)$_POST['jaar'];
    $username = $_SESSION['user'];
    
    // 1. Haal de live status op
    $status = $db->query("SELECT * FROM game_status WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($status && $status['round_active'] == 1) {
        $song_id = $status['current_song_id'];
        $start_time = $status['start_time'];
        $huidige_tijd = microtime(true);
        
        $reactie_snelheid = $huidige_tijd - $start_time;
        
        // 2. Haal de VOLLEDIGE gegevens van het liedje op uit de database
        $stmt = $db->prepare("SELECT artist, title, year FROM game_songs WHERE id = ?");
        $stmt->execute([$song_id]);
        $song_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $echt_jaar = (int)$song_info['year'];
        
        // Sla het gekozen jaartal op voor de live monitor van de admin
        $stmt = $db->prepare("INSERT INTO scores (username, points, gekozen_jaar) VALUES (?, 0, ?) 
                              ON CONFLICT(username) DO UPDATE SET gekozen_jaar = ?");
        $stmt->execute([$username, $gekozen_jaar, $gekozen_jaar]);

        // Bouw de basis-respons met de liedjesgegevens
        $responsData = [
            'artist' => $song_info['artist'],
            'title' => $song_info['title'],
            'correct_year' => $echt_jaar
        ];
        
        if ($gekozen_jaar === $echt_jaar) {
            // 🎉 GOED ANTWOORD
            $bonus = max(0, (10 - $reactie_snelheid) * 5);
            $punten_erbij = round(50 + $bonus);
            
            $stmt = $db->prepare("UPDATE scores SET points = points + ? WHERE username = ?");
            $stmt->execute([$punten_erbij, $username]);
            
            $responsData['status'] = 'correct';
            $responsData['points'] = $punten_erbij;
        } else {
            // ❌ FOUT ANTWOORD
            $responsData['status'] = 'wrong';
        }
        
        header('Content-Type: application/json');
        echo json_encode($responsData);
        exit;
    }
}
?>

