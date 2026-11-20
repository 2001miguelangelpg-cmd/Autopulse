<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: login.php (Acceso / Iniciar sesión)
 */

require_once __DIR__ . '/includes/auth.php';
requireVisitor();

$pageTitle         = 'ACCESO - Autopulse';
$pageDescription   = 'Inicia sesión en tu cuenta de Autopulse.';
$currentPage       = 'login';
$isAppLayout       = false;
$pageCss           = 'estilos.css';
$showFooter        = true;
$showWhatsapp      = true;

require_once __DIR__ . '/includes/header.php';
?>

    <!-- FORMULARIO DE ACCESO -->
    <main class="login-container">
        <div class="login-logo-slot" style="text-align: center; margin-bottom: 25px;">
            <a href="index.php" style="text-decoration: none; display: inline-block;">
                <img src="assets/img/logo-autopulse.png" alt="Autopulse Logo" style="height: 55px; width: auto; display: block; margin: 0 auto; object-fit: contain;">
            </a>
        </div>

        <!-- Mensaje de estado/error -->
        <div id="alertBox" class="alert-box"></div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="userOrEmail">Username or E-mail</label>
                <input type="text" id="userOrEmail" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" required autocomplete="current-password">
            </div>

            <div class="button-group">
                <button type="submit" id="submitBtn" class="btn-login">Iniciar sesión</button>
                <a href="registro.php" class="btn-register">REGISTRARSE</a>
            </div>
        </form>

        <div class="form-footer">
            <button type="button" class="btn-demo" onclick="loginAsDemo()">Iniciar con Perfil Demo</button>
        </div>
    </main>

    <script>
        // Mostrar mensajes de alerta
        function showAlert(msg, isSuccess = false) {
            const alertBox = document.getElementById('alertBox');
            alertBox.innerText = msg;
            alertBox.className = isSuccess ? 'alert-box success show' : 'alert-box error show';
        }

        // Proceso de inicio de sesión persistente con PHP y SQLite
        async function handleLogin(e) {
            e.preventDefault();

            const userOrEmail = document.getElementById('userOrEmail').value.trim();
            const password = document.getElementById('password').value;
            const btn = document.getElementById('submitBtn');

            if (!userOrEmail || !password) {
                showAlert('Por favor completa todos los campos.');
                return;
            }

            btn.disabled = true;

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userOrEmail, password })
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('autopulse_logged_user', JSON.stringify(data.user));
                    showAlert(`¡Bienvenido de nuevo, ${data.user.nombre || data.user.username}! Redirigiendo...`, true);

                    setTimeout(() => {
                        window.location.href = 'home.php';
                    }, 1000);
                } else {
                    showAlert(data.error || 'Usuario/correo o contraseña incorrectos');
                }
            } catch (err) {
                showAlert('Error de conexión con el servidor');
            } finally {
                btn.disabled = false;
            }
        }

        // Acceso rápido con perfil de prueba (Demo)
        async function loginAsDemo() {
            try {
                const response = await fetch('api/auth.php?action=demo', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('autopulse_logged_user', JSON.stringify(data.user));
                    showAlert('Iniciando sesión como Usuario Demo...', true);

                    setTimeout(() => {
                        window.location.href = 'home.php';
                    }, 1000);
                } else {
                    showAlert('Error al cargar perfil demo');
                }
            } catch (err) {
                showAlert('Error de conexión');
            }
        }
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

