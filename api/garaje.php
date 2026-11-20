<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * API: api/garaje.php
 * Persistencia en SQLite del garaje virtual con aislamiento estricto por usuario y validación de límites PRO.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$userId = getLoggedInUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Inicia sesión para acceder a tu garaje.']);
    exit;
}

$pdo = getDB();
$currentUser = getCurrentUser();
$isPro = isProUser($currentUser);
$action = $_GET['action'] ?? '';

// Mapeo de fila de base de datos a objeto JS de la vista
function formatVehicleRow(array $row): array {
    return [
        'id'               => (string)$row['id'],
        'name'             => $row['nombre_modelo'] ?? '',
        'plate'            => $row['placa'] ?? '',
        'cc'               => $row['cilindraje'] ?? 'bajo',
        'baseKm'           => (int)($row['kilometraje_actual'] ?? 0),
        'odometerBaseDate' => $row['fecha_base_odometro'] ?? date('c'),
        'image'            => $row['fotografia'] ?? null,
        'soatDate'         => $row['fecha_expedicion_soat'] ?? null,
        'tecnoDate'        => $row['fecha_ultima_tecno'] ?? null,
        'tireKm'           => ($row['km_compra_llantas'] !== null && $row['km_compra_llantas'] !== '') ? (int)$row['km_compra_llantas'] : null,
        'tireUse'          => $row['compuesto_uso'] ?? 'normal',
        'oilKm'            => ($row['km_ultimo_aceite'] !== null && $row['km_ultimo_aceite'] !== '') ? (int)$row['km_ultimo_aceite'] : null,
        'oilType'          => $row['tipo_aceite'] ?? 'mineral',
        'lastMaint'        => $row['fecha_ultimo_mantenimiento'] ?? null,
        'maintInterval'    => (int)($row['frecuencia_meses'] ?? 6),
    ];
}

// 1. INSERTAR VEHÍCULO NUEVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'insert') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Validación de límite de cuenta gratuita (máximo 1 vehículo)
    if (!$isPro) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_garage_db WHERE usuario_id = ?");
        $countStmt->execute([$userId]);
        $currentCount = (int)$countStmt->fetchColumn();

        if ($currentCount >= 1) {
            echo json_encode([
                'success' => false,
                'error'   => 'Límite gratuito alcanzado (máx. 1 moto). Activa Autopulse PRO para monitorear motocicletas ilimitadas.'
            ]);
            exit;
        }
    }

    $name    = trim($input['name'] ?? '');
    $plate   = trim(strtoupper($input['plate'] ?? ''));
    $baseKm  = isset($input['baseKm']) ? (int)$input['baseKm'] : null;

    if (empty($name) || empty($plate) || $baseKm === null) {
        echo json_encode(['success' => false, 'error' => 'Nombre/Modelo, Placa y Kilometraje son campos obligatorios (*)']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_garage_db (
            usuario_id, tipo_vehiculo, nombre_modelo, placa, cilindraje,
            kilometraje_actual, fecha_base_odometro, fotografia,
            fecha_expedicion_soat, fecha_ultima_tecno, km_compra_llantas,
            compuesto_uso, km_ultimo_aceite, tipo_aceite,
            fecha_ultimo_mantenimiento, frecuencia_meses
        ) VALUES (
            ?, 'moto', ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?
        )
    ");

    $odometerDate = !empty($input['odometerBaseDate']) ? $input['odometerBaseDate'] : date('Y-m-d H:i:s');

    $stmt->execute([
        $userId,
        $name,
        $plate,
        $input['cc'] ?? 'bajo',
        $baseKm,
        $odometerDate,
        $input['image'] ?? null,
        !empty($input['soatDate']) ? $input['soatDate'] : null,
        !empty($input['tecnoDate']) ? $input['tecnoDate'] : null,
        ($input['tireKm'] !== '' && $input['tireKm'] !== null) ? (int)$input['tireKm'] : null,
        $input['tireUse'] ?? 'normal',
        ($input['oilKm'] !== '' && $input['oilKm'] !== null) ? (int)$input['oilKm'] : null,
        $input['oilType'] ?? 'mineral',
        !empty($input['lastMaint']) ? $input['lastMaint'] : null,
        (int)($input['maintInterval'] ?? 6)
    ]);

    $insertedId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Vehículo registrado con éxito',
        'id'      => (string)$insertedId
    ]);
    exit;
}

