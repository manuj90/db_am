<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/paths.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Agencia Multimedia'; ?></title>
    
    <!-- Tailwind CSS 4.1 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configuración personalizada de Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#2563eb',
                        'secondary': '#7c3aed',
                        'accent': '#dc2626',
                        'admin': '#991b1b'
                    }
                }
            }
        }
    </script>
    
    <!-- CSS personalizado -->
    <style type="text/tailwindcss">
        @layer components {
            .btn {
                @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 cursor-pointer inline-flex items-center justify-center;
            }
            .btn-primary {
                @apply bg-primary text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50;
            }
            .btn-secondary {
                @apply bg-gray-600 text-white hover:bg-gray-700;
            }
            .btn-outline {
                @apply border-2 border-gray-300 text-gray-700 hover:bg-gray-50;
            }
            .card {
                @apply bg-white rounded-xl shadow-lg p-6 border border-gray-100;
            }
            .form-input {
                @apply w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition placeholder-gray-400;
            }
            .form-label {
                @apply block text-sm font-medium text-gray-700 mb-2;
            }
            .form-error {
                @apply text-red-600 text-sm mt-1;
            }
            .project-card {
                @apply bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 border border-gray-100;
            }
            .spinner {
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Estilos específicos para esta página */
        <?php if (isset($pageStyles)): ?>
            <?php echo $pageStyles; ?>
        <?php endif; ?>
    </style>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo $pageDescription ?? 'Agencia de diseño multimedia profesional. Descubre nuestros proyectos de diseño web, gráfico, animación y más.'; ?>">
    <meta name="keywords" content="diseño, multimedia, web, gráfico, animación, video, UI/UX">
    <meta name="author" content="Agencia Multimedia">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $pageTitle ?? 'Agencia Multimedia'; ?>">
    <meta property="og:description" content="<?php echo $pageDescription ?? 'Agencia de diseño multimedia profesional'; ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSS adicional si se especifica -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $bodyClass ?? 'bg-gray-50 text-gray-900 antialiased'; ?>">
    
    <!-- Contenedor de notificaciones -->
    <div id="notifications-container" class="fixed top-20 right-4 z-50 space-y-2"></div>
    
    <!-- Loading overlay (oculto por defecto) -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="spinner"></div>
            <span class="text-gray-700">Cargando...</span>
        </div>
    </div>