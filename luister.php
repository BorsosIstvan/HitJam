<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
require_once('db.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// AJAX verzoek van de Play-knop om de spelers te activeren
if (isset($_GET['ajax_start_music'])) {
    $huidige_tijd = microtime(true);
    $stmt = $db->prepare("UPDATE game_status SET music_started = 1, start_time = ? WHERE id = 1");
    $stmt->execute([$huidige_tijd]);
    echo json_encode(['status' => 'started']);
    exit;
}

// AJAX verzoek om de live gekozen antwoorden van spelers op te halen
if (isset($_GET['ajax_get_live_answers'])) {
    $spelers = $db->query("SELECT username, gekozen_jaar FROM scores WHERE gekozen_jaar > 0")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($spelers);
    exit;
}

try {
    $stmt = $db->prepare("SELECT artist, title, year FROM game_songs WHERE id = ?");
    $stmt->execute([$id]);
    $current_song = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_song) { die("Liedje niet gevonden!"); }
} catch (Exception $e) { die("Database fout: " . $e->getMessage()); }

$schone_artiest = str_replace('&', ' ', $current_song['artist']);
$zoekterm = urlencode($schone_artiest . " " . $current_song['title']);
$api_url = "https://apple.com" . $zoekterm . "&limit=1&entity=song";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$preview_url = "";
if ($response) {
    $json = json_decode($response, true);
    if (isset($json['results']['previewUrl'])) { $preview_url = $json['results']['previewUrl']; }
}
if (empty($preview_url)) { die("Fout: Geen audio gevonden."); }
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Live Jam Control</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #0b0c10; color: #ffffff; display: flex; justify-content: center; min-height: 100vh; }
        .app-container { width: 100%; max-width: 450px; background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%); padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 0 30px rgba(0,0,0,0.5); text-align: center; }
        .header-section h2 { font-size: 26px; font-weight: 900; background: linear-gradient(45deg, #ff2d55, #ff9500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; }
        .audio-control-box { margin: 20px 0; display: flex; justify-content: center; }
        .btn-audio { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #ff2d55, #e01b43); border: none; color: white; font-size: 35px; cursor: pointer; box-shadow: 0 8px 25px rgba(255, 45, 85, 0.4); }
        .btn-audio.playing { background: #121212; border: 3px solid #ff2d55; color: #ff2d55; box-shadow: none; }
        
        /* Live Monitor Box voor de Admin */
        .monitor-box { background: rgba(255,255,255,0.04); padding: 15px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); margin: 20px 0; text-align: left; }
        .live-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        .live-badge { background: #007bff; padding: 2px 8px; border-radius: 10px; font-weight: bold; }
        
        .btn-reveal { width: 100%; padding: 16px; border-radius: 14px; font-size: 16px; font-weight: 700; background-color: #ffffff; color: #0b0c10; border: none; cursor: pointer; text-transform: uppercase; }
        .secret-info { display: none; background: rgba(255, 255, 255, 0.04); padding: 20px; border-radius: 20px; border: 2px dashed #ff9500; margin-top: 15px; }
        .year-display { font-size: 54px; font-weight: 900; color: #ff9500; }
        .btn-menu { color: #8f8f8f; text-decoration: none; margin-top: 15px; display: inline-block; font-size: 14px; }
    </style>
</head>
<body>

    <div class="app-container">
        <div class="header-section">
            <h2>🎵 Live Jam</h2>
            <div style="color:#aaa; font-size:13px;">Druk op Play om de spelers te activeren!</div>
        </div>

        <audio id="audioPlayer" src="<?= $preview_url ?>"></audio>

        <div class="audio-control-box">
            <button class="btn-audio" id="playBtn" onclick="startMuziekEnQuiz()">▶️</button>
        </div>

        <!-- 👁️ LIVE MONITOR: Wie heeft er gedrukt? -->
        <div class="monitor-box">
            <h4 style="margin:0 0 10px 0; text-transform:uppercase; font-size:12px; color:#ff9500; letter-spacing:1px;">⚡ Live Antwoorden van Spelers</h4>
            <div id="liveAnswersContainer">
                <p style="color:#555; font-size:13px; margin:0;">Wachten tot spelers klikken...</p>
            </div>
        </div>

        <div>
            <button class="btn-reveal" id="revealBtn" onclick="revealInfo()">👁️ Sluit ronde & Onthul</button>
            <div class="secret-info" id="secretBox">
                <div class="year-display"><?= $current_song['year'] ?></div>
                <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars($current_song['title']) ?></div>
                <div style="color:#aaa; font-size:14px;"><?= htmlspecialchars($current_song['artist']) ?></div>
            </div>
            <a href="leider_dashboard.php" class="btn-menu">⬅️ Naar Scorebord</a>
        </div>
    </div>

    <script>
        var audio = document.getElementById('audioPlayer');
        var playBtn = document.getElementById('playBtn');
        let monitorInterval;

        function startMuziekEnQuiz() {
            if (audio.paused) {
                // 1. Vertel de Pi dat de muziek start -> dit opent het scherm bij de spelers!
                fetch('luister.php?id=<?= $id ?>&ajax_start_music=1')
                    .then(r => r.json())
                    .then(data => {
                        audio.play();
                        playBtn.innerHTML = "⏸️";
                        playBtn.classList.add('playing');
                        
                        // 2. Start de live-kijker om de antwoorden van de spelers te zien [INDEX]
                        startLiveMonitor();
                    });
            } else {
                audio.pause();
                playBtn.innerHTML = "▶️";
                playBtn.classList.remove('playing');
            }
        }

        function startLiveMonitor() {
            monitorInterval = setInterval(() => {
                fetch('luister.php?id=<?= $id ?>&ajax_get_live_answers=1')
                    .then(r => r.json())
                    .then(spelers => {
                        if(spelers.length === 0) return;
                        let html = '';
                        spelers.forEach(speler => {
                            html += `<div class="live-row">
                                        <span>👤 <strong>${speler.username}</strong> heeft geklikt!</span>
                                        <span class="live-badge">${speler.gekozen_jaar}</span>
                                     </div>`;
                        });
                        document.getElementById('liveAnswersContainer').innerHTML = html;
                    });
            }, 1000); // Check elke seconde [INDEX]
        }

        function revealInfo() {
            clearInterval(monitorInterval);
            document.getElementById('secretBox').style.display = 'block';
            document.getElementById('revealBtn').style.display = 'none';
            audio.pause();
            // Sluit de ronde af voor spelers
            fetch('verwerk_antwoord.php?sluit_ronde=1');
        }
    </script>

</body>
</html>
