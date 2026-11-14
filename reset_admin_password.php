<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';

$username = $argv[1] ?? 'admin';
$password = getenv('NEIS_ADMIN_PASSWORD');

if (!is_string($username) || $username === '' || strlen($username) > 100) {
    fwrite(STDERR, "A valid username is required.\n");
    exit(1);
}
if (!is_string($password) || strlen($password) < 14) {
    fwrite(STDERR, "Set a password of at least 14 characters using the local PowerShell helper.\n");
    exit(1);
}

$pdo = db();
$hash = password_hash($password, PASSWORD_DEFAULT);
$statement = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
$statement->execute([$hash, $username]);

if ($statement->rowCount() === 0) {
    $statement = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
    $statement->execute([$username, $hash, 'System Administrator', 'Administrator']);
}

fwrite(STDOUT, "Administrator password updated.\n");
