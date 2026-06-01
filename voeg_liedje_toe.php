<?php
session_start();

// Veiligheid: Alleen ingelogde admins/spelleiders mogen liedjes toevoegen
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once('db.php');

$succesmelding = "";
$foutmelding = "";

// Formulierverwerking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artist = trim($_POST['artist']);
    $title = trim($_POST['title']);
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $theme = trim($_POST['theme']);

    // Basisvalidatie
    if (empty($artist) || empty($title) || $year < 1900 || $year > 2026 || empty($theme)) {
        $foutmelding = "❌ Vul alle velden correct in. Jaartal moet tussen 1900 en 2026 liggen.";
    } else {
        try {
            // Controleer eerst of dit liedje al bestaat om duplicaten te voorkomen
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM game_songs WHERE artist = ? AND title = ?");
            $checkStmt->execute([$artist, $title]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $foutmelding = "❌ Dit liedje ('" . htmlspecialchars($artist) . " - " . htmlspecialchars($title) . "') staat al in de database!";
            } else {
                // Voeg de nieuwe track toe aan SQLite
                $insertStmt = $db->prepare("INSERT INTO game_songs (artist, title, year, theme) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$artist, $title, $year, $theme]);
                
                $succesmelding = "🎉 '" . htmlspecialchars($title) . "' is succesvol toegevoegd!";
                
                // Maak de velden leeg voor de volgende invoer
                $artist = $title = $year = $theme = "";
            }
        } catch (Exception $e) {
            $foutmelding = "❌ Database fout: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Liedje Toevoegen</title>
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
        }

        .header-section h2 {
            font-size: 26px;
            font-weight: 900;
            margin: 10px 0;
            background: linear-gradient(45deg, #007bff, #00ffcc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        .instruction {
            color: #b3b3b3;
            font-size: 14px;
            text-align: center;
            margin-bottom: 25px;
        }

        /* Formulier Styling */
        .form-box {
            background: rgba(255, 255, 255, 0.04);
            padding: 20px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 12px;
            color: #aaa;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-field, select {
            width: 100%;
            padding: 14px;
            background: #121212;
            border: 1px solid #333;
            color: white;
            border-radius: 12px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .input-field:focus, select:focus {
            border-color: #00ffcc;
            outline: none;
        }

        /* Knoppen */
        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
        }

        .btn-submit {
            background: linear-gradient(90deg, #007bff, #00ffcc);
            color: white;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-submit:active {
            transform: scale(0.97);
        }

        .btn-back {
            background: #1f2026;
            color: #ffffff;
            border: 1px solid #33343f;
            text-decoration: none;
            text-align: center;
            display: block;
            box-sizing: border-box;
        }

        /* Meldingen */
        .alert {
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid #28a745;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <div>
            <div class="header-section">
                <h2>➕ Track Toevoegen</h2>
                <div class="instruction">Breid de HitJam-database live uit met nieuwe muziek.</div>
            </div>

            <!-- Feedback meldingen -->
            <?php if (!empty($foutmelding)): ?>
                <div class="alert alert-danger"><?= $foutmelding ?></div>
            <?php endif; ?>
            <?php if (!empty($succesmelding)): ?>
                <div class="alert alert-success"><?= $succesmelding ?></div>
            <?php endif; ?>

            <!-- Het Invulformulier -->
            <div class="form-box">
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Artiest of Groep</label>
                        <input type="text" name="artist" class="input-field" placeholder="Bijv. Michael Jackson" value="<?= isset($artist) ? htmlspecialchars($artist) : '' ?>" required autocomplete="off">
                    </div>

                    <div class="input-group">
                        <label>Titel van het liedje</label>
                        <input type="text" name="title" class="input-field" placeholder="Bijv. Billie Jean" value="<?= isset($title) ? htmlspecialchars($title) : '' ?>" required autocomplete="off">
                    </div>

                    <div class="input-group">
                        <label>Uitgavejaar</label>
                        <input type="number" name="year" class="input-field" placeholder="Bijv. 1982" min="1900" max="2026" value="<?= isset($year) && $year > 0 ? $year : '' ?>" required autocomplete="off">
                    </div>

                    <div class="input-group">
                        <label>Thema / Genre</label>
                        <select name="theme" required>
                            <option value="" disabled <?= empty($theme) ? 'selected' : '' ?>>Kies een thema...</option>
                            <option value="Pop" <?= (isset($theme) && $theme === 'Pop') ? 'selected' : '' ?>>Pop & Dance</option>
                            <option value="Rock" <?= (isset($theme) && $theme === 'Rock') ? 'selected' : '' ?>>Rock & Alt</option>
                            <option value="80s" <?= (isset($theme) && $theme === '80s') ? 'selected' : '' ?>>80s Nostalgie</option>
                            <option value="90s" <?= (isset($theme) && $theme === '90s') ? 'selected' : '' ?>>90s Nostalgie</option>
                            <option value="NL" <?= (isset($theme) && $theme === 'NL') ? 'selected' : '' ?>>Nederlandse hits</option>
                            <option value="Classics" <?= (isset($theme) && $theme === 'Classics') ? 'selected' : '' ?>>60s & 70s Classics</option>
                            <option value="HipHop" <?= (isset($theme) && $theme === 'HipHop') ? 'selected' : '' ?>>Hip-Hop & R&B</option>
                            <option value="Party" <?= (isset($theme) && $theme === 'Party') ? 'selected' : '' ?>>Film & Foute Party</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-submit">💾 Opslaan in Database</button>
                </form>
            </div>
        </div>

        <!-- Terug naar de verwerkingstoren -->
        <a href="leider_dashboard.php" class="btn btn-back">⬅️ Spelleider Dashboard</a>

    </div>

</body>
</html>
