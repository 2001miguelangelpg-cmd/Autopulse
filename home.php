<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: home.php (Panel de Usuario)
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$isPro = isProUser($user);
$remainingSeconds = getProRemainingSeconds($user);

$pageTitle         = 'Autopulse - Panel de Usuario';
$currentPage       = 'home';
$isAppLayout       = true;
$pageCss           = 'styles.css';
$showFooter        = true;

require_once __DIR__ . '/includes/header.php';
?>

    <section class="hero">
        <div class="container">
            <h1>Bienvenido a tu Panel <span>Autopulse</span></h1>
            <p>Monitorea y administra el estado de tus vehículos, vigencia de SOAT, mantenimientos preventivos y cambio
                de aceite en tiempo real.</p>
            <a href="garaje.php" class="btn">Gestionar Garaje</a>
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

    <section id="planes" class="pricing">
        <div class="container">
            <h2 class="section-title">Planes Hechos para Moteros</h2>

            <!-- CUADRO DE PLANES TRADICIONAL (Se oculta al activarse Autopulse Pro) -->
            <div id="pricingCard" class="price-card <?= $isPro ? 'hidden' : '' ?>">
                <h3>Plan Autopulse Pro</h3>
                <p style="margin-top: 8px;">Ideal para familias, mensajeros o moteros organizados que quieren el control
                    total.</p>
                <div class="price">$4.900 <span>COP/mes</span></div>
                <p
                    style="text-align: left; max-width: 290px; margin: 0 auto; line-height: 1.8; font-size: 14px; color: var(--gray);">
                    • Control de más de 1 moto en la misma cuenta<br>
                    • <b>Acceso completo a manuales de usuario y técnicos</b><br>
                    • Alertas y recordatorios visuales ilimitados<br>
                    • Soporte prioritario
                </p>
                <a href="planes.php" style="text-decoration: none;">
                    <button class="btn" style="margin-top: 25px; width: 100%;">Activar Plan Pro</button>
                </a>
            </div>

            <!-- BLOQUE DINÁMICO: ESTADO PREMIUM ACTIVADO (Reemplaza la tarjeta de planes) -->
            <div id="proStatusBox" class="pro-status-container <?= $isPro ? 'active' : '' ?>">
                <div class="pro-badge-icon">👑</div>
                <div class="pro-status-title">AUTOPULSE PRO ACTIVO</div>
                <div class="pro-status-text">¡Tu cuenta cuenta con todos los privilegios premium habilitados! Las
                    restricciones del garaje virtual y la base de datos de manuales técnicos han sido removidas.</div>
                <div id="landingCountdown" class="pro-timer"><?= gmdate("i:s", max(0, $remainingSeconds)) ?></div>
            </div>

        </div>
    </section>

    <div id="toast" class="toast"></div>

    <script>
        const landingChannel = new BroadcastChannel('autopulse_pro_stream');
        let timerInterval = null;
        let isUserPro = <?= $isPro ? 'true' : 'false' ?>;
        let serverRemaining = <?= (int)$remainingSeconds ?>;

        function checkGlobalPremiumStatus() {
            const pricingCard = document.getElementById('pricingCard');
            const proStatusBox = document.getElementById('proStatusBox');

            const savedExpiry = localStorage.getItem('autopulse_pro_expiry');
            const isLocalProActive = savedExpiry && parseInt(savedExpiry) > Date.now();

            if (isUserPro || isLocalProActive) {
                pricingCard.classList.add('hidden');
                proStatusBox.classList.add('active');

                let secondsLeft = serverRemaining;
                if (savedExpiry) {
                    const localSeconds = Math.floor((parseInt(savedExpiry) - Date.now()) / 1000);
                    if (localSeconds > 0) secondsLeft = localSeconds;
                }
                if (secondsLeft > 0) {
                    startLandingTimer(secondsLeft);
                }
            } else {
                pricingCard.classList.remove('hidden');
                proStatusBox.classList.remove('active');
                if (timerInterval) clearInterval(timerInterval);
            }
        }

        function startLandingTimer(duration) {
            if (timerInterval) clearInterval(timerInterval);
            let timer = duration, minutes, seconds;
            const display = document.getElementById('landingCountdown');

            timerInterval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(timerInterval);
                    localStorage.removeItem('autopulse_pro_expiry');
                    isUserPro = false;
                    checkGlobalPremiumStatus();
                }
            }, 1000);
        }

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.innerText = msg;
            if (isError) {
                toast.className = "toast show error";
            } else {
                toast.className = "toast show";
            }
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        landingChannel.onmessage = (e) => {
            if (e.data.event === 'PRO_ACTIVATED') {
                showToast('👑 ¡Suscripción AUTOPULSE PRO activada!');
                isUserPro = true;
                if (e.data.expiry) {
                    localStorage.setItem('autopulse_pro_expiry', e.data.expiry);
                    serverRemaining = Math.floor((e.data.expiry - Date.now()) / 1000);
                }
                checkGlobalPremiumStatus();
            }
            if (e.data.event === 'PRO_EXPIRED') {
                showToast('⚠️ Tu acceso AUTOPULSE PRO ha caducado.', true);
                isUserPro = false;
                localStorage.removeItem('autopulse_pro_expiry');
                checkGlobalPremiumStatus();
            }
        };

        window.addEventListener('DOMContentLoaded', () => {
            checkGlobalPremiumStatus();
            setInterval(checkGlobalPremiumStatus, 5000);
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
