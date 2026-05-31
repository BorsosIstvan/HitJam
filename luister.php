<?php
session_start();
// Veiligheid: Alleen ingelogde mensen mogen de muziekspeler bedienen
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// 1. Koppel de databaseverbinding
require_once('db.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // 2. Zoek het liedje op in de SQLite database
    $stmt = $db->prepare("SELECT artist, title, year FROM game_songs WHERE id = ?");
    $stmt->execute([$id]);
    $current_song = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_song) {
        die("<p style='color:red; font-family:sans-serif; text-align:center; margin-top:50px;'>❌ Liedje met ID " . $id . " niet gevonden in de HitJam database!</p>");
    }
} catch (Exception $e) {
    die("Database fout: " . $e->getMessage());
}

// 3. Maak de juiste zoekterm voor Apple Music
// 🔥 CRUCIALE FIX: Vervang '&' door een spatie zodat Apple's zoekmachine niet crasht!
$schone_artiest = str_replace('&', ' ', $current_song['artist']);

$zoekterm = urlencode($schone_artiest . " " . $current_song['title']);
$api_url = "https://apple.com" . $zoekterm . "&limit=1&entity=song";


// 4. Start de stabiele cURL-verbinding naar Apple Music
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$preview_url = "";
if ($response) {
    $json = json_decode($response, true);
    if (isset($json['results'][0]['previewUrl'])) {
        $preview_url = $json['results'][0]['previewUrl'];
    }
}

// Foutcontrole als Apple het nummer niet kan vinden
if (empty($preview_url)) {
    die("<p style='color:red; font-family:sans-serif; text-align:center; margin-top:50px;'>❌ Fout: Kon geen audio-preview vinden voor '" . htmlspecialchars($current_song['artist'] . " - " . $current_song['title']) . "'.</p>");
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Speel Nummer</title>
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

        .header-section h2 {
            font-size: 26px;
            font-weight: 900;
            margin: 10px 0;
            background: linear-gradient(45deg, #ff2d55, #ff9500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .instruction {
            color: #b3b3b3;
            font-size: 14px;
        }

        /* Grote ronde afspeelknop in Apple Music stijl */
        .audio-control-box {
            margin: 40px 0;
            display: flex;
            justify-content: center;
        }

        .btn-audio {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff2d55, #e01b43);
            border: none;
            color: white;
            font-size: 40px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(255, 45, 85, 0.4);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-audio:active {
            transform: scale(0.94);
        }

        /* Knop verandert van kleur als hij speelt */
        .btn-audio.playing {
            background: #121212;
            border: 3px solid #ff2d55;
            box-shadow: 0 0 20px rgba(255, 45, 85, 0.2);
            color: #ff2d55;
        }

        /* Reveal & Antwoord gedeelte */
        .btn-reveal {
            width: 100%;
            padding: 18px;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 700;
            background-color: #ffffff;
            color: #0b0c10;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255,255,255,0.1);
            transition: all 0.2s;
            text-transform: uppercase;
        }

        .btn-reveal:active {
            transform: scale(0.97);
        }

        .secret-info {
            display: none; /* Standaard verborgen */
            background: rgba(255, 255, 255, 0.04);
            padding: 25px;
            border-radius: 24px;
            border: 2px dashed #ff9500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .year-display {
            font-size: 64px;
            font-weight: 900;
            color: #ff9500; /* Goud/oranje voor het jaartal */
            margin: 5px 0;
            letter-spacing: -1px;
        }

        .track-title {
            font-size: 22px;
            font-weight: 800;
            margin: 10px 0 5px 0;
        }

        .track-artist {
            color: #b3b3b3;
            font-size: 16px;
            margin: 0;
        }

        .btn-menu {
            padding: 12px;
            font-size: 14px;
            color: #8f8f8f;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <div class="header-section">
            <h2>🎵 Live Jam</h2>
            <div class="instruction">Zet je JBL-box luidruchtig aan!</div>
        </div>

        <!-- De onzichtbare HTML5 audiospeler die de Apple Music stream inlaadt -->
        <audio id="audioPlayer" src="<?= $preview_url ?>"></audio>

        <!-- Grote interactieve afspeelknop -->
        <div class="audio-control-box">
            <button class="btn-audio" id="playBtn" onclick="toggleAudio()">▶️</button>
        </div>

        <div>
            <!-- Knop om de gegevens te onthullen -->
            <button class="btn-reveal" id="revealBtn" onclick="revealInfo()">👁️ Onthul Antwoord</button>

            <!-- Het verborgen antwoordpaneel -->
            <div class="secret-info" id="secretBox">
                <div style="font-size: 12px; color: #ff9500; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;">Uitgebracht in:</div>
                <div class="year-display"><?= $current_song['year'] ?></div>
                <div class="track-title"><?= htmlspecialchars($current_song['title']) ?></div>
                <div class="track-artist"><?= htmlspecialchars($current_song['artist']) ?></div>
            </div>
            
            <a href="index.php" class="btn-menu">⬅️ Terug naar Menu</a>
        </div>

    </div>

    <script>
        var audio = document.getElementById('audioPlayer');
        var playBtn = document.getElementById('playBtn');

        function toggleAudio() {
            if (audio.paused) {
                audio.play();
                playBtn.innerHTML = "⏸️";
                playBtn.classList.add('playing');
            } else {
                audio.pause();
                playBtn.innerHTML = "▶️";
                playBtn.classList.remove('playing');
            }
        }

        // Als de 30 seconden voorbij zijn, zet de knop netjes terug
        audio.onended = function() {
            playBtn.innerHTML = "▶️";
            playBtn.classList.remove('playing');
        };

        function revealInfo() {
            // Laat het geheime paneel zien
            document.getElementById('secretBox').style.display = 'block';
            // Verberg de onthulknop
            document.getElementById('revealBtn').style.display = 'none';
            // Stop de muziek automatisch bij het onthullen
            audio.pause();
        }
    </script>

</body>
</html>
