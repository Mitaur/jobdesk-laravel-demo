<?php

/**
 * JobDesk – writes the production settings into .env on the server.
 *
 * Reads its input from environment variables (never from the command line):
 *   JD_APP_URL, JD_DB_NAME, JD_DB_USER, JD_DB_TEMP_PASSWORD
 *
 * The temporary DB password (set in the hosting panel) is replaced by a new random
 * password generated here. The new password is written only to .env and is never
 * printed. If the database refuses the change, the temporary password is kept and
 * a warning tells you to change it in the hosting panel.
 *
 * Usage: php deploy/hostinger/configure-env.php /path/to/.env
 */
$envFile = $argv[1] ?? '.env';
$need = ['JD_APP_URL', 'JD_DB_NAME', 'JD_DB_USER', 'JD_DB_TEMP_PASSWORD'];
foreach ($need as $key) {
    if (getenv($key) === false || getenv($key) === '') {
        fwrite(STDERR, "missing environment variable $key\n");
        exit(2);
    }
}

$host = '127.0.0.1';
$db = getenv('JD_DB_NAME');
$user = getenv('JD_DB_USER');
$password = getenv('JD_DB_TEMP_PASSWORD');
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10];
$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4";

// 1) connect with the temporary password and rotate it
try {
    $pdo = new PDO($dsn, $user, $password, $options);
    $server = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    echo 'database server: '.(stripos($server, 'mariadb') !== false ? 'MariaDB ' : 'MySQL ').strtok($server, '-')."\n";

    $new = rtrim(strtr(base64_encode(random_bytes(30)), '+/', 'Kq'), '=').'a7Z';
    try {
        $pdo->exec('ALTER USER CURRENT_USER() IDENTIFIED BY '.$pdo->quote($new));
    } catch (Throwable) {
        $pdo->exec('SET PASSWORD = PASSWORD('.$pdo->quote($new).')');
    }
    $pdo = null;

    new PDO($dsn, $user, $new, $options); // must work with the new password
    $temp = $password;
    $password = $new;
    echo "database password: replaced by a new random one (stored only in .env)\n";

    // The old password must be dead everywhere. Some hosts keep a second account entry
    // (e.g. user@localhost for socket connections) – check TCP and socket separately.
    $routes = ['TCP 127.0.0.1' => $dsn, 'local socket' => "mysql:host=localhost;dbname=$db"];
    foreach ($routes as $label => $route) {
        try {
            new PDO($route, $user, $temp, $options);
            echo "WARNING: the temporary password still works over $label – change it in the hosting panel.\n";
        } catch (PDOException $e) {
            $denied = ((int) ($e->errorInfo[1] ?? 0) === 1045) || str_contains($e->getMessage(), '1045');
            echo $denied
                ? "temporary password over $label: refused (good)\n"
                : "temporary password over $label: could not be checked (not reachable)\n";
        }
    }
} catch (Throwable $e) {
    echo 'WARNING: could not rotate the database password ('.get_class($e).'). '
        ."The temporary password is kept – change it in the hosting panel and update .env.\n";
    try {
        new PDO($dsn, $user, $password, $options);
    } catch (Throwable) {
        fwrite(STDERR, "cannot connect to the database with the given credentials\n");
        exit(1);
    }
}

// 2) write the production settings
$isMaria = isset($server) && stripos($server, 'mariadb') !== false;
$settings = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => getenv('JD_APP_URL'),
    'LOG_CHANNEL' => 'daily',
    'LOG_LEVEL' => 'info',
    'LOG_DAILY_DAYS' => '7',
    'DB_CONNECTION' => $isMaria ? 'mariadb' : 'mysql',
    'DB_HOST' => $host,
    'DB_PORT' => '3306',
    'DB_DATABASE' => $db,
    'DB_USERNAME' => $user,
    'DB_PASSWORD' => $password,
    'SESSION_SECURE_COOKIE' => 'true',
    'MAIL_MAILER' => 'log',
    'QUEUE_CONNECTION' => 'sync',
    'DEMO_MODE' => 'true',
];

$lines = file($envFile, FILE_IGNORE_NEW_LINES);
foreach ($settings as $key => $value) {
    $line = $key.'='.(preg_match('/[\s#"\'\\\\$]/', $value) ? '"'.addcslashes($value, '"\\$').'"' : $value);
    $found = false;
    foreach ($lines as $i => $existing) {
        if (preg_match('/^#?\s*'.preg_quote($key, '/').'=/', $existing)) {
            $lines[$i] = $line;
            $found = true;
            break;
        }
    }
    if (! $found) {
        $lines[] = $line;
    }
}
file_put_contents($envFile, implode("\n", $lines)."\n");
chmod($envFile, 0600);

echo '.env written: '.implode(', ', array_keys($settings))." (values not shown)\n";
