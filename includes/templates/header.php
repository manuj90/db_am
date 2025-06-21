<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/paths.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Ganymede - Agencia Multimedia'; ?></title>

    <!-- Tailwind CSS 4.1 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configuración personalizada de Tailwind - Ganymede Theme -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        // Colores principales del logo
                        'aurora-pink': '#ff0080',
                        'aurora-orange': '#ff8c00',
                        'aurora-blue': '#00d4ff',
                        'aurora-purple': '#8b5cf6',

                        // Colores base para modo oscuro
                        'dark': '#0a0a0f',
                        'surface': '#1a1a2e',
                        'surface-light': '#2d2d4a',

                        // Colores funcionales
                        'primary': '#ff0080',
                        'secondary': '#ff8c00',
                        'accent': '#00d4ff',
                        'admin': '#8b5cf6'
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'aurora': 'aurora 10s linear infinite',
                        'parallax': 'parallax 20s linear infinite',
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                    },


                }
            }
        }
    </script>

    <!-- Ganymede Aurora Theme CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet">

    <!-- Meta tags -->
    <meta name="description"
        content="<?php echo $pageDescription ?? 'Ganymede - Agencia de diseño multimedia. Donde las ideas alcanzan la escala de Ganímedes con creatividad orbital y tecnología que impacta en todo el sistema digital.'; ?>">
    <meta name="keywords" content="diseño, multimedia, web, gráfico, animación, video, UI/UX, ganymede, agencia">
    <meta name="author" content="Ganymede - Agencia Multimedia">
    <meta name="theme-color" content="#99006d">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $pageTitle ?? 'Ganymede - Agencia Multimedia'; ?>">
    <meta property="og:description"
        content="<?php echo $pageDescription ?? 'Donde las ideas alcanzan la escala de Ganímedes'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/images/logo/LogoFull.png">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/logo/LogoFav.png">

    <!-- CSS adicional si se especifica -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Estilos específicos para esta página -->
    <?php if (isset($pageStyles)): ?>
        <style>
            <?php echo $pageStyles; ?>
        </style>
    <?php endif; ?>
</head>

<body class="<?php echo $bodyClass ?? 'bg-dark text-gray-100 antialiased'; ?> dark">

    <div id="notifications-container" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <div id="loading-overlay"
        class="hidden fixed inset-0 bg-dark/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="glass-dark rounded-lg p-6 flex items-center space-x-3">
            <div class="spinner"></div>
            <span class="text-gray-100">Cargando...</span>
        </div>
    </div>

    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">

    <script src="<?php echo ASSETS_URL; ?>/js/animations.js"></script>

    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>