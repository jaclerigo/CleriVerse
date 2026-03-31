<?php

declare(strict_types=1);

use CleriVerse\Astronomy\MercuryCalculator;

// ── Validação de entrada ──────────────────────────────────────────────────────
$year  = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT) ?: (int) date('Y');
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?: (int) date('n');
$year  = max(1900, min(2100, $year));
$month = max(1,    min(12,   $month));

// ── Navegação de meses ───────────────────────────────────────────────────────
$currentDate = new DateTime("{$year}-{$month}-01");

$prevDate = clone $currentDate;
$prevDate->modify('-1 month');
$nextDate = clone $currentDate;
$nextDate->modify('+1 month');

// ── Cálculo ──────────────────────────────────────────────────────────────────
$calculator = new MercuryCalculator();
$data       = $calculator->getMonthData($year, $month);
$days       = $data['days'];
$events     = $data['events'];

// ── Nomes dos meses e dias em português ─────────────────────────────────────
$monthNames = [
    1  => 'Janeiro', 2  => 'Fevereiro', 3  => 'Março',
    4  => 'Abril',   5  => 'Maio',      6  => 'Junho',
    7  => 'Julho',   8  => 'Agosto',    9  => 'Setembro',
    10 => 'Outubro', 11 => 'Novembro',  12 => 'Dezembro',
];
$dayNames = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

// ── Dia inicial da grelha (Seg = 0, Dom = 6) ─────────────────────────────────
$firstDow = (int) $currentDate->format('w'); // 0=Dom, 6=Sáb
$offset   = ($firstDow + 6) % 7;             // converte para Seg=0

// ── Hoje ─────────────────────────────────────────────────────────────────────
$today        = new DateTime();
$isThisMonth  = ($year === (int) $today->format('Y') && $month === (int) $today->format('n'));
$todayDay     = $isThisMonth ? (int) $today->format('j') : 0;

// ── Dia selecionado (por defeito: hoje ou o 1º) ───────────────────────────────
$selectedDay = filter_input(INPUT_GET, 'day', FILTER_VALIDATE_INT) ?: ($todayDay ?: 1);
$selectedDay = max(1, min(count($days), $selectedDay));
$selected    = $days[$selectedDay - 1];

// ── JSON para JavaScript ──────────────────────────────────────────────────────
$daysJson = json_encode($days);
?>

