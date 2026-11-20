<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: index.php (Página Principal Pública)
 */

require_once __DIR__ . '/includes/auth.php';

$pageTitle         = 'Autopulse - Control de Mantenimiento y SOAT de Motos';
$pageDescription   = 'Controla el SOAT, cambio de aceite y mantenimiento de tu moto en Colombia. Evita multas y cuida tu motor. ¡Pruébala gratis ya!';
$pageKeywords      = 'motos, SOAT, mantenimiento, fotomultas, cambio de aceite, Colombia, taller, moteros';
$currentPage       = 'index';
$isAppLayout       = false;
$pageCss           = 'estilos.css';
$showFooter        = true;

$loggedIn = isLoggedIn();

require_once __DIR__ . '/includes/header.php';
?>

    <section class="hero">
        <div class="container">
            <h1>¿Olvidaste cuándo se vence tu <span>SOAT</span>?</h1>
            <p>No esperes a que un retén o una fotomulta te lo recuerden. Controla los papeles, el kilometraje y el
                aceite de tu moto gratis desde tu celular.</p>
            <?php if ($loggedIn): ?>
                <a href="home.php" class="btn">¡Ir a Mi Panel de Control!</a>
            <?php else: ?>
                <a href="registro.php" class="btn">¡Registra tu Moto Gratis Ya!</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title">¿Cómo funciona Autopulse?</h2>
            <div class="grid">
                <div class="card">
                    <h3>🟢 Semáforo Inteligente</h3>
                    <p>Alertas visuales automáticas en verde, amarillo y rojo que te indican el estado crítico de tus
                        documentos obligatorios.</p>
                </div>
                <div class="card">
                    <h3>🏍️ Garaje Virtual</h3>
                    <p>Registra el kilometraje actual, placa y modelo de tu motocicleta para calcular de forma
                        matemática sus próximos mantenimientos.</p>
                </div>
                <div class="card">
                    <h3>🛢️ Control de Aceite</h3>
                    <p>Monitorea el desgaste de los lubricantes y la vida útil de tus llantas para prevenir daños
                        mecánicos costosos en tu motor.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing">
        <div class="container">
            <h2 class="section-title">Planes Hechos para Moteros</h2>
            <div class="price-card">
                <h3>Plan Autopulse Pro</h3>
                <p>Ideal para familias, mensajeros o moteros organizados que quieren el control total.</p>
                <div class="price">$4.900 <span>COP/mes</span></div>
                <p>• Control de más de 1 moto en la misma cuenta<br>
                    • <b>Acceso completo a manuales de usuario y técnicos</b><br>
                    • Alertas y recordatorios visuales ilimitados<br>
                    • Soporte prioritario</p>
                <a href="<?= $loggedIn ? 'planes.php' : 'login.php' ?>" class="btn btn-pricing">Activar Plan Pro</a>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

