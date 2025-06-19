<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Redirigir si ya está logueado
redirectIfLoggedIn();

// Configuración de página
$pageTitle = 'Registro - Agencia Multimedia';
$pageDescription = 'Crea tu cuenta en Agencia Multimedia';
$bodyClass = 'bg-gray-50';

// Variables para el formulario
$formData = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'telefono' => ''
];
$errors = [];
$successMessage = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $formData['nombre'] = sanitize($_POST['nombre'] ?? '');
    $formData['apellido'] = sanitize($_POST['apellido'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['telefono'] = sanitize($_POST['telefono'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['password_confirm'] = $_POST['password_confirm'] ?? '';
    
    // Intentar registrar usuario
    try {
        $result = registerUser($formData);
        
        if ($result['success']) {
            // Registro exitoso - intentar login automático
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

// Incluir header
include '../includes/templates/header.php';
?>

<div class="min-h-screen flex">
    <!-- Panel izquierdo - Información -->
    <div class="hidden lg:block relative w-0 flex-1">
        <div class="absolute inset-0 h-full w-full bg-gradient-to-br from-secondary to-primary">
            <div class="flex flex-col justify-center items-center h-full text-white p-12">
                <svg class="w-24 h-24 mb-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                
                <h1 class="text-4xl font-bold mb-4 text-center">Únete a nosotros</h1>
                <p class="text-xl text-purple-100 text-center max-w-md mb-8">
                    Crea tu cuenta y forma parte de nuestra comunidad de diseño
                </p>
                
                <div class="grid grid-cols-1 gap-6 max-w-sm">
                    <div class="bg-white bg-opacity-10 rounded-lg p-4">
                        <div class="flex items-center space-x-3 mb-2">
                            <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="font-semibold">Interactúa</span>
                        </div>
                        <p class="text-purple-100 text-sm">Califica y comenta proyectos de nuestra agencia</p>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-lg p-4">
                        <div class="flex items-center space-x-3 mb-2">
                            <svg class="w-6 h-6 text-red-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold">Personaliza</span>
                        </div>
                        <p class="text-purple-100 text-sm">Crea tu lista de proyectos favoritos</p>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-lg p-4">
                        <div class="flex items-center space-x-3 mb-2">
                            <svg class="w-6 h-6 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold">Gestiona</span>
                        </div>
                        <p class="text-purple-100 text-sm">Accede a tu dashboard personal</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel derecho - Formulario -->
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <!-- Logo y título -->
            <div class="text-center">
                <div class="flex justify-center">
                    <svg class="w-12 h-12 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Crear Cuenta</h2>
                <p class="mt-2 text-sm text-gray-600">
                    ¿Ya tienes cuenta? 
                    <a href="<?php echo url('public/login.php'); ?>" class="font-medium text-primary hover:text-blue-700 transition-colors">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
            
            <!-- Mensaje de éxito -->
            <?php if ($successMessage): ?>
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                            <p class="text-sm text-green-600 mt-2">
                            <a href="<?php echo url('public/login.php'); ?>" class="font-medium underline">Ir al login</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Mensajes de error generales -->
            <?php if (isset($errors['general'])): ?>
                <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo htmlspecialchars($errors['general']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de registro -->
            <form class="mt-8 space-y-6" method="POST" action="" novalidate>
                <!-- Nombre y Apellido -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre" class="form-label">
                            Nombre *
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($formData['nombre']); ?>"
                               class="form-input <?php echo isset($errors['nombre']) ? 'border-red-500' : ''; ?>"
                               placeholder="Tu nombre"
                               required
                               autocomplete="given-name">
                        <?php if (isset($errors['nombre'])): ?>
                            <p class="form-error"><?php echo htmlspecialchars($errors['nombre']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="apellido" class="form-label">
                            Apellido *
                        </label>
                        <input type="text" 
                               id="apellido" 
                               name="apellido" 
                               value="<?php echo htmlspecialchars($formData['apellido']); ?>"
                               class="form-input <?php echo isset($errors['apellido']) ? 'border-red-500' : ''; ?>"
                               placeholder="Tu apellido"
                               required
                               autocomplete="family-name">
                        <?php if (isset($errors['apellido'])): ?>
                            <p class="form-error"><?php echo htmlspecialchars($errors['apellido']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="form-label">
                        Email *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>"
                           class="form-input <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                           placeholder="tu@email.com"
                           required
                           autocomplete="email">
                    <?php if (isset($errors['email'])): ?>
                        <p class="form-error"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Teléfono (opcional) -->
                <div>
                    <label for="telefono" class="form-label">
                        Teléfono <span class="text-gray-400">(opcional)</span>
                    </label>
                    <input type="tel" 
                           id="telefono" 
                           name="telefono" 
                           value="<?php echo htmlspecialchars($formData['telefono']); ?>"
                           class="form-input <?php echo isset($errors['telefono']) ? 'border-red-500' : ''; ?>"
                           placeholder="+54 11 1234-5678"
                           autocomplete="tel">
                    <?php if (isset($errors['telefono'])): ?>
                        <p class="form-error"><?php echo htmlspecialchars($errors['telefono']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Contraseña -->
                <div>
                    <label for="password" class="form-label">
                        Contraseña *
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>"
                               placeholder="Mínimo 6 caracteres"
                               required
                               autocomplete="new-password">
                        <button type="button" 
                                onclick="togglePassword('password')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="form-error"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500">Mínimo 6 caracteres</p>
                </div>
                
                <!-- Confirmar Contraseña -->
                <div>
                    <label for="password_confirm" class="form-label">
                        Confirmar Contraseña *
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               class="form-input <?php echo isset($errors['password_confirm']) ? 'border-red-500' : ''; ?>"
                               placeholder="Repite tu contraseña"
                               required
                               autocomplete="new-password">
                        <button type="button" 
                                onclick="togglePassword('password_confirm')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <p class="form-error"><?php echo htmlspecialchars($errors['password_confirm']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Términos y condiciones -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="terms" 
                           name="terms" 
                           required
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="terms" class="ml-2 text-sm text-gray-600">
                        Acepto los 
                        <a href="#" class="text-primary hover:text-blue-700 underline">términos y condiciones</a>
                        y la 
                        <a href="#" class="text-primary hover:text-blue-700 underline">política de privacidad</a>
                    </label>
                </div>
                
                <!-- Botón de envío -->
                <button type="submit" 
                        class="w-full btn btn-primary py-3 text-lg font-semibold"
                        id="registerBtn">
                    <span id="register-text">Crear Cuenta</span>
                    <div id="register-loading" class="hidden flex items-center">
                        <div class="spinner mr-2"></div>
                        Creando cuenta...
                    </div>
                </button>
            </form>
            
            <!-- Enlaces adicionales -->
            <div class="mt-6 text-center">
            <a href="<?php echo url('public/index.php'); ?>" class="text-primary hover:text-blue-700 transition-colors">
                    ← Volver a los proyectos
                </a>
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
document.querySelector('form').addEventListener('submit', function(e) {
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
document.getElementById('password_confirm').addEventListener('input', function() {
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
document.getElementById('email').addEventListener('blur', function() {
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
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    
    if (password.length > 0 && password.length < 6) {
        this.classList.add('border-red-500');
    } else {
        this.classList.remove('border-red-500');
    }
});

// Validación de longitud mínima para nombre y apellido
document.getElementById('nombre').addEventListener('blur', function() {
    if (this.value.trim().length > 0 && this.value.trim().length < 2) {
        this.classList.add('border-red-500');
    } else {
        this.classList.remove('border-red-500');
    }
});

document.getElementById('apellido').addEventListener('blur', function() {
    if (this.value.trim().length > 0 && this.value.trim().length < 2) {
        this.classList.add('border-red-500');
    } else {
        this.classList.remove('border-red-500');
    }
});

// Remover errores al empezar a escribir
['nombre', 'apellido', 'email', 'telefono', 'password', 'password_confirm'].forEach(fieldId => {
    document.getElementById(fieldId).addEventListener('input', function() {
        this.classList.remove('border-red-500');
    });
});

// Focus automático en el primer campo con error
window.addEventListener('load', function() {
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