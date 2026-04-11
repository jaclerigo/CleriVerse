/**
 * CleriVerse – Mercury Phases Interactive JS
 *
 * Responsável por:
 *  - Actualizar o painel de detalhe quando o utilizador clica num dia
 *  - Redesenhar a fase de Mercúrio em SVG (mesmo algoritmo do lado PHP)
 */

'use strict';

const CV_LOCATION_STORAGE_KEY = 'cleriverse_location';
const EARTH_AXIAL_TILT_DEG = 23.44;
const VERNAL_EQUINOX_APPROX_DAY = 81;
const STANDARD_ALTITUDE_DEG = -0.5667;
const SIDEREAL_RATE_DEG_PER_DAY = 360.98564736629;
const GST0_BASE_DEG = 100.46061837;
const GST0_LINEAR_DEG = 36000.770053608;
const GST0_QUADRATIC_DEG = 0.000387933;
const GST0_CUBIC_DIVISOR = 38710000;
const JULIAN_DAY_J2000 = 2451545.0;
const NEVER_RISES_THRESHOLD = 1;
const ALWAYS_VISIBLE_THRESHOLD = -1;
const MINUTES_PER_DAY = 1440;

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
    setText('dMagnitude',   cell.dataset.magnitude);
    setText('dDist',        cell.dataset.dist + ' AU');
    updateDetailMaxAltitude(cell);
    updateDetailObservationTimes(cell);

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

