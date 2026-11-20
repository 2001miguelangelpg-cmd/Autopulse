<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Componente: includes/auth.php
 * Gestión de sesiones de usuario, roles de acceso y validación de seguridad.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['usuario_id']) || !empty($_SESSION['user_id']);
}

function getLoggedInUserId(): ?int {
    if (!empty($_SESSION['usuario_id'])) {
        return (int)$_SESSION['usuario_id'];
    }
    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    return null;
}

function getCurrentUser(): ?array {
    $userId = getLoggedInUserId();
    if (!$userId) {
        return null;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        // La sesión apunta a un usuario inexistente
        $_SESSION = [];
        return null;
    }

    // Verificar si el plan PRO expiró por tiempo
    if ($user['rol'] === 'pro' && !empty($user['autopulse_pro_expiry'])) {
        $expiryTimestamp = strtotime($user['autopulse_pro_expiry']);
        if ($expiryTimestamp && $expiryTimestamp <= time()) {
            $update = $pdo->prepare("UPDATE usuarios SET rol = 'gratuito', autopulse_pro_expiry = NULL WHERE id = ?");
            $update->execute([$userId]);
            $user['rol'] = 'gratuito';
            $user['autopulse_pro_expiry'] = null;
        }
    }

    return $user;
}

function isProUser(?array $user = null): bool {
    if ($user === null) {
        $user = getCurrentUser();
    }
    if (!$user) {
        return false;
    }

    if ($user['rol'] === 'pro') {
        if (empty($user['autopulse_pro_expiry'])) {
            return true;
        }
        return strtotime($user['autopulse_pro_expiry']) > time();
    }

    return false;
}

function getProRemainingSeconds(?array $user = null): int {
    if ($user === null) {
        $user = getCurrentUser();
    }
    if (!$user || $user['rol'] !== 'pro' || empty($user['autopulse_pro_expiry'])) {
        return 0;
    }
    $expiry = strtotime($user['autopulse_pro_expiry']);
    return max(0, $expiry - time());
}

function requireLogin(string $redirectTo = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

function requireVisitor(string $redirectTo = 'home.php'): void {
    if (isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

function loginUserSession(array $user): void {
    $_SESSION['usuario_id'] = (int)$user['id'];
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['username']   = $user['username'] ?? '';
    $_SESSION['nombre']     = $user['nombre'] ?? '';
    $_SESSION['email']      = $user['email'] ?? '';
    $_SESSION['rol']        = $user['rol'] ?? 'gratuito';
}

function logoutUserSession(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
