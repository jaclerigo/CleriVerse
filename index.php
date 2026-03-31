<?php

/**
 * CleriVerse – Ferramentas para Cálculos Astronómicos
 *
 * Ponto de entrada principal. Carrega o autoloader do Composer,
 * valida o parâmetro de página e delega para o ficheiro correto.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

// Detecta o caminho base para deploys em subpasta (ex.: /cleriverse)
$_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_appDir  = str_replace('\\', '/', __DIR__);
define('BASE_PATH', ($_docRoot !== '' && strpos($_appDir, $_docRoot) === 0) ? substr($_appDir, strlen($_docRoot)) : '');
unset($_docRoot, $_appDir);

// Roteamento simples por query-string: ?page=mercury
$page = $_GET['page'] ?? 'mercury';

// Sanitização: apenas letras minúsculas e underscores
$page = preg_replace('/[^a-z_]/', '', strtolower($page));

$pageFile = __DIR__ . '/pages/' . $page . '.php';

if (!file_exists($pageFile)) {
    $page     = 'mercury';
    $pageFile = __DIR__ . '/pages/mercury.php';
}

require_once __DIR__ . '/templates/header.php';
require_once $pageFile;
require_once __DIR__ . '/templates/footer.php';
