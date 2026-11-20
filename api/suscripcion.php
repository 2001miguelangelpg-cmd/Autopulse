<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * API: api/suscripcion.php
 * Pasarela simulada de pago y activación de suscripción Autopulse PRO en base de datos.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$userId = getLoggedInUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Inicia sesión para gestionar tu suscripción.']);
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? '';

// ACTIVAR PLAN PRO SIMULADO (1 HORA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'activate_pro') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $name = trim($input['name'] ?? '');
    $card = trim($input['card'] ?? '');
    $date = trim($input['date'] ?? '');
    $cvv  = trim($input['cvv'] ?? '');

    if (empty($name) || empty($card) || empty($date) || empty($cvv)) {
        echo json_encode(['success' => false, 'error' => 'Por favor completa todos los campos de pago requeridos.']);
        exit;
    }

    // Duración de prueba PRO: 1 hora
    $durationSeconds = 3600;
    $expiryTimestamp = time() + $durationSeconds;
    $expiryIso = date('Y-m-d H:i:s', $expiryTimestamp);

    // Actualizar usuario en base de datos SQLite
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET rol = 'pro', autopulse_pro_expiry = ?
        WHERE id = ?
    ");
    $stmt->execute([$expiryIso, $userId]);

    $_SESSION['rol'] = 'pro';

    echo json_encode([
        'success'           => true,
        'message'           => '¡Plan Autopulse PRO activado con éxito por 1 hora!',
        'expiry'            => $expiryTimestamp * 1000, // milisegundos para compatibilidad con JS
        'expiry_iso'        => $expiryIso,
        'remaining_seconds' => $durationSeconds
    ]);
    exit;
}

// ESTADO DE SUSCRIPCIÓN (GET)
$currentUser = getCurrentUser();
$isPro = isProUser($currentUser);
$remaining = getProRemainingSeconds($currentUser);

echo json_encode([
    'success'           => true,
    'is_pro'            => $isPro,
    'remaining_seconds' => $remaining,
    'expiry'            => ($isPro && !empty($currentUser['autopulse_pro_expiry'])) ? strtotime($currentUser['autopulse_pro_expiry']) * 1000 : null
]);
