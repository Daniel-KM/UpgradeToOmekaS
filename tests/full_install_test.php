#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Test end-to-end de la procédure complète d’install_omeka_s.php.
 *
 * Le script d’installation est servi tel quel par « php -S » puis appelé en
 * POST avec les paramètres d’une base et d’une sélection, exactement comme
 * depuis le navigateur. On vérifie ensuite le déploiement complet : cœur Omeka
 * téléchargé et décompressé, config/database.ini, local.config.php, et modules
 * de la sélection installés.
 *
 * Codes de sortie : 0 = succès ou ignoré (env/réseau absent), 1 = échec.
 *
 * Usage :
 *   php tests/full_install_test.php ["Nom de la sélection"]
 *
 * Configuration via variables d’environnement : DB_HOST, DB_PORT, DB_USER,
 * DB_PASSWORD (valeurs par défaut adaptées à la stack Docker locale).
 */

$root = dirname(__DIR__);
$scriptPath = $root . '/_scripts/install_omeka_s.php';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = (string) getenv('DB_PASSWORD');
$dbPort = (int) (getenv('DB_PORT') ?: 3306);

$selection = $argv[1] ?? 'Database for researcher';
$locale = 'fr';

function out(string $s): void
{
    fwrite(STDOUT, $s . PHP_EOL);
}

function skip(string $s): void
{
    out('SKIP: ' . $s);
    exit(0);
}

function fail(string $s): void
{
    out('FAIL: ' . $s);
    exit(1);
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
        $path = $dir . '/' . $f;
        is_dir($path) ? rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}

// --- Préconditions -----------------------------------------------------------

if (!is_file($scriptPath)) {
    fail('Script introuvable : ' . $scriptPath);
}
if (!extension_loaded('pdo_mysql')) {
    skip('Extension pdo_mysql absente.');
}

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    skip('Base de données indisponible (' . $e->getMessage() . ').');
}

$ping = @file_get_contents(
    'https://raw.githubusercontent.com/omeka/omeka-s/develop/README.md',
    false,
    stream_context_create(['http' => ['timeout' => 8], 'https' => ['timeout' => 8]])
);
if ($ping === false) {
    skip('Accès réseau à github.com indisponible.');
}

// --- Préparation -------------------------------------------------------------

$work = sys_get_temp_dir() . '/omeka_full_install_' . bin2hex(random_bytes(4));
$dbName = 'omeka_e2e_' . bin2hex(random_bytes(4));
$logFile = sys_get_temp_dir() . '/omeka_e2e_server_' . bin2hex(random_bytes(4)) . '.log';

mkdir($work, 0775, true);
if (!copy($scriptPath, $work . '/install_omeka_s.php')) {
    fail('Copie du script impossible dans ' . $work);
}

$server = null;

register_shutdown_function(function () use (&$server, $pdo, $dbName, $work, $logFile) {
    if (is_resource($server)) {
        proc_terminate($server);
    }
    try {
        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    } catch (Throwable $e) {
    }
    rrmdir($work);
    @unlink($logFile);
});

// --- Démarrage du serveur php -S ---------------------------------------------

$port = 0;
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $logFile, 'w'],
    2 => ['file', $logFile, 'a'],
];
for ($try = 0; $try < 10; $try++) {
    $candidate = random_int(8100, 8999);
    $proc = proc_open(
        sprintf('exec php -d display_errors=0 -S 127.0.0.1:%d -t %s', $candidate, escapeshellarg($work)),
        $descriptors,
        $pipes
    );
    if (!is_resource($proc)) {
        continue;
    }
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $fp = @fsockopen('127.0.0.1', $candidate, $errno, $errstr, 0.2);
        if ($fp) {
            fclose($fp);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if ($ready) {
        $server = $proc;
        $port = $candidate;
        break;
    }
    proc_terminate($proc);
}
if (!$port) {
    fail('Impossible de démarrer le serveur php -S.');
}
out(sprintf('Serveur php -S sur 127.0.0.1:%d (docroot %s).', $port, $work));

// --- Requête POST d’installation ---------------------------------------------

$fields = http_build_query([
    'host' => $dbHost,
    'port' => $dbPort,
    'socket' => '',
    'dbname' => $dbName,
    'user' => $dbUser,
    'password' => $dbPass,
    'locale' => $locale,
    'log_level' => 'ERR',
    'selection' => $selection,
]);
$context = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
        . 'Content-Length: ' . strlen($fields) . "\r\n",
    'content' => $fields,
    'timeout' => 600,
    'ignore_errors' => true,
]]);

out(sprintf('POST installation (base « %s », sélection « %s »)…', $dbName, $selection));
$response = @file_get_contents("http://127.0.0.1:$port/install_omeka_s.php", false, $context);
if ($response === false) {
    fail('Requête POST échouée (délai dépassé ou serveur arrêté). Journal : ' . @file_get_contents($logFile));
}

// --- Vérifications
// ------------------------------------------------------------

$errors = [];
$has = fn (string $p): bool => file_exists($work . '/' . $p);

if (!$has('index.php')) {
    $errors[] = 'index.php manquant (cœur Omeka non déployé).';
}
if (!$has('application')) {
    $errors[] = 'dossier application/ manquant.';
}

if (!$has('config/database.ini')) {
    $errors[] = 'config/database.ini manquant.';
} else {
    $ini = parse_ini_file($work . '/config/database.ini') ?: [];
    if (($ini['dbname'] ?? '') !== $dbName) {
        $errors[] = 'database.ini : dbname incorrect.';
    }
    if (($ini['user'] ?? '') !== $dbUser) {
        $errors[] = 'database.ini : user incorrect.';
    }
}

if ($has('config/local.config.php')) {
    $cfg = (string) file_get_contents($work . '/config/local.config.php');
    if (strpos($cfg, "'locale' => 'fr'") === false) {
        $errors[] = 'local.config.php : locale non appliquée.';
    }
}

$modulesDir = $work . '/modules';
$moduleDirs = is_dir($modulesDir)
    ? array_values(array_filter(
        array_diff(scandir($modulesDir) ?: [], ['.', '..']),
        fn ($f) => is_dir($modulesDir . '/' . $f)
    ))
    : [];
if (!in_array('Common', $moduleDirs, true)) {
    $errors[] = 'module Common (forcé) non installé.';
}
if (count($moduleDirs) < 3) {
    $errors[] = 'trop peu de modules installés (' . count($moduleDirs) . ').';
}

// L’installeur s’auto-supprime uniquement quand toute la procédure réussit.
if ($has('install_omeka_s.php')) {
    $errors[] = 'le script ne s’est pas auto-supprimé (finalisation en échec).';
}

try {
    $exists = (bool) $pdo
        ->query('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ' . $pdo->quote($dbName))
        ->fetchColumn();
    if (!$exists) {
        $errors[] = 'la base de données n’a pas été créée.';
    }
} catch (Throwable $e) {
    $errors[] = 'vérification de la base impossible : ' . $e->getMessage();
}

// --- Rapport
// ------------------------------------------------------------------

if ($moduleDirs) {
    out(sprintf('Modules installés (%d) : %s', count($moduleDirs), implode(', ', $moduleDirs)));
}

if ($errors) {
    // Extrait des messages d’erreur affichés par l’installeur, le cas échéant.
    if (preg_match_all('~<li>(.+?)</li>~s', $response, $m)) {
        out('Messages de l’installeur : ' . implode(' | ', array_map('strip_tags', $m[1])));
    }
    fail(implode(' | ', $errors));
}

out(sprintf('PASS: procédure complète réussie (cœur Omeka %s + config + sélection « %s »).', '4.2.1', $selection));
exit(0);
