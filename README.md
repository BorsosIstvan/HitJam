"# HitJam" 
Hier is een compleet, professioneel overzicht van jouw applicatie HitJam. Je kunt de onderstaande tekst kopiëren en opslaan als een .txt of .md (Markdown) bestand op je computer.
Dit document bevat alle bouwstenen, database-structuren, API's en logica, zodat een andere AI (of jijzelf) de app in de toekomst in één keer begrijpt en direct verder kan ontwikkelen.
------------------------------
## 🎵 HitJam - Project Architectuur & Documentatie
HitJam is een interactieve, multiplayer muziekquiz-game die draait op een lokale Apache webserver (Raspberry Pi) met een SQLite database. Het spel combineert het scannen van fysieke/digitale QR-codes met een bliksemsnelle realtime quiz-battle op mobiele telefoons, waarbij muziek via een JBL-box (of andere speaker) wordt afgespeeld.
------------------------------
## 🛠️ 1. Technische Stack & Bouwstenen

* Serveromgeving: Raspberry Pi met Apache2 webserver.
* Backend: Pure PHP (zonder zware frameworks) met PDO voor database-interactie en cURL voor externe API-verzoeken.
* Frontend: HTML5, CSS3 (Modern Dark Mode / Neon-feeststijl, geoptimaliseerd voor mobiel/PWA-formaat), en pure JavaScript (AJAX / Fetch API) voor realtime updates.
* Database: SQLite 3 (opgeslagen in een beveiligde map buiten de openbare root: /var/www/html/HitData/hitjam.db).
* QR-Bibliotheek: PHP QR Code (qrlib.php), draait volledig lokaal op de Pi (maakt gebruik van de PHP-GD extensie).

------------------------------
## 🔌 2. Externe API-Koppeling (Legale Streaming)
De app maakt gebruik van de iTunes / Apple Music Search API:

* URL: https://apple.com[ZOEKTERM]&limit=1&entity=song
* Functie: PHP zoekt via cURL op basis van artiest + titel. De API geeft een JSON-lijst terug waaruit de app de 30-seconden audio-preview (previewUrl) vist.
* Voordeel: 100% gratis, legaal (geen auteursrechtenschending via lokale hosting) en spelers hebben geen eigen Spotify/Apple-abonnement of app nodig; het speelt direct af in de mobiele browser via een HTML5 <audio> tag.
* Slimme logica: De code filtert speciale tekens zoals & automatisch weg uit de artiestennaam (bijv. "Suzan & Freek" wordt "Suzan Freek") om vastlopen van de Apple-zoekmachine te voorkomen.

------------------------------
## 🗄️ 3. Database Structuur (SQLite)
De database bestaat uit drie tabellen:
## users
Slaat de spelers en de admin/spelleider op. Wachtwoorden worden veilig versleuteld met de industriestandaard password_hash() (bcrypt).

* id (INTEGER, PRIMARY KEY, AUTOINCREMENT)
* username (TEXT, UNIQUE)
* password (TEXT)
* role (TEXT, standaard 'speler', 'admin' voor de spelleider)
* created_at (DATETIME)

## game_songs
De muziekbibliotheek (bevat standaard 70 iconische wereldhits verdeeld over genres).

* id (INTEGER, PRIMARY KEY, AUTOINCREMENT)
* artist (TEXT)
* title (TEXT)
* year (INTEGER)
* theme (TEXT, bijv: Pop, Rock, 80s, 90s, NL, Classics, HipHop, Party)
* created_at (DATETIME)

## game_status
Houdt de live-status van het huidige spel bij. Dit regelt de synchronisatie tussen de admin en de spelers.

* id (INTEGER, PRIMARY KEY)
* current_song_id (INTEGER, welk liedje nu speelt)
* round_active (INTEGER, 0 of 1)
* music_started (INTEGER, 0 of 1, activeert de knoppen bij spelers)
* start_time (REAL, timestamp in milliseconden van het startmoment van de muziek)

## scores
Houdt de live-scores en het momenteel gekozen antwoord van spelers bij.

* username (TEXT, PRIMARY KEY)
* points (INTEGER, standaard 0)
* gekozen_jaar (INTEGER, standaard 0, reset elke ronde)

