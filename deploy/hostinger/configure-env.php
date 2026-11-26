<?php

/**
 * JobDesk – writes the production settings into .env on the server.
 *
 * Reads its input from environment variables (never from the command line):
 *   JD_APP_URL, JD_DB_NAME, JD_DB_USER, JD_DB_TEMP_PASSWORD
 *
 * The temporary DB password (set in the hosting panel) is replaced by a new random
 * password generated here. The new password is written only to .env and is never
 * printed. Some hosts keep two account entries for one database user (TCP and the
 * local socket); both are switched to the new password. If the database refuses the
 * change, the temporary password is kept and a warning tells you to change it in
 * the hosting panel.
 *
 * Usage:
 *   php deploy/hostinger/configure-env.php /path/to/.env
 *   php deploy/hostinger/configure-env.php --retire-temp /path/to/.env
 *       (existing deploy: switches every account entry that still accepts
 *        JD_DB_TEMP_PASSWORD to the password already in .env; .env is not changed)
 */
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10];

function denied(PDOException $e): bool
{
    return ((int) ($e->errorInfo[1] ?? 0) === 1045) || str_contains($e->getMessage(), '1045');
}

function set_password(PDO $pdo, string $new): void
{
    try {
        $pdo->exec('ALTER USER CURRENT_USER() IDENTIFIED BY '.$pdo->quote($new));
    } catch (Throwable) {
        $pdo->exec('SET PASSWORD = PASSWORD('.$pdo->quote($new).')');
    }
}

/**
 * The temporary password must stop working everywhere. Checks TCP and the local
 * socket separately; an entry that still accepts it gets the new password too.
 * Prints only the result per route, never a password.
 */
function retire_temp_password(string $db, string $user, string $temp, string $new, array $options): bool
{
    $routes = [
        'TCP 127.0.0.1' => "mysql:host=127.0.0.1;port=3306;dbname=$db;charset=utf8mb4",
        'local socket' => "mysql:host=localhost;dbname=$db;charset=utf8mb4",
    ];
    $allGood = true;
    foreach ($routes as $label => $dsn) {
        try {
            $pdo = new PDO($dsn, $user, $temp, $options);
        } catch (PDOException $e) {
            echo denied($e)
                ? "temporary password over $label: refused (good)\n"
                : "temporary password over $label: could not be checked (not reachable)\n";

            continue;
        }
        try {
            set_password($pdo, $new);
            $pdo = null;
            new PDO($dsn, $user, $new, $options);
            try {
                new PDO($dsn, $user, $temp, $options);
                throw new RuntimeException('still accepted');
            } catch (PDOException $e) {
                if (! denied($e)) {
                    throw $e;
                }
            }
            echo "temporary password over $label: replaced by the .env password (good)\n";
        } catch (Throwable $e) {
            $allGood = false;
            echo "WARNING: the temporary password still works over $label (".get_class($e).') '
                ."– change the password in the hosting panel and update .env.\n";
        }
    }

    return $allGood;
}

// --retire-temp: fix an existing deploy, do not touch .env
if (($argv[1] ?? '') === '--retire-temp') {
    $envFile = $argv[2] ?? '.env';
    $temp = getenv('JD_DB_TEMP_PASSWORD');
    if ($temp === false || $temp === '' || ! is_readable($envFile)) {
        fwrite(STDERR, "usage: JD_DB_TEMP_PASSWORD=... php configure-env.php --retire-temp /path/to/.env\n");
        exit(2);
    }
    $env = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^(DB_DATABASE|DB_USERNAME|DB_PASSWORD)=(.*)$/', $line, $m)) {
            $value = $m[2];
            if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
                $value = stripcslashes(substr($value, 1, -1));
            }
            $env[$m[1]] = $value;
        }
    }
    foreach (['DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
        if (($env[$key] ?? '') === '') {
            fwrite(STDERR, "$key missing in .env\n");
            exit(2);
        }
    }
    if ($env['DB_PASSWORD'] === $temp) {
        fwrite(STDERR, ".env still uses the temporary password – nothing to do here\n");
        exit(1);
    }
    exit(retire_temp_password($env['DB_DATABASE'], $env['DB_USERNAME'], $temp, $env['DB_PASSWORD'], $options) ? 0 : 1);
}

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
$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4";

// 1) connect with the temporary password and rotate it
try {
    $pdo = new PDO($dsn, $user, $password, $options);
    $server = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    echo 'database server: '.(stripos($server, 'mariadb') !== false ? 'MariaDB ' : 'MySQL ').strtok($server, '-')."\n";

    $new = rtrim(strtr(base64_encode(random_bytes(30)), '+/', 'Kq'), '=').'a7Z';
    set_password($pdo, $new);
    $pdo = null;

    new PDO($dsn, $user, $new, $options); // must work with the new password
    $temp = $password;
    $password = $new;
    echo "database password: replaced by a new random one (stored only in .env)\n";

    // some hosts keep a second account entry (e.g. user@localhost for the socket)
    retire_temp_password($db, $user, $temp, $new, $options);
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
