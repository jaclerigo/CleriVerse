<!DOCTYPE html>
<html lang="pt" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CleriVerse – Ferramentas Astronómicas</title>
    <!-- Aplicar tema antes do render para evitar flash de conteúdo não estilizado -->
    <script>(function () {
        try {
            var t = localStorage.getItem('cleriverse_theme');
            if (t === 'light' || t === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', t);
            }
        } catch (e) { /* modo privado ou armazenamento bloqueado */ }
    }());</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-md site-navbar sticky-top">
    <div class="container-xl">
        <a href="<?= BASE_PATH ?>/" class="navbar-brand logo">
            <span class="logo-icon">🪐</span>
            <span class="logo-text">CleriVerse</span>
        </a>
        <!-- Botões de acção sempre visíveis (fora do collapse) -->
        <div class="d-flex align-items-center gap-1 ms-auto me-2">
            <button id="locationBtn" class="navbar-action-btn"
                    title="Definir localização para cálculos mais precisos"
                    aria-label="Definir localização">📍</button>
            <button id="themeToggleBtn" class="navbar-action-btn"
                    title="Mudar para tema claro"
                    aria-label="Alternar tema">☀️</button>
        </div>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="<?= BASE_PATH ?>/?page=mercury"
                       class="nav-link <?= ($page ?? '') === 'mercury' ? 'active' : '' ?>">
                        ☿ Mercúrio
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">
    <div class="container-xl">
