<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: planes.php (Planes de Suscripción PRO y Pasarela Segura Simulada)
 */

require_once __DIR__ . '/includes/auth.php';

$user = getCurrentUser();
$isPro = isProUser($user);
$remainingSeconds = getProRemainingSeconds($user);
$loggedIn = isLoggedIn();

$pageTitle         = 'Autopulse PRO — Planes de Suscripción';
$currentPage       = 'planes';
$isAppLayout       = $loggedIn;
$pageCss           = 'stylesplanes.css';
$showFooter        = true;

require_once __DIR__ . '/includes/header.php';
?>

    <div class="container">

        <div class="brand-header" style="text-align: center; margin-bottom: 30px;">
            <?php renderAutopulseLogo('large', true); ?>
            <div class="brand-tagline" style="margin-top: 10px; color: var(--gray); font-size: 13px; font-weight: 600; letter-spacing: 1px;">Modelos de Negocios Digitales</div>
        </div>

        <div class="flow-wrapper">

            <div id="step-plan" class="plan-card <?= $isPro ? 'hidden' : '' ?>">
                <div class="plan-icon">👑</div>
                <div class="plan-title">Plan Autopulse Pro</div>
                <div class="plan-subtitle">Ideal para familias, mensajeros o moteros organizados que quieren el control
                    total.</div>

                <div class="plan-price">$4.900 <span>COP/mes</span></div>

                <div class="plan-features">
                    <div class="feature-item">Control de más de 1 moto en la misma cuenta</div>
                    <div class="feature-item"><strong>Acceso completo a manuales de usuario y técnicos</strong></div>
                    <div class="feature-item">Alertas y recordatorios visuales ilimitados</div>
                    <div class="feature-item">Soporte prioritario</div>
                </div>

                <button class="btn-checkout" onclick="showCheckout()">ACTIVAR PLAN PRO</button>
            </div>

            <div id="step-checkout" class="checkout-form">
                <div class="checkout-title">PASARELA DE PAGO seguro</div>
                <div class="checkout-sub">Completa los datos para activar tu suscripción</div>

                <div class="order-summary">
                    <span class="order-label">Plan Autopulse Pro</span>
                    <span class="order-price">$4.900 COP</span>
                </div>

                <div class="simulation-notice">
                    🔒 <strong>Autopulse PRO:</strong> Al procesar, el plan PRO se activará en tu cuenta y base de datos por un
                    lapso de 1 hora.
                </div>

                <div class="form-group">
                    <label>Nombre del Tarjetahabiente</label>
                    <input id="pay-name" type="text" placeholder="Ej: Miguel Ángel" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Número de Tarjeta</label>
                    <input id="pay-card" type="text" maxlength="19" placeholder="4532 •••• •••• ••••"
                        oninput="formatCardNumber(this)">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Exp. (MM/AA)</label>
                        <input id="pay-date" type="text" maxlength="5" placeholder="MM/AA" oninput="formatExpiry(this)">
                    </div>
                    <div class="form-group">
                        <label>CVC / CVV</label>
                        <input id="pay-cvv" type="password" maxlength="4" placeholder="•••">
                    </div>
                </div>

                <button class="btn-pay" id="btnPay" onclick="processSimulatedPayment()">PAGAR Y ACTIVAR PRO</button>
            </div>

            <div id="step-success" class="success-screen <?= $isPro ? 'active' : '' ?>">
                <div class="success-icon">✓</div>
                <div class="success-title">¡PLAN PRO ACTIVADO CON ÉXITO!</div>
                <div class="success-text">Has desbloqueado todas las funciones premium por 1 hora.<br>Tiempo restante de
                    prueba:</div>
                <div id="countdown" class="timer-display"><?= gmdate("i:s", max(0, $remainingSeconds)) ?></div>
            </div>

        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const proChannel = new BroadcastChannel('autopulse_pro_stream');
        const IS_LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
        let serverRemaining = <?= (int)$remainingSeconds ?>;
        let countdownInterval = null;

        function showCheckout() {
            if (!IS_LOGGED_IN) {
                showToast('⚠️ Inicia sesión para activar tu plan PRO');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1200);
                return;
            }
            document.getElementById('step-plan').classList.add('hidden');
            document.getElementById('step-checkout').classList.add('active');
        }

        function formatCardNumber(input) {
            let v = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let matches = v.match(/\d{4,16}/g);
            let match = matches && matches[0] || '';
            let parts = [];
            for (let i = 0, len = match.length; i < len; i += 4) {
                parts.push(match.substring(i, i + 4));
            }
            input.value = parts.length > 0 ? parts.join(' ') : v;
        }

        function formatExpiry(input) {
            let v = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (v.length >= 2) {
                input.value = v.substring(0, 2) + '/' + v.substring(2, 4);
            } else {
                input.value = v;
            }
        }

        async function processSimulatedPayment() {
            const name = document.getElementById('pay-name').value.trim();
            const card = document.getElementById('pay-card').value.trim();
            const date = document.getElementById('pay-date').value.trim();
            const cvv = document.getElementById('pay-cvv').value.trim();
            const btn = document.getElementById('btnPay');

            if (!name || !card || !date || !cvv) {
                showToast('⚠️ Por favor completa todos los campos de pago.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'PROCESANDO...';

            try {
                const response = await fetch('api/suscripcion.php?action=activate_pro', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, card, date, cvv })
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('autopulse_pro_expiry', data.expiry);
                    proChannel.postMessage({ event: 'PRO_ACTIVATED', expiry: data.expiry });

                    document.getElementById('step-checkout').classList.remove('active');
                    document.getElementById('step-checkout').style.display = 'none';
                    document.getElementById('step-plan').classList.add('hidden');
                    document.getElementById('step-success').classList.add('active');

                    startVisualTimer(data.remaining_seconds || 3600);
                    showToast('✅ ¡Plan PRO activado con éxito!');
                } else {
                    showToast('❌ ' + (data.error || 'Error al procesar pago'));
                }
            } catch (err) {
                showToast('❌ Error de comunicación con la pasarela');
            } finally {
                btn.disabled = false;
                btn.innerText = 'PAGAR Y ACTIVAR PRO';
            }
        }

        function startVisualTimer(duration) {
            if (countdownInterval) clearInterval(countdownInterval);
            let timer = duration, minutes, seconds;
            const display = document.getElementById('countdown');

            countdownInterval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(countdownInterval);
                    localStorage.removeItem('autopulse_pro_expiry');
                    proChannel.postMessage({ event: 'PRO_EXPIRED' });
                    window.location.reload();
                }
            }, 1000);
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.innerText = msg;
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (serverRemaining > 0) {
                startVisualTimer(serverRemaining);
            } else {
                const savedExpiry = localStorage.getItem('autopulse_pro_expiry');
                if (savedExpiry) {
                    const remainingTime = Math.floor((parseInt(savedExpiry) - Date.now()) / 1000);
                    if (remainingTime > 0) {
                        document.getElementById('step-plan').classList.add('hidden');
                        document.getElementById('step-checkout').style.display = 'none';
                        document.getElementById('step-success').classList.add('active');
                        startVisualTimer(remainingTime);
                    } else {
                        localStorage.removeItem('autopulse_pro_expiry');
                    }
                }
            }
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
