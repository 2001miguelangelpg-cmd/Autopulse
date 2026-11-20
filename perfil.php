<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: perfil.php (Gestión de Perfil, Seguridad y Suscripción)
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$isPro = isProUser($user);
$remainingSeconds = getProRemainingSeconds($user);

$pdo = getDB();
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM user_garage_db WHERE usuario_id = ?");
$stmtCount->execute([(int)$user['id']]);
$totalMotos = (int)$stmtCount->fetchColumn();

$pageTitle         = 'Mi Perfil — Autopulse';
$currentPage       = 'perfil';
$isAppLayout       = true;
$pageCss           = 'styles.css';
$showFooter        = true;

require_once __DIR__ . '/includes/header.php';
?>

    <div class="container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">

        <!-- ENCABEZADO DE PERFIL -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 25px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 65px; height: 65px; border-radius: 50%; background: #111; border: 2px solid var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-family: var(--font-title); font-size: 26px; font-weight: bold;">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($user['nombre'] ?? 'U', 0, 1))) ?>
                </div>
                <div>
                    <h1 style="font-size: 22px; margin: 0; color: #fff; font-family: var(--font-title);">
                        <?= htmlspecialchars($user['nombre']) ?>
                    </h1>
                    <div style="color: var(--gray); font-size: 13px; margin-top: 4px;">
                        <?= htmlspecialchars($user['email']) ?> &bull; Usuario: @<?= htmlspecialchars($user['username'] ?? 'motero') ?>
                    </div>
                </div>
            </div>
            <div>
                <?php if ($isPro): ?>
                    <span style="background: rgba(243, 156, 18, 0.15); border: 1px solid rgba(243, 156, 18, 0.4); color: #f39c12; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; font-family: var(--font-title);">
                        👑 AUTOPULSE PRO ACTIVO
                    </span>
                <?php else: ?>
                    <span style="background: #1A1A1A; border: 1px solid var(--border); color: var(--gray); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; font-family: var(--font-title);">
                        CUENTA GRATUITA
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px;">

            <!-- FORMULARIO 1: INFORMACIÓN PERSONAL -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 25px;">
                <h3 style="color: var(--blue); font-size: 16px; margin-bottom: 20px; font-family: var(--font-title);">
                    👤 DATOS PERSONALES
                </h3>

                <form id="profileForm" onsubmit="handleUpdateProfile(event)">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Nombre Completo *</label>
                        <input id="p-name" type="text" required value="<?= htmlspecialchars($user['nombre']) ?>"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Correo Electrónico *</label>
                        <input id="p-email" type="email" required value="<?= htmlspecialchars($user['email']) ?>"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Teléfono / WhatsApp</label>
                        <input id="p-phone" type="text" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" placeholder="+57 300 123 4567"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Ciudad / Municipio</label>
                        <input id="p-city" type="text" value="<?= htmlspecialchars($user['ciudad'] ?? '') ?>" placeholder="Medellín, Cali, Bogotá..."
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <button type="submit" id="btnSaveProfile" class="btn" style="width: 100%; padding: 12px; font-size: 13px;">
                        GUARDAR CAMBIOS
                    </button>
                </form>
            </div>

            <!-- FORMULARIO 2: CAMBIO DE CONTRASEÑA -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 25px;">
                <h3 style="color: var(--blue); font-size: 16px; margin-bottom: 20px; font-family: var(--font-title);">
                    🔒 SEGURIDAD & CONTRASEÑA
                </h3>

                <form id="passwordForm" onsubmit="handleChangePassword(event)">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Contraseña Actual *</label>
                        <input id="p-curr-pass" type="password" required placeholder="••••••••"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Nueva Contraseña *</label>
                        <input id="p-new-pass" type="password" required placeholder="••••••••"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase;">Confirmar Nueva Contraseña *</label>
                        <input id="p-conf-pass" type="password" required placeholder="••••••••"
                               style="width: 100%; padding: 10px 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <button type="submit" id="btnSavePass" class="btn" style="width: 100%; padding: 12px; font-size: 13px; background-color: var(--surface2); border: 1px solid var(--border);">
                        ACTUALIZAR CONTRASEÑA
                    </button>
                </form>
            </div>

            <!-- TARJETA 3: ESTADO DE SUSCRIPCIÓN -->
            <div style="background: var(--surface); border: 2px solid <?= $isPro ? 'var(--green)' : 'var(--border)' ?>; border-radius: 12px; padding: 25px; grid-column: 1 / -1;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="color: <?= $isPro ? 'var(--green)' : 'var(--text)' ?>; font-size: 18px; margin: 0; font-family: var(--font-title);">
                            <?= $isPro ? '👑 ESTADO: AUTOPULSE PRO HABILITADO' : '🛡️ PLAN ACTUAL: CUENTA GRATUITA' ?>
                        </h3>
                        <p style="color: var(--gray); font-size: 13px; margin-top: 6px; line-height: 1.6;">
                            <?= $isPro
                                ? 'Cuentas con monitoreo ilimitado de motos y acceso a toda la biblioteca de manuales técnicos.'
                                : 'Tu cuenta actual permite registrar un máximo de 1 motocicleta y acceso limitado.' ?>
                        </p>
                    </div>

                    <div>
                        <?php if ($isPro): ?>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; color: var(--gray); text-transform: uppercase; font-weight: bold;">Tiempo Restante:</div>
                                <div id="profileTimer" style="font-family: var(--font-title); font-size: 24px; color: var(--green); font-weight: bold;">
                                    <?= gmdate("i:s", max(0, $remainingSeconds)) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="planes.php" class="btn" style="text-decoration: none; padding: 10px 20px; font-size: 13px;">
                                MEJORAR A PLAN PRO ($4.900)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="font-size: 13px; color: var(--gray);">
                        Motos monitoreadas en tu garaje: <strong style="color: #fff;"><?= $totalMotos ?></strong> <?= $isPro ? '(Sin límites)' : '(Límite: 1)' ?>
                    </div>
                    <a href="garaje.php" style="color: var(--blue); text-decoration: none; font-size: 13px; font-weight: bold;">
                        Ir a Mi Garaje &rarr;
                    </a>
                </div>
            </div>

        </div>

    </div>

    <div id="toast" class="toast"></div>

    <script>
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

        async function handleUpdateProfile(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveProfile');
            btn.disabled = true;

            const payload = {
                nombre: document.getElementById('p-name').value.trim(),
                email: document.getElementById('p-email').value.trim(),
                telefono: document.getElementById('p-phone').value.trim(),
                ciudad: document.getElementById('p-city').value.trim()
            };

            try {
                const res = await fetch('api/perfil.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast('✅ ' + data.message);
                } else {
                    showToast('❌ ' + (data.error || 'Error al actualizar'), true);
                }
            } catch (err) {
                showToast('❌ Error de comunicación con el servidor', true);
            } finally {
                btn.disabled = false;
            }
        }

        async function handleChangePassword(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSavePass');

            const current_password = document.getElementById('p-curr-pass').value;
            const new_password = document.getElementById('p-new-pass').value;
            const confirm_password = document.getElementById('p-conf-pass').value;

            if (new_password !== confirm_password) {
                showToast('❌ Las contraseñas nuevas no coinciden', true);
                return;
            }

            btn.disabled = true;

            try {
                const res = await fetch('api/perfil.php?action=change_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ current_password, new_password, confirm_password })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('✅ ' + data.message);
                    document.getElementById('passwordForm').reset();
                } else {
                    showToast('❌ ' + (data.error || 'Error al cambiar contraseña'), true);
                }
            } catch (err) {
                showToast('❌ Error de conexión', true);
            } finally {
                btn.disabled = false;
            }
        }

        <?php if ($isPro): ?>
        let remainingSeconds = <?= $remainingSeconds ?>;
        function updateProfileTimer() {
            const display = document.getElementById('profileTimer');
            if (!display) return;

            if (remainingSeconds <= 0) {
                display.textContent = "00:00";
                return;
            }

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            display.textContent = (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
            remainingSeconds--;
        }
        setInterval(updateProfileTimer, 1000);
        <?php endif; ?>
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
