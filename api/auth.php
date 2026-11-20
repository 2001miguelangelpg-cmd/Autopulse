<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * API: api/auth.php
 * Autenticación, registro, perfil demo, control de sesiones y cierre de sesión.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? '';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // INICIAR SESIÓN
    if ($action === 'login') {
        $userOrEmail = trim($input['userOrEmail'] ?? '');
        $password    = $input['password'] ?? '';

        if (empty($userOrEmail) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Por favor completa todos los campos']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)");
        $stmt->execute([$userOrEmail, $userOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUserSession($user);
            echo json_encode([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'user'    => [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'nombre'   => $user['nombre'],
                    'email'    => $user['email'],
                    'rol'      => $user['rol']
                ]
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Usuario/correo o contraseña incorrectos']);
        exit;
    }

    // REGISTRO DE NUEVO USUARIO
    if ($action === 'register') {
        $username        = trim($input['username'] ?? '');
        $nombre          = trim($input['nombre'] ?? '');
        $email           = trim(strtolower($input['email'] ?? ''));
        $password        = $input['password'] ?? '';
        $confirmPassword = $input['confirmPassword'] ?? '';

        if (empty($username) || empty($nombre) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Por favor completa todos los campos obligatorios']);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden']);
            exit;
        }

        // Verificar unicidad de username y email
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE LOWER(username) = LOWER(?) OR LOWER(email) = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'El nombre de usuario o correo ya está registrado']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $pdo->prepare("
            INSERT INTO usuarios (username, nombre, email, password_hash, rol)
            VALUES (?, ?, ?, ?, 'gratuito')
        ");
        $insert->execute([$username, $nombre, $email, $hash]);

        $userId = (int)$pdo->lastInsertId();
        $user = [
            'id'       => $userId,
            'username' => $username,
            'nombre'   => $nombre,
            'email'    => $email,
            'rol'      => 'gratuito'
        ];
        loginUserSession($user);

        echo json_encode([
            'success' => true,
            'message' => '¡Registro exitoso!',
            'user'    => $user
        ]);
        exit;
    }

    // INICIO RÁPIDO CON MODO DEMO
    if ($action === 'demo') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute(['demo@autopulse.com']);
        $demoUser = $stmt->fetch();

        if (!$demoUser) {
            $insert = $pdo->prepare("
                INSERT INTO usuarios (username, nombre, email, password_hash, telefono, ciudad, rol)
                VALUES ('motero_demo', 'Juan Pérez', 'demo@autopulse.com', ?, '+57 300 123 4567', 'Medellín', 'gratuito')
            ");
            $insert->execute([password_hash('123', PASSWORD_BCRYPT)]);
            $stmt->execute(['demo@autopulse.com']);
            $demoUser = $stmt->fetch();
        }

        loginUserSession($demoUser);
        echo json_encode([
            'success' => true,
            'message' => 'Iniciado como Usuario Demo',
            'user'    => [
                'id'       => $demoUser['id'],
                'username' => $demoUser['username'],
                'nombre'   => $demoUser['nombre'],
                'email'    => $demoUser['email'],
                'rol'      => $demoUser['rol']
            ]
        ]);
        exit;
    }

    // CERRAR SESIÓN
    if ($action === 'logout') {
        logoutUserSession();
        echo json_encode(['success' => true, 'message' => 'Sesión finalizada']);
        exit;
    }
}

// ESTADO ACTUAL DE AUTENTICACIÓN (GET)
$currentUser = getCurrentUser();
if ($currentUser) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id'       => $currentUser['id'],
            'username' => $currentUser['username'],
            'nombre'   => $currentUser['nombre'],
            'email'    => $currentUser['email'],
            'rol'      => $currentUser['rol'],
            'is_pro'   => isProUser($currentUser),
            'pro_seconds' => getProRemainingSeconds($currentUser)
        ]
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
