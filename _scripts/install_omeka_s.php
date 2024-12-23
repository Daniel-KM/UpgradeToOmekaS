<?php declare(strict_types=1);

/**
 * Installer facilement Omeka S
 *
 * Ce script a été réalisé pour l’Université des Antilles et l’Université de la Guyane.
 * @see https://manioc.org
 *
 * @copyright Daniel Berthereau, 2024
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 *
 * Portions of code come from Omeka.
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

/**
 * Adapted:
 * @see \Omeka\Stdlib\Cli
 * @see \Omeka\Stdlib\Environment
 * @see \EasyAdmin\Mvc\Controller\Plugin\Addons
 *
 * @see https://github.com/omeka/omeka-s/blob/develop/application/src/Stdlib/Cli.php
 * @see https://github.com/omeka/omeka-s/blob/develop/application/src/Stdlib/Environment.php
 * @see https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin/-/blob/master/src/Mvc/Controller/Plugin/Addons.php
 */
class Utils
{
    const OMEKA_VERSION = '4.1.1';
    const PHP_MINIMUM_VERSION = '7.4.0';
    const PHP_MINIMUM_VERSION_ID = 70400;
    const MYSQL_MINIMUM_VERSION = '5.7.9';
    const MARIADB_MINIMUM_VERSION = '10.2.6';
    const PHP_REQUIRED_EXTENSIONS = [
        'fileinfo',
        'json',
        'mbstring',
        'PDO',
        'pdo_mysql',
        'xml',
    ];

    const OMEKA_LOCALE = 'fr';
    const OMEKA_LOG_LEVEL = 'NOTICE';

    public function __invoke(): self
    {
        return $this;
    }

    public function log($message = null)
    {
        static $messages = [];
        if ($message !== null) {
            $messages[] = $message;
        }
        return $messages;
    }

    /**
     * Get a command path.
     *
     * Returns the path to the provided command or boolean false if the command
     * is not found.
     *
     * @param string $command
     * @return string|false
     */
    public function getCommandPath($command)
    {
        $command = sprintf('command -v %s', escapeshellarg($command));
        return $this->execute($command);
    }

    /**
     * Execute a command.
     *
     * Expects arguments to be properly escaped.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function execute($command)
    {
        if (function_exists('proc_open')) {
            return $this->procOpen($command);
        } elseif (function_exists('exec')) {
            return $this->exec($command);
        } else {
            return false;
        }
    }

    /**
     * Execute command using PHP's exec function.
     *
     * @link http://php.net/manual/en/function.exec.php
     * @param string $command
     * @return string|false
     */
    public function exec($command)
    {
        $output = null;
        $exitCode = null;
        exec($command, $output, $exitCode);
        if (0 !== $exitCode) {
            $this->log(sprintf('Command "%s" failed with status code %s.', $command, $exitCode));
            return false;
        }
        return implode(PHP_EOL, $output);
    }