------------------------------
## 💻 4. Overzicht van de Bestanden & Functies
De applicatie is opgedeeld in de volgende bestanden in de map /hitjam/:

   1. db.php: Centraal verbindingsbestand voor SQLite. Maakt automatisch de database, tabellen en het standaard administrator-account (admin met wachtwoord admin123) aan als deze nog niet bestaan.
   2. login.php: Gecombineerde, strakke login- en registratiepagina. Spelers kunnen hier zelf een account aanmaken.
   3. index.php: Het hoofdmenu (Dark Mode). Toont dynamisch knoppen op basis van je rol (Speler ziet Start Quiz, Admin ziet Spelleider Controle). Bevat de session_destroy() uitlogfunctionaliteit.
   4. kaart.php: (Klassieke modus) Speler vraagt een handkaart op. De Pi kiest een willekeurig nummer en genereert een officiële QR-code met een brede witte rand (geoptimaliseerd voor de ingebouwde Samsung/iPhone camera-app).
   5. leider_dashboard.php: (Battle modus) Het controlepaneel voor de admin. Toont het live scorebord (ranglijst) uit de database. Bevat de knop om een nieuwe ronde te starten (kiest een willekeurig nummer en activeert de status) en de knop om de scores te resetten.
   6. luister.php: De afspeelpagina voor de admin (gekoppeld aan JBL). Roept de Apple API op. Zodra de admin op ▶️ Play drukt, wordt music_started = 1 en start de milliseconden-timer. Bevat een Live Monitor die via AJAX elke seconde de tabel scores uitleest en live aan de admin toont welke speler op welk jaartal klikt.
   7. check_status.php: Onzichtbaar script dat door spelers via een JavaScript setInterval (elke seconde) wordt aangeroepen. Zodra music_started == 1, genereert dit script het juiste jaartal + 3 logische nep-jaartallen (binnen een straal van 7 jaar) en stuurt dit als JSON naar de speler.
   8. speelveld.php: Het interactieve speelveld voor spelers. Toont een pulserende animatie tijdens het wachten. Zodra de muziek start, verschijnen er 4 grote jaarknoppen.
   9. verwerk_antwoord.php: Verwerkt het geklikte jaartal van de speler. Slaat direct het gekozen jaar op voor de admin-monitor. Als het antwoord goed is, berekent het script de reactiesnelheid (huidige_tijd - start_time) en geeft een score (50 basispunten + tijd-bonus). Stuurt de uitslag én de liedjesgegevens (titel, artiest) in JSON terug naar de speler.
   10. voeg_liedje_toe.php: Beheerderspagina met een invulformulier (Artiest, Titel, Jaar, Thema) om de muziekdatabase via de browser handmatig uit te breiden. Voorkomt automatisch duplicaten.
   11. import_hitjam.php: Eenmalig importscript dat de database vult met de meegeleverde startlijst van 70 hits.

------------------------------
## 🏁 5. Live Spelverloop (Hoe de Battle werkt)

   1. Voorbereiding: De spelers openen speelveld.php op hun telefoon (wachtscherm). De spelleider opent leider_dashboard.php op een telefoon die verbonden is met de JBL-box.
   2. Ronde Start: De admin klikt op "Start Live Battle". De database kiest een willekeurig nummer. De admin komt op luister.php (de spelers zien nog niks, dus geen valsspeelgelegenheid).
   3. Muziek Start: De admin drukt op de grote roze Play-knop. De muziek begint te spelen. Op exact dezelfde milliseconde opent de database de quiz en verschijnen op de telefoons van álle spelers 4 jaarknoppen.
   4. De Battle: Spelers luisteren en drukken zo snel mogelijk. Hun telefoons sturen direct de klik naar de Pi.
   5. Live Feedback: De admin ziet op zijn scherm live de namen van de spelers verschijnen met hun gekozen jaartal. De spelers zien op hun eigen scherm direct of ze het goed/fout hadden én de complete trackinfo (Artiest/Titel/Jaar).
   6. Uitslag: De admin drukt op "Sluit ronde & Onthul" (stopt de muziek) en gaat terug naar het dashboard om de nieuwe ranglijst te bekijken en de volgende ronde te starten.

------------------------------
## 💡 Ideeën voor toekomstige AI-ontwikkeling (Volgende stappen)
Als je dit bestand later aan een AI geeft, kun je die AI vragen om:

* Een Thema-filter te bouwen op het leider_dashboard.php (bijv. "Speel deze ronde alleen met 'NL' of '80s'").
* Een Live chat of emoji-reactiesysteem toe te voegen zodat spelers elkaar kunnen plagen tijdens het wachten.
* Een Timer-balk (Visual Countdown) op het scherm van de speler te tekenen die aftelt van 10 naar 0 seconden.
* De app om te bouwen tot een volledige Progressive Web App (PWA) met een vernieuwde manifest.json zodat hij als een échte app op Android/iOS start.

Veel plezier met de verdere ontwikkeling van HitJam! Heb je voor nu nog vragen over deze structuur of wil je nog iets toevoegen?


