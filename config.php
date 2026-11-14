<?php
declare(strict_types=1);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

define('APP_NAME', 'Network Equipment Inventory');
define('DB_DIR', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'neis');
define('DB_FILE', DB_DIR . DIRECTORY_SEPARATOR . 'inventory.sqlite');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!extension_loaded('pdo_sqlite')) {
        http_response_code(500);
        exit('PHP SQLite support is not enabled. Enable pdo_sqlite and sqlite3 in your PHP configuration, then restart Apache.');
    }
    if (!is_file(DB_FILE) || !is_readable(DB_FILE)) {
        http_response_code(500);
        exit('The inventory database is unavailable. Check the private database installation and permissions.');
    }
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    initialize_database($pdo);
    return $pdo;
}

function initialize_database(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'Administrator',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        username TEXT NOT NULL PRIMARY KEY,
        failed_count INTEGER NOT NULL DEFAULT 0,
        last_attempt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        lock_until TEXT DEFAULT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS equipment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asset_tag TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        manufacturer TEXT DEFAULT '',
        model TEXT DEFAULT '',
        serial_number TEXT DEFAULT '',
        ip_address TEXT DEFAULT '',
        mac_address TEXT DEFAULT '',
        location TEXT NOT NULL DEFAULT '',
        department TEXT DEFAULT '',
        assigned_to TEXT DEFAULT '',
        purchase_date TEXT DEFAULT '',
        warranty_expiry TEXT DEFAULT '',
        status TEXT NOT NULL DEFAULT 'In Use',
        condition_note TEXT NOT NULL DEFAULT 'Good',
        notes TEXT DEFAULT '',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        equipment_id INTEGER NOT NULL,
        maintenance_date TEXT NOT NULL,
        maintenance_type TEXT NOT NULL,
        performed_by TEXT DEFAULT '',
        cost REAL NOT NULL DEFAULT 0,
        details TEXT DEFAULT '',
        next_due TEXT DEFAULT '',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
    )");

    $eqCount = (int)$pdo->query('SELECT COUNT(*) FROM equipment')->fetchColumn();
    if ($eqCount === 0) {
        $seed = $pdo->prepare("INSERT INTO equipment
        (asset_tag,name,category,manufacturer,model,serial_number,ip_address,mac_address,location,department,assigned_to,purchase_date,warranty_expiry,status,condition_note,notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $samples = [
            ['NET-0001','Core Network Switch','Switch','Cisco','Catalyst 2960','SN-DEMO-001','192.168.1.2','00:11:22:33:44:55','Server Room','ICT','ICT Department','2025-01-10','2028-01-10','In Use','Good','Main distribution switch (sample record)'],
            ['NET-0002','Office Router','Router','MikroTik','RB4011','SN-DEMO-002','192.168.1.1','00:11:22:33:44:66','Server Room','ICT','ICT Department','2025-02-15','2027-02-15','In Use','Good','Gateway router (sample record)'],
            ['NET-0003','Access Point - Floor 1','Wireless Access Point','TP-Link','EAP225','SN-DEMO-003','192.168.1.21','00:11:22:33:44:77','Floor 1','Administration','Reception','2025-03-05','2027-03-05','In Use','Good','Wi-Fi coverage (sample record)'],
            ['NET-0004','Spare 24-Port Switch','Switch','D-Link','DGS-1024D','SN-DEMO-004','','00:11:22:33:44:88','ICT Store','ICT','ICT Store','2024-08-20','2026-08-20','In Storage','Fair','Spare equipment (sample record)'],
            ['NET-0005','Old Office Router','Router','TP-Link','TL-R600VPN','SN-DEMO-005','192.168.1.9','','ICT Workshop','ICT','ICT Department','2023-06-01','2024-06-01','Under Maintenance','Needs Repair','Sample maintenance case']
        ];
        foreach ($samples as $row) $seed->execute($row);
    }
}

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            exit('Security check failed. Go back and try again.');
        }
    }
}
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function is_admin(): bool {
    $role = strtolower((string)($_SESSION['role'] ?? ''));
    return $role === 'administrator';
}
function require_login(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied. Administrator privileges are required.');
    }
}
function is_login_locked(string $username): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT failed_count, lock_until, last_attempt FROM login_attempts WHERE username = ?');
    $stmt->execute([$username]);
    $attempt = $stmt->fetch();
    if (!$attempt) return false;
    $lockUntil = $attempt['lock_until'];
    if (is_string($lockUntil) && $lockUntil !== '') {
        $until = new DateTimeImmutable($lockUntil);
        if ($until > new DateTimeImmutable('now')) {
            return true;
        }
    }
    $failedCount = (int)$attempt['failed_count'];
    if ($failedCount < 5) return false;
    $lastAttempt = new DateTimeImmutable($attempt['last_attempt']);
    $elapsed = $lastAttempt->diff(new DateTimeImmutable('now'));
    return $elapsed->i < 15;
}
function reset_login_attempts(string $username): void {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (username, failed_count, last_attempt, lock_until) VALUES (?, 0, CURRENT_TIMESTAMP, NULL) ON CONFLICT(username) DO UPDATE SET failed_count = 0, last_attempt = CURRENT_TIMESTAMP, lock_until = NULL');
    $stmt->execute([$username]);
}
function record_login_failure(string $username): void {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT failed_count, lock_until FROM login_attempts WHERE username = ?');
    $stmt->execute([$username]);
    $attempt = $stmt->fetch();
    $failedCount = $attempt ? (int)$attempt['failed_count'] : 0;
    $nextCount = $failedCount + 1;
    $lockUntil = $nextCount >= 5 ? (new DateTimeImmutable('+15 minutes'))->format(DATE_ATOM) : null;
    if ($attempt) {
        $stmt = $pdo->prepare('UPDATE login_attempts SET failed_count = ?, last_attempt = CURRENT_TIMESTAMP, lock_until = ? WHERE username = ?');
        $stmt->execute([$nextCount, $lockUntil, $username]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO login_attempts (username, failed_count, last_attempt, lock_until) VALUES (?, ?, CURRENT_TIMESTAMP, ?)');
    $stmt->execute([$username, $nextCount, $lockUntil]);
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = ['message'=>$message,'type'=>$type]; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert '.e($f['type']).'">'.e($f['message']).'</div>';
    }
}
function redirect(string $url): never { header('Location: '.$url); exit; }
function categories(): array { return ['Router','Switch','Wireless Access Point','Firewall','Modem','Server','Patch Panel','Network Cable','SFP/Transceiver','Other']; }
function statuses(): array { return ['In Use','In Storage','Under Maintenance','Retired','Lost']; }
function conditions(): array { return ['Good','Fair','Needs Repair','Damaged']; }
