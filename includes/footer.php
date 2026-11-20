<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Componente: includes/footer.php
 * Pie de página modular con estilos originales, botón WhatsApp opcional y logout global.
 */

$showFooter   = $showFooter ?? true;
$showWhatsapp = $showWhatsapp ?? false;
$footerText   = $footerText ?? ($isAppLayout
    ? 'Derechos de autor © 2026 Autopulse | Diseñado para Modelos de Negocios Digitales - Unilatina'
    : 'Derechos de autor © 2027 Autopulse | Desarrollado por Miguel Angel Porras');
?>

<?php if ($showWhatsapp): ?>
    <!-- BOTÓN FLOTANTE WHATSAPP -->
    <a href="https://wa.me/573000000000" class="whatsapp-float" target="_blank" rel="noopener noreferrer"
        aria-label="Contacto por WhatsApp">
        <svg viewBox="0 0 32 32">
            <path
                d="M16 0c-8.837 0-16 7.163-16 16 0 2.825 0.737 5.607 2.137 8.048l-2.137 7.952 8.134-2.132c2.378 1.298 5.068 1.98 7.866 1.98 8.837 0 16-7.163 16-16s-7.163-16-16-16zM16 29.35c-2.48 0-4.908-0.67-7.027-1.933l-0.504-0.301-5.221 1.368 1.393-5.184-0.331-0.526c-1.385-2.203-2.115-4.755-2.115-7.374 0-7.614 6.191-13.805 13.805-13.805s13.805 6.191 13.805 13.805-6.191 13.805-13.805 13.805zM22.511 19.167c-0.357-0.179-2.112-1.042-2.439-1.161s-0.565-0.179-0.803 0.179c-0.238 0.357-0.923 1.161-1.131 1.4-0.208 0.238-0.417 0.268-0.774 0.089s-1.513-0.558-2.882-1.778c-1.066-0.95-1.786-2.123-1.994-2.48-0.208-0.357-0.022-0.55 0.156-0.728 0.161-0.16 0.357-0.417 0.536-0.625 0.179-0.208 0.238-0.357 0.357-0.595 0.119-0.238 0.060-0.446-0.030-0.625s-0.803-1.935-1.101-2.649c-0.29-0.695-0.585-0.601-0.803-0.612s-0.446-0.012-0.685-0.012c-0.238 0-0.625 0.089-0.952 0.446s-1.25 1.221-1.25 2.977c0 1.756 1.279 3.453 1.458 3.691s2.519 3.847 6.103 5.394c0.852 0.368 1.518 0.588 2.037 0.753 0.856 0.272 1.636 0.234 2.252 0.142 0.687-0.103 2.112-0.863 2.41-1.697 0.298-0.833 0.298-1.548 0.208-1.697-0.089-0.149-0.327-0.238-0.685-0.417z">
            </path>
        </svg>
    </a>
<?php endif; ?>

<?php if ($showFooter): ?>
    <!-- PIE DE PÁGINA -->
    <footer>
        <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 20px; text-align: center;">
            <p style="margin: 0; color: var(--gray, #B0B0B0); font-size: 13px;">
                <?= htmlspecialchars($footerText) ?>
            </p>
        </div>
    </footer>
<?php endif; ?>

<!-- Script Global de Logout -->
<script>
function logout() {
    fetch('api/auth.php?action=logout', { method: 'POST' })
        .then(() => {
            localStorage.removeItem('autopulse_logged_user');
            localStorage.removeItem('autopulse_pro_expiry');
            window.location.href = 'index.php';
        })
        .catch(() => {
            window.location.href = 'index.php';
        });
}
</script>

</body>

</html>
