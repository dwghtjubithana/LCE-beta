<?php
/**
 * config.php
 * Database Credentials & PDO Instellingen voor SuriCore LCE.
 * * Beveiligingsadvies: Dit bestand bevat gevoelige informatie. 
 * Zorg dat de permissies op de server correct zijn ingesteld.
 */

$host = 'localhost';
$db   = 'dbmyu6uoo7735j';
$user = 'urdfdufwfbbwq';
$pass = 'K%`^2c1%Wkg2';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Gemini API key should be provided via environment to avoid hardcoding secrets.
$geminiApiKey = getenv('GEMINI_API_KEY') ?: '';

// De verbinding wordt in scan_submit.php opgeroepen via:
// $pdo = new PDO($dsn, $user, $pass, $options);
?>
