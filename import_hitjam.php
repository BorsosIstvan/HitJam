<?php
session_start();
// Veiligheid: Alleen ingelogde admins mogen de database vullen
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Toegang geweigerd. Log eerst in als admin.");
}

require_once('db.php'); // Laadt de verbinding met /var/www/HitData/hitjam.db

try {
    // De lijst met 70 liedjes, exact geformatteerd voor Apple Music
    $songs = [
        // Pop & Dance Hits
        ["artist" => "ABBA", "title" => "Dancing Queen", "year" => 1976, "theme" => "Pop"],
        ["artist" => "Madonna", "title" => "Like a Virgin", "year" => 1984, "theme" => "Pop"],
        ["artist" => "Britney Spears", "title" => "...Baby One More Time", "year" => 1998, "theme" => "Pop"],
        ["artist" => "Lady Gaga", "title" => "Bad Romance", "year" => 2009, "theme" => "Pop"],
        ["artist" => "The Weeknd", "title" => "Blinding Lights", "year" => 2019, "theme" => "Pop"],
        ["artist" => "Harry Styles", "title" => "As It Was", "year" => 2022, "theme" => "Pop"],
        ["artist" => "Kylie Minogue", "title" => "Can't Get You Out of My Head", "year" => 2001, "theme" => "Pop"],
        ["artist" => "Justin Timberlake", "title" => "Can't Stop the Feeling!", "year" => 2016, "theme" => "Pop"],
        ["artist" => "Rihanna", "title" => "Umbrella", "year" => 2007, "theme" => "Pop"],
        ["artist" => "Spice Girls", "title" => "Wannabe", "year" => 1996, "theme" => "Pop"],

        // Rock & Alternatief
        ["artist" => "AC/DC", "title" => "Highway to Hell", "year" => 1979, "theme" => "Rock"],
        ["artist" => "Nirvana", "title" => "Smells Like Teen Spirit", "year" => 1991, "theme" => "Rock"],
        ["artist" => "Bon Jovi", "title" => "Livin' on a Prayer", "year" => 1986, "theme" => "Rock"],
        ["artist" => "Guns N' Roses", "title" => "Sweet Child O' Mine", "year" => 1987, "theme" => "Rock"],
        ["artist" => "Oasis", "title" => "Wonderwall", "year" => 1995, "theme" => "Rock"],
        ["artist" => "The Killers", "title" => "Mr. Brightside", "year" => 2003, "theme" => "Rock"],
        ["artist" => "Coldplay", "title" => "Viva La Vida", "year" => 2008, "theme" => "Rock"],
        ["artist" => "Pink Floyd", "title" => "Another Brick in the Wall, Pt. 2", "year" => 1979, "theme" => "Rock"],
        ["artist" => "Linkin Park", "title" => "In the End", "year" => 2000, "theme" => "Rock"],
        ["artist" => "The White Stripes", "title" => "Seven Nation Army", "year" => 2003, "theme" => "Rock"],

        // Typisch '80s & '90s Nostalgie
        ["artist" => "Rick Astley", "title" => "Never Gonna Give You Up", "year" => 1987, "theme" => "80s"],
        ["artist" => "a-ha", "title" => "Take On Me", "year" => 1984, "theme" => "80s"],
        ["artist" => "Cyndi Lauper", "title" => "Girls Just Want to Have Fun", "year" => 1983, "theme" => "80s"],
        ["artist" => "Wham!", "title" => "Wake Me Up Before You Go-Go", "year" => 1984, "theme" => "80s"],
        ["artist" => "Eurythmics", "title" => "Sweet Dreams (Are Made of This)", "year" => 1983, "theme" => "80s"],
        ["artist" => "Vanilla Ice", "title" => "Ice Ice Baby", "year" => 1990, "theme" => "90s"],
        ["artist" => "MC Hammer", "title" => "U Can't Touch This", "year" => 1990, "theme" => "90s"],
        ["artist" => "Backstreet Boys", "title" => "I Want It That Way", "year" => 1999, "theme" => "90s"],
        ["artist" => "Coolio", "title" => "Gangsta's Paradise", "year" => 1995, "theme" => "90s"],
        ["artist" => "No Doubt", "title" => "Don't Speak", "year" => 1995, "theme" => "90s"],

        // Nederlandse Bodem
        ["artist" => "Guus Meeuwis", "title" => "Het Is Een Nacht... (Levensecht)", "year" => 1995, "theme" => "NL"],
        ["artist" => "Andre Hazes", "title" => "Bloed, Zweet en Tranen", "year" => 2002, "theme" => "NL"],
        ["artist" => "Marco Borsato", "title" => "Dromen Zijn Bedrog", "year" => 1994, "theme" => "NL"],
        ["artist" => "Acda en de Munnik", "title" => "Het Regent Zonnestralen", "year" => 1998, "theme" => "NL"],
        ["artist" => "BLØF", "title" => "Zoutelande", "year" => 2017, "theme" => "NL"],
        ["artist" => "Suzan & Freek", "title" => "Als Het Avond Is", "year" => 2018, "theme" => "NL"],
        ["artist" => "Danny de Munk", "title" => "Ik Voel Me Zo Verdomd Alleen", "year" => 1984, "theme" => "NL"],
        ["artist" => "Kris Kross Amsterdam", "title" => "Hij Is Van Mij", "year" => 2018, "theme" => "NL"],
        ["artist" => "Flemming", "title" => "Amsterdam", "year" => 2021, "theme" => "NL"],
        ["artist" => "Chef'Special", "title" => "In Your Arms", "year" => 2014, "theme" => "NL"],

        // Gouwe Ouwe
        ["artist" => "The Beatles", "title" => "Hey Jude", "year" => 1968, "theme" => "Classics"],
        ["artist" => "The Rolling Stones", "title" => "(I Can't Get No) Satisfaction", "year" => 1965, "theme" => "Classics"],
        ["artist" => "Elvis Presley", "title" => "Suspicious Minds", "year" => 1969, "theme" => "Classics"],
        ["artist" => "Fleetwood Mac", "title" => "Go Your Own Way", "year" => 1977, "theme" => "Classics"],
        ["artist" => "Bob Marley & The Wailers", "title" => "Three Little Birds", "year" => 1977, "theme" => "Classics"],
        ["artist" => "Stevie Wonder", "title" => "Superstition", "year" => 1972, "theme" => "Classics"],
        ["artist" => "Marvin Gaye", "title" => "What's Going On", "year" => 1971, "theme" => "Classics"],
        ["artist" => "Creedence Clearwater Revival", "title" => "Bad Moon Rising", "year" => 1969, "theme" => "Classics"],
        ["artist" => "The Beach Boys", "title" => "Good Vibrations", "year" => 1966, "theme" => "Classics"],
        ["artist" => "Bill Withers", "title" => "Ain't No Sunshine", "year" => 1971, "theme" => "Classics"],

        // Hip-Hop & R&B Anthems
        ["artist" => "Eminem", "title" => "Lose Yourself", "year" => 2002, "theme" => "HipHop"],
        ["artist" => "Dr. Dre", "title" => "Still D.R.E.", "year" => 1999, "theme" => "HipHop"],
        ["artist" => "Outkast", "title" => "Hey Ya!", "year" => 2003, "theme" => "HipHop"],
        ["artist" => "50 Cent", "title" => "In Da Club", "year" => 2003, "theme" => "HipHop"],
        ["artist" => "Usher", "title" => "Yeah!", "year" => 2004, "theme" => "R&B"],
        ["artist" => "Alicia Keys", "title" => "If I Ain't Got You", "year" => 2003, "theme" => "R&B"],
        ["artist" => "The Black Eyed Peas", "title" => "I Gotta Feeling", "year" => 2009, "theme" => "Pop"],
        ["artist" => "Beyoncé", "title" => "Single Ladies (Put a Ring on It)", "year" => 2008, "theme" => "Pop"],
        ["artist" => "Jay-Z", "title" => "Empire State of Mind", "year" => 2009, "theme" => "HipHop"],
        ["artist" => "Snoop Dogg", "title" => "Drop It Like It's Hot", "year" => 2004, "theme" => "HipHop"],

        // Film & Foute Party Knallers
        ["artist" => "Celine Dion", "title" => "My Heart Will Go On", "year" => 1997, "theme" => "Party"],
        ["artist" => "Los Del Rio", "title" => "Macarena", "year" => 1993, "theme" => "Party"],
        ["artist" => "Village People", "title" => "Y.M.C.A.", "year" => 1978, "theme" => "Party"],
        ["artist" => "Aqua", "title" => "Barbie Girl", "year" => 1997, "theme" => "Party"],
        ["artist" => "Pharrell Williams", "title" => "Happy", "year" => 2013, "theme" => "Party"],
        ["artist" => "Psy", "title" => "Gangnam Style", "year" => 2012, "theme" => "Party"],
        ["artist" => "Earth, Wind & Fire", "title" => "September", "year" => 1978, "theme" => "Party"],
        ["artist" => "Survivor", "title" => "Eye of the Tiger", "year" => 1982, "theme" => "Party"],
        ["artist" => "John Travolta & Olivia Newton-John", "title" => "You're the One That I Want", "year" => 1978, "theme" => "Party"],
        ["artist" => "Ray Parker Jr.", "title" => "Ghostbusters", "year" => 1984, "theme" => "Party"]
    ];

    // SQL Statements voorbereiden
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM game_songs WHERE artist = ? AND title = ?");
    $insertStmt = $db->prepare("INSERT INTO game_songs (artist, title, year, theme) VALUES (?, ?, ?, ?)");

    $toegevoegd = 0;
    $overgeslagen = 0;

    foreach ($songs as $song) {
        $checkStmt->execute([$song['artist'], $song['title']]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$song['artist'], $song['title'], $song['year'], $song['theme']]);
            $toegevoegd++;
        } else {
            $overgeslagen++;
        }
    }

    echo "<div style='font-family:sans-serif; max-width:500px; margin:30px auto; padding:20px; border-radius:12px; background:#1f1126; color:#fff; text-align:center; border:2px solid #ff2d55;'>";
    echo "<h2 style='color:#ff9500;'>🔥 HitJam Database Gevuld!</h2>";
    echo "<p>Nieuwe tracks toegevoegd: <strong style='color:#ff2d55;'>$toegevoegd</strong></p>";
    echo "<p>Al aanwezig: <strong>$overgeslagen</strong></p>";
    echo "<br><a href='index.php' style='background:#ff2d55; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-weight:bold; display:inline-block;'>Naar Startmenu</a>";
    echo "</div>";

} catch (Exception $e) {
    die("<p style='color:red;'>❌ Fout tijdens importeren: " . $e->getMessage() . "</p>");
}
?>
