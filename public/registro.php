<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

redirectIfLoggedIn();

$pageTitle = 'Registro - Agencia Multimedia';
$pageDescription = 'Crea tu cuenta en Agencia Multimedia';
$bodyClass = 'bg-gray-50';

$formData = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'telefono' => ''
];
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nombre'] = sanitize($_POST['nombre'] ?? '');
    $formData['apellido'] = sanitize($_POST['apellido'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['telefono'] = sanitize($_POST['telefono'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['password_confirm'] = $_POST['password_confirm'] ?? '';

    try {
        $result = registerUser($formData);

        if ($result['success']) {
            $user = authenticateUser($formData['email'], $formData['password']);
            if ($user) {
                loginUser($user);
                setFlashMessage('success', '¡Cuenta creada exitosamente! Bienvenido a Agencia Multimedia.');
                redirect(url('dashboard/user/index.php'));
            } else {
                $successMessage = 'Cuenta creada exitosamente. Ahora puedes iniciar sesión.';
            }
        } else {
            $errors = $result['errors'];
        }

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $errors['general'] = 'Error interno del servidor. Intente nuevamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Ganymede</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'aurora-pink': '#ff0080', 'aurora-orange': '#ff8c00', 'aurora-blue': '#00d4ff', 'aurora-purple': '#8b5cf6',
                        'dark': '#0a0a0f', 'surface': '#1a1a2e', 'surface-light': '#2d2d4a', 'primary': '#ff0080',
                    },
                }
            }
        }
    </script>
    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet">
</head>

<body class="bg-dark text-gray-200 dark">

    <div class="min-h-screen flex">
        <div class="hidden lg:block relative w-0 flex-1 overflow-hidden">
            <video class="absolute top-0 left-0 w-full h-full object-cover pointer-events-none" autoplay muted loop
                playsinline poster="<?php echo ASSETS_URL; ?>/images/log_reg/Stars.jpg">
                <source src="<?php echo ASSETS_URL; ?>/images/log_reg/StarsVid.mp4" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-dark/60"></div>
            <div class="relative flex flex-col justify-center items-center h-full text-white p-12">
                <a href="<?php echo url('public/index.php'); ?>">
                    <img src="<?php echo asset('images/logo/LogoFav.png'); ?>" alt="Ganymede Logo"
                        class="w-20 h-20 mb-8">
                </a>
                <h1 class="text-4xl font-bold mb-4 text-center">Únete a la órbita</h1>
                <p class="text-xl text-gray-300 text-center max-w-md mb-8">
                    Crea tu cuenta y forma parte de nuestra comunidad creativa.
                </p>
                <div class="space-y-5 max-w-sm text-lg w-full">
                    <div class="flex items-center gap-x-4 bg-white/5 border border-white/10 p-4 rounded-xl">
                        <span
                            class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-green-400/20 text-green-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-6 h-6">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-2.429 0-4.817.178-7.152.521C2.87 3.061 1.5 4.795 1.5 6.741v6.018c0 1.946 1.37 3.68 3.348 3.97.877.129 1.761.234 2.652.316V21a.75.75 0 0 0 1.28.53l4.184-4.183a.39.39 0 0 1 .266-.112c2.006-.05 3.982-.22 5.922-.506 1.978-.29 3.348-2.023 3.348-3.97V6.741c0-1.947-1.37-3.68-3.348-3.97A49.145 49.145 0 0 0 12 2.25ZM8.25 8.625a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Zm2.625 1.125a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875-1.125a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <h5 class="font-semibold text-white">Interactúa</h5>
                            <p class="text-sm text-gray-400">Califica y comenta proyectos.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-x-4 bg-white/5 border border-white/10 p-4 rounded-xl">
                        <span
                            class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-red-400/20 text-red-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-6 h-6">
                                <path
                                    d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                            </svg>
                        </span>
                        <div>
                            <h5 class="font-semibold text-white">Personaliza</h5>
                            <p class="text-sm text-gray-400">Crea tu lista de proyectos favoritos.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-x-4 bg-white/5 border border-white/10 p-4 rounded-xl">
                        <span
                            class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-purple-400/20 text-purple-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-6 h-6">
                                <path fill-rule="evenodd"
                                    d="M2.25 6a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V6Zm18 3H3.75v9a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V9Zm-15-3.75A.75.75 0 0 0 4.5 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H5.25Zm1.5.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V6Zm3-.75A.75.75 0 0 0 9 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H9.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <h5 class="font-semibold text-white">Gestiona</h5>
                            <p class="text-sm text-gray-400">Accede a tu dashboard personal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="text-center">
                    <a href="<?php echo url('public/index.php'); ?>" class="inline-block md:hidden">
                        <img class="h-12 w-auto mx-auto" src="<?php echo asset('images/logo/LogoFav.png'); ?>"
                            alt="Ganymede Logo">
                    </a>
                    <h2 class="mt-6 text-3xl font-bold text-white">Crear Cuenta</h2>
                    <p class="mt-2 text-sm text-gray-400">
                        ¿Ya tienes cuenta?
                        <a href="<?php echo url('public/login.php'); ?>"
                            class="font-semibold text-primary hover:text-aurora-pink/80 transition-colors">
                            Inicia sesión aquí
                        </a>
                    </p>
                </div>

                <div class="mt-8">
                    <?php if ($successMessage): ?>
                        <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-4 text-center">
                            <p class="text-sm text-green-300"><?php echo htmlspecialchars($successMessage); ?></p>
                            <a href="<?php echo url('public/login.php'); ?>"
                                class="font-semibold text-primary hover:text-aurora-pink/80 mt-2 inline-block">Ir al login
                                →</a>
                        </div>
                    <?php else: ?>
                        <form class="space-y-4" method="POST" action="" novalidate>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="nombre" class="block text-sm font-medium leading-6 text-gray-300">Nombre
                                        *</label>
                                    <div class="mt-1">
                                        <input type="text" id="nombre" name="nombre"
                                            value="<?php echo htmlspecialchars($formData['nombre']); ?>"
                                            class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['nombre']) ? 'ring-2 ring-red-500' : ''; ?>"
                                            required>
                                    </div>
                                    <?php if (isset($errors['nombre'])): ?>
                                        <p class="mt-1 text-sm text-red-400"><?php echo htmlspecialchars($errors['nombre']); ?>
                                        </p><?php endif; ?>
                                </div>
                                <div>
                                    <label for="apellido" class="block text-sm font-medium leading-6 text-gray-300">Apellido
                                        *</label>
                                    <div class="mt-1">
                                        <input type="text" id="apellido" name="apellido"
                                            value="<?php echo htmlspecialchars($formData['apellido']); ?>"
                                            class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['apellido']) ? 'ring-2 ring-red-500' : ''; ?>"
                                            required>
                                    </div>
                                    <?php if (isset($errors['apellido'])): ?>
                                        <p class="mt-1 text-sm text-red-400">
                                            <?php echo htmlspecialchars($errors['apellido']); ?></p><?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium leading-6 text-gray-300">Email *</label>
                                <div class="mt-1">
                                    <input type="email" id="email" name="email"
                                        value="<?php echo htmlspecialchars($formData['email']); ?>"
                                        class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['email']) ? 'ring-2 ring-red-500' : ''; ?>"
                                        required>
                                </div>
                                <?php if (isset($errors['email'])): ?>
                                    <p class="mt-1 text-sm text-red-400"><?php echo htmlspecialchars($errors['email']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium leading-6 text-gray-300">Contraseña
                                    *</label>
                                <div class="mt-1">
                                    <input type="password" id="password" name="password"
                                        class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['password']) ? 'ring-2 ring-red-500' : ''; ?>"
                                        required>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <p class="mt-1 text-sm text-red-400"><?php echo htmlspecialchars($errors['password']); ?>
                                    </p><?php endif; ?>
                            </div>

                            <div>
                                <label for="password_confirm"
                                    class="block text-sm font-medium leading-6 text-gray-300">Confirmar Contraseña *</label>
                                <div class="mt-1">
                                    <input type="password" id="password_confirm" name="password_confirm"
                                        class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['password_confirm']) ? 'ring-2 ring-red-500' : ''; ?>"
                                        required>
                                </div>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <p class="mt-1 text-sm text-red-400">
                                        <?php echo htmlspecialchars($errors['password_confirm']); ?></p><?php endif; ?>
                            </div>

                            <div class="flex items-center pt-2">
                                <input type="checkbox" id="terms" name="terms" required
                                    class="w-4 h-4 text-primary bg-white/10 border-white/20 rounded focus:ring-primary focus:ring-offset-dark">
                                <label for="terms" class="ml-2 block text-sm text-gray-400">Acepto los <a href="#"
                                        class="font-semibold text-primary hover:text-aurora-pink/80 underline">términos</a>
                                    y <a href="#"
                                        class="font-semibold text-primary hover:text-aurora-pink/80 underline">políticas de
                                        privacidad</a></label>
                            </div>

                            <div>
                                <button type="submit" id="registerBtn"
                                    class="flex w-full justify-center rounded-full bg-primary px-4 py-3 text-base font-semibold text-white shadow-sm hover:bg-aurora-pink/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-transform hover:scale-[1.02]">
                                    <span id="register-text">Crear Cuenta</span>
                                    <div id="register-loading" class="hidden items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>Creando cuenta...</span>
                                    </div>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="mt-8 text-center">
                        <a href="<?php echo url('public/index.php'); ?>"
                            class="text-sm font-semibold text-gray-400 hover:text-white transition">← Volver a los
                            proyectos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = passwordInput.parentNode.querySelector('.eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m0 0l3.122 3.122M12 12l4.242-4.242"/>
        `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
            }
        }

        // Loading state en el formulario
        document.querySelector('form').addEventListener('submit', function (e) {
            const termsCheckbox = document.getElementById('terms');

            if (!termsCheckbox.checked) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones');
                return;
            }

            const submitBtn = document.getElementById('registerBtn');
            const registerText = document.getElementById('register-text');
            const registerLoading = document.getElementById('register-loading');

            submitBtn.disabled = true;
            registerText.classList.add('hidden');
            registerLoading.classList.remove('hidden');
        });

        // Validación de contraseñas en tiempo real
        document.getElementById('password_confirm').addEventListener('input', function () {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('border-red-500');

                let errorElement = this.parentNode.parentNode.querySelector('.form-error');
                if (!errorElement) {
                    errorElement = document.createElement('p');
                    errorElement.className = 'form-error';
                    this.parentNode.parentNode.appendChild(errorElement);
                }
                errorElement.textContent = 'Las contraseñas no coinciden';
            } else {
                this.classList.remove('border-red-500');
                const errorElement = this.parentNode.parentNode.querySelector('.form-error');
                if (errorElement && errorElement.textContent === 'Las contraseñas no coinciden') {
                    errorElement.remove();
                }
            }
        });

        // Validación de email en tiempo real
        document.getElementById('email').addEventListener('blur', function () {
            const email = this.value.trim();

            if (email && !isValidEmail(email)) {
                this.classList.add('border-red-500');

                let errorElement = this.parentNode.querySelector('.form-error');
                if (!errorElement) {
                    errorElement = document.createElement('p');
                    errorElement.className = 'form-error';
                    this.parentNode.appendChild(errorElement);
                }
                errorElement.textContent = 'Email inválido';
            } else {
                this.classList.remove('border-red-500');
                const errorElement = this.parentNode.querySelector('.form-error');
                if (errorElement && errorElement.textContent === 'Email inválido') {
                    errorElement.remove();
                }
            }
        });

        // Función helper para validar email
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Validación de longitud de contraseña
        document.getElementById('password').addEventListener('input', function () {
            const password = this.value;

            if (password.length > 0 && password.length < 6) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });

        // Validación de longitud mínima para nombre y apellido
        document.getElementById('nombre').addEventListener('blur', function () {
            if (this.value.trim().length > 0 && this.value.trim().length < 2) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });

        document.getElementById('apellido').addEventListener('blur', function () {
            if (this.value.trim().length > 0 && this.value.trim().length < 2) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });

        // Remover errores al empezar a escribir
        ['nombre', 'apellido', 'email', 'telefono', 'password', 'password_confirm'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', function () {
                this.classList.remove('border-red-500');
            });
        });

        // Focus automático en el primer campo con error
        window.addEventListener('load', function () {
            const firstError = document.querySelector('.border-red-500');
            if (firstError) {
                firstError.focus();
            } else {
                document.getElementById('nombre').focus();
            }
        });
    </script>

</body>

</html>