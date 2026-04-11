<?php

declare(strict_types=1);

namespace CleriVerse\Astronomy;

use Carbon\Carbon;
use deepskylog\AstronomyLibrary\Targets\Earth;
use deepskylog\AstronomyLibrary\Targets\Mercury;
use deepskylog\AstronomyLibrary\Targets\Sun;

/**
 * Calcula as fases de Mercúrio usando os algoritmos de Jean Meeus
 * (Astronomical Algorithms) com as tabelas VSOP87.
 */
class MercuryCalculator
{
    private const EARTH_AXIAL_TILT_DEG = 23.44;
    private const VERNAL_EQUINOX_APPROX_DAY = 81;
    private const J2000_MEAN_OBLIQUITY_DEG = 23.439291;
    private const OBLIQUITY_RATE_DECREASE_DEG_PER_CENTURY = 0.0130042;
    private const MERCURY_MAG_BASE = -0.42;
    private const MERCURY_MAG_PHASE_L1 = 0.0380;
    private const MERCURY_MAG_PHASE_L2 = 0.000273;
    private const MERCURY_MAG_PHASE_L3 = 0.000002;
    private const MIN_MAG_DISTANCE_FACTOR = 1.0e-8;

    /**
     * Devolve os dados de fase para todos os dias de um mês.
     *
     * @return array{year:int,month:int,days:array,events:array}
     */
    public function getMonthData(int $year, int $month): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $days        = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = $this->getDayData($year, $month, $day);
        }

        return [
            'year'   => $year,
            'month'  => $month,
            'days'   => $days,
            'events' => $this->findMonthlyEvents($days),
        ];
    }

    /**
     * Calcula os dados de fase de Mercúrio para um dia específico (meio-dia UTC).
     *
     * Grandezas calculadas (Meeus, Cap. 41):
     *   r  = distância heliocêntrica de Mercúrio (AU)
     *   Δ  = distância geocêntrica de Mercúrio (AU)
     *   R  = distância Terra–Sol (AU)
     *
     * Ângulo de fase:  cos i = (r² + Δ² − R²) / (2 r Δ)
     * Fração iluminada: k = (1 + cos i) / 2
     *
     * @return array<string,mixed>
     */
    public function getDayData(int $year, int $month, int $day): array
    {
        $date    = Carbon::create($year, $month, $day, 12, 0, 0, 'UTC');
        $mercury = new Mercury();
        $earth   = new Earth();
        $sun     = new Sun();

        // Coordenadas heliocentricas VSOP87: [L (graus), B (graus), R (AU)]
        $mercuryHel = $mercury->calculateHeliocentricCoordinates($date);
        $earthHel   = $earth->calculateHeliocentricCoordinates($date);

        // Distância heliocêntrica de Mercúrio (r)
        $r = $mercuryHel[2]; // AU

        // Coordenadas retangulares geocêntricas de Mercúrio
        $x = $mercuryHel[2] * cos(deg2rad($mercuryHel[1])) * cos(deg2rad($mercuryHel[0]))
           - $earthHel[2]   * cos(deg2rad($earthHel[1]))   * cos(deg2rad($earthHel[0]));
        $y = $mercuryHel[2] * cos(deg2rad($mercuryHel[1])) * sin(deg2rad($mercuryHel[0]))
           - $earthHel[2]   * cos(deg2rad($earthHel[1]))   * sin(deg2rad($earthHel[0]));
        $z = $mercuryHel[2] * sin(deg2rad($mercuryHel[1]))
           - $earthHel[2]   * sin(deg2rad($earthHel[1]));

        // Δ = distância geocêntrica de Mercúrio (AU)
        $delta = sqrt($x ** 2 + $y ** 2 + $z ** 2);

        // Longitude eclíptica geocêntrica de Mercúrio (graus)
        $mercuryLon = rad2deg(atan2($y, $x));
        $mercuryGeoLon = $this->normalizeDegrees($mercuryLon);
        $mercuryGeoLat = rad2deg(atan2($z, sqrt($x ** 2 + $y ** 2)));

        // Dados geocêntricos do Sol: [Odot (graus), beta (graus), R (AU)]
        $sunData = $sun->calculateOdotBetaR($date);
        $R      = $sunData[2]; // distância Terra–Sol (AU)
        $sunLon = $sunData[0]; // longitude eclíptica geocêntrica do Sol (graus)

        // Ângulo de fase (Meeus 41.2)
        $cosI = ($r * $r + $delta * $delta - $R * $R) / (2.0 * $r * $delta);
        $cosI = max(-1.0, min(1.0, $cosI)); // clampar por segurança numérica
        $phaseAngle = rad2deg(acos($cosI));

        // Fração iluminada (Meeus 41.1)
        $illumination = (1.0 + cos(deg2rad($phaseAngle))) / 2.0;
        $magnitude = $this->calculateVisualMagnitude($r, $delta, $phaseAngle);

        // Conversão aproximada para coordenadas equatoriais geocêntricas (J2000)
        $jd = ((float) $date->getTimestamp() / 86400.0) + 2440587.5;
        $julianCentury = ($jd - 2451545.0) / 36525.0;
        $obliquity = self::J2000_MEAN_OBLIQUITY_DEG - self::OBLIQUITY_RATE_DECREASE_DEG_PER_CENTURY * $julianCentury;

        $lambda = deg2rad($mercuryGeoLon);
        $beta = deg2rad($mercuryGeoLat);
        $epsilon = deg2rad($obliquity);

        $sinDec = sin($beta) * cos($epsilon) + cos($beta) * sin($epsilon) * sin($lambda);
        $sinDec = max(-1.0, min(1.0, $sinDec));
        $declination = rad2deg(asin($sinDec));

        $yEq = sin($lambda) * cos($epsilon) - tan($beta) * sin($epsilon);
        $xEq = cos($lambda);
        $rightAscension = $this->normalizeDegrees(rad2deg(atan2($yEq, $xEq)));

        // Elongação (positivo = Este, negativo = Oeste)
        $elongation = $mercuryLon - $sunLon;
        if ($elongation > 180.0) {
            $elongation -= 360.0;
        } elseif ($elongation < -180.0) {
            $elongation += 360.0;
        }
        $isEastern   = $elongation >= 0.0;
        $elongationAbs = abs($elongation);
        $maxVisibilityAltitude = $this->estimateMaxVisibilityAltitude($elongationAbs, $year, $month, $day);

        return [
            'year'           => $year,
            'month'          => $month,
            'day'            => $day,
            'phase_angle'    => round($phaseAngle, 2),
            'illumination'   => round($illumination, 4),
            'illumination_pct' => round($illumination * 100, 1),
            'elongation'     => round($elongationAbs, 2),
            'is_eastern'     => $isEastern,
            'direction'      => $isEastern ? 'E' : 'O',
            'star_type'      => $isEastern ? 'Estrela da Tarde' : 'Estrela da Manhã',
            'visibility_window' => $isEastern ? 'após o pôr do Sol' : 'antes do nascer do Sol',
            'max_visibility_altitude' => round($maxVisibilityAltitude, 1),
            'magnitude'      => round($magnitude, 2),
            'right_ascension' => round($rightAscension, 4),
            'declination'    => round($declination, 4),
            'phase_name'     => $this->getPhaseName($illumination),
            'distance_au'    => round($delta, 4),
            'helio_dist_au'  => round($r, 4),
        ];
    }

    /**
     * Estima a altura máxima visível de Mercúrio no crepúsculo (aproximação).
     */
    private function estimateMaxVisibilityAltitude(
        float $elongationAbs,
        int $year,
        int $month,
        int $day,
        float $latitude = 0.0
    ): float {
        $sunDeclination = $this->estimateSunDeclination($year, $month, $day);
        $eclipticAngle = 90.0 - abs($latitude - $sunDeclination);
        $eclipticAngle = max(0.0, min(90.0, $eclipticAngle));

        $elongationRad = deg2rad($elongationAbs);
        $angleRad = deg2rad($eclipticAngle);
        $maxAltitude = rad2deg(asin(sin($elongationRad) * sin($angleRad)));
        return max(0.0, min(90.0, $maxAltitude));
    }

    /**
     * Estima a declinação solar diária (graus) para aproximações de visibilidade.
     */
    private function estimateSunDeclination(int $year, int $month, int $day): float
    {
        $date = Carbon::create($year, $month, $day, 0, 0, 0, 'UTC');
        $dayOfYear = (int) $date->dayOfYear;
        $daysInYear = (int) $date->daysInYear;
        return self::EARTH_AXIAL_TILT_DEG * sin(
            deg2rad((360.0 / $daysInYear) * ($dayOfYear - self::VERNAL_EQUINOX_APPROX_DAY))
        );
    }

    /**
     * Estima a magnitude visual aparente de Mercúrio (aproximação fotométrica).
     */
    private function calculateVisualMagnitude(float $r, float $delta, float $phaseAngle): float
    {
        $distanceFactor = max(self::MIN_MAG_DISTANCE_FACTOR, $r * $delta);

        return self::MERCURY_MAG_BASE
            + (5.0 * log10($distanceFactor))
            + (self::MERCURY_MAG_PHASE_L1 * $phaseAngle)
            - (self::MERCURY_MAG_PHASE_L2 * ($phaseAngle ** 2))
            + (self::MERCURY_MAG_PHASE_L3 * ($phaseAngle ** 3));
    }

    /**
     * Normaliza um ângulo para o intervalo [0, 360).
     */
    private function normalizeDegrees(float $degrees): float
    {
        $normalized = fmod($degrees, 360.0);
        return $normalized < 0.0 ? $normalized + 360.0 : $normalized;
    }

    /**
     * Nome da fase em português com base na fração iluminada.
     */
    public function getPhaseName(float $illumination): string
    {
        if ($illumination < 0.05) {
            return 'Nova';
        }
        if ($illumination < 0.45) {
            return 'Crescente';
        }
        if ($illumination < 0.55) {
            return 'Quarto';
        }
        if ($illumination < 0.95) {
            return 'Gibosa';
        }
        return 'Cheia';
    }

    /**
     * Identifica eventos notáveis no mês (maiores elongações, conjunções).
     *
     * @param  array<int,array<string,mixed>> $days
     * @return array<int,array<string,string>>
     */
    private function findMonthlyEvents(array $days): array
    {
        $events = [];
        $count  = count($days);

        for ($i = 1; $i < $count - 1; $i++) {
            $prev = $days[$i - 1];
            $curr = $days[$i];
            $next = $days[$i + 1];

            // Maior elongação: máximo local da elongação absoluta
            if (
                $curr['elongation'] > $prev['elongation'] &&
                $curr['elongation'] > $next['elongation'] &&
                $curr['elongation'] > 10.0
            ) {
                $label = $curr['is_eastern']
                    ? 'Maior Elongação Oriental'
                    : 'Maior Elongação Ocidental';

                $events[$curr['day']] = [
                    'type'  => $curr['is_eastern'] ? 'greatest_eastern' : 'greatest_western',
                    'label' => $label,
                    'icon'  => '★',
                ];
            }

            // Conjunção inferior (Mercúrio entre Terra e Sol, fase quase nova)
            if ($curr['elongation'] < 5.0 && $curr['illumination'] < 0.15) {
                $events[$curr['day']] = [
                    'type'  => 'inferior_conjunction',
                    'label' => 'Conjunção Inferior',
                    'icon'  => '●',
                ];
            }

            // Conjunção superior (Mercúrio atrás do Sol, fase quase cheia)
            if ($curr['elongation'] < 5.0 && $curr['illumination'] > 0.85) {
                $events[$curr['day']] = [
                    'type'  => 'superior_conjunction',
                    'label' => 'Conjunção Superior',
                    'icon'  => '○',
                ];
            }
        }

        return $events;
    }

    /**
     * Gera o caminho SVG para a fase de Mercúrio.
     *
     * O disco iluminado é construído com dois arcos:
     *   1. Semicírculo do limbo (lado brilhante)
     *   2. Semi-elipse do terminador (eixo menor b = r|1−2k|)
     *
     * Para elongação Este: lado direito iluminado (estrela da tarde).
     * Para elongação Oeste: lado esquerdo iluminado (estrela da manhã).
     *
     * @param float $illumination Fração iluminada (0–1)
     * @param bool  $isEastern    true = Este, false = Oeste
     * @param int   $size         Tamanho em píxeis
     */
    public static function renderPhaseSvg(
        float $illumination,
        bool $isEastern,
        int $size = 44
    ): string {
        $r   = round(($size / 2.0) - 2.0, 3);
        $cx  = $size / 2.0;
        $cy  = $size / 2.0;

        $attrs  = "width=\"{$size}\" height=\"{$size}\" "
                . "viewBox=\"0 0 {$size} {$size}\" "
                . "xmlns=\"http://www.w3.org/2000/svg\"";
        $disk   = "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" "
                . "fill=\"#111827\" stroke=\"#374151\" stroke-width=\"1\"/>";

        if ($illumination >= 0.99) {
            // Fase cheia
            $lit  = "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"#e8a87c\"/>";
            return "<svg {$attrs}>{$disk}{$lit}</svg>";
        }

        if ($illumination < 0.01) {
            // Fase nova
            return "<svg {$attrs}>{$disk}</svg>";
        }

        // Eixo menor do terminador
        $b    = round($r * abs(1.0 - 2.0 * $illumination), 3);
        $topX = $cx;
        $topY = round($cy - $r, 3);
        $botX = $cx;
        $botY = round($cy + $r, 3);

        if ($isEastern) {
            $limbSweep = 1;
            $termSweep = $illumination >= 0.5 ? 0 : 1;
        } else {
            $limbSweep = 0;
            $termSweep = $illumination >= 0.5 ? 1 : 0;
        }

        $path = "M {$topX},{$topY} "
              . "A {$r},{$r} 0 0 {$limbSweep} {$botX},{$botY} "
              . "A {$b},{$r} 0 0 {$termSweep} {$topX},{$topY} Z";

        $lit = "<path d=\"{$path}\" fill=\"#e8a87c\"/>";
        return "<svg {$attrs}>{$disk}{$lit}</svg>";
    }
}
