<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * API: api/perfil.php
 * Edición de perfil de usuario y cambio de contraseña con aislamiento estricto por sesión.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$userId = getLoggedInUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Inicia sesión.']);
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // ACTUALIZAR DATOS DE PERFIL
    if ($action === 'update') {
        $nombre   = trim($input['nombre'] ?? '');
        $email    = trim(strtolower($input['email'] ?? ''));
        $telefono = trim($input['telefono'] ?? '');
        $ciudad   = trim($input['ciudad'] ?? '');

        if (empty($nombre) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Nombre y correo electrónico son obligatorios.']);
            exit;
        }

        // Verificar si el correo ya está en uso por otro usuario
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado por otra cuenta.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nombre = ?, email = ?, telefono = ?, ciudad = ?
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $email, $telefono, $ciudad, $userId]);

        $_SESSION['nombre'] = $nombre;
        $_SESSION['email']  = $email;

        echo json_encode(['success' => true, 'message' => 'Perfil actualizado con éxito.']);
        exit;
    }

    // CAMBIAR CONTRASEÑA
    if ($action === 'change_password') {
        $currentPass = $input['current_password'] ?? '';
        $newPass     = $input['new_password'] ?? '';
        $confirmPass = $input['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            echo json_encode(['success' => false, 'error' => 'Completa los campos de contraseña.']);
            exit;
        }

        if ($newPass !== $confirmPass) {
            echo json_encode(['success' => false, 'error' => 'La nueva contraseña y su confirmación no coinciden.']);
            exit;
        }

        // Obtener hash actual
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPass, $row['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'La contraseña actual es incorrecta.']);
            exit;
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $update->execute([$newHash, $userId]);

        echo json_encode(['success' => true, 'message' => 'Contraseña modificada correctamente.']);
        exit;
    }
}

// OBTENER DATOS DEL PERFIL (GET)
$stmt = $pdo->prepare("SELECT id, username, nombre, email, telefono, ciudad, rol, autopulse_pro_expiry, created_at FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo json_encode([
    'success' => true,
    'user'    => $user,
    'is_pro'  => isProUser($user),
    'pro_seconds' => getProRemainingSeconds($user)
]);
