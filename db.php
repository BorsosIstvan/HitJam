<?php
try {
    // Koppel met de database in de beveiligde map HitData
    $db_path = '/var/www/html/HitData/hitjam.db';
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
	
	// 🔥 EXTRA KOLOMMEN CHECK: Zorg dat SQLite de gekozen antwoorden kan opslaan
	try {
		$db->exec("ALTER TABLE scores ADD COLUMN gekozen_jaar INTEGER DEFAULT 0");
	} catch(Exception $e) { /* Kolom bestaat al, negeer de fout */ }

	try {
		$db->exec("ALTER TABLE game_status ADD COLUMN music_started INTEGER DEFAULT 0");
	} catch(Exception $e) { /* Kolom bestaat al, negeer de fout */ }


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
