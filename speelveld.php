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
        
        /* Resultaten feedback */
        .feedback-screen { display: none; margin: auto; font-size: 20px; font-weight: bold; }
        
        .btn-back { padding: 16px; border-radius: 16px; font-size: 16px; font-weight: 700; text-decoration: none; text-align: center; background: #1f2026; color: #ffffff; border: 1px solid #33343f; display: block; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- SCHERM 1: WACHTEN -->
        <div class="waiting-box" id="waitingScreen">
            <div class="pulse-circle"></div>
            <h3 style="color:#ff9500; text-transform:uppercase; letter-spacing:1px;">Luister goed...</h3>
            <p style="color:#aaa; font-size:14px;">Zodra de muziek start op de JBL-box, verschijnen de jaartallen hier op je scherm!</p>
        </div>

        <!-- SCHERM 2: QUIZ INTERFACE -->
        <div class="quiz-box" id="quizScreen">
            <h2 style="font-size:26px; font-weight:900; color:#ff2d55; text-transform:uppercase; margin:0;">Kies het juiste jaar!</h2>
            <p style="color:#aaa; font-size:14px; margin-top:5px;">Druk bliksemsnel voor bonuspunten!</p>
            
            <div class="choices-grid" id="choicesGrid">
                <!-- Knoppen worden live ingeladen door JavaScript -->
            </div>
        </div>

        <!-- SCHERM 3: FEEDBACK (NA HET DRUKKEN) -->
        <div class="feedback-screen" id="feedbackScreen">
            <div id="feedbackText" style="font-size: 28px; margin-bottom: 15px;"></div>
            <p style="color:#aaa; font-size:14px;">Kijk op het scherm van de spelleider voor de tussenstand.</p>
        </div>

        <a href="index.php" class="btn-back" id="backBtn">⬅️ Hoofdmenu</a>

    </div>

    <script>
        let momenteelRondeId = 0;
        let alGeantwoord = false;

        // Dit script vraagt elke seconde aan de Pi of er een ronde start
        setInterval(function() {
            if (alGeantwoord) return; // Als de speler al gedrukt heeft, hoeven we niks te doen

            fetch('check_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.round_active == 1 && data.music_started == 1 && data.current_song_id !== momenteelRondeId) {
                        // 🚀 RONDE START! Wissel van scherm
                        momenteelRondeId = data.current_song_id;
                        document.getElementById('waitingScreen').style.display = 'none';
                        document.getElementById('backBtn').style.display = 'none';
                        document.getElementById('quizScreen').style.display = 'flex';
                        
                        // Bouw de 4 jaarknoppen op het scherm
                        let gridHTML = '';
                        data.options.forEach(jaar => {
                            gridHTML += `<button class="btn-choice" onclick="stuurAntwoord(${jaar})">${jaar}</button>`;
                        });
                        document.getElementById('choicesGrid').innerHTML = gridHTML;
                    } else if (data.round_active == 0 && momenteelRondeId !== 0) {
                        // De admin heeft de ronde gereset, zet speler weer in de wachtstand
                        resetNaarWachtstand();
                    }
                });
        }, 1000);

        function stuurAntwoord(gekozenJaar) {
            alGeantwoord = true;
            document.getElementById('quizScreen').style.display = 'none';
            document.getElementById('feedbackScreen').style.display = 'block';
            document.getElementById('feedbackText').innerHTML = "⏳ Verwerken...";

            // Stuur het jaartal naar de server via een POST aanvraag
            let formData = new FormData();
            formData.append('jaar', gekozenJaar);

            fetch('verwerk_antwoord.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'correct') {
                        document.getElementById('feedbackText').innerHTML = `🎉 GOED!<br><span style='color:#00ffcc; font-size:22px;'>+${res.points} Punten!</span>`;
                    } else {
                        document.getElementById('feedbackText').innerHTML = `❌ FOUT!<br><span style='color:#ff2d55; font-size:20px;'>Het was ${res.correct_year}</span>`;
                    }
                });
        }

        function resetNaarWachtstand() {
            alGeantwoord = false;
            momenteelRondeId = 0;
            document.getElementById('feedbackScreen').style.display = 'none';
            document.getElementById('quizScreen').style.display = 'none';
            document.getElementById('waitingScreen').style.display = 'flex';
            document.getElementById('backBtn').style.display = 'block';
        }
    </script>

</body>
</html>
