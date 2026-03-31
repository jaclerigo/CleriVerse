-- ──────────────────────────────────────────────────────────────────────────
-- CleriVerse – Schema MariaDB
-- Compatível com MariaDB 10.3+ (cPanel)
-- ──────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS `cleriverse`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cleriverse`;

-- ── Cache de fases de Mercúrio ─────────────────────────────────────────────
-- Permite armazenar cálculos já efectuados para evitar recalcular em cada
-- pedido. A aplicação funciona sem esta tabela (cálculo directo em PHP).

CREATE TABLE IF NOT EXISTS `mercury_phases_cache` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `calc_date`         DATE            NOT NULL COMMENT 'Data do cálculo (meio-dia UTC)',
    `phase_angle`       DECIMAL(8,4)    NOT NULL COMMENT 'Ângulo de fase (graus)',
    `illumination`      DECIMAL(6,4)    NOT NULL COMMENT 'Fracção iluminada (0-1)',
    `elongation`        DECIMAL(8,4)    NOT NULL COMMENT 'Elongação absoluta (graus)',
    `is_eastern`        TINYINT(1)      NOT NULL COMMENT '1=Este (tarde), 0=Oeste (manhã)',
    `distance_au`       DECIMAL(10,6)   NOT NULL COMMENT 'Distância geocêntrica (AU)',
    `helio_dist_au`     DECIMAL(10,6)   NOT NULL COMMENT 'Distância heliocêntrica (AU)',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_calc_date` (`calc_date`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache dos cálculos de fases de Mercúrio';
