<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$isAdminUser = isAdmin();
$dashboardPath = url($isAdminUser ? 'dashboard/admin/index.php' : 'dashboard/user/index.php');
$dashboardLabel = $isAdminUser ? 'Dashboard Admin' : 'Mi Dashboard';
$userType = $isAdminUser ? 'Administrador' : 'Usuario';

$pageTitle = 'Mi Perfil - ' . ($isAdminUser ? 'Admin' : 'Usuario');

$currentUserId = getCurrentUserId();

// Procesar acciones POST (excepto subida de foto, que es por AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido.');
        redirect('dashboard/shared/perfil.php');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? '')
            ];
            $result = updateUserProfile($currentUserId, $data);
            if ($result['success']) {
                $_SESSION['nombre'] = $data['nombre'];
                $_SESSION['apellido'] = $data['apellido'];
                $_SESSION['email'] = $data['email'];
                setFlashMessage('success', 'Perfil actualizado correctamente.');
            } else {
                setFlashMessage('error', 'Error al actualizar perfil: ' . implode(', ', $result['errors'] ?? []));
            }
            break;

        case 'change_password':
            $result = changeUserPassword($currentUserId, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '');
            if ($result['success']) {
                setFlashMessage('success', 'Contraseña cambiada correctamente.');
            } else {
                setFlashMessage('error', 'Error al cambiar contraseña: ' . implode(', ', $result['errors'] ?? []));
            }
            break;

        case 'remove_photo':
            $currentUserForRemove = getUserById($currentUserId);
            if (!empty($currentUserForRemove['foto_perfil'])) {
                // CORREGIDO: Usar __DIR__ en lugar de ASSETS_PATH
                $photoPath = __DIR__ . '/../../assets/images/usuarios/' . $currentUserForRemove['foto_perfil'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            $db = getDB();
            if ($db->update("UPDATE USUARIOS SET foto_perfil = NULL WHERE id_usuario = :id", ['id' => $currentUserId])) {
                $_SESSION['foto_perfil'] = null;
                setFlashMessage('success', 'Foto de perfil eliminada.');
            } else {
                setFlashMessage('error', 'Error al eliminar la foto de perfil.');
            }
            break;
    }

    redirect('dashboard/shared/perfil.php');
}

$currentUser = getUserById($currentUserId);
$userStats = getUserStats($currentUserId);
$recentActivity = getUserRecentActivity($currentUserId, 10);

include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';

