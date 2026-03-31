<?php

declare(strict_types=1);

namespace CleriVerse\Astronomy;

use Andrmoel\AstronomyBundle\AstronomicalObjects\Planets\Mercury;
use Andrmoel\AstronomyBundle\AstronomicalObjects\Sun;
use Andrmoel\AstronomyBundle\TimeOfInterest;

/**
 * Calcula as fases de Mercúrio usando os algoritmos de Jean Meeus
 * (Astronomical Algorithms) com as tabelas VSOP87.
 */
class MercuryCalculator
{
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
        $toi     = TimeOfInterest::create($year, $month, $day, 12, 0, 0);
        $mercury = Mercury::create($toi);
        $sun     = Sun::create($toi);

        // Distância heliocêntrica de Mercúrio (r)
        $mercuryHel = $mercury->getHeliocentricEclipticalSphericalCoordinates();
        $r          = $mercuryHel->getRadiusVector(); // AU

        // Coordenadas geocêntricas eclípticas de Mercúrio
        $mercuryGeo = $mercury->getGeocentricEclipticalSphericalCoordinates();
        $delta      = $mercuryGeo->getRadiusVector(); // AU
        $mercuryLon = $mercuryGeo->getLongitude();    // graus

        // Coordenadas geocêntricas do Sol (R = distância Terra–Sol)
        $sunGeo = $sun->getGeocentricEclipticalSphericalCoordinates();
        $R      = $sunGeo->getRadiusVector();  // AU
        $sunLon = $sunGeo->getLongitude();     // graus

        // Ângulo de fase (Meeus 41.2)
        $cosI = ($r * $r + $delta * $delta - $R * $R) / (2.0 * $r * $delta);
        $cosI = max(-1.0, min(1.0, $cosI)); // clampar por segurança numérica
        $phaseAngle = rad2deg(acos($cosI));

        // Fração iluminada (Meeus 41.1)
        $illumination = (1.0 + cos(deg2rad($phaseAngle))) / 2.0;

        // Elongação (positivo = Este, negativo = Oeste)
        $elongation = $mercuryLon - $sunLon;
        if ($elongation > 180.0) {
            $elongation -= 360.0;
        } elseif ($elongation < -180.0) {
            $elongation += 360.0;
        }
        $isEastern   = $elongation >= 0.0;
        $elongationAbs = abs($elongation);

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
            'phase_name'     => $this->getPhaseName($illumination),
            'distance_au'    => round($delta, 4),
            'helio_dist_au'  => round($r, 4),
        ];
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
