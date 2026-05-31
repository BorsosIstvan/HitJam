<?php
session_start();
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Speelveld</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #0b0c10; color: #ffffff; display: flex; justify-content: center; min-height: 100vh; }
        .app-container { width: 100%; max-width: 450px; background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%); padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 0 30px rgba(0,0,0,0.5); text-align: center; }
        
        /* Wachtscherm animatie */
        .waiting-box { margin: auto; }
        .pulse-circle { width: 80px; height: 80px; background: #ff2d55; border-radius: 50%; margin: 20px auto; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(0.9); opacity: 1; box-shadow: 0 0 0 0 rgba(255, 45, 85, 0.7); } 70% { transform: scale(1); opacity: 0.8; box-shadow: 0 0 0 20px rgba(255, 45, 85, 0); } 100% { transform: scale(0.9); opacity: 1; } }
        
        /* Quiz Grid */
        .quiz-box { display: none; flex-direction: column; justify-content: center; gap: 15px; margin: auto; width: 100%; }
        .choices-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px; }
        
        .btn-choice { padding: 25px 10px; border-radius: 20px; font-size: 24px; font-weight: 900; border: 2px solid #33343f; background: #1f2026; color: white; cursor: pointer; transition: all 0.1s; }
        .btn-choice:active { transform: scale(0.95); }
        
        /* Nieuw Resultaten/Uitslag Scherm */
        .feedback-screen { display: none; margin: auto; width: 100%; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .result-badge { font-size: 28px; font-weight: 900; margin-bottom: 20px; text-transform: uppercase; }
        .result-correct { color: #00ffcc; }
        .result-wrong { color: #ff2d55; }
        
        .song-info-card { background: rgba(255, 255, 255, 0.04); padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 25px; }
        .info-year { font-size: 48px; font-weight: 900; color: #ff9500; margin-bottom: 5px; }
        .info-title { font-size: 20px; font-weight: 800; margin-bottom: 5px; }
        .info-artist { color: #b3b3b3; font-size: 15px; }
        
        .next-round-info { background: rgba(0, 123, 255, 0.1); border: 1px solid #007bff; padding: 12px; border-radius: 12px; font-size: 13px; color: #00ffcc; margin-top: 15px; }
        
        .btn-back { padding: 16px; border-radius: 16px; font-size: 16px; font-weight: 700; text-decoration: none; text-align: center; background: #1f2026; color: #ffffff; border: 1px solid #33343f; display: block; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- SCHERM 1: WACHTEN OP START -->
        <div class="waiting-box" id="waitingScreen">
            <div class="pulse-circle"></div>
            <h3 style="color:#ff9500; text-transform:uppercase; letter-spacing:1px;">Luister goed...</h3>
            <p style="color:#aaa; font-size:14px;">Zodra de spelleider de muziek start, verschijnen de jaartallen hier!</p>
        </div>

        <!-- SCHERM 2: QUIZ MEERKEUZE -->
        <div class="quiz-box" id="quizScreen">
            <h2 style="font-size:26px; font-weight:900; color:#ff2d55; text-transform:uppercase; margin:0;">Kies het juiste jaar!</h2>
            <p style="color:#aaa; font-size:14px; margin-top:5px;">Druk bliksemsnel voor bonuspunten!</p>
            
            <div class="choices-grid" id="choicesGrid">
                <!-- Knoppen worden live geladen -->
            </div>
        </div>

        <!-- SCHERM 3: LIVE UITSLAG VAN HET LIEDJE -->
        <div class="feedback-screen" id="feedbackScreen">
            <div id="resultStatus" class="result-badge"></div>
            
            <!-- De kaart die alle liedjesgegevens laat zien na het drukken -->
            <div class="song-info-card">
                <div class="info-year" id="infoYear">1990</div>
                <div class="info-title" id="infoTitle">Liedje Titel</div>
                <div class="info-artist" id="infoArtist">Artiest Naam</div>
            </div>
            
            <div class="next-round-info">
                ⏳ Wacht tot de spelleider de volgende ronde start. Jouw scherm springt dan automatisch weer op scherp!
            </div>
        </div>

        <!-- Terugknop: Altijd zichtbaar onderaan het scherm -->
        <a href="index.php" class="btn-back" id="backBtn">⬅️ Verlaat Spel / Menu</a>

    </div>

    <script>
        let momenteelRondeId = 0;
        let alGeantwoord = false;

        // Vraag elke seconde aan de Pi naar de status
        setInterval(function() {
            fetch('check_status.php')
                .then(response => response.json())
                .then(data => {
                    // Als er een NIEUWE ronde actief is en de muziek is gestart
                    if (data.round_active == 1 && data.music_started == 1 && data.current_song_id !== momenteelRondeId) {
                        momenteelRondeId = data.current_song_id;
                        alGeantwoord = false; // Reset antwoord-vlag voor de nieuwe ronde
                        
                        // Schakel schermen
                        document.getElementById('waitingScreen').style.display = 'none';
                        document.getElementById('feedbackScreen').style.display = 'none';
                        document.getElementById('quizScreen').style.display = 'flex';
                        
                        // Bouw de 4 jaarknoppen
                        let gridHTML = '';
                        data.options.forEach(jaar => {
                            gridHTML += `<button class="btn-choice" onclick="stuurAntwoord(${jaar})">${jaar}</button>`;
                        });
                        document.getElementById('choicesGrid').innerHTML = gridHTML;
                    } 
                    // Als de admin de ronde heeft afgesloten (round_active wordt 0) en de speler staat nog in het uitslagscherm
                    else if (data.round_active == 0 && momenteelRondeId !== 0) {
                        resetNaarWachtstand();
                    }
                });
        }, 1000);

        function stuurAntwoord(gekozenJaar) {
            if (alGeantwoord) return;
            alGeantwoord = true;
            
            document.getElementById('quizScreen').style.display = 'none';
            document.getElementById('feedbackScreen').style.display = 'block';
            
            document.getElementById('resultStatus').className = "result-badge";
            document.getElementById('resultStatus').innerHTML = "⏳ Controleren...";

            let formData = new FormData();
            formData.append('jaar', gekozenJaar);

            // Stuur antwoord naar de Pi en vang de complete liedjesgegevens op
            fetch('verwerk_antwoord.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(res => {
                    // Vul de liedjeskaart live in met de gegevens van de Pi
                    document.getElementById('infoYear').innerHTML = res.correct_year;
                    document.getElementById('infoTitle').innerHTML = res.title;
                    document.getElementById('infoArtist').innerHTML = res.artist;
                    
                    // Toon of het goed of fout was
                    let statusDiv = document.getElementById('resultStatus');
                    if (res.status === 'correct') {
                        statusDiv.classList.add('result-correct');
                        statusDiv.innerHTML = `🎉 GOED!<br><span style='font-size:18px; color:#ffffff;'>+${res.points} Punten</span>`;
                    } else {
                        statusDiv.classList.add('result-wrong');
                        statusDiv.innerHTML = "❌ FOUT!";
                    }
                });
        }

        function resetNaarWachtstand() {
            alGeantwoord = false;
            momenteelRondeId = 0;
            document.getElementById('feedbackScreen').style.display = 'none';
            document.getElementById('quizScreen').style.display = 'none';
            document.getElementById('waitingScreen').style.display = 'flex';
        }
    </script>

</body>
</html>
