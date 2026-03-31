/**
 * CleriVerse – Definições do Utilizador
 *
 * Responsável por:
 *  - Alternância entre tema escuro e claro (gravado em localStorage)
 *  - Solicitação e gravação das coordenadas geográficas (localStorage)
 */

'use strict';

/* ── Chaves de armazenamento ─────────────────────────────────────────────── */

const CV_THEME_KEY    = 'cleriverse_theme';
const CV_LOCATION_KEY = 'cleriverse_location';

/* ── Tema ────────────────────────────────────────────────────────────────── */

function cvApplyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(CV_THEME_KEY, theme);
    cvUpdateThemeButton(theme);
}

function cvUpdateThemeButton(theme) {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    if (theme === 'dark') {
        btn.setAttribute('title', 'Mudar para tema claro');
        btn.textContent = '☀️';
    } else {
        btn.setAttribute('title', 'Mudar para tema escuro');
        btn.textContent = '🌙';
    }
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') || 'dark';
    cvApplyTheme(current === 'dark' ? 'light' : 'dark');
}

/* ── Localização ─────────────────────────────────────────────────────────── */

function cvGetSavedLocation() {
    try {
        const raw = localStorage.getItem(CV_LOCATION_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function cvSaveLocation(lat, lon) {
    const data = { lat: parseFloat(lat), lon: parseFloat(lon), ts: Date.now() };
    localStorage.setItem(CV_LOCATION_KEY, JSON.stringify(data));
    cvUpdateLocationButton();
}

function cvUpdateLocationButton() {
    const btn = document.getElementById('locationBtn');
    if (!btn) return;
    const loc = cvGetSavedLocation();
    if (loc) {
        btn.setAttribute('title',
            'Localização guardada: ' + loc.lat.toFixed(4) + '°, ' + loc.lon.toFixed(4) + '°. Clique para alterar.');
        btn.classList.add('location-set');
    } else {
        btn.setAttribute('title', 'Definir localização para cálculos mais precisos');
        btn.classList.remove('location-set');
    }
}

function cvShowLocationModal() {
    const el = document.getElementById('locationModal');
    if (!el || typeof bootstrap === 'undefined') return;

    // Preencher campos com coordenadas guardadas, se existirem
    const loc = cvGetSavedLocation();
    const latEl = document.getElementById('locationLat');
    const lonEl = document.getElementById('locationLon');
    if (loc) {
        if (latEl) latEl.value = loc.lat;
        if (lonEl) lonEl.value = loc.lon;
    } else {
        if (latEl) latEl.value = '';
        if (lonEl) lonEl.value = '';
    }

    // Limpar mensagens de erro anteriores
    const autoErr = document.getElementById('locationAutoError');
    const formErr = document.getElementById('locationFormError');
    if (autoErr) { autoErr.textContent = ''; autoErr.classList.add('d-none'); }
    if (formErr) { formErr.textContent = ''; formErr.classList.add('d-none'); }

    bootstrap.Modal.getOrCreateInstance(el).show();
}

function cvRequestAutoLocation() {
    const btn     = document.getElementById('locationAutoBtn');
    const autoErr = document.getElementById('locationAutoError');

    if (!navigator.geolocation) {
        if (autoErr) {
            autoErr.textContent = 'O seu browser não suporta geolocalização.';
            autoErr.classList.remove('d-none');
        }
        return;
    }

    if (btn) { btn.disabled = true; btn.textContent = 'A obter localização…'; }
    if (autoErr) autoErr.classList.add('d-none');

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const lat = pos.coords.latitude.toFixed(6);
            const lon = pos.coords.longitude.toFixed(6);

            const latEl = document.getElementById('locationLat');
            const lonEl = document.getElementById('locationLon');
            if (latEl) latEl.value = lat;
            if (lonEl) lonEl.value = lon;

            if (btn) { btn.disabled = false; btn.textContent = '📡 Obter automaticamente'; }
        },
        function (err) {
            if (btn) { btn.disabled = false; btn.textContent = '📡 Obter automaticamente'; }

            let msg = 'Não foi possível obter a localização automaticamente.';
            if (err.code === 1)      msg = 'Permissão de localização negada pelo utilizador.';
            else if (err.code === 2) msg = 'Posição indisponível de momento.';
            else if (err.code === 3) msg = 'Tempo de espera excedido.';

            if (autoErr) {
                autoErr.textContent = msg;
                autoErr.classList.remove('d-none');
            }
        },
        { timeout: 10000, maximumAge: 60000 }
    );
}

function cvSaveLocationFromForm() {
    const latVal  = document.getElementById('locationLat').value.trim();
    const lonVal  = document.getElementById('locationLon').value.trim();
    const formErr = document.getElementById('locationFormError');

    const lat = parseFloat(latVal);
    const lon = parseFloat(lonVal);

    if (isNaN(lat) || lat < -90 || lat > 90) {
        if (formErr) {
            formErr.textContent = 'Latitude inválida. Introduza um valor entre -90 e 90.';
            formErr.classList.remove('d-none');
        }
        return;
    }
    if (isNaN(lon) || lon < -180 || lon > 180) {
        if (formErr) {
            formErr.textContent = 'Longitude inválida. Introduza um valor entre -180 e 180.';
            formErr.classList.remove('d-none');
        }
        return;
    }

    cvSaveLocation(lat, lon);

    const el = document.getElementById('locationModal');
    if (el) bootstrap.Modal.getInstance(el).hide();
}

/* ── Inicialização ───────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    // Sincronizar botão de tema com o valor guardado
    const savedTheme = localStorage.getItem(CV_THEME_KEY) || 'dark';
    cvUpdateThemeButton(savedTheme);

    // Sincronizar botão de localização
    cvUpdateLocationButton();

    // Abrir modal de localização ao clicar no botão da navbar
    const locationBtn = document.getElementById('locationBtn');
    if (locationBtn) {
        locationBtn.addEventListener('click', cvShowLocationModal);
    }

    // Solicitar localização automaticamente na primeira visita da sessão,
    // caso ainda não existam coordenadas guardadas
    if (!cvGetSavedLocation() && !sessionStorage.getItem('cv_loc_prompted')) {
        sessionStorage.setItem('cv_loc_prompted', '1');
        setTimeout(cvShowLocationModal, 800);
    }
});
