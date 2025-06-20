<nav class="bg-white shadow-lg sticky top-0 z-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo y título -->
            <div class="flex items-center">
                <a href="<?php echo url('public/index.php'); ?>" class="flex items-center space-x-3 text-xl font-bold text-gray-900 hover:text-primary transition-colors">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Agencia Multimedia</span>
                </a>
            </div>
            
            <!-- Menú principal (desktop) -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="<?php echo url('public/index.php'); ?>" 
                   class="text-gray-700 hover:text-primary transition-colors font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary border-b-2 border-primary' : ''; ?>">
                    Proyectos
                </a>
                
                <!-- Dropdown de Categorías (Arreglado) -->
                <div class="relative">
                    <button onclick="toggleDropdown('categoryDropdown')" 
                            class="flex items-center space-x-1 text-gray-700 hover:text-primary transition-colors font-medium focus:outline-none">
                        <span>Categorías</span>
                        <svg class="w-4 h-4 transition-transform" id="categoryDropdownIcon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <div id="categoryDropdown" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-100 animate-fade-in">
                        <?php foreach (getAllCategories() as $cat): ?>
                            <a href="<?php echo url('public/categoria.php?categoria=' . $cat['id_categoria']); ?>" 
                               class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <div class="w-2 h-2 bg-primary rounded-full mr-3"></div>
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($cat['nombre']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars(truncateText($cat['descripcion'], 40)); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="<?php echo url('public/buscar.php'); ?>" 
                               class="flex items-center px-4 py-2 text-primary hover:bg-blue-50 transition text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Ver todas las categorías
                            </a>
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo url('public/buscar.php'); ?>" 
                   class="text-gray-700 hover:text-primary transition-colors font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'buscar.php') ? 'text-primary border-b-2 border-primary' : ''; ?>">
                    Buscar
                </a>
                
                <!-- Usuario logueado -->
                <?php if (isLoggedIn()): ?>
                    <div class="relative">
                        <button onclick="toggleDropdown('userDropdown')" 
                                class="flex items-center space-x-2 text-gray-700 hover:text-primary transition-colors font-medium focus:outline-none">
                            
                            <!-- Avatar con foto de perfil o iniciales -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold overflow-hidden border-2 border-gray-200 hover:border-primary transition-colors">
                                <?php 
                                $photoPath = '';
                                $hasPhoto = false;
                                
                                // Verificar si existe foto de perfil
                                if (!empty($_SESSION['foto_perfil'])) {
                                    $photoPath = __DIR__ . '/../../assets/images/usuarios/' . $_SESSION['foto_perfil'];
                                    if (file_exists($photoPath)) {
                                        $hasPhoto = true;
                                        $photoUrl = asset('images/usuarios/' . $_SESSION['foto_perfil']);
                                    }
                                }
                                
                                if ($hasPhoto): ?>
                                    <img src="<?php echo $photoUrl; ?>" 
                                         alt="Foto de perfil" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-primary text-white flex items-center justify-center">
                                        <?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <span><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                            <svg class="w-4 h-4 transition-transform" id="userDropdownIcon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-100 animate-fade-in">
                            <!-- Header del dropdown con foto de perfil -->
                            <div class="px-4 py-3 border-b border-gray-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-200">
                                        <?php if ($hasPhoto): ?>
                                            <img src="<?php echo $photoUrl; ?>" 
                                                 alt="Foto de perfil" 
                                                 class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-primary text-white flex items-center justify-center text-sm font-semibold">
                                                <?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></p>
                                        <p class="text-sm text-gray-500 truncate"><?php echo $_SESSION['email']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="<?php echo url('dashboard/' . (isAdmin() ? 'admin' : 'user') . '/index.php'); ?>" 
                               class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h0a2 2 0 012 2v0H8v0z"/>
                                </svg>
                                Dashboard
                            </a>
                            
                            <a href="<?php echo url('dashboard/shared/perfil.php'); ?>" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Mi Perfil
                            </a>
                            
                            <a href="<?php echo url('dashboard/user/favoritos.php'); ?>" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                Mis Favoritos
                            </a>
                            
                            <a href="<?php echo url('dashboard/user/comentarios.php'); ?>" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                Mis Comentarios
                            </a>
                            
                            <a href="<?php echo url('dashboard/user/clasificaciones.php'); ?>" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                Mis Calificaciones
                            </a>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <a href="<?php echo url('public/logout.php'); ?>" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 transition text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Usuario no logueado -->
                    <div class="flex items-center space-x-4">
                        <a href="<?php echo url('public/login.php'); ?>" 
                           class="text-gray-700 hover:text-primary transition-colors font-medium">
                            Iniciar Sesión
                        </a>
                        <a href="<?php echo url('public/registro.php'); ?>" 
                           class="btn btn-primary">
                            Registrarse
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Botón menú móvil -->
            <button onclick="toggleMobileMenu()" 
                    class="md:hidden text-gray-700 hover:text-primary focus:outline-none focus:text-primary transition-colors">
                <svg class="w-6 h-6" id="menuIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg class="w-6 h-6 hidden" id="closeIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Menú móvil -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-gray-200 py-4">
            <div class="space-y-2">
                <a href="<?php echo url('public/index.php'); ?>" 
                   class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors font-medium">
                    Proyectos
                </a>
                
                <!-- Categorías en móvil (mejorado) -->
                <div class="px-3 py-2">
                    <div class="mb-2">
                        <span class="text-sm font-medium text-gray-700">Categorías</span>
                    </div>
                    <div class="grid grid-cols-1 gap-1">
                        <?php foreach (getAllCategories() as $cat): ?>
                            <a href="<?php echo url('public/categoria.php?categoria=' . $cat['id_categoria']); ?>" 
                               class="flex items-center px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors text-sm">
                                <div class="w-2 h-2 bg-primary rounded-full mr-3"></div>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <a href="<?php echo url('public/buscar.php'); ?>" 
                   class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors font-medium">
                    Buscar
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <div class="border-t border-gray-200 pt-2 mt-2">
                        <!-- Header móvil con foto de perfil -->
                        <div class="px-3 py-2 flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-200">
                                <?php if ($hasPhoto): ?>
                                    <img src="<?php echo $photoUrl; ?>" 
                                         alt="Foto de perfil" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-primary text-white flex items-center justify-center text-sm font-semibold">
                                        <?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate"><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></p>
                                <p class="text-sm text-gray-500 truncate"><?php echo $_SESSION['email']; ?></p>
                            </div>
                        </div>
                        
                        <a href="<?php echo url('dashboard/' . (isAdmin() ? 'admin' : 'user') . '/index.php'); ?>" 
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Dashboard
                        </a>
                        
                        <a href="<?php echo url('dashboard/shared/perfil.php'); ?>"
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Mi Perfil
                        </a>
                        
                        <a href="<?php echo url('dashboard/user/favoritos.php'); ?>" 
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Mis Favoritos
                        </a>
                        
                        <a href="<?php echo url('dashboard/user/comentarios.php'); ?>" 
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Mis Comentarios
                        </a>
                        
                        <a href="<?php echo url('dashboard/user/clasificaciones.php'); ?>" 
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Mis Calificaciones
                        </a>
                        
                        <a href="<?php echo url('public/logout.php'); ?>" 
                           class="block px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                            Cerrar Sesión
                        </a>
                    </div>
                <?php else: ?>
                    <div class="border-t border-gray-200 pt-2 mt-2 space-y-2">
                        <a href="<?php echo url('public/login.php'); ?>" 
                           class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md transition-colors">
                            Iniciar Sesión
                        </a>
                        
                        <a href="<?php echo url('public/registro.php'); ?>" 
                           class="block px-3 py-2 bg-primary text-white hover:bg-blue-700 rounded-md transition-colors text-center">
                            Registrarse
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
// JavaScript para funcionalidad del menú
function toggleDropdown(dropdownId) {
    // Cerrar otros dropdowns primero
    const allDropdowns = ['userDropdown', 'categoryDropdown'];
    allDropdowns.forEach(id => {
        if (id !== dropdownId) {
            const dropdown = document.getElementById(id);
            const icon = document.getElementById(id.replace('Dropdown', 'DropdownIcon'));
            if (dropdown) dropdown.classList.add('hidden');
            if (icon) icon.style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle el dropdown solicitado
    const dropdown = document.getElementById(dropdownId);
    const icon = document.getElementById(dropdownId.replace('Dropdown', 'DropdownIcon'));
    
    if (dropdown) {
        dropdown.classList.toggle('hidden');
        
        if (icon) {
            icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }
}

function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const menuIcon = document.getElementById('menuIcon');
    const closeIcon = document.getElementById('closeIcon');
    
    mobileMenu.classList.toggle('hidden');
    menuIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
}

// Cerrar dropdowns al hacer click fuera
document.addEventListener('click', function(event) {
    const dropdowns = ['userDropdown', 'categoryDropdown'];
    
    dropdowns.forEach(dropdownId => {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown && !event.target.closest('.relative')) {
            dropdown.classList.add('hidden');
            const icon = document.getElementById(dropdownId.replace('Dropdown', 'DropdownIcon'));
            if (icon) {
                icon.style.transform = 'rotate(0deg)';
            }
        }
    });
});

// Cerrar menú móvil al cambiar tamaño de ventana
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) { // md breakpoint
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');
        const closeIcon = document.getElementById('closeIcon');
        
        if (mobileMenu) mobileMenu.classList.add('hidden');
        if (menuIcon) menuIcon.classList.remove('hidden');
        if (closeIcon) closeIcon.classList.add('hidden');
    }
});

// Agregar animación fade-in (CSS)
const style = document.createElement('style');
style.textContent = `
    .animate-fade-in {
        animation: fadeIn 0.15s ease-out forwards;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
</script>