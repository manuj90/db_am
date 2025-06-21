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
    <title>Iniciar Sesión - Ganymede</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'aurora-pink': '#ff0080',
                        'aurora-orange': '#ff8c00', 
                        'aurora-blue': '#00d4ff',
                        'aurora-purple': '#8b5cf6',
                        'dark': '#0a0a0f',
                        'surface': '#1a1a2e',
                        'surface-light': '#2d2d4a',
                        'primary': '#ff0080',
                        // ... y el resto de tu tema
                    },
                    // ... el resto de tu configuración de keyframes, etc.
                }
            }
        }
    </script>

    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark text-gray-200 dark">

    <div class="min-h-screen flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="text-center">
                    <a href="<?php echo url('public/index.php'); ?>" class="inline-block">
                        <img class="h-12 w-auto mx-auto invert" src="<?php echo asset('images/logo/LogoFull.png'); ?>" alt="Ganymede Logo">
                    </a>
                    <h2 class="mt-6 text-3xl font-bold text-white">Iniciar Sesión</h2>
                    <p class="mt-2 text-sm text-gray-400">
                        ¿No tienes cuenta? 
                        <a href="<?php echo url('public/registro.php'); ?>" class="font-semibold text-primary hover:text-aurora-pink/80 transition-colors">
                            Regístrate aquí
                        </a>
                    </p>
                </div>
                
                <div class="mt-8">
                    <?php if (isset($errors['login'])): ?>
                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                <div class="ml-3"><p class="text-sm text-red-300"><?php echo htmlspecialchars($errors['login']); ?></p></div>
                             </div>
                            </div>
                            <?php endif; ?>
                    
                    <form class="space-y-6" method="POST" action="" novalidate>
                        <div>
                            <label for="email" class="block text-sm font-medium leading-6 text-gray-300">Email</label>
                            <div class="mt-2">
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['email']) ? 'ring-2 ring-red-500' : ''; ?>"
                                       placeholder="tu@email.com" required autocomplete="email">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <p class="mt-2 text-sm text-red-400"><?php echo htmlspecialchars($errors['email']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium leading-6 text-gray-300">Contraseña</label>
                            <div class="mt-2 relative">
                                <input type="password" id="password" name="password"
                                       class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['password']) ? 'ring-2 ring-red-500' : ''; ?>"
                                       placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-200">
                                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <p class="mt-2 text-sm text-red-400"><?php echo htmlspecialchars($errors['password']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-primary bg-white/10 border-white/20 rounded focus:ring-primary focus:ring-offset-dark">
                                <label for="remember" class="ml-2 block text-sm text-gray-400">Recordarme</label>
                            </div>
                            <a href="#" class="text-sm font-semibold text-primary hover:text-aurora-pink/80">¿Olvidaste tu contraseña?</a>
                        </div>
                        
                        <button type="submit" id="loginBtn" class="flex w-full justify-center rounded-full bg-primary px-4 py-3 text-base font-semibold text-white shadow-sm hover:bg-aurora-pink/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-transform hover:scale-[1.02]">
                            <span id="login-text">Iniciar Sesión</span>
                            <div id="login-loading" class="hidden items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Iniciando sesión...</span>
                            </div>
                        </button>
                    </form>
                    
                    <div class="mt-8 text-center">
                        <a href="<?php echo url('public/index.php'); ?>" class="text-sm font-semibold text-gray-400 hover:text-white transition">← Volver a los proyectos</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hidden lg:block relative w-0 flex-1 overflow-hidden">
            <video class="absolute top-0 left-0 w-full h-full object-cover pointer-events-none" autoplay muted loop playsinline poster="<?php echo ASSETS_URL; ?>/images/log_reg/Stars.jpg">
                <source src="<?php echo ASSETS_URL; ?>/images/log_reg/StarsVid.mp4" type="video/mp4">
            </video>
        
            <div class="absolute inset-0 bg-dark/60"></div>
        
            <div class="relative flex flex-col justify-center items-center h-full text-white p-12">
                <div class="w-24 h-24 mb-8 flex items-center justify-center bg-white/5 rounded-2xl border border-white/10">
                    <img src="<?php echo asset('images/logo/LogoFav.png'); ?>" alt="Ganymede Logo" class="w-16 h-16">
                </div>
                <h1 class="text-4xl font-bold mb-4 text-center">Bienvenido de vuelta</h1>
                <p class="text-xl text-gray-300 text-center max-w-md mb-8">
                    Accede a tu cuenta para explorar, calificar y comentar en el universo de Ganymede.
                </p>
        
                <div class="space-y-4 max-w-sm text-lg">
                    <div class="flex items-center gap-x-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-yellow-400/20 text-yellow-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path fill-rule="evenodd"
                                    d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5ZM16.5 15a.75.75 0 0 1 .712.513l.394 1.183c.15.447.5.799.948.948l1.183.395a.75.75 0 0 1 0 1.422l-1.183.395c-.447.15-.799.5-.948.948l-.395 1.183a.75.75 0 0 1-1.422 0l-.395-1.183a1.5 1.5 0 0 0-.948-.948l-1.183-.395a.75.75 0 0 1 0-1.422l1.183-.395c.447-.15.799.5.948.948l.395-1.183A.75.75 0 0 1 16.5 15Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Califica proyectos</span>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-red-400/20 text-red-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path
                                    d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                            </svg>
                        </span>
                        <span>Guarda favoritos</span>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-green-400/20 text-green-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-2.429 0-4.817.178-7.152.521C2.87 3.061 1.5 4.795 1.5 6.741v6.018c0 1.946 1.37 3.68 3.348 3.97.877.129 1.761.234 2.652.316V21a.75.75 0 0 0 1.28.53l4.184-4.183a.39.39 0 0 1 .266-.112c2.006-.05 3.982-.22 5.922-.506 1.978-.29 3.348-2.023 3.348-3.97V6.741c0-1.947-1.37-3.68-3.348-3.97A49.145 49.145 0 0 0 12 2.25ZM8.25 8.625a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Zm2.625 1.125a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875-1.125a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Comenta y opina</span>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-400/20 text-purple-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path fill-rule="evenodd"
                                    d="M2.25 6a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V6Zm18 3H3.75v9a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V9Zm-15-3.75A.75.75 0 0 0 4.5 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H5.25Zm1.5.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V6Zm3-.75A.75.75 0 0 0 9 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H9.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Dashboard personal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // El JavaScript no necesita cambios, funcionará con el nuevo HTML.
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

        document.querySelector('form').addEventListener('submit', function () {
            const submitBtn = document.getElementById('loginBtn');
            if (!submitBtn) return;
            const loginText = document.getElementById('login-text');
            const loginLoading = document.getElementById('login-loading');

            submitBtn.disabled = true;
            if (loginText) loginText.classList.add('hidden');
            if (loginLoading) loginLoading.classList.remove('hidden');
        });

        window.addEventListener('load', function () {
            const firstError = document.querySelector('.ring-red-500');
            if (firstError) {
                firstError.focus();
            } else {
                document.getElementById('email')?.focus();
            }
        });

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function () {
                this.classList.remove('ring-red-500');
            });
        });
    </script>

</body>

</html>