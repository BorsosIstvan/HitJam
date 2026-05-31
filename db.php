<?php
try {
    // Koppel met de database in de beveiligde map HitData
    $db_path = '/var/www/HitData/hitjam.db';
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Maak direct de 'users' tabel aan als deze nog niet bestaat
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'speler',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Maak ook alvast de 'game_songs' tabel aan in deze nieuwe database
    $db->exec("CREATE TABLE IF NOT EXISTS game_songs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artist TEXT NOT NULL,
        title TEXT NOT NULL,
        year INTEGER NOT NULL,
        theme TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Zorg ervoor dat er altijd minstens één admin/spelleider account bestaat
    $checkAdmin = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($checkAdmin == 0) {
        // We hashen het wachtwoord veilig met bcrypt
        $veilig_wachtwoord = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute(['admin', $veilig_wachtwoord]);
    }

} catch (Exception $e) {
    die("Fout bij het verbinden met de beveiligde SQLite database: " . $e->getMessage());
}
?>
