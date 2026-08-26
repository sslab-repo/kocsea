<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array {
    if (!empty($_SESSION['user_id'])) {
        static $cached = null;
        if ($cached !== null) return $cached;
        $stmt = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if ($u && (int)$u['is_active'] === 1) {
            $cached = $u;
            return $u;
        }
    }
    return null;
}

function require_login(): void {
    if (!current_user()) {
        redirect(base_url('login.php'));
    }
}

function require_admin(): void {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function attempt_login(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM users WHERE name = ? AND is_active = 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['user_id'] = $u['id'];
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
