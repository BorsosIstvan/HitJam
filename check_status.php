<?php
session_start();
if (!isset($_SESSION['loggedin'])) { exit; }
require_once('db.php');

$status = $db->query("SELECT * FROM game_status WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$options = [];
// 🔥 UPDATE: Alleen jaartallen meesturen als de admin ÉCHT op Play heeft gedrukt!
if ($status && $status['round_active'] == 1 && $status['music_started'] == 1) {
    $song_id = $status['current_song_id'];
    
    $stmt = $db->prepare("SELECT year FROM game_songs WHERE id = ?");
    $stmt->execute([$song_id]);
    $real_year = (int)$stmt->fetchColumn();
    
    $years_pool = [$real_year];
    while (count($years_pool) < 4) {
        $fake_year = $real_year + rand(-7, 7);
        if (!in_array($fake_year, $years_pool) && $fake_year <= 2026) {
            $years_pool[] = $fake_year;
        }
    }
    sort($years_pool);
    $options = $years_pool;
}

header('Content-Type: application/json');
echo json_encode([
    'round_active' => $status['round_active'] ?? 0,
    'music_started' => $status['music_started'] ?? 0, // Stuur dit extra mee
    'current_song_id' => $status['current_song_id'] ?? 0,
    'options' => $options
]);
?>