// 2. ACTUALIZAR VEHÍCULO EXISTENTE (ESTRICTAMENTE AISLADO POR usuario_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de vehículo no válido']);
        exit;
    }

    $name   = trim($input['name'] ?? '');
    $plate  = trim(strtoupper($input['plate'] ?? ''));
    $baseKm = isset($input['baseKm']) ? (int)$input['baseKm'] : 0;

    // Verificar si se envió imagen nueva
    $hasNewImage = array_key_exists('image', $input) && !empty($input['image']);

    if ($hasNewImage) {
        $stmt = $pdo->prepare("
            UPDATE user_garage_db SET
                nombre_modelo = ?, placa = ?, cilindraje = ?, kilometraje_actual = ?,
                fotografia = ?, fecha_expedicion_soat = ?, fecha_ultima_tecno = ?,
                km_compra_llantas = ?, compuesto_uso = ?, km_ultimo_aceite = ?,
                tipo_aceite = ?, fecha_ultimo_mantenimiento = ?, frecuencia_meses = ?
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([
            $name, $plate, $input['cc'] ?? 'bajo', $baseKm,
            $input['image'],
            !empty($input['soatDate']) ? $input['soatDate'] : null,
            !empty($input['tecnoDate']) ? $input['tecnoDate'] : null,
            ($input['tireKm'] !== '' && $input['tireKm'] !== null) ? (int)$input['tireKm'] : null,
            $input['tireUse'] ?? 'normal',
            ($input['oilKm'] !== '' && $input['oilKm'] !== null) ? (int)$input['oilKm'] : null,
            $input['oilType'] ?? 'mineral',
            !empty($input['lastMaint']) ? $input['lastMaint'] : null,
            (int)($input['maintInterval'] ?? 6),
            $id, $userId
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE user_garage_db SET
                nombre_modelo = ?, placa = ?, cilindraje = ?, kilometraje_actual = ?,
                fecha_expedicion_soat = ?, fecha_ultima_tecno = ?,
                km_compra_llantas = ?, compuesto_uso = ?, km_ultimo_aceite = ?,
                tipo_aceite = ?, fecha_ultimo_mantenimiento = ?, frecuencia_meses = ?
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([
            $name, $plate, $input['cc'] ?? 'bajo', $baseKm,
            !empty($input['soatDate']) ? $input['soatDate'] : null,
            !empty($input['tecnoDate']) ? $input['tecnoDate'] : null,
            ($input['tireKm'] !== '' && $input['tireKm'] !== null) ? (int)$input['tireKm'] : null,
            $input['tireUse'] ?? 'normal',
            ($input['oilKm'] !== '' && $input['oilKm'] !== null) ? (int)$input['oilKm'] : null,
            $input['oilType'] ?? 'mineral',
            !empty($input['lastMaint']) ? $input['lastMaint'] : null,
            (int)($input['maintInterval'] ?? 6),
            $id, $userId
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Cambios guardados con éxito']);
    exit;
}

// 3. ELIMINAR VEHÍCULO (ESTRICTAMENTE AISLADO POR usuario_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID no válido']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM user_garage_db WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $userId]);

    echo json_encode(['success' => true, 'message' => 'Vehículo eliminado']);
    exit;
}

// 4. LISTAR VEHÍCULOS DEL USUARIO AUTENTICADO (GET POR DEFECTO)
$stmt = $pdo->prepare("SELECT * FROM user_garage_db WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$vehicles = [];
foreach ($rows as $row) {
    $vehicles[] = formatVehicleRow($row);
}

echo json_encode([
    'success'  => true,
    'vehicles' => $vehicles,
    'is_pro'   => $isPro,
    'count'    => count($vehicles)
]);
