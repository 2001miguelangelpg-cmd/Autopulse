<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: registro.php (Registro de Nuevos Usuarios)
 */

require_once __DIR__ . '/includes/auth.php';
requireVisitor();

$pageTitle         = 'REGISTRARSE - Autopulse';
$pageDescription   = 'Crea tu cuenta en Autopulse y gestiona el mantenimiento de tu moto.';
$currentPage       = 'registro';
$isAppLayout       = false;
$pageCss           = 'estilos.css';
$showFooter        = true;
$showWhatsapp      = true;

require_once __DIR__ . '/includes/header.php';
?>

    <main class="register-container">
        <div class="register-logo-slot" style="text-align: center; margin-bottom: 20px;">
            <a href="index.php" style="text-decoration: none; display: inline-block;">
                <img src="assets/img/logo-autopulse.png" alt="Autopulse Logo" style="height: 55px; width: auto; display: block; margin: 0 auto; object-fit: contain;">
            </a>
        </div>

        <h1 class="title">REGISTRARSE</h1>

        <!-- Mensaje de estado/error -->
        <div id="alertBox" class="alert-box"></div>

        <form id="registerForm" onsubmit="handleRegister(event)">
            <div class="form-group">
                <label for="username">Nombre de usuario</label>
                <input type="text" id="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="firstname">Nombre</label>
                <input type="text" id="firstname" required>
            </div>

            <div class="form-group">
                <label for="lastname">Apellidos</label>
                <input type="text" id="lastname" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail Address</label>
                <input type="email" id="email" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña</label>
                <input type="password" id="confirmPassword" placeholder="Confirmar Contraseña" required
                    autocomplete="new-password">
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">Registro</button>
        </form>

        <div class="form-footer">
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            <button type="button" class="btn-demo" onclick="createDemoAccount()">Cargar Perfil Demo de Prueba</button>
        </div>
    </main>

    <script>
        // Mostrar mensajes de alerta
        function showAlert(msg, isSuccess = false) {
            const alertBox = document.getElementById('alertBox');
            alertBox.innerText = msg;
            alertBox.className = isSuccess ? 'alert-box success show' : 'alert-box error show';
        }

        // Manejar el registro de nuevos usuarios persistente en SQLite
        async function handleRegister(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const email = document.getElementById('email').value.trim().toLowerCase();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const btn = document.getElementById('btnSubmit');

            if (password !== confirmPassword) {
                showAlert('Las contraseñas no coinciden');
                return;
            }

            btn.disabled = true;

            try {
                const response = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username,
                        nombre: firstname + ' ' + lastname,
                        email,
                        password,
                        confirmPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('autopulse_logged_user', JSON.stringify(data.user));
                    showAlert('¡Registro exitoso! Iniciando tu garaje virtual...', true);

                    setTimeout(() => {
                        window.location.href = 'home.php';
                    }, 1200);
                } else {
                    showAlert(data.error || 'Error al procesar el registro');
                }
            } catch (err) {
                showAlert('Error de conexión con el servidor');
            } finally {
                btn.disabled = false;
            }
        }

        // Crear perfil determinado (Demo) para pruebas rápidas
        async function createDemoAccount() {
            try {
                const response = await fetch('api/auth.php?action=demo', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('autopulse_logged_user', JSON.stringify(data.user));
                    showAlert('Perfil Demo cargado (Usuario: motero_demo | Clave: 123). Redirigiendo...', true);
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
