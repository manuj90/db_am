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
                @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 cursor-pointer;
            }
            .btn-primary {
                @apply bg-primary text-white hover:bg-blue-700;
            }
            .btn-secondary {
                @apply bg-gray-600 text-white hover:bg-gray-700;
            }
            .btn-accent {
                @apply bg-accent text-white hover:bg-red-700;
            }
            .card {
                @apply bg-white rounded-xl shadow-lg p-6;
            }
            .form-input {
                @apply w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition;
            }
            .project-card {
                @apply bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300;
            }
            .admin-sidebar {
                @apply bg-gray-800 text-white min-h-screen fixed left-0 top-0 w-64 pt-16;
            }
            .user-sidebar {
                @apply bg-white rounded-lg shadow-md p-4 sticky top-8;
            }
        }
        
        <?php if (isset($pageStyles)): ?>
            <?php echo $pageStyles; ?>
        <?php endif; ?>
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $bodyClass ?? 'bg-gray-50 text-gray-900'; ?>">