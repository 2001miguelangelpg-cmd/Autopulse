<?php
/**
 * Autopulse - Sistema de Gestión de Motos en Colombia
 * Vista: manuales.php (Biblioteca de Manuales Técnicos y de Usuario)
 * Matriz de acceso: Bloqueo PRO para cuentas gratuitas y acceso total para PRO.
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$isPro = isProUser($user);

$pageTitle         = 'Autopulse — Manuales Técnicos de Motocicletas';
$currentPage       = 'manuales';
$isAppLayout       = true;
$pageCss           = 'stylesmanuales.css';
$showFooter        = true;

require_once __DIR__ . '/includes/header.php';
?>

    <div class="container">

        <div class="section-header">
            <div class="section-st">Biblioteca de Manuales</div>
            <div id="proBadge" class="pro-status-badge <?= $isPro ? 'pro' : 'free' ?>">
                <?= $isPro ? 'PLAN PRO ACTIVO' : 'CUENTA GRATUITA' ?>
            </div>
        </div>

        <div class="search-wrapper">
            <input id="searchInput" type="text" class="search-input"
                placeholder="🔍 Buscar por marca o modelo (Ej: Honda, Yamaha...)" oninput="filterManuales()">
        </div>

        <div id="premiumLock" class="premium-lockout-box <?= $isPro ? '' : 'active' ?>">
            <div class="lock-icon">👑</div>
            <div class="lock-title">CONTENIDO EXCLUSIVO DE AUTOPULSE PRO</div>
            <div class="lock-text">La consulta en línea y descarga de diagramas eléctricos, manuales de usuario y
                manuales de taller estructurados de alta velocidad están limitados a usuarios Premium.</div>
            <a href="planes.php" style="display: inline-block; text-decoration: none;">
                <button class="btn-unlock">DESBLOQUEAR ACCESO PRO</button>
            </a>
        </div>

        <div id="resultsContainer" class="results-container <?= $isPro ? '' : 'hidden' ?>">
            <div id="manualsList"></div>
        </div>

    </div>

    <div id="viewModal" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-content">
            <div class="modal-header">
                <div id="modalHeaderTitle" class="modal-title-text">VISOR TÉCNICO AUTOPULSE</div>
                <button class="btn-close-modal" onclick="closeManualModal()">✕</button>
            </div>
            <div id="modalBodyContent" class="modal-body"></div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const proStream = new BroadcastChannel('autopulse_pro_stream');
        let isProUser = <?= $isPro ? 'true' : 'false' ?>;
        let manualsData = [];

        async function loadManuals() {
            try {
                const res = await fetch('api/manuales.php');
                const data = await res.json();
                if (data.success) {
                    manualsData = data.manuales || [];
                    if (data.is_pro !== undefined) {
                        isProUser = !!data.is_pro;
                    }
                    checkPremiumStatus();
                    filterManuales();
                }
            } catch (err) {
                console.error('Error al cargar manuales:', err);
            }
        }

        function checkPremiumStatus() {
            const badge = document.getElementById('proBadge');
            const lockBox = document.getElementById('premiumLock');
            const resultsContainer = document.getElementById('resultsContainer');

            const savedExpiry = localStorage.getItem('autopulse_pro_expiry');
            const isLocalPro = savedExpiry && parseInt(savedExpiry) > Date.now();
            const activePro = isProUser || isLocalPro;

            if (activePro) {
                badge.innerText = "PLAN PRO ACTIVO";
                badge.className = "pro-status-badge pro";
                lockBox.classList.remove('active');
                resultsContainer.classList.remove('hidden');
            } else {
                badge.innerText = "CUENTA GRATUITA";
                badge.className = "pro-status-badge free";
                lockBox.classList.add('active');
                resultsContainer.classList.add('hidden');
            }
        }

        function filterManuales() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const listContainer = document.getElementById('manualsList');
            listContainer.innerHTML = '';

            const filtered = manualsData.filter(m =>
                (m.modelo && m.modelo.toLowerCase().includes(query)) ||
                (m.tipo && m.tipo.toLowerCase().includes(query)) ||
                (m.marca && m.marca.toLowerCase().includes(query))
            );

            if (filtered.length === 0) {
                listContainer.innerHTML = `<div class="empty-search-state">No se encontraron manuales que coincidan con tu búsqueda.</div>`;
                return;
            }

            filtered.forEach((m, index) => {
                const card = document.createElement('div');
                card.className = 'manual-card';
                card.innerHTML = `
                    <div class="manual-info">
                        <div class="manual-title">${m.modelo}</div>
                        <div style="font-size: 12px; color: var(--blue); font-weight:600; margin-top:2px;">${m.tipo}</div>
                        <div class="manual-meta">
                            <span>💾 ${m.size}</span>
                            <span>📄 ${m.paginas} Pág.</span>
                        </div>
                    </div>
                    <div class="actions-wrapper">
                        <button class="btn-action view" onclick="openManualViewer(${index})">👁️ Ver</button>
                        <button class="btn-action download" onclick="executeManualDownload(${index})">📥 Guardar</button>
                    </div>
                `;
                listContainer.appendChild(card);
            });
        }

        function openManualViewer(index) {
            const manual = manualsData[index];
            if (!manual) return;

            const modal = document.getElementById('viewModal');
            const bodyContent = document.getElementById('modalBodyContent');
            document.getElementById('modalHeaderTitle').innerText = `${manual.modelo.toUpperCase()}`;

            modal.classList.add('open');
            bodyContent.innerHTML = `
                <div class="loader-container">
                    <div style="font-family: var(--font-title); font-size:12px; font-weight:700; color:var(--blue);">DECODIFICANDO FICHERO SEGURO...</div>
                    <div style="font-size:11px; color:var(--gray); margin-top:4px;">Tamaño: ${manual.size} | Fichero en base de datos</div>
                    <div class="progress-bar-bg">
                        <div id="modalProgressBar" class="progress-bar-fill"></div>
                    </div>
                </div>
            `;

            let progress = 0;
            const bar = document.getElementById('modalProgressBar');
            const interval = setInterval(() => {
                progress += 5;
                if (bar) bar.style.width = `${progress}%`;

                if (progress >= 100) {
                    clearInterval(interval);
                    bodyContent.innerHTML = `
                        <div style="margin-bottom:12px;">
                            <span style="background:rgba(0,119,255,0.1); color:var(--blue); padding:4px 8px; font-size:11px; font-weight:700; border-radius:4px; text-transform:uppercase;">
                                ${manual.tipo}
                            </span>
                        </div>
                        <p style="margin-bottom:14px; font-size:13px; color:var(--gray);">
                            Visualizando la sección de tolerancias mecánicas y calibración de fábrica para <strong>${manual.modelo}</strong> (${manual.paginas} páginas indexadas).
                        </p>
                        <div style="font-size:11px; font-weight:700; color:var(--green); text-transform:uppercase; margin-bottom:4px; font-family: var(--font-title);">
                            Ficha Técnico de Datos y Ajustes:
                        </div>
                        <div class="blueprint-box">${manual.esquema || 'Información protegida'}</div>
                        <div style="margin-top:20px; text-align:right;">
                            <button class="btn-action download" style="width:100%; padding:11px;" onclick="executeManualDownload(${index})">📥 Descargar Fichero Completo (.TXT)</button>
                        </div>
                    `;
                }
            }, 35);
        }

        function executeManualDownload(index) {
            const manual = manualsData[index];
            if (!manual) return;

            showToast(`📦 Compilando descarga de: ${manual.modelo}...`, 'success');

            setTimeout(() => {
                const contenidoDelArchivo = `========================================================\n` +
                    `    AUTOPULSE PRO DIAGNOSTICS - MANUAL DE ESPECIFICACIONES\n` +
                    `========================================================\n\n` +
                    `Modelo: ${manual.modelo}\n` +
                    `Tipo de Documento: ${manual.tipo}\n` +
                    `Peso Estimado original: ${manual.size}\n` +
                    `Total páginas indexadas: ${manual.paginas}\n\n` +
                    `--------------------------------------------------------\n` +
                    `ESQUEMAS Y DATOS TÉCNICOS EXTRAÍDOS DE FÁBRICA:\n` +
                    `--------------------------------------------------------\n` +
                    `${manual.esquema || 'Información de taller'}\n\n` +
                    `========================================================\n` +
                    ` Fichero descargado mediante Autopulse PRO en Colombia \n` +
                    `========================================================`;

                const blob = new Blob([contenidoDelArchivo], { type: "text/plain;charset=utf-8" });
                const url = URL.createObjectURL(blob);

                const elementoEnlace = document.createElement('a');
                elementoEnlace.href = url;
                elementoEnlace.download = `Manual_${manual.modelo.replace(/\s+/g, '_')}_AutopulsePRO.txt`;
                document.body.appendChild(elementoEnlace);
                elementoEnlace.click();

                document.body.removeChild(elementoEnlace);
                URL.revokeObjectURL(url);

                showToast(`✅ ¡Archivo guardado con éxito!`, 'success');
            }, 600);
        }

        function closeManualModal() {
            document.getElementById('viewModal').classList.remove('open');
        }

        function closeModalOnOverlay(e) {
            if (e.target === document.getElementById('viewModal')) {
                closeManualModal();
            }
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.className = `toast show ${type}`;
            setTimeout(() => { t.classList.remove('show'); }, 3000);
        }

        proStream.onmessage = (e) => {
            if (e.data.event === 'PRO_ACTIVATED') {
                showToast('👑 ¡Acceso a Manuales PRO desbloqueado!', 'success');
                isProUser = true;
                checkPremiumStatus();
                loadManuals();
            }
            if (e.data.event === 'PRO_EXPIRED') {
                closeManualModal();
                showToast('⚠️ Tu acceso PRO ha expirado.', 'error');
                isProUser = false;
                checkPremiumStatus();
            }
        };

        window.onload = function () {
            loadManuals();
            setInterval(checkPremiumStatus, 4000);
        };
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
