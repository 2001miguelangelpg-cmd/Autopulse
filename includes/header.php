<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Componente: includes/header.php
 * Cabecera y navegación unificada con la paleta original (#1a1a1a, #242424, #0077FF).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logo.php';

$currentUser    = getCurrentUser();
$userIsLoggedIn = ($currentUser !== null);
$userIsPro      = isProUser($currentUser);

$pageTitle       = $pageTitle ?? 'Autopulse - Control de Mantenimiento y SOAT de Motos';
$pageDescription = $pageDescription ?? 'Controla el SOAT, cambio de aceite y mantenimiento de tu moto en Colombia. Evita multas y cuida tu motor.';
$pageKeywords    = $pageKeywords ?? 'motos, SOAT, mantenimiento, fotomultas, cambio de aceite, Colombia, taller, moteros';
$isAppLayout     = $isAppLayout ?? $userIsLoggedIn;
$currentPage     = $currentPage ?? '';
$showAlertBadge  = $showAlertBadge ?? false;
$pageCss         = $pageCss ?? ($isAppLayout ? 'styles.css' : 'estilos.css');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    
    <!-- Favicon Oficial Autopulse -->
    <link rel="icon" type="image/png" href="assets/img/logo-autopulse.png">
    
    <!-- Tipografía Google Fonts: Outfit (Títulos y Tecnología) + Plus Jakarta Sans & Montserrat (Cuerpo e Interfaz) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Orbitron:wght@700;800;900&family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Hoja de Estilos Específica de la Vista -->
    <?php if (is_array($pageCss)): ?>
        <?php foreach ($pageCss as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php elseif (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageCss) ?>">
    <?php endif; ?>

    <!-- Paleta Original Estricta y Variables Unificadas -->
    <style>
        :root {
            --bg-color: #1a1a1a !important;
            --bg: #1a1a1a !important;
            --surface-color: #242424 !important;
            --surface: #242424 !important;
            --surface2: #2e2e2e !important;
            --surface3: #383838 !important;
            --surface-color-2: #2e2e2e !important;
            --border: rgba(255, 255, 255, 0.08) !important;
            --border-line: rgba(255, 255, 255, 0.15) !important;
            --blue-primary: #0077FF !important;
            --blue: #0077FF !important;
            --blue-hover: #0055cc !important;
            --green: #2ECC71 !important;
            --green-success: #2ecc71 !important;
            --red: #E74C3C !important;
            --red-error: #e74c3c !important;
            --orange-warning: #f39c12 !important;
            --gold: #f39c12 !important;
            --text-color: #F0F0F0 !important;
            --text: #F0F0F0 !important;
            --text-muted: #B0B0B0 !important;
            --gray: #B0B0B0 !important;
            --font-title: 'Orbitron', 'Outfit', sans-serif !important;
            --font-main: 'Plus Jakarta Sans', 'Montserrat', sans-serif !important;
            --font-body: 'Plus Jakarta Sans', 'Segoe UI', sans-serif !important;
        }

        body {
            background-color: var(--bg) !important;
            color: var(--text) !important;
            font-family: var(--font-main) !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, .section-title, .brand-name, .form-st, .modal-title {
            font-family: var(--font-title) !important;
        }

        .navbar {
            background-color: #111111 !important;
            border-bottom: 1px solid var(--border) !important;
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: var(--text);
            text-decoration: none;
            font-family: var(--font-title);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: color 0.25s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--blue) !important;
        }

        .nav-right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-pro-tag {
            background: rgba(243, 156, 18, 0.15);
            border: 1px solid rgba(243, 156, 18, 0.4);
            color: #f39c12;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            font-family: var(--font-title);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-free-tag {
            background: #242424;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #888;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            font-family: var(--font-title);
        }

        .btn-logout {
            background-color: transparent;
            color: var(--red);
            border: 1px solid var(--red);
            padding: 6px 16px;
            border-radius: 6px;
            font-family: var(--font-title);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-logout:hover {
            background-color: var(--red);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(231, 76, 60, 0.4);
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 12px;
                padding: 12px 15px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            .nav-right-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <!-- CABECERA DE NAVEGACIÓN MODULAR Y UNIFICADA -->
    <header class="navbar">
        <div class="nav-container">
            <?php if ($userIsLoggedIn): ?>
                <a href="home.php" class="logo-link" style="text-decoration: none;">
                    <img src="assets/img/logo-autopulse.png" alt="Autopulse Logo" class="logo-img" style="height: 46px; width: auto; display: block; object-fit: contain;">
                </a>
                <nav class="nav-links">
                    <a href="home.php" class="<?= ($currentPage === 'home') ? 'active' : '' ?>">INICIO</a>
                    <a href="perfil.php" class="<?= ($currentPage === 'perfil') ? 'active' : '' ?>">PERFIL</a>
                    <a href="garaje.php" class="<?= ($currentPage === 'garaje') ? 'active' : '' ?>">GARAJE</a>
                    <a href="planes.php" class="<?= ($currentPage === 'planes') ? 'active' : '' ?>">PLANES</a>
                    <a href="manuales.php" class="<?= ($currentPage === 'manuales') ? 'active' : '' ?>">MANUALES DE USUARIO</a>
                </nav>
                <div class="nav-right-actions">
                    <?php if ($userIsPro): ?>
                        <span class="badge-pro-tag">👑 PRO</span>
                    <?php else: ?>
                        <span class="badge-free-tag">GRATIS</span>
                    <?php endif; ?>

                    <?php if ($showAlertBadge): ?>
                        <div id="alertBadge" class="alert-badge" style="display: none;">0 alertas</div>
                    <?php endif; ?>

                    <button onclick="logout()" class="btn-logout">SALIR</button>
                </div>
            <?php else: ?>
                <a href="index.php" class="logo-link" style="text-decoration: none;">
                    <img src="assets/img/logo-autopulse.png" alt="Autopulse Logo" class="logo-img" style="height: 46px; width: auto; display: block; object-fit: contain;">
                </a>
                <nav class="nav-links">
                    <a href="index.php" class="<?= ($currentPage === 'index') ? 'active' : '' ?>">INICIO</a>
                    <a href="planes.php" class="<?= ($currentPage === 'planes') ? 'active' : '' ?>">PLANES</a>
                    <a href="registro.php" class="<?= ($currentPage === 'registro') ? 'active' : '' ?>">REGISTRARSE</a>
                    <a href="login.php" class="<?= ($currentPage === 'login') ? 'active' : '' ?>">ACCESO</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