    /**
     * Execute command using PHP's proc_open function.
     *
     * For servers that allow proc_open. Logs standard error.
     *
     * @link http://php.net/manual/en/function.proc-open.php
     * @param string $command
     * @return string|false
     */
    public function procOpen($command)
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w'], // STDERR
        ];

        $pipes = [];
        $proc = proc_open($command, $descriptorSpec, $pipes, getcwd());
        if (!is_resource($proc)) {
            return false;
        }

        // Set non-blocking mode on STDOUT and STDERR.
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // Poll STDOUT and STDIN in a loop, waiting for EOF. We do this to avoid
        // issues with stream_get_contents() where either stream could hang.
        $output = '';
        $errors = '';
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            // Sleep to avoid tight busy-looping on the streams
            usleep(25000);
            if (!feof($pipes[1])) {
                $output .= stream_get_contents($pipes[1]);
            }
            if (!feof($pipes[2])) {
                $errors .= stream_get_contents($pipes[2]);
            }
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($proc);
        if (0 !== $exitCode) {
            // Log standard error if any.
            if (strlen($errors)) {
                $this->log($errors);
            }
            $this->log(sprintf('Command "%s" failed with status code %s.', $command, $exitCode));
            return false;
        }

        return trim($output);
    }

    /**
     * Helper to download a file.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function downloadFile($source, $destination): bool
    {
        $handle = @fopen($source, 'rb');
        if (empty($handle)) {
            return false;
        }
        $result = (bool) file_put_contents($destination, $handle);
        @fclose($handle);
        return $result;
    }

    /**
     * Helper to unzip a file.
     *
     * @param string $source A local file.
     * @param string $destination A writeable dir.
     * @return bool
     */
    public function unzipFile($source, $destination): bool
    {
        // Unzip via php-zip.
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            $result = $zip->open($source);
            if ($result === true) {
                $result = $zip->extractTo($destination);
                $zip->close();
            } else {
                $zipErrors = [
                    ZipArchive::ER_EXISTS => 'File already exists',
                    ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                    ZipArchive::ER_INVAL => 'Invalid argument',
                    ZipArchive::ER_MEMORY => 'Malloc failure',
                    ZipArchive::ER_NOENT => 'No such file',
                    ZipArchive::ER_NOZIP => 'Not a zip archive',
                    ZipArchive::ER_OPEN => 'Can’t open file',
                    ZipArchive::ER_READ => 'Read error',
                    ZipArchive::ER_SEEK => 'Seek error',
                ];
                $this->log(sprintf('Erreur lors de la décompression : %s', $zipErrors[$result] ?? 'Other zip error'));
                $result = false;
            }
        }

        // Unzip via command line
        else {
            // Check if the zip command exists.
            try {
                $status = $output = $errors = null;
                $this->execute('unzip', $status, $output, $errors);
            } catch (Exception $e) {
                $status = 1;
            }
            // A return value of 0 indicates the convert binary is working correctly.
            $result = $status == 0;
            if ($result) {
                $command = 'unzip ' . escapeshellarg($source) . ' -d ' . escapeshellarg($destination);
                try {
                    $this->execute($command, $status, $output, $errors);
                } catch (Exception $e) {
                    $status = 1;
                }
                $result = $status == 0;
            }
        }

        return $result;
    }

    /**
     * Move all files and dirs from a directory to another one.
     */
    public function moveFilesFromDirToDir($source, $destination): bool
    {
        if ($source === $destination) {
            return true;
        }
        if (!file_exists($source) || !is_dir($source) || !is_readable($source) || !is_writeable($source)) {
            return false;
        }
        if (!file_exists($destination)) {
            mkdir($destination, 0775, true);
        }
        if (!file_exists($destination) || !is_dir($destination) || !is_writeable($destination)) {
            return false;
        }
        // Since rename() moves all the contents of a directory, only the root
        // files and dirs needs to be processed.
        $filesOrDirs = array_diff(scandir($source) ?: [], ['.', '..']);
        $result = true;
        foreach ($filesOrDirs as $fileOrDir) {
            $sourceFileOrDir = $source . DIRECTORY_SEPARATOR . $fileOrDir;
            $destinationFileOrDir = $destination . DIRECTORY_SEPARATOR . $fileOrDir;
            $result = rename($sourceFileOrDir, $destinationFileOrDir);
            if (!$result) {
                $this->log(sprintf('Impossible de déplacer %1$s', $fileOrDir));
                break;
            }
        }
        return $result;
    }

    /**
     * Remove all files and dirs of a dir from the filesystem.
     *
     * It does not remove the passed dir.
     *
     * @param string $dirpath Absolute path.
     * @param array $except Absolute paths. Only root paths can be skipped.
     */
    public function removeFilesAndDirsInDir(string $dirPath, array $except = []): bool
    {
        if (!file_exists($dirPath)) {
            return true;
        }
        if ($dirPath === '/'
            || strpos($dirPath, '/..') !== false
            || substr($dirPath, 0, 1) !== '/'
        ) {
            return false;
        }
        // Process the first level here and use rmDir for other ones.
        $filesOrDirs = array_diff(scandir($dirPath) ?: [], ['.', '..']);
        foreach ($filesOrDirs as $fileOrDir) {
            $path = $dirPath . '/' . $fileOrDir;
            if (in_array($path, $except)) {
                continue;
            }
            if (is_dir($path)) {
                $this->rmDir($path);
            } else {
                unlink($path);
            }
        }
        return true;
    }

    /**
     * Remove a dir from filesystem.
     *
     * @param string $dirpath Absolute path.
     */
    public function rmDir(string $dirPath): bool
    {
        if (!file_exists($dirPath)) {
            return true;
        }
        if ($dirPath === '/'
            || strpos($dirPath, '/..') !== false
            || substr($dirPath, 0, 1) !== '/'
        ) {
            return false;
        }
        $files = array_diff(scandir($dirPath) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dirPath . '/' . $file;
            if (is_dir($path)) {
                $this->rmDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dirPath);
    }
}

