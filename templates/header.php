<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CleriVerse – Ferramentas Astronómicas</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="/" class="logo">
            <span class="logo-icon">🪐</span>
            <span class="logo-text">CleriVerse</span>
        </a>
        <nav class="main-nav">
            <a href="/?page=mercury" class="nav-link <?= ($page ?? '') === 'mercury' ? 'active' : '' ?>">
                ☿ Mercúrio
            </a>
        </nav>
    </div>
</header>
<main class="site-main">
