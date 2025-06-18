<nav class="bg-gray-900 text-white shadow-lg fixed w-full top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="/public/" class="text-xl font-bold text-white hover:text-gray-300 transition">
                    Agencia Multimedia
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-6">
                <a href="/public/" class="hover:text-gray-300 transition">Inicio</a>
                <a href="/public/categoria.php" class="hover:text-gray-300 transition">Categorías</a>
                <a href="/public/buscar.php" class="hover:text-gray-300 transition">Buscar</a>
                
                <?php if (isLoggedIn()): ?>
                    <div class="relative">
                        <button onclick="toggleDropdown('userDropdown')" 
                                class="flex items-center space-x-2 hover:text-gray-300 transition">
                            <span><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="/dashboard/<?php echo isAdmin() ? 'admin' : 'user'; ?>/" 
                               class="block px-4 py-2 text-gray-800 hover:bg-gray-100 transition">
                                Dashboard
                            </a>
                            <a href="/dashboard/user/perfil.php" 
                               class="block px-4 py-2 text-gray-800 hover:bg-gray-100 transition">
                                Mi Perfil
                            </a>
                            <hr class="my-1">
                            <a href="/public/logout.php" 
                               class="block px-4 py-2 text-gray-800 hover:bg-gray-100 transition">
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/public/login.php" class="hover:text-gray-300 transition">Iniciar Sesión</a>
                    <a href="/public/registro.php" class="btn btn-primary">Registrarse</a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile menu button -->
            <button onclick="toggleMobileMenu()" class="md:hidden text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden md:hidden pb-4">
            <a href="/public/" class="block py-2 hover:text-gray-300">Inicio</a>
            <a href="/public/categoria.php" class="block py-2 hover:text-gray-300">Categorías</a>
            <a href="/public/buscar.php" class="block py-2 hover:text-gray-300">Buscar</a>
            <?php if (isLoggedIn()): ?>
                <a href="/dashboard/<?php echo isAdmin() ? 'admin' : 'user'; ?>/" class="block py-2 hover:text-gray-300">Dashboard</a>
                <a href="/public/logout.php" class="block py-2 hover:text-gray-300">Cerrar Sesión</a>
            <?php else: ?>
                <a href="/public/login.php" class="block py-2 hover:text-gray-300">Iniciar Sesión</a>
                <a href="/public/registro.php" class="block py-2 hover:text-gray-300">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Spacer para el nav fijo -->
<div class="h-16"></div>