// La plupart des tests sont indépendants, sauf pour la base de données qui
// nécessite un test sur php.
// On peut donc afficher la plupart des problèmes en une seule fois.

$utils = new Utils();

$isValid = true;
$isSystemValid = true;
$isPhpValid = true;
$isDatabaseValid = true;
// $currentMod = null;
// $requireChmod = false;
$failed = false;

$isPost = filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST';
if ($isPost) {
    // TODO Mysqli permet aussi d’utiliser directement les paramètres définis par défaut, mais c’est rare, et de toute façon Omeka utilise pdo.
    $host = trim((string) filter_input(INPUT_POST, 'host'));
    $port = intval(trim((string) filter_input(INPUT_POST, 'port'))) ?: null;
    $socket = trim((string) filter_input(INPUT_POST, 'socket')) ?: null;
    $dbname = trim((string) filter_input(INPUT_POST, 'dbname'));
    $user = trim((string) filter_input(INPUT_POST, 'user'));
    $password = trim((string) filter_input(INPUT_POST, 'password'));
    $locale = trim((string) filter_input(INPUT_POST, 'locale'));
    $logLevel = filter_input(INPUT_POST, 'log_level');
} else {
    $host = 'localhost';
    $port = '';
    $socket = '';
    $dbname = '';
    $user = '';
    $password = '';
    // Omeka.
    $locale = $utils::OMEKA_LOCALE;
    $logLevel = $utils::OMEKA_LOG_LEVEL;
}

// Test du système de fichiers.

// Vérification des droits d'écriture par le serveur web.
$isReadableAndWriteable = is_readable(__DIR__) && is_writeable(__DIR__);
if (!$isReadableAndWriteable) {
    // $currentMod = substr(sprintf('%o', fileperms(__DIR__)), -4);
    try {
        $result = chmod(__DIR__, 0775);
        if (!$result || !is_readable(__DIR__) || !is_writeable(__DIR__)) {
            throw new Exception();
        }
        // $requireChmod = true;
        $isReadableAndWriteable = true;
    } catch (Exception $e) {
        $isReadableAndWriteable = false;
        $isSystemValid = false;
        $utils->log('Le serveur web n’a pas les droits d’écriture dans le dossier en cours.');
    }
}

// Test de l’écriture réelle du fichier, normalement inutile.
if ($isReadableAndWriteable) {
    $randomFile = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(48))), 0, 8);
    $randomPath = __DIR__ . '/' . $randomFile;
    try {
        $result = file_put_contents($randomPath, 'test');
        if (!$result) {
            throw new Exception();
        }
        unlink($randomPath);
    } catch (Exception $e) {
        $isSystemValid = false;
        $utils->log('Le serveur web ne peut pas écrire de fichier dans le dossier en cours.');
    }
}

// Test si le dossier est vide (sauf le présent fichier).
// TODO Le dossier pourrait ne pas être vide et convenir : test si le dossier ne contient pas les fichiers et dossiers Omeka.
$result = scandir(__DIR__);
if ($result === false) {
    $isSystemValid = false;
    $utils->log('Le serveur web ne peut pas compter les fichiers existants dans le dossier en cours.');
} elseif (count($result) > 3) {
    // Scandir compte « . », « .. » et le présent fichier.
    $isSystemValid = false;
    $utils->log('Le dossier en cours n’est pas vide.');
}

// Vérification complémentaire : unzip ou l’extension zip doivent être
// installés pour décompresser le fichier zip.
if (!extension_loaded('zip')) {
    try {
        $command = $utils->getCommandPath('unzip');
        if (!$command) {
            throw new Exception;
        }
    } catch (Exception $e) {
        $isSystemValid = false;
        $utils->log('L’extension php « zip » ou la commande « unzip » doivent être disponibles pour décompresser Omeka.');
    }
}

// Test si le serveur a accès à internet.
$fileToDownload = 'https://raw.githubusercontent.com/omeka/omeka-s/refs/heads/develop/README.md';
try {
    $result = $utils->downloadFile($fileToDownload, __DIR__ . '/README.md');
    if (!$result) {
        throw new Exception;
    }
    unlink(__DIR__ . '/README.md');
} catch (Exception $e) {
    $isSystemValid = false;
    $utils->log('Impossible de télécharger un fichier depuis le site github.com.');
}

// Test de la version php.
if (PHP_VERSION_ID < Utils::PHP_MINIMUM_VERSION_ID) {
    $isPhpValid = false;
    $utils->log(sprintf(
        'La version de PHP %1$s ne permet pas d’installer la dernière version d’Omeka S, qui requiert %2$s. ',
        PHP_VERSION, Utils::PHP_MINIMUM_VERSION
    ));
}

// Vérifcation des extensions php.
$result = [];
foreach (Utils::PHP_REQUIRED_EXTENSIONS as $extension) {
    if (!extension_loaded($extension)) {
        $result[] = $extension;
    }
}
if (count($result)) {
    $isPhpValid = false;
    $utils->log(sprintf(
        'La version de PHP %1$s permet d’installer la dernière version d’Omeka S, mais il manque %2$s sur le serveur : %3$s.',
        PHP_VERSION,
        count($result) === 1 ? 'l’extension php suivante' : 'les extensions php suivantes',
        implode(', ', $result)
    ));
    if (extension_loaded('intl')) {
        $utils->log('L’extension php « intl » est également recommandée pour une meilleure prise en charge des langues.');
    }
}

$isValid = $isValid && $isSystemValid && $isPhpValid;

// Vérification de la base de données (requiert php et les informations sur la base).

// On peut tester la base même s'il y a des problèmes dans le système de fichiers.
if ($isPhpValid && $isPost) {
    if (!preg_match('/^[\w.-]*$/', $host)) {
        $isDatabaseValid = false;
        $utils->log('Le nom d’hôte n’est pas conforme.');
    }
    if ($socket && !preg_match('~^[\w./-]*$~', $socket)) {
        $isDatabaseValid = false;
        $utils->log('Le socket n’est pas conforme.');
    }
    if ($socket && $host) {
        $isDatabaseValid = false;
        $utils->log('Il n’est pas possible de spécifier à la fois l’hôte et le socket.');
    }
    if (!preg_match('/^[\w.-]*$/', $dbname)) {
        $isDatabaseValid = false;
        $utils->log('Le nom de la base n’est pas conforme.');
    }
    if (!preg_match('/^[\w.-]*$/', $user)) {
        $isDatabaseValid = false;
        $utils->log('Le nom de l’utilisateur n’est pas conforme.');
    }
    if (!$password) {
        $isDatabaseValid = false;
        $utils->log('Le mot de passe est vide.');
    }

    // Vérifier complémentaire si l’utilisateur existe (via mysqli si disponible).
    if ($isDatabaseValid) {
        $dsn = $socket
            ? "mysql:unix_socket=$socket;charset=utf8mb4"
            : "mysql:host=$host" . ($port ? ";port=$port" : '') . ';charset=utf8mb4';
        $dbOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        try {
            $pdo = new PDO($dsn, $user, $password, $dbOptions);
            if (!$pdo) {
                $isDatabaseValid = false;
                $utils->log('La configuration du serveur ou de l’utilisateur (nom/mot de passe) n’est pas correcte.');
            } else {
                $pdo = null;
            }
        } catch (Exception $e) {
            $isDatabaseValid = false;
            $utils->log('La configuration du serveur ou de l’utilisateur (nom/mot de passe) n’est pas correcte.');
        }
    }

    // Vérifier la version de mysql/mariadb.
    if ($isDatabaseValid) {
        $dsn = $socket
            ? "mysql:unix_socket=$socket;charset=utf8mb4"
            : "mysql:host=$host" . ($port ? ";port=$port" : '') . ';charset=utf8mb4';
        $dbOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        try {
            $pdo = new PDO($dsn, $user, $password, $dbOptions);
            $sql = 'SELECT VERSION();';
            $stmt = $pdo->query($sql);
            $dbVersion = $stmt->fetchColumn();
            if (strpos($dbVersion, 'MariaDB') === false) {
                if (!version_compare($dbVersion, Utils::MYSQL_MINIMUM_VERSION, '>=')) {
                    $isDatabaseValid = false;
                    $utils->log(sprintf(
                        'La version de MySQL (%1$s) est inférieure à la version nécessaire pour Omeka (%2$s).',
                        $dbVersion, Utils::MYSQL_MINIMUM_VERSION
                    ));
                }
            } else {
                if (!version_compare($dbVersion, Utils::MARIADB_MINIMUM_VERSION, '>=')) {
                    $isDatabaseValid = false;
                    $utils->log(sprintf(
                        'La version de MariaDB (%1$s) est inférieure à la version nécessaire pour Omeka (%2$s).',
                        $dbVersion, Utils::MARIADB_MINIMUM_VERSION
                    ));
                }
            }
        } catch (Exception $e) {
            // La base de données n’est pas disponible pour l’utilisateur ou n’existe pas.
            $isDatabaseValid = false;
            $utils->log('La base de données est incorrecte.');
        }
    }

    // Vérifier si la base est vide ou créer une base vide.
    if ($isDatabaseValid) {
        // Vérifie si la base existe.
        $dsn = $socket
            ? "mysql:unix_socket=$socket;charset=utf8mb4"
            : "mysql:host=$host" . ($port ? ";port=$port" : '') . ';charset=utf8mb4';
        $dbOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        try {
            $pdo = new PDO($dsn, $user, $password, $dbOptions);
            $sql = sprintf(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = %s;',
                $pdo->quote($dbname)
            );
            $stmt = $pdo->query($sql);
            $hasDatabase = (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            // La base de données n’est pas disponible pour l’utilisateur ou n’existe pas.
            $hasDatabase = false;
        }
        if ($hasDatabase) {
            // Vérifier si la base est bien vide.
            $dsnb = $socket
                ? "mysql:unix_socket=$socket;dbname=$dbname;charset=utf8mb4"
                : "mysql:host=$host" . ($port ? ";port=$port" : '') . ";dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsnb, $user, $password, $dbOptions);
            $stmt = $pdo->query('SHOW TABLES;');
            $hasTables = (bool) $stmt->fetchColumn();
            if ($hasTables) {
                $isDatabaseValid = false;
                $utils->log('La base de données doit être vide.');
            } else {
                $isDatabaseValid = true;
            }
        } else {
            // Sinon créer la base.
            try {
                $pdo = new PDO($dsn, $user, $password, $dbOptions);
                // Avec "create database", le nom de la base ne doit pas être quote(), mais « ` » si besoin.
                $sql = sprintf('CREATE DATABASE `%s`;', $dbname);
                $result = $pdo->exec($sql);
                if ($result === false) {
                    $isDatabaseValid = false;
                    $utils->log('Impossible de créer la base de données.');
                } else {
                    $isDatabaseValid = true;
                }
            } catch (Exception $e) {
                $isDatabaseValid = false;
            }
        }
    }
}

$isValid = $isValid && $isSystemValid && $isPhpValid && $isDatabaseValid;

// Preparation de l’installation.
if ($isValid && $isPost) {
    // Télécharger le zip.
    $fileToDownload = sprintf('https://github.com/omeka/omeka-s/releases/download/v%1$s/omeka-s-%1$s.zip', Utils::OMEKA_VERSION);
    $zipFile = __DIR__ . '/omeka-s.zip';
    $result = $utils->downloadFile($fileToDownload, $zipFile);
    if (!$result) {
        $failed = true;
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
        $utils->log('Impossible de télécharger le fichier.');
    }

    // Décompresser le zip dans le dossier en cours.
    if (!$failed) {
        $result = $utils->unzipFile($zipFile, __DIR__);
        if (!$result) {
            $failed = true;
            $utils->log('Impossible de décompresser le fichier.');
            // On supprime tous les fichiers créés, notamment .htaccess.
            $utils->removeFilesAndDirsInDir(__DIR__, [__FILE__]);
        }
        // Dans tous les cas, on supprime le fichier téléchargé.
        unlink($zipFile);
    }

    // Déplacer les fichiers depuis le dossier vers la racine du dossier en cours.
    if (!$failed) {
        // Le sous-dossier dans le zip est toujours « omeka-s ».
        $sourceDir = __DIR__ . '/omeka-s';
        $result = $utils->moveFilesFromDirToDir($sourceDir, __DIR__);
        if (!$result) {
            $failed = true;
            $utils->log('Impossible de déplacer les fichiers.');
            $utils->removeFilesAndDirsInDir(__DIR__, [__FILE__]);
        }
        $utils->rmDir(__DIR__ . '/omeka-s');
    }

    // Copier la configuration de la base de données dans database.ini.
    if (!$failed) {
        $dbIni = <<<INI
            user     = "$user"
            password = "$password"
            dbname   = "$dbname"
            host     = "$host"
            ;port     = $port
            ;unix_socket = "$socket"
            ;log_path = ""
            
            INI;
        if ($port) {
            $dbIni = str_replace(';port', 'port', $dbIni);
        }
        if ($socket) {
            $dbIni = str_replace(';unix_socket', 'unix_socket', $dbIni);
        }
        $result = file_put_contents(__DIR__ . '/config/database.ini', $dbIni);
        if (!$result) {
            $failed = true;
            $utils->log('Impossible de créer le fichier config/database.ini.');
            $utils->removeFilesAndDirsInDir(__DIR__, [__FILE__]);
        }
    }

    // Modifier les droits des dossiers et fichiers.
    // Normalement inutile.
    if (!$failed) {
        chmod(__DIR__ . '/files', 0775);
        chmod(__DIR__ . '/logs/application.log', 0775);
        chmod(__DIR__ . '/logs/sql.log', 0775);
    }

    // Modifier config.
    if (!$failed) {
        // var_export() ne peut pas être utilisé car il y a des constantes de
        // classes non disponibles et qu'en tout état de cause il est préférable
        // de conserver telles quelles.
        $configPath = __DIR__ . '/config/local.config.php';
        $config = file_get_contents($configPath);
        if ($locale) {
            $config = str_replace("'locale' => 'en_US',", sprintf("'locale' => '%s',", $locale), $config);
        }
        if ($logLevel !== 'none') {
            $config = str_replace("'log' => false,", "'log' => true,", $config);
            $config = str_replace(
                "'priority' => \Laminas\Log\Logger::NOTICE,",
                sprintf("'priority' => \Laminas\Log\Logger::%s,", $logLevel),
                $config
            );
        }
        file_put_contents($configPath, $config);
    }
}

$isFinalized = $isValid && $isPost && !$failed;

if ($isFinalized) {
    // Supprimer le présent fichier.
    unlink(__FILE__);

    // Préparer la redirection.
    $urlOmeka = filter_input(INPUT_SERVER, 'REQUEST_SCHEME')
        . '://'
        . filter_input(INPUT_SERVER, 'SERVER_NAME')
        . (in_array(filter_input(INPUT_SERVER, 'SERVER_PORT'), ['80', '443']) ? '' : ':' . filter_input(INPUT_SERVER, 'SERVER_PORT'))
        // Ne pas ajouter « index.php ».
        . dirname(filter_input(INPUT_SERVER, 'REQUEST_URI'));
}

$meta = [
    'title' => 'Installer Omeka S facilement',
    'author' => 'Daniel Berthereau',
    'description' => 'Installer Omeka S simplement avec un fichier unique à déposer sur le serveur.',
];

$locales = [
    '' => 'Défaut',
    'ca_ES' => 'Català (Espanya) [ca_ES]',
    'cs' => 'Čeština [cs]',
    'de_DE' => 'Deutsch (Deutschland) [de_DE]',
    'et' => 'Eesti [et]',
    'en_US' => 'English (United States) [en_US]',
    'es_419' => 'Español (Latinoamérica) [es_419]',
    'es' => 'Español [es]',
    'eu' => 'Euskara [eu]',
    'fr' => 'Français [fr]',
    'hr' => 'Hrvatski [hr]',
    'it' => 'Italiano [it]',
    'lt' => 'Lietuvių [lt]',
    'hu_HU' => 'Magyar (Magyarország) [hu_HU]',
    'nl_NL' => 'Nederlands (Nederland) [nl_NL]',
    'pl' => 'Polski [pl]',
    'pt_BR' => 'Português (Brasil) [pt_BR]',
    'pt_PT' => 'Português (Portugal) [pt_PT]',
    'ro' => 'Română [ro]',
    'fi_FI' => 'Suomi (Suomi) [fi_FI]',
    'sv_SE' => 'Svenska (Sverige) [sv_SE]',
    'tr_TR' => 'Türkçe (Türkiye) [tr_TR]',
    'el_GR' => 'Ελληνικά (Ελλάδα) [el_GR]',
    'bg_BG' => 'Български (България) [bg_BG]',
    'mn' => 'Монгол [mn]',
    'ru' => 'Русский [ru]',
    'sr_RS' => 'Српски (Србија) [sr_RS]',
    'uk' => 'Українська [uk]',
    'ar' => 'العربية [ar]',
    'ko_KR' => '한국어(대한민국) [ko_KR]',
    'zh_CN' => '中文（中国） [zh_CN]',
    'zh_TW' => '中文（台灣） [zh_TW]',
    'ja' => '日本語 [ja]',
];

?>
<!DOCTYPE html>
<html lang="fr" prefix="og: https://ogp.me/ns#">
    <head>
        <meta charSet="utf-8"/>
        <meta name="viewport" content="width=device-width,initial-scale=1"/>
        <meta name="author" content="<?= htmlspecialchars($meta['author']) ?>"/>
        <meta name="description" content="<?= htmlspecialchars($meta['description']) ?>"/>
        <meta property="og:title" content="<?= htmlspecialchars($meta['title']) ?>"/>
        <meta property="og:description" content="<?= htmlspecialchars($meta['description']) ?>"/>
        <?php if ($isFinalized): ?>
        <meta http-equiv="refresh" content="10;url=<?= htmlspecialchars($urlOmeka) ?>"/>
        <?php endif; ?>
        <title><?= htmlspecialchars($meta['title']) ?></title>
        <style>
            header {
                padding: 5% 10% 0;
            }
            main {
                padding: 0 10%;
            }

            label {
                display: inline-block;
                width: 33%;
                text-align: right;
                margin-bottom: 1em;
                margin-right: 0.5em;
            }
            input {
                display: inline-block;
                width: 33%;
            }

            .radios > label {
                vertical-align: top;
            }
            .radio-group {
                display: inline-block;
                width: initial;
                width: 50%;
            }
            .radio-group label {
                display: inline;
                width: initial;
                text-align: initial;
            }
            .radio-group input {
                display: inline;
                width: initial;
            }

            details {
                display: block;
                margin-bottom: 1em;
            }
            summary {
                width: 33%;
                text-align: right;
                margin-bottom: 1em;
                font-style: italic;
            }
            summary:hover {
                cursor: pointer;
            }

            button {
                width: 50%;
                margin-left: 25%;
                margin-top: 4em;
            }
            button:hover {
                cursor: pointer;
            }
        </style>
    </head>
    <body>

        <header>
            <h1><?= htmlspecialchars($meta['title']) ?></h1>
        </header>

        <main>

            <?php if ($utils->log()): ?>
            <p>Omeka ne peut pas être installé en raison des erreurs suivantes :</p>
            <ul>
                <?php foreach ($utils->log() as $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                Vérifiez la configuration chez votre hébergeur.
            </p>
            <?php else: ?>
            <p>
                Aucun problème n’a été détecté sur le serveur.
            </p>
            <?php endif; ?>

            <?php if ($isSystemValid && $isPhpValid && !$isFinalized): ?>

            <form method="post">

                <h2>Base de données</h2>

                <p>
                    Omeka a besoin d’un accès à une base de données pour fonctionner.
                    Merci d’en indiquer les paramètres ci-dessous.
                    Si la base n’existe pas, l’utilisateur doit avoir les droits de création.
                </p>

                <label for="host">Serveur</label>
                <input type="text" id="host" name="host" value="<?= htmlspecialchars($host) ?>"/><br/>

                <label for="dbname">Nom de la base</label>
                <input type="text" id="dbname" name="dbname" required="required" value="<?= htmlspecialchars($dbname) ?>"/><br/>

                <label for="user">Utilisateur de la base</label>
                <input type="text" id="user" name="user" required="required" value="<?= htmlspecialchars($user) ?>"/><br/>

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required="required" value="<?= htmlspecialchars($password) ?>"/><br/>

                <details>
                    <summary>Configuration spécifique</summary>
                    <label for="port">Port</label>
                    <input type="number" id="port" name="port" min="0" max="65535" value="<?= htmlspecialchars((string) $port) ?>"/><br/>

                    <label for="socket">Socket</label>
                    <input type="text" id="socket" name="socket" value="<?= htmlspecialchars((string) $socket) ?>"/><br/>
                </details>

                <h2>Options principales du fichier de configuration</h2>

                <label for="locale">Langue par défaut</label>
                <select id="locale" name="locale">
                    <?php foreach ($locales as $code => $label): ?>
                    <option value="<?= $code ?>"<?= $code === $locale ? ' selected="selected"' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <details>
                    <summary>Configuration spécifique</summary>
                    <div class="radios">
                        <label for="logLevel">Gravité minimale des journaux</label>
                        <div id="logLevel" class="radio-group">
                            <input type="radio" id="log_none" name="log_level" value="none"<?= $logLevel === 'none' ? ' checked="checked"' : '' ?>/>
                            <label for="log_none">Aucun</label>
                            <input type="radio" id="log_err" name="log_level" value="ERR"<?= $logLevel === 'ERR' ? ' checked="checked"' : '' ?>/>
                            <label for="log_err">Erreur</label>
                            <input type="radio" id="log_warn" name="log_level" value="WARN"<?= $logLevel === 'WARN' ? ' checked="checked"' : '' ?>/>
                            <label for="log_warn">Avertissement</label>
                            <br/>
                            <input type="radio" id="log_notice" name="log_level" value="NOTICE"<?= $logLevel === 'NOTICE' ? ' checked="checked"' : '' ?>/>
                            <label for="log_notice">Note</label>
                            <input type="radio" id="log_info" name="log_level" value="INFO"<?= $logLevel === 'INFO' ? ' checked="checked"' : '' ?>/>
                            <label for="log_info">Info</label>
                            <input type="radio" id="log_debug" name="log_level" value="DEBUG"<?= $logLevel === 'DEBUG' ? ' checked="checked"' : '' ?>/>
                            <label for="log_debug">Débogage</label>
                        </div>
                    </div>
                </details>

                <button type="submit">
                    <h2>Installer Omeka S</h2>
                </button>

            </form>

        <?php elseif ($isFinalized): ?>

        <p>
            Bravo, Omeka est préinstallé ! Vous pouvez désormais <a href="<?= htmlspecialchars($urlOmeka) ?>">finaliser l’installation</a> ou patientez dix secondes pour y aller automatiquement.
        </p>

        <?php endif; ?>

        </main>

    </body>
</html>
