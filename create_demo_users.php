<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

require_once __DIR__ . '/config.php';
$pdo = db();

$accounts = [
    ['username' => 'technician1', 'full_name' => 'Demo Technician', 'role' => 'Technician'],
    ['username' => 'viewer1', 'full_name' => 'Demo Viewer', 'role' => 'Viewer'],
];

foreach ($accounts as $account) {
    $check = $pdo->prepare('SELECT id, role FROM users WHERE username = ?');
    $check->execute([$account['username']]);
    $existing = $check->fetch();

    if ($existing && !in_array($existing['role'], ['Technician', 'Viewer'], true)) {
        echo "Skipped {$account['username']}: that username belongs to a different role.\n";
        continue;
    }

    $password = bin2hex(random_bytes(8));
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ?, is_active = 1 WHERE id = ?');
        $stmt->execute([$hash, $account['full_name'], $account['role'], (int) $existing['id']]);
        echo "Updated {$account['role']} login: {$account['username']} / {$password}\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$account['username'], $hash, $account['full_name'], $account['role']]);
        echo "Created {$account['role']} login: {$account['username']} / {$password}\n";
    }
}

echo "\nExisting Administrator login and inventory/maintenance records are preserved.\n";
