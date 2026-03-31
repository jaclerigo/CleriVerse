<?php

/**
 * Configuração da base de dados MariaDB para o CleriVerse.
 *
 * Em produção, substitua as credenciais pelas do seu painel cPanel
 * ou carregue-as a partir de variáveis de ambiente.
 */

declare(strict_types=1);

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'cleriverse');
define('DB_USER',    getenv('DB_USER')    ?: 'cleriverse_user');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma ligação PDO à base de dados.
 * Retorna null se a ligação falhar (permite que a app funcione sem BD).
 */
function getDbConnection(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo;
    }

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // A aplicação funciona sem BD (cálculos feitos em tempo real)
        $pdo = null;
    }

    return $pdo;
}
