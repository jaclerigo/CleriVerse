/**
 * CleriVerse – Mercury Phases Interactive JS
 *
 * Responsável por:
 *  - Actualizar o painel de detalhe quando o utilizador clica num dia
 *  - Redesenhar a fase de Mercúrio em SVG (mesmo algoritmo do lado PHP)
 */

'use strict';

const CV_LOCATION_STORAGE_KEY = 'cleriverse_location';
const VERNAL_EQUINOX_APPROX_DAY = 81;

/* ── Desenhador de fases SVG ──────────────────────────────────────────────── */

/**
 * Gera o SVG da fase de Mercúrio (espelho do renderPhaseSvg do PHP).
 *
 * @param {number}  illumination  Fração iluminada (0–1)
 * @param {boolean} isEastern     true = Este (Tarde), false = Oeste (Manhã)
 * @param {number}  size          Tamanho em píxeis
 * @returns {string} Markup SVG
 */
function renderPhaseSvg(illumination, isEastern, size) {
    size = size || 160;
    const r  = (size / 2) - 2;
    const cx = size / 2;
    const cy = size / 2;

    const ns     = 'http://www.w3.org/2000/svg';
    const svg    = document.createElementNS(ns, 'svg');
    svg.setAttribute('width',   size);
    svg.setAttribute('height',  size);
    svg.setAttribute('viewBox', `0 0 ${size} ${size}`);

    // Fundo (disco escuro)
    const disk = document.createElementNS(ns, 'circle');
    disk.setAttribute('cx', cx);
    disk.setAttribute('cy', cy);
    disk.setAttribute('r',  r);
    disk.setAttribute('fill',         '#111827');
    disk.setAttribute('stroke',       '#374151');
    disk.setAttribute('stroke-width', '1');
    svg.appendChild(disk);

    if (illumination >= 0.99) {
        // Fase cheia
        const full = document.createElementNS(ns, 'circle');
        full.setAttribute('cx',   cx);
        full.setAttribute('cy',   cy);
        full.setAttribute('r',    r);
        full.setAttribute('fill', '#e8a87c');
        svg.appendChild(full);
        return svg;
    }

    if (illumination < 0.01) {
        return svg; // Fase nova – apenas o disco escuro
    }

    // Eixo menor do terminador
    const b    = r * Math.abs(1 - 2 * illumination);
    const topX = cx;
    const topY = cy - r;
    const botX = cx;
    const botY = cy + r;

    let limbSweep, termSweep;
    if (isEastern) {
        limbSweep = 1;
        termSweep = illumination >= 0.5 ? 0 : 1;
    } else {
        limbSweep = 0;
        termSweep = illumination >= 0.5 ? 1 : 0;
    }

    const d = [
        `M ${topX},${topY}`,
        `A ${r},${r} 0 0 ${limbSweep} ${botX},${botY}`,
        `A ${b},${r} 0 0 ${termSweep} ${topX},${topY}`,
        'Z',
    ].join(' ');

    const lit = document.createElementNS(ns, 'path');
    lit.setAttribute('d',    d);
    lit.setAttribute('fill', '#e8a87c');
    svg.appendChild(lit);

    return svg;
}

/* ── Actualização do painel de detalhe ────────────────────────────────────── *//**
 * Chamada quando o utilizador clica numa célula do calendário.
 * Actualiza o painel de detalhe sem recarregar a página.
 *
 * @param {HTMLElement} cell  A célula clicada
 */
function selectDay(cell) {
    const day         = parseInt(cell.dataset.day, 10);
    const year        = parseInt(cell.dataset.year, 10);
    const month       = parseInt(cell.dataset.month, 10);
    const illumination = parseFloat(cell.dataset.illumination);
    const isEastern   = cell.dataset.isEastern === '1';

    // Highlight
    document.querySelectorAll('.cal-cell.cal-selected').forEach(function (c) {
        c.classList.remove('cal-selected');
    });
    cell.classList.add('cal-selected');

    // Actualizar texto do dia
    const monthName = MONTH_NAMES[month - 1];
    const detailDay = document.getElementById('detailDay');
    if (detailDay) {
        detailDay.textContent = `${day} de ${monthName} de ${year}`;
    }

    // Actualizar estatísticas
    setText('dPhaseName',   cell.dataset.phaseName);
    setText('dIllum',       cell.dataset.illuminationPct + '%');
    setText('dPhaseAngle',  cell.dataset.phaseAngle + '°');
    setText('dElongation',  cell.dataset.elongation + '° ' + cell.dataset.direction);
    setText('dStarType',    cell.dataset.starType);
    setText('dDist',        cell.dataset.dist + ' AU');
    updateDetailMaxAltitude(cell);

    // Actualizar SVG grande
    const container = document.getElementById('detailPhaseSvg');
    if (container) {
        container.innerHTML = '';
        const svgEl = renderPhaseSvg(illumination, isEastern, 160);
        container.appendChild(svgEl);
    }
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function getSavedLatitude() {
    try {
        const raw = localStorage.getItem(CV_LOCATION_STORAGE_KEY);
        if (!raw) return 0;
        const location = JSON.parse(raw);
        const latitude = Number(location?.lat);
        return Number.isFinite(latitude) ? latitude : 0;
    } catch (e) {
        return 0;
    }
}

function estimateMaxVisibilityAltitude(elongation, year, month, day, latitude) {
    const dayOfYear = getDayOfYearUtc(year, month, day);
    const daysInYear = isLeapYear(year) ? 366 : 365;
    const sunDeclination = estimateSunDeclination(dayOfYear, daysInYear);
    const eclipticAngle = clamp(90 - Math.abs(latitude - sunDeclination), 0, 90);
    const elongationRad = degToRad(elongation);
    const eclipticAngleRad = degToRad(eclipticAngle);
    return clamp(radToDeg(Math.asin(Math.sin(elongationRad) * Math.sin(eclipticAngleRad))), 0, 90);
}

function getDayOfYearUtc(year, month, day) {
    const start = Date.UTC(year, 0, 1);
    const current = Date.UTC(year, month - 1, day);
    return Math.floor((current - start) / 86400000) + 1;
}

function estimateSunDeclination(dayOfYear, daysInYear) {
    return 23.44 * Math.sin(
        degToRad((360 / daysInYear) * (dayOfYear - VERNAL_EQUINOX_APPROX_DAY))
    );
}

function isLeapYear(year) {
    return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
}

function degToRad(value) {
    return value * (Math.PI / 180);
}

function radToDeg(value) {
    return value * (180 / Math.PI);
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function updateDetailMaxAltitude(cell) {
    const latitude = getSavedLatitude();
    const day = parseInt(cell.dataset.day, 10);
    const year = parseInt(cell.dataset.year, 10);
    const month = parseInt(cell.dataset.month, 10);
    const isEastern = cell.dataset.isEastern === '1';
    const maxAltitude = estimateMaxVisibilityAltitude(
        parseFloat(cell.dataset.elongation),
        year,
        month,
        day,
        latitude
    );
    const visibilityWindow = isEastern ? 'após o pôr do Sol' : 'antes do nascer do Sol';
    setText('dMaxAlt', maxAltitude.toFixed(1) + '° ' + visibilityWindow);
}

/* ── Inicialização ────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    // Scroll para a célula seleccionada se estiver fora do viewport
    const selected = document.querySelector('.cal-cell.cal-selected');
    if (selected) {
        updateDetailMaxAltitude(selected);
        selected.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
});
