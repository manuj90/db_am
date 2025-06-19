<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Redirigir si ya está logueado
redirectIfLoggedIn();

// Variables para el formulario
$email = '';
$errors = [];

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email)) {
        $errors['email'] = 'El email es requerido';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Email inválido';
    }
    
    if (empty($password)) {
        $errors['password'] = 'La contraseña es requerida';
    }
    
    // Si no hay errores, intentar autenticar
    if (empty($errors)) {
        try {
            $user = authenticateUser($email, $password);
            
            if ($user) {
                // Login exitoso
                loginUser($user);
                
                // Redirigir a la página solicitada o al dashboard
                $redirectUrl = $_SESSION['redirect_after_login'] ?? 
                              (isAdmin() ? url('dashboard/admin/index.php') : url('dashboard/user/index.php'));
                
                unset($_SESSION['redirect_after_login']);
                redirect($redirectUrl);
                
            } else {
                $errors['login'] = 'Email o contraseña incorrectos';
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $errors['login'] = 'Error interno del servidor. Intente nuevamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Agencia Multimedia</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex">
    <!-- Panel izquierdo - Formulario -->
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <!-- Logo y título -->
            <div class="text-center">
                <div class="flex justify-center">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Iniciar Sesión</h2>
                <p class="mt-2 text-sm text-gray-600">
                    ¿No tienes cuenta? 
                    <a href="<?php echo url('public/registro.php'); ?>" class="font-medium text-blue-600 hover:text-blue-700 transition-colors">
                        Regístrate aquí
                    </a>
                </p>
            </div>
            
            <!-- Mensajes de error generales -->
            <?php if (isset($errors['login'])): ?>
                <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo htmlspecialchars($errors['login']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de login -->
            <form class="mt-8 space-y-6" method="POST" action="" novalidate>
                <!-- Campo Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($email); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                           placeholder="tu@email.com"
                           required
                           autocomplete="email">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Campo Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Contraseña
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>"
                               placeholder="••••••••"
                               required
                               autocomplete="current-password">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m0 0l3.122 3.122M12 12l4.242-4.242"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Recordarme y recuperar contraseña -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="remember" 
                               name="remember" 
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="remember" class="ml-2 text-sm text-gray-600">
                            Recordarme
                        </label>
                    </div>
                    
                    <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                
                <!-- Botón de envío -->
                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        id="loginBtn">
                    <span id="login-text">Iniciar Sesión</span>
                    <div id="login-loading" class="hidden flex items-center">
                        <div class="spinner-border spinner-border-sm mr-2" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        Iniciando sesión...
                    </div>
                </button>
            </form>
            
            <!-- Enlaces adicionales -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-gray-50 text-gray-500">O</span>
                    </div>
                </div>
                
                <div class="mt-6 text-center">
                    <a href="<?php echo url('public/index.php'); ?>" class="text-blue-600 hover:text-blue-700 transition-colors">
                        ← Volver a los proyectos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel derecho - Imagen/Información -->
    <div class="hidden lg:block relative w-0 flex-1">
        <div class="absolute inset-0 h-full w-full bg-gradient-to-br from-blue-600 to-indigo-700">
            <div class="flex flex-col justify-center items-center h-full text-white p-12">
                <svg class="w-24 h-24 mb-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                
                <h1 class="text-4xl font-bold mb-4 text-center">Bienvenido de vuelta</h1>
                <p class="text-xl text-blue-100 text-center max-w-md mb-8">
                    Accede a tu cuenta para explorar proyectos, comentar y gestionar tus favoritos
                </p>
                
                <div class="grid grid-cols-1 gap-4 max-w-sm">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>Califica proyectos</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-red-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                        </svg>
                        <span>Guarda favoritos</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                        <span>Comenta y opina</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-purple-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>Dashboard personal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}

// Loading state en el formulario
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = document.getElementById('loginBtn');
    const loginText = document.getElementById('login-text');
    const loginLoading = document.getElementById('login-loading');
    
    submitBtn.disabled = true;
    loginText.classList.add('hidden');
    loginLoading.classList.remove('hidden');
});

// Función helper para validar email
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Focus automático en el primer campo con error o en email
window.addEventListener('load', function() {
    const firstError = document.querySelector('.border-red-500');
    if (firstError) {
        firstError.focus();
    } else {
        document.getElementById('email').focus();
    }
});

// Remover errores al empezar a escribir
document.getElementById('email').addEventListener('input', function() {
    this.classList.remove('border-red-500');
});

document.getElementById('password').addEventListener('input', function() {
    this.classList.remove('border-red-500');
});
</script>

</body>
</html>