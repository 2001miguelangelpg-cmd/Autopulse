<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Componente: includes/logo.php
 * Renderizador del logotipo oficial Autopulse.
 */

function renderAutopulseLogo(string $size = 'default', bool $withText = true): void {
    $height = ($size === 'large' ? '65px' : ($size === 'small' ? '36px' : '46px'));
    $margin = ($size === 'large' ? 'margin: 0 auto;' : '');
    ?>
    <img src="assets/img/logo-autopulse.png" alt="Autopulse Logo" class="logo-img" style="height: <?= $height ?>; width: auto; display: block; <?= $margin ?> object-fit: contain;">
    <?php
}
