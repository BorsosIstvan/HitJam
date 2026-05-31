<?php
session_start();
if (!isset($_SESSION['loggedin'])) { exit; }
require_once('db.php');

// Haal de live status op
$status = $db->query("SELECT * FROM game_status WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

// Als er een ronde actief is, halen we ook de 4 jaartallen op (eenmalig per ronde)
$options = [];
if ($status && $status['round_active'] == 1) {
    $song_id = $status['current_song_id'];
    
    // Haal het echte jaartal van het liedje op
    $stmt = $db->prepare("SELECT year FROM game_songs WHERE id = ?");
    $stmt->execute([$song_id]);
    $real_year = (int)$stmt->fetchColumn();
    
    // Genereer 3 nep-jaartallen in de buurt van het echte jaar
    $years_pool = [$real_year];
    while (count($years_pool) < 4) {
        $fake_year = $real_year + rand(-7, 7);
        if (!in_all($fake_year, $years_pool) && $fake_year <= 2026) {
            $years_pool[] = $fake_year;
        }
    }
    // Sorteer de jaartallen zodat het juiste antwoord niet altijd op dezelfde plek staat
    sort($years_pool);
    $options = $years_pool;
}

// Stuur alles netjes als JSON-tekst terug naar de browser van de speler
header('Content-Type: application/json');
echo json_encode([
    'round_active' => $status['round_active'] ?? 0,
    'current_song_id' => $status['current_song_id'] ?? 0,
    'options' => $options
]);

function in_all($needle, $haystack) { return in_array($needle, $haystack); }
?>