function getSavedLocation() {
    try {
        const raw = localStorage.getItem(CV_LOCATION_STORAGE_KEY);
        if (!raw) return { lat: 0, lon: 0 };
        const location = JSON.parse(raw);
        const latitude = Number(location?.lat);
        const longitude = Number(location?.lon);
        return {
            lat: Number.isFinite(latitude) ? latitude : 0,
            lon: Number.isFinite(longitude) ? longitude : 0,
        };
    } catch (e) {
        return { lat: 0, lon: 0 };
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
    return EARTH_AXIAL_TILT_DEG * Math.sin(
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
    const location = getSavedLocation();
    const day = parseInt(cell.dataset.day, 10);
    const year = parseInt(cell.dataset.year, 10);
    const month = parseInt(cell.dataset.month, 10);
    const isEastern = cell.dataset.isEastern === '1';
    const fallbackMaxAltitude = Number.parseFloat(cell.dataset.maxAltitude);
    const estimatedMaxAltitude = estimateMaxVisibilityAltitude(
        Math.abs(parseFloat(cell.dataset.elongation)),
        year,
        month,
        day,
        location.lat
    );
    const maxAltitude = Number.isFinite(estimatedMaxAltitude)
        ? estimatedMaxAltitude
        : (Number.isFinite(fallbackMaxAltitude) ? fallbackMaxAltitude : 0);
    const visibilityWindow = cell.dataset.visibilityWindow || (isEastern ? 'após o pôr do Sol' : 'antes do nascer do Sol');
    setText('dMaxAlt', maxAltitude.toFixed(1) + '° ' + visibilityWindow);
}

function updateDetailObservationTimes(cell) {
    const location = getSavedLocation();
    const year = parseInt(cell.dataset.year, 10);
    const month = parseInt(cell.dataset.month, 10);
    const day = parseInt(cell.dataset.day, 10);
    const rightAscension = parseFloat(cell.dataset.rightAscension);
    const declination = parseFloat(cell.dataset.declination);

    if (!Number.isFinite(rightAscension) || !Number.isFinite(declination)) {
        setText('dAzimuth', '--');
        setText('dRiseTime', '--');
        setText('dTransitTime', '--');
        setText('dSetTime', '--');
        return;
    }

    const events = calculateRiseTransitSet(
        year,
        month,
        day,
        rightAscension,
        declination,
        location.lat,
        location.lon
    );

    setText('dAzimuth', events.riseAzimuth);
    setText('dRiseTime', events.riseTime);
    setText('dTransitTime', events.transitTime);
    setText('dSetTime', events.setTime);
}

function calculateRiseTransitSet(year, month, day, rightAscensionDeg, declinationDeg, latitudeDeg, longitudeDeg) {
    const latitude = degToRad(latitudeDeg);
    const declination = degToRad(declinationDeg);
    const altitude = degToRad(STANDARD_ALTITUDE_DEG);
    const cosH0 = (
        (Math.sin(altitude) - Math.sin(latitude) * Math.sin(declination)) /
        (Math.cos(latitude) * Math.cos(declination))
    );

    const theta0 = greenwichSiderealTime0h(year, month, day);
    const transitFraction = normalizeFraction(
        (rightAscensionDeg - longitudeDeg - theta0) / 360
    );
    const transitTime = formatUtcTimeFromFraction(transitFraction);

    if (cosH0 > NEVER_RISES_THRESHOLD) {
        return {
            riseAzimuth: '--',
            riseTime: '--',
            transitTime,
            setTime: '--',
        };
    }

    if (cosH0 < ALWAYS_VISIBLE_THRESHOLD) {
        return {
            riseAzimuth: 'Circumpolar',
            riseTime: 'Sempre visível',
            transitTime,
            setTime: 'Sempre visível',
        };
    }

    const h0 = radToDeg(Math.acos(clamp(cosH0, -1, 1)));
    const riseFraction = normalizeFraction(transitFraction - (h0 / SIDEREAL_RATE_DEG_PER_DAY));
    const setFraction = normalizeFraction(transitFraction + (h0 / SIDEREAL_RATE_DEG_PER_DAY));

    const riseAzimuth = calculateAzimuthAtRise(latitudeDeg, declinationDeg, h0);

    return {
        riseAzimuth: Number.isFinite(riseAzimuth) ? `${riseAzimuth.toFixed(1)}°` : '--',
        riseTime: formatUtcTimeFromFraction(riseFraction),
        transitTime,
        setTime: formatUtcTimeFromFraction(setFraction),
    };
}

function calculateAzimuthAtRise(latitudeDeg, declinationDeg, hourAngleDeg) {
    const latitude = degToRad(latitudeDeg);
    const declination = degToRad(declinationDeg);
    const altitude = degToRad(STANDARD_ALTITUDE_DEG);
    const riseHourAngleDeg = -Math.abs(hourAngleDeg);
    const hourAngle = degToRad(riseHourAngleDeg);

    const sinAzimuth = (Math.cos(declination) * Math.sin(hourAngle)) / Math.cos(altitude);
    const cosAzimuth = (
        (Math.sin(declination) - Math.sin(altitude) * Math.sin(latitude)) /
        (Math.cos(altitude) * Math.cos(latitude))
    );

    return normalizeDegrees(radToDeg(Math.atan2(sinAzimuth, cosAzimuth)));
}

function greenwichSiderealTime0h(year, month, day) {
    const jd = julianDay(year, month, day);
    const t = (jd - JULIAN_DAY_J2000) / 36525.0;
    const theta = GST0_BASE_DEG
        + (GST0_LINEAR_DEG * t)
        + (GST0_QUADRATIC_DEG * t * t)
        - ((t * t * t) / GST0_CUBIC_DIVISOR);
    return normalizeDegrees(theta);
}

function julianDay(year, month, day) {
    let y = year;
    let m = month;
    if (m <= 2) {
        y -= 1;
        m += 12;
    }
    const a = Math.floor(y / 100);
    const b = 2 - a + Math.floor(a / 4);
    return Math.floor(365.25 * (y + 4716))
        + Math.floor(30.6001 * (m + 1))
        + day + b - 1524.5;
}

function normalizeDegrees(value) {
    const normalized = value % 360;
    return normalized < 0 ? normalized + 360 : normalized;
}

function normalizeFraction(value) {
    const normalized = value % 1;
    return normalized < 0 ? normalized + 1 : normalized;
}

function formatUtcTimeFromFraction(dayFraction) {
    const totalMinutes = Math.round(normalizeFraction(dayFraction) * MINUTES_PER_DAY);
    const hours = Math.floor((totalMinutes % MINUTES_PER_DAY) / 60);
    const minutes = totalMinutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')} UTC`;
}

/* ── Inicialização ────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    // Scroll para a célula seleccionada se estiver fora do viewport
    const selected = document.querySelector('.cal-cell.cal-selected');
    if (selected) {
        updateDetailMaxAltitude(selected);
        updateDetailObservationTimes(selected);
        selected.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
});