?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Mi Perfil</h1>
                    <p class="text-gray-400 mt-2 text-lg">Administra tu información personal y de tu cuenta.</p>
                </div>
                <a href="<?= $dashboardPath ?>"
                    class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path
                            d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                        <path
                            d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                    </svg>

                    Volver al <?= $dashboardLabel ?>
                </a>
            </div>
            <?php if ($isAdminUser): ?>
                <div
                    class="inline-flex items-center gap-x-2 px-3 py-1 rounded-full text-xs font-medium bg-purple-500/10 text-purple-300 mt-4 border border-purple-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                        <path fill-rule="evenodd"
                            d="M8 1a3.5 3.5 0 0 0-3.5 3.5V7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7V4.5A3.5 3.5 0 0 0 8 1Zm2 6V4.5a2 2 0 1 0-4 0V7h4Z"
                            clip-rule="evenodd" />
                    </svg>


                    Cuenta de Administrador
                </div>
            <?php endif; ?>
        </div>

        <?php if (hasFlashMessage('success')): ?>
            <div
                class="mb-6 bg-green-500/10 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg flex items-center gap-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                        clip-rule="evenodd" />
                </svg>
                <span><?= getFlashMessage('success') ?></span>
            </div>
        <?php endif; ?>
        <?php if (hasFlashMessage('error')): ?>
            <div
                class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg flex items-center gap-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-2.707-8.293a.75.75 0 0 0-1.06 1.06L8.94 10l-1.707 1.707a.75.75 0 1 0 1.06 1.06L10 11.06l1.707 1.707a.75.75 0 1 0 1.06-1.06L11.06 10l1.707-1.707a.75.75 0 0 0-1.06-1.06L10 8.94 7.293 7.293Z"
                        clip-rule="evenodd" />
                </svg>
                <span><?= getFlashMessage('error') ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">

                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-white mb-6">Foto de Perfil</h2>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="relative flex-shrink-0">
                            <div id="avatar-container"
                                class="w-24 h-24 rounded-full bg-gradient-to-br from-primary to-aurora-purple flex items-center justify-center border-4 border-surface">
                                <?php if (!empty($currentUser['foto_perfil']) && file_exists(ASSETS_PATH . '/images/usuarios/' . $currentUser['foto_perfil'])): ?>
                                    <img src="<?= asset('images/usuarios/' . $currentUser['foto_perfil']) ?>"
                                        alt="Foto de perfil" class="w-full h-full rounded-full object-cover">
                                <?php else: ?>
                                    <span
                                        class="text-3xl font-bold text-white"><?= strtoupper(substr($currentUser['nombre'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <div id="uploadFormContainer" class="space-y-4">
                                <div>
                                    <label for="foto_perfil"
                                        class="block text-sm font-medium text-gray-300 mb-2">Cambiar foto</label>
                                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*"
                                        class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-white/5 file:text-gray-300 hover:file:bg-white/10 transition cursor-pointer">
                                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, GIF o WebP. Máximo 5MB.</p>
                                </div>

                                <div class="flex items-center gap-x-4">
                                    <button type="button" id="upload-button" disabled
                                        class="inline-flex items-center gap-x-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
                                        </svg>

                                        Subir Foto
                                    </button>
                                    <div id="upload-status" class="text-sm h-5"></div>
                                </div>

                            </div>
                            <?php if (!empty($currentUser['foto_perfil'])): ?>
                                <form method="POST" class="mt-4">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-white mb-6">Información Personal</h2>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nombre" class="block text-sm font-medium leading-6 text-gray-300">Nombre
                                    *</label>
                                <input type="text" id="nombre" name="nombre"
                                    value="<?= htmlspecialchars($currentUser['nombre']) ?>"
                                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                    required>
                            </div>
                            <div>
                                <label for="apellido" class="block text-sm font-medium leading-6 text-gray-300">Apellido
                                    *</label>
                                <input type="text" id="apellido" name="apellido"
                                    value="<?= htmlspecialchars($currentUser['apellido']) ?>"
                                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                    required>
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium leading-6 text-gray-300">Email *</label>
                            <input type="email" id="email" name="email"
                                value="<?= htmlspecialchars($currentUser['email']) ?>"
                                class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                required>
                        </div>
                        <div class="flex items-center justify-end pt-4 border-t border-white/10">
                            <button type="submit"
                                class="inline-flex items-center gap-x-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6">
                                    <path
                                        d="M12 1.5a.75.75 0 0 1 .75.75V7.5h-1.5V2.25A.75.75 0 0 1 12 1.5ZM11.25 7.5v5.69l-1.72-1.72a.75.75 0 0 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 1 0-1.06-1.06l-1.72 1.72V7.5h3.75a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-9a3 3 0 0 1-3-3v-9a3 3 0 0 1 3-3h3.75Z" />
                                </svg>

                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-white mb-6">Cambiar Contraseña</h2>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label for="current_password"
                                class="block text-sm font-medium leading-6 text-gray-300">Contraseña Actual *</label>
                            <input type="password" id="current_password" name="current_password"
                                class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                required>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new_password"
                                    class="block text-sm font-medium leading-6 text-gray-300">Nueva Contraseña *</label>
                                <input type="password" id="new_password" name="new_password"
                                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                    minlength="6" required>
                            </div>
                            <div>
                                <label for="confirm_password"
                                    class="block text-sm font-medium leading-6 text-gray-300">Confirmar Contraseña
                                    *</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                                    minlength="6" required>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-white/10">
                            <p class="text-sm text-gray-500">Mínimo 6 caracteres</p>
                            <button type="submit"
                                class="inline-flex items-center gap-x-2 rounded-full bg-aurora-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-orange/80 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6">
                                    <path fill-rule="evenodd"
                                        d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                                        clip-rule="evenodd" />
                                </svg>

                                Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Información de Cuenta</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-400">Tipo:</span><span
                                class="font-medium text-white"><?= $userType ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-400">Miembro desde:</span><span
                                class="font-medium text-white"><?= formatDate($currentUser['fecha_registro']) ?></span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-400">Estado:</span><span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/10 text-green-300">Activo</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-400">ID de usuario:</span><span
                                class="font-medium text-white">#<?= $currentUser['id_usuario'] ?></span></div>
                    </div>
                </div>
                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Mis Estadísticas</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-3"><svg xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16" fill="currentColor" class="size-4 fill-red-700">
                                    <path
                                        d="M2 6.342a3.375 3.375 0 0 1 6-2.088 3.375 3.375 0 0 1 5.997 2.26c-.063 2.134-1.618 3.76-2.955 4.784a14.437 14.437 0 0 1-2.676 1.61c-.02.01-.038.017-.05.022l-.014.006-.004.002h-.002a.75.75 0 0 1-.592.001h-.002l-.004-.003-.015-.006a5.528 5.528 0 0 1-.232-.107 14.395 14.395 0 0 1-2.535-1.557C3.564 10.22 1.999 8.558 1.999 6.38L2 6.342Z" />
                                </svg>
                                <span class="text-gray-300">Favoritos</span>
                            </div><span class="font-bold text-white"><?= $userStats['favoritos'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-3"><svg xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16" fill="currentColor" class="size-4 fill-sky-700">
                                    <path fill-rule="evenodd"
                                        d="M1 8.74c0 .983.713 1.825 1.69 1.943.764.092 1.534.164 2.31.216v2.351a.75.75 0 0 0 1.28.53l2.51-2.51c.182-.181.427-.286.684-.294a44.298 44.298 0 0 0 3.837-.293C14.287 10.565 15 9.723 15 8.74V4.26c0-.983-.713-1.825-1.69-1.943a44.447 44.447 0 0 0-10.62 0C1.712 2.435 1 3.277 1 4.26v4.482ZM5.5 6.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm2.5 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm3.5 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-300">Comentarios</span>
                            </div><span class="font-bold text-white"><?= $userStats['comentarios'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-3"><svg xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16" fill="currentColor" class="size-4 fill-yellow-700">
                                    <path fill-rule="evenodd"
                                        d="M8 1.75a.75.75 0 0 1 .692.462l1.41 3.393 3.664.293a.75.75 0 0 1 .428 1.317l-2.791 2.39.853 3.575a.75.75 0 0 1-1.12.814L7.998 12.08l-3.135 1.915a.75.75 0 0 1-1.12-.814l.852-3.574-2.79-2.39a.75.75 0 0 1 .427-1.318l3.663-.293 1.41-3.393A.75.75 0 0 1 8 1.75Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-300">Calificaciones</span>
                            </div><span class="font-bold text-white"><?= $userStats['calificaciones'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Actividad Reciente</h3>
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <p class="text-sm">No hay actividad reciente</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-64 overflow-y-auto pr-2">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="flex items-start gap-x-3 text-sm">
                                    <div class="mt-1 w-2 h-2 rounded-full <?php if ($activity['tipo'] === 'comentario')
                                        echo 'bg-blue-400';
                                    elseif ($activity['tipo'] === 'favorito')
                                        echo 'bg-red-400';
                                    else
                                        echo 'bg-yellow-400'; ?>">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-200 truncate"><?= htmlspecialchars($activity['descripcion']) ?></p>
                                        <p class="text-gray-500 text-xs"><?= timeAgo($activity['fecha']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function confirmLogout() {
        if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
            window.location.href = '<?= url('public/logout.php') ?>';
        }
    }
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>