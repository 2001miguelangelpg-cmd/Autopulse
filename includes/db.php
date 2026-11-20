<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Componente: includes/db.php
 * Conexión PDO SQLite con inicialización automática, permisos y pre-sembrado.
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbDir = __DIR__ . '/../database';
    $dbFile = $dbDir . '/autopulse.sqlite';

    // 1. Asegurar existencia de directorio con permisos 0777 para Apache (www-data)
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0777, true);
    }
    @chmod($dbDir, 0777);

    // 2. Conectar a SQLite mediante PDO
    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,
    ]);

    // 3. Permisos en el fichero de base de datos
    @chmod($dbFile, 0777);

    // 4. Optimizaciones de rendimiento y llaves foráneas
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("PRAGMA journal_mode = WAL;");

    // 5. Creación de tablas si no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            nombre TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            telefono TEXT DEFAULT '',
            ciudad TEXT DEFAULT '',
            foto_perfil TEXT DEFAULT '',
            rol TEXT DEFAULT 'gratuito',
            autopulse_pro_expiry TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS user_garage_db (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            tipo_vehiculo TEXT DEFAULT 'moto',
            nombre_modelo TEXT NOT NULL,
            placa TEXT NOT NULL,
            cilindraje TEXT DEFAULT 'bajo',
            kilometraje_actual INTEGER NOT NULL,
            fecha_base_odometro TEXT DEFAULT CURRENT_TIMESTAMP,
            fotografia TEXT,
            fecha_expedicion_soat TEXT,
            fecha_ultima_tecno TEXT,
            km_compra_llantas INTEGER,
            compuesto_uso TEXT DEFAULT 'normal',
            km_ultimo_aceite INTEGER,
            tipo_aceite TEXT DEFAULT 'mineral',
            fecha_ultimo_mantenimiento TEXT,
            frecuencia_meses INTEGER DEFAULT 6,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS manuales_db (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            marca TEXT NOT NULL,
            modelo TEXT NOT NULL,
            tipo_manual TEXT NOT NULL,
            tamano_archivo TEXT,
            num_paginas INTEGER,
            ficha_tecnica_tolerancias TEXT
        );
    ");

    // 6. Pre-sembrado del usuario Demo si no existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['demo@autopulse.com']);
    if (!$stmt->fetch()) {
        $insertDemo = $pdo->prepare("
            INSERT INTO usuarios (username, nombre, email, password_hash, telefono, ciudad, rol, autopulse_pro_expiry)
            VALUES (?, ?, ?, ?, ?, ?, 'gratuito', NULL)
        ");
        $insertDemo->execute([
            'motero_demo',
            'Juan Pérez',
            'demo@autopulse.com',
            password_hash('123', PASSWORD_BCRYPT),
            '+57 300 123 4567',
            'Medellín'
        ]);
    }

    // 7. Pre-sembrado de manuales técnicos si la tabla está vacía
    $countManuals = (int)$pdo->query("SELECT COUNT(*) FROM manuales_db")->fetchColumn();
    if ($countManuals === 0) {
        $manuals = [
            [
                'honda', 'Honda CB 190R', 'Manual de Taller / Despiece', '14.2 MB', 320,
                "[SISTEMA ELÉCTRICO PGM-FI]\n- Resistencia Inyector: 11.1-11.5 Ω\n- Presión Bomba Combustible: 294 kPa\n- Luz de válvulas (Frío): Admisión: 0.08mm | Escape: 0.24mm\n- Torque de culata: 32 N·m"
            ],
            [
                'honda', 'Honda XR 150L', 'Manual de Usuario', '4.8 MB', 115,
                "[MANTENIMIENTO PERIÓDICO]\n- Capacidad Aceite: 1.0 Litros (10W-30 API MA)\n- Bujía recomendada: CPR8EA-9 (Holgura: 0.8-0.9mm)\n- Ralentí motor: 1,400 ± 100 rpm\n- Cadena de arrastre: Holgura 25-35mm"
            ],
            [
                'honda', 'Honda Twister CB 250', 'Diagrama Eléctrico de Taller', '8.1 MB', 240,
                "[DIAGRAMA DE ENCENDIDO ELECTRÓNICO]\n- Alternador salida trifásica: 14.5V a 5000 rpm\n- Resistencia Bobina de Pulso: 240 Ω\n- Fusible Principal: 30A | Fusibles Auxiliares: 10A/15A"
            ],
            [
                'yamaha', 'Yamaha FZ25', 'Manual de Taller', '18.5 MB', 412,
                "[PARÁMETROS DE REPARACIÓN DE MOTOR]\n- Presión de compresión: 1200 kPa\n- Presión de aceite de lubricación: 140 kPa a 5000rpm\n- Holgura de válvula: Adm: 0.05-0.10mm | Esc: 0.10-0.15mm"
            ],
            [
                'yamaha', 'Yamaha R15 V3', 'Manual de Usuario', '6.2 MB', 148,
                "[SISTEMA VVA - VARIABLE VALVE ACTUATION]\n- Punto de activación solenoide: 7,400 rpm\n- Líquido de refrigeración: Mezcla 1:1 Etilenglicol (0.49L)\n- Relación de compresión: 11.6:1"
            ],
            [
                'yamaha', 'Yamaha XTZ 125', 'Guía de Mantenimiento Preventivo', '3.1 MB', 90,
                "[SISTEMA DE CHASIS Y SUSPENSIÓN]\n- Aceite barra delantera: 154 cm³ por barra (10W)\n- Presión neumático delantero: 22 psi | Trasero: 25 psi\n- Desgaste límite pastilla de freno: 4.5mm"
            ],
            [
                'suzuki', 'Suzuki Gixxer 150 SF', 'Manual Técnico de Despiece', '11.4 MB', 280,
                "[SISTEMA SEP - SUZUKI ECO PERFORMANCE]\n- Sensor de O2 voltaje oscilación: 0.1V a 0.9V\n- Resistencia sensor temperatura motor (ECT): 2.4 kΩ a 20°C\n- Holgura del cable del acelerador: 2.0-4.0mm"
            ],
            [
                'suzuki', 'Suzuki GN 125', 'Manual de Taller Integral', '9.7 MB', 195,
                "[SISTEMA MECÁNICO GENERAL]\n- Holgura taqués admisión/escape: 0.08 - 0.13 mm\n- Embrague: juego libre de la maneta: 10-15 mm\n- Capacidad del tanque de combustible: 10.3 Litros"
            ],
            [
                'ktm', 'KTM Duke 200 / 390', 'Manual Técnico de Reparación', '22.3 MB', 560,
                "[SISTEMA DE DOBLE ÁRBOL DE LEVAS - DOHC]\n- Torque tornillos de biela: 12 N·m + 90°\n- Presión del sistema de combustible (EFI): 4.2 Bar\n- Luz de válvulas: Adm: 0.10-0.15mm | Esc: 0.15-0.20mm"
            ],
            [
                'bajaj', 'Pulsar NS 200 FI', 'Diagrama de Inyección y Eléctrico', '12.0 MB', 180,
                "[TECNOLOGÍA TRIPLE BUJÍA DTS-i]\n- Sincronización de chispa controlada por ECU de 16 bits\n- Resistencia de la bobina de encendido primaria: 0.4 a 0.6 Ω\n- Holgura de electrodo de bujías: 0.7 a 0.8 mm"
            ]
        ];

        $insManual = $pdo->prepare("
            INSERT INTO manuales_db (marca, modelo, tipo_manual, tamano_archivo, num_paginas, ficha_tecnica_tolerancias)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($manuals as $m) {
            $insManual->execute($m);
        }
    }

    return $pdo;
}
