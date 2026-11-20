<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * API: api/manuales.php
 * Consulta y descarga de fichas y manuales técnicos según matriz de acceso PRO.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$userId = getLoggedInUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Inicia sesión para acceder a la biblioteca de manuales.']);
    exit;
}

$pdo = getDB();
$currentUser = getCurrentUser();
$isPro = isProUser($currentUser);

$stmt = $pdo->query("SELECT id, marca, modelo, tipo_manual, tamano_archivo, num_paginas, ficha_tecnica_tolerancias FROM manuales_db ORDER BY id ASC");
$rows = $stmt->fetchAll();

$manuales = [];
foreach ($rows as $r) {
    $item = [
        'id'      => (int)$r['id'],
        'marca'   => $r['marca'],
        'modelo'  => $r['modelo'],
        'tipo'    => $r['tipo_manual'],
        'size'    => $r['tamano_archivo'],
        'paginas' => (int)$r['num_paginas'],
    ];

    // Si el usuario es PRO, adjuntar el esquema técnico completo
    if ($isPro) {
        $item['esquema'] = $r['ficha_tecnica_tolerancias'];
    } else {
        $item['esquema'] = "[CONTENIDO BLOQUEADO - REQUIERE AUTOPULSE PRO]";
    }

    $manuales[] = $item;
}

echo json_encode([
    'success'  => true,
    'manuales' => $manuales,
    'is_pro'   => $isPro
]);