<div class="page-mercury">

    <!-- ── Cabeçalho da ferramenta ───────────────────────────────────────── -->
    <div class="tool-header">
        <h1 class="tool-title">
            <span class="planet-icon">☿</span> Fases de Mercúrio
        </h1>
        <p class="tool-desc">
            Visualização das fases do planeta Mercúrio ao longo do mês,
            calculadas com algoritmos VSOP87 (Jean Meeus).
        </p>
    </div>

    <!-- ── Navegação de meses ────────────────────────────────────────────── -->
    <div class="month-nav">
        <a href="<?= BASE_PATH ?>/?page=mercury&year=<?= $prevDate->format('Y') ?>&month=<?= (int) $prevDate->format('n') ?>"
           class="btn btn-nav" title="Mês anterior">&#8592; <?= $monthNames[(int) $prevDate->format('n')] ?></a>

        <form class="month-form" method="get" action="<?= BASE_PATH ?>/">
            <input type="hidden" name="page" value="mercury">
            <select name="month" class="form-select form-select-sm select-month" onchange="this.form.submit()">
                <?php foreach ($monthNames as $m => $name): ?>
                    <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <select name="year" class="form-select form-select-sm select-year" onchange="this.form.submit()">
                <?php for ($y = 1900; $y <= 2100; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>

        <a href="<?= BASE_PATH ?>/?page=mercury&year=<?= $nextDate->format('Y') ?>&month=<?= (int) $nextDate->format('n') ?>"
           class="btn btn-nav" title="Próximo mês"><?= $monthNames[(int) $nextDate->format('n')] ?> &#8594;</a>
    </div>

    <!-- ── Painel de detalhe do dia selecionado ──────────────────────────── -->
    <div class="card detail-panel mb-4" id="detailPanel">
        <div class="card-body d-flex gap-4 align-items-center flex-wrap">
            <div class="detail-phase-svg" id="detailPhaseSvg">
                <?= MercuryCalculator::renderPhaseSvg($selected['illumination'], $selected['is_eastern'], 160) ?>
            </div>
            <div class="detail-info flex-grow-1">
            <h2 class="detail-day" id="detailDay">
                <?= $selectedDay ?> de <?= $monthNames[$month] ?> de <?= $year ?>
            </h2>
            <ul class="detail-stats" id="detailStats">
                <li>
                    <span class="stat-label">Fase</span>
                    <span class="stat-value" id="dPhaseName"><?= htmlspecialchars($selected['phase_name']) ?></span>
                </li>
                <li>
                    <span class="stat-label">Fração Iluminada</span>
                    <span class="stat-value" id="dIllum"><?= $selected['illumination_pct'] ?>%</span>
                </li>
                <li>
                    <span class="stat-label">Ângulo de Fase</span>
                    <span class="stat-value" id="dPhaseAngle"><?= $selected['phase_angle'] ?>°</span>
                </li>
                <li>
                    <span class="stat-label">Elongação</span>
                    <span class="stat-value" id="dElongation">
                        <?= $selected['elongation'] ?>° <?= $selected['direction'] ?>
                    </span>
                </li>
                <li>
                    <span class="stat-label">Visibilidade</span>
                    <span class="stat-value" id="dStarType"><?= htmlspecialchars($selected['star_type']) ?></span>
                </li>
                <li>
                    <span class="stat-label">Dist. Geocêntrica</span>
                    <span class="stat-value" id="dDist"><?= $selected['distance_au'] ?> AU</span>
                </li>
            </ul>
            <?php if (isset($events[$selectedDay])): ?>
                <div class="event-badge event-<?= htmlspecialchars($events[$selectedDay]['type']) ?>">
                    <?= htmlspecialchars($events[$selectedDay]['icon']) ?>
                    <?= htmlspecialchars($events[$selectedDay]['label']) ?>
                </div>
            <?php endif; ?>
            </div>
        </div><!-- /.card-body -->
    </div><!-- /.card -->

    <!-- ── Grelha do calendário ──────────────────────────────────────────── -->
    <div class="calendar-section">
        <h2 class="section-title"><?= $monthNames[$month] ?> <?= $year ?></h2>

        <div class="calendar-grid" id="calendarGrid">
            <!-- Cabeçalho dos dias da semana -->
            <?php foreach ($dayNames as $dn): ?>
                <div class="cal-header"><?= $dn ?></div>
            <?php endforeach; ?>

            <!-- Células vazias antes do 1º dia -->
            <?php for ($i = 0; $i < $offset; $i++): ?>
                <div class="cal-cell cal-empty"></div>
            <?php endfor; ?>

            <!-- Dias do mês -->
            <?php foreach ($days as $dayData): ?>
                <?php
                $d        = $dayData['day'];
                $isToday  = ($d === $todayDay);
                $isSel    = ($d === $selectedDay);
                $hasEvent = isset($events[$d]);
                $classes  = 'cal-cell';
                if ($isToday)  $classes .= ' cal-today';
                if ($isSel)    $classes .= ' cal-selected';
                if ($hasEvent) $classes .= ' cal-event';
                ?>
                <div class="<?= $classes ?>"
                     data-day="<?= $d ?>"
                     data-year="<?= $year ?>"
                     data-month="<?= $month ?>"
                     data-illumination="<?= $dayData['illumination'] ?>"
                     data-illumination-pct="<?= $dayData['illumination_pct'] ?>"
                     data-phase-angle="<?= $dayData['phase_angle'] ?>"
                     data-elongation="<?= $dayData['elongation'] ?>"
                     data-is-eastern="<?= $dayData['is_eastern'] ? '1' : '0' ?>"
                     data-direction="<?= $dayData['direction'] ?>"
                     data-star-type="<?= htmlspecialchars($dayData['star_type']) ?>"
                     data-phase-name="<?= htmlspecialchars($dayData['phase_name']) ?>"
                     data-dist="<?= $dayData['distance_au'] ?>"
                     onclick="selectDay(this)">

                    <span class="cal-day-num"><?= $d ?></span>

                    <div class="cal-phase-svg">
                        <?= MercuryCalculator::renderPhaseSvg($dayData['illumination'], $dayData['is_eastern'], 44) ?>
                    </div>

                    <div class="cal-meta">
                        <span class="cal-illum"><?= $dayData['illumination_pct'] ?>%</span>
                        <span class="cal-elong"><?= $dayData['elongation'] ?>°<?= $dayData['direction'] ?></span>
                    </div>

                    <?php if ($hasEvent): ?>
                        <div class="cal-event-badge" title="<?= htmlspecialchars($events[$d]['label']) ?>">
                            <?= htmlspecialchars($events[$d]['icon']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div><!-- /.calendar-grid -->
    </div><!-- /.calendar-section -->

    <!-- ── Legenda ───────────────────────────────────────────────────────── -->
    <div class="card legend-section">
        <div class="card-body">
        <h3 class="legend-title">Legenda</h3>
        <div class="legend-items">
            <div class="legend-item">
                <?= MercuryCalculator::renderPhaseSvg(0.02, true, 36) ?>
                <span>Nova (&lt;5%)</span>
            </div>
            <div class="legend-item">
                <?= MercuryCalculator::renderPhaseSvg(0.25, true, 36) ?>
                <span>Crescente</span>
            </div>
            <div class="legend-item">
                <?= MercuryCalculator::renderPhaseSvg(0.50, true, 36) ?>
                <span>Quarto</span>
            </div>
            <div class="legend-item">
                <?= MercuryCalculator::renderPhaseSvg(0.75, true, 36) ?>
                <span>Gibosa</span>
            </div>
            <div class="legend-item">
                <?= MercuryCalculator::renderPhaseSvg(0.98, true, 36) ?>
                <span>Cheia (&gt;95%)</span>
            </div>
            <div class="legend-item legend-direction">
                <div class="legend-direction-row">
                    <?= MercuryCalculator::renderPhaseSvg(0.40, true, 36) ?>
                    <span>Este (Tarde)</span>
                </div>
                <div class="legend-direction-row">
                    <?= MercuryCalculator::renderPhaseSvg(0.40, false, 36) ?>
                    <span>Oeste (Manhã)</span>
                </div>
            </div>
            <div class="legend-item">
                <span class="event-icon event-greatest_eastern">★</span>
                <span>Maior Elongação</span>
            </div>
        </div>
        </div><!-- /.card-body -->
    </div><!-- /.card -->

    <!-- ── Dados em JSON para o JavaScript ───────────────────────────────── -->
    <script>
        const MERCURY_MONTH_DATA = <?= $daysJson ?>;
        const SELECTED_DAY       = <?= $selectedDay ?>;
        const CURRENT_YEAR       = <?= $year ?>;
        const CURRENT_MONTH      = <?= $month ?>;
        const MONTH_NAMES = <?= json_encode(array_values($monthNames)) ?>;
    </script>

</div><!-- /.page-mercury -->
