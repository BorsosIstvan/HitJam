<?php
session_start();
require_once('db.php');

// Als iemand al is ingelogd, stuur hem direct door naar het menu
if (isset($_SESSION['loggedin'])) {
    header("Location: index.php");
    exit;
}

$foutmelding = "";
$succesmelding = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulier voor INLOGGEN
    if (isset($_POST['action_login'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Wachtwoord klopt! Sessie starten
            $_SESSION['loggedin'] = true;
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $foutmelding = "❌ Ongeldige gebruikersnaam of wachtwoord.";
        }
    }

    // Formulier voor REGISTREREN
    if (isset($_POST['action_register'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (strlen($username) < 3 || strlen($password) < 4) {
            $foutmelding = "❌ Gebruikersnaam (min. 3 tekens) of wachtwoord te kort.";
        } else {
            try {
                // Hash het wachtwoord veilig voor opslag in SQLite
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'speler')");
                $stmt->execute([$username, $hashed_password]);
                
                $succesmelding = "🎉 Account aangemaakt! Je kunt nu inloggen.";
            } catch (PDOException $e) {
                // SQLite geeft een error als de UNIQUE constraint faalt (gebruikersnaam bestaat al)
                $foutmelding = "❌ Deze gebruikersnaam is helaas al bezet.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - Inloggen</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #0b0c10; color: #ffffff; display: flex; justify-content: center; min-height: 100vh; }
        .app-container { width: 100%; max-width: 450px; background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%); padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; }
        .logo-section { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 42px; font-weight: 900; background: linear-gradient(45deg, #ff2d55, #ff9500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; margin: 0; }
        
        .form-box { background: rgba(255,255,255,0.05); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 20px; }
        .form-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; text-align: center; color: #ff9500; text-transform: uppercase; letter-spacing: 1px; }
        
        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; font-size: 12px; color: #aaa; margin-bottom: 5px; text-transform: uppercase; }
        .input-field { width: 100%; padding: 14px; background: #121212; border: 1px solid #333; color: white; border-radius: 12px; font-size: 16px; box-sizing: border-box; }
        .input-field:focus { border-color: #ff2d55; outline: none; }
        
        .btn { width: 100%; padding: 15px; border-radius: 14px; font-size: 16px; font-weight: bold; border: none; cursor: pointer; transition: all 0.2s; margin-top: 10px; }
        .btn-primary { background: linear-gradient(90deg, #ff2d55, #e01b43); color: white; }
        .btn-secondary { background: #1f2026; color: white; border: 1px solid #33343f; margin-top: 5px; }
        .btn:active { transform: scale(0.97); }
        
        .alert { padding: 12px; border-radius: 10px; font-size: 14px; text-align: center; margin-bottom: 15px; font-weight: bold; }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545; }
        .alert-success { background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745; }
        
        .toggle-text { text-align: center; font-size: 13px; color: #aaa; margin-top: 15px; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="app-container">
        <div class="logo-section">
            <h1 class="logo">HitJam</h1>
            <p style="color: #8f8f8f; font-size: 12px; margin: 5px 0 0 0; letter-spacing: 1px; text-transform: uppercase;">Aanmelden & Registreren</p>
        </div>

        <?php if (!empty($foutmelding)): ?>
            <div class="alert alert-danger"><?= $foutmelding ?></div>
        <?php endif; ?>
        <?php if (!empty($succesmelding)): ?>
            <div class="alert alert-success"><?= $succesmelding ?></div>
        <?php endif; ?>

        <!-- INLOG FORMULIER -->
        <div class="form-box" id="loginBox">
            <div class="form-title">🔑 Inloggen</div>
            <form method="POST" action="">
                <div class="input-group">
                    <label>Gebruikersnaam</label>
                    <input type="text" name="username" class="input-field" required autocomplete="off">
                </div>
                <div class="input-group">
                    <label>Wachtwoord</label>
                    <input type="password" name="password" class="input-field" required>
                </div>
                <button type="submit" name="action_login" class="btn btn-primary">Start Spelen</button>
            </form>
            <div class="toggle-text" onclick="toggleForms()">Nieuw hier? Maak een account aan</div>
        </div>

        <!-- REGISTRATIE FORMULIER (Standaard verborgen) -->
        <div class="form-box" id="registerBox" style="display: none;">
            <div class="form-title">📝 Account Maken</div>
            <form method="POST" action="">
                <div class="input-group">
                    <label>Kies Gebruikersnaam</label>
                    <input type="text" name="username" class="input-field" required autocomplete="off">
                </div>
                <div class="input-group">
                    <label>Kies Wachtwoord</label>
                    <input type="password" name="password" class="input-field" required>
                </div>
                <button type="submit" name="action_register" class="btn btn-secondary">Account Registreren</button>
            </form>
            <div class="toggle-text" onclick="toggleForms()">Al een account? Log hier in</div>
        </div>
    </div>

    <script>
        function toggleForms() {
            const loginBox = document.getElementById('loginBox');
            const registerBox = document.getElementById('registerBox');
            if (loginBox.style.display === 'none') {
                loginBox.style.display = 'block';
                registerBox.style.display = 'none';
            } else {
                loginBox.style.display = 'none';
                registerBox.style.display = 'block';
            }
        }
    </script>

</body>
</html>
