<?php
session_start();

// Tijdelijke test-login voor de opzet (Gebruik je eigen login-systeem als je dat al hebt)
if (isset($_GET['login_as'])) {
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = $_GET['login_as']; // 'admin' of 'speler1'
}

$is_logged_in = isset($_SESSION['loggedin']);
$is_admin = (isset($_SESSION['user']) && $_SESSION['user'] === 'admin');
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>HitJam - The Music Quiz Battle</title>
    <style>
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            margin: 0;
            background-color: #0b0c10; /* Diep donker paars/zwart */
            color: #ffffff;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        /* Telefoon container */
        .app-container {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(180deg, #1f1126 0%, #0b0c10 100%); /* Paarse gloed bovenaan */
            padding: 30px 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }

        /* Logo & Header */
        .header-section {
            text-align: center;
            margin-top: 40px;
        }

        .logo {
            font-size: 48px;
            font-weight: 900;
            letter-spacing: -1px;
            background: linear-gradient(45deg, #ff2d55, #ff9500); /* Neon rood naar oranje */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            margin: 0;
        }

        .tagline {
            color: #8f8f8f;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* Menu knoppen */
        .menu-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 40px 0;
        }

        .btn {
            padding: 18px;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn:active {
            transform: scale(0.96);
        }

        .btn-primary {
            background: linear-gradient(90deg, #ff2d55, #e01b43);
            color: white;
            box-shadow: 0 4px 20px rgba(255, 45, 85, 0.3);
        }

        .btn-secondary {
            background: #1f2026;
            color: #ffffff;
            border: 1px solid #33343f;
        }

        .btn-admin {
            background: linear-gradient(90deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
        }

        /* Gebruikers info */
        .user-status {
            background: rgba(255,255,255,0.05);
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            text-align: center;
            color: #b3b3b3;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .user-name {
            color: #ff9500;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            font-size: 11px;
            color: #4f4f4f;
            text-align: center;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- Logo gedeelte -->
        <div class="header-section">
            <h1 class="logo">HitJam</h1>
            <div class="tagline">The Music Quiz Battle</div>
        </div>

        <!-- Menu knoppen gebaseerd op status -->
        <div class="menu-section">
            <?php if (!$is_logged_in): ?>
                <!-- Dit is puur voor de test, hier komt straks je eigen login-koppeling -->
                <p style="text-align:center; color:#aaa; font-size:14px;">Demo Inloggen als:</p>
                <a href="index.php?login_as=speler1" class="btn btn-primary">📱 Inloggen als Speler</a>
                <a href="index.php?login_as=admin" class="btn btn-admin">👑 Inloggen als Spelleider</a>
            <?php else: ?>
                
                <div class="user-status">
                    Ingelogd als: <span class="user-name"><?= htmlspecialchars($_SESSION['user']) ?></span>
                </div>

                <!-- Iedereen kan meespelen -->
                <a href="speelveld.php" class="btn btn-primary">🎮 Start Quiz Battle</a>
                
                <!-- Iedereen kan zijn eigen QR-handkaart opvragen -->
                <a href="kaart.php" class="btn btn-secondary">🃏 Mijn Handkaart</a>

                <!-- Alleen de Admin/Spelleider ziet de dashboard/JBL-knop -->
                <?php if ($is_admin): ?>
                    <a href="leider_dashboard.php" class="btn btn-admin">👑 Spelleider Controle (JBL)</a>
                <?php endif; ?>

                <a href="?logout=1" class="btn btn-secondary" style="color: #ff2d55; margin-top: 20px; font-size: 14px; padding: 10px;">Uitloggen</a>
                <?php 
                if (isset($_GET['logout'])) { 
                    session_destroy(); 
                    header("Location: index.php"); 
                } 
                ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            POWERED BY RASPBERRY PI & APPLE MUSIC
        </div>

    </div>

</body>
</html>
