<header class="sticky top-0 z-50 p-4">
    <input type="checkbox" id="mobile-menu-toggle" class="peer/mobile hidden">

    <nav
        class="w-full max-w-7xl mx-auto bg-surface/50 backdrop-blur-lg border border-white/10 rounded-full shadow-2xl shadow-black/20">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-20">

                <div class="flex-1 flex items-center justify-start">
                    <a href="<?php echo url('public/index.php'); ?>" class="flex-shrink-0">
                        <img class="h-8 w-auto invert" src="<?php echo asset('images/logo/LogoFull.png'); ?>"
                            alt="Ganymede Logo">
                    </a>
                </div>

                <div class="hidden md:flex items-center justify-center gap-x-6">
                    <a href="<?php echo url('public/index.php'); ?>"
                        class="text-base font-semibold leading-6 text-white hover:text-gray-300 transition">Proyectos</a>

                    <div class="relative">
                        <input type="checkbox" id="category-menu-toggle" class="peer hidden">
                        <label for="category-menu-toggle"
                            class="flex items-center gap-x-1 text-base font-semibold leading-6 text-white hover:text-gray-300 transition cursor-pointer">
                            <span>Categorías</span>
                            <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </label>
                        <div
                            class="hidden peer-checked:block absolute -left-8 top-full z-10 mt-3 w-screen max-w-md overflow-hidden rounded-3xl bg-surface/80 backdrop-blur-lg shadow-lg ring-1 ring-white/10 animate-fade-in">
                            <div class="p-4">
                                <?php foreach (getAllCategories() as $cat): ?>
                                    <div
                                        class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base leading-6 hover:bg-white/5 transition">
                                        <div
                                            class="h-11 w-11 flex flex-none items-center justify-center rounded-lg bg-white/5 group-hover:bg-white/10">
                                            <div class="w-2 h-2 bg-primary rounded-full"></div>
                                        </div>
                                        <div class="flex-auto">
                                            <a href="<?php echo url('public/categoria.php?categoria=' . $cat['id_categoria']); ?>"
                                                class="block font-semibold text-white">
                                                <?php echo htmlspecialchars($cat['nombre']); ?>
                                                <span class="absolute inset-0"></span>
                                            </a>
                                            <p class="mt-1 text-gray-400 text-sm">
                                                <?php echo htmlspecialchars(truncateText($cat['descripcion'], 40)); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?php echo url('public/buscar.php'); ?>"
                        class="text-base font-semibold leading-6 text-white hover:text-gray-300 transition">Búsqueda</a>
                </div>

                <div class="flex-1 flex items-center justify-end">
                    <div class="hidden md:block">
                        <?php if (isLoggedIn()): ?>
                            <div class="relative ml-4">
                                <input type="checkbox" id="user-menu-toggle" class="peer hidden">
                                <label for="user-menu-toggle"
                                    class="relative flex rounded-full bg-gray-800 text-sm cursor-pointer focus-within:ring-2 focus-within:ring-white focus-within:ring-offset-2 focus-within:ring-offset-primary">
                                    <span class="sr-only">Abrir menú de usuario</span>
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-base font-semibold overflow-hidden bg-primary">
                                        <?php
                                        $hasPhoto = !empty($_SESSION['foto_perfil']) && file_exists(__DIR__ . '/../../assets/images/usuarios/' . $_SESSION['foto_perfil']);
                                        if ($hasPhoto):
                                            $photoUrl = asset('images/usuarios/' . $_SESSION['foto_perfil']);
                                            ?>
                                            <img src="<?php echo $photoUrl; ?>" alt="Foto de perfil"
                                                class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span
                                                class="text-white"><?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </label>

                                <div
                                    class="hidden peer-checked:block absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-xl bg-surface/80 backdrop-blur-lg py-2 shadow-lg ring-1 ring-white/10 focus:outline-none animate-fade-in">
                                    <div class="px-4 py-3 border-b border-white/10">
                                        <p class="text-sm text-gray-300">Conectado como</p>
                                        <p class="truncate text-base font-medium text-white">
                                            <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?>
                                        </p>
                                    </div>
                                    <div class="py-1">
                                        <a href="<?php echo url('dashboard/shared/perfil.php'); ?>"
                                            class="flex items-center gap-3 px-4 py-2 text-base text-gray-200 hover:bg-white/10 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                class="w-5 h-5">
                                                <path fill-rule="evenodd"
                                                    d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Mi Perfil
                                        </a>
                                        <a href="<?php echo url('dashboard/' . (isAdmin() ? 'admin' : 'user') . '/index.php'); ?>"
                                            class="flex items-center gap-3 px-4 py-2 text-base text-gray-200 hover:bg-white/10 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                class="w-5 h-5">
                                                <path fill-rule="evenodd"
                                                    d="M2.25 6a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V6Zm18 3H3.75v9a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V9Zm-15-3.75A.75.75 0 0 0 4.5 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H5.25Zm1.5.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V6Zm3-.75A.75.75 0 0 0 9 6v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H9.75Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Dashboard
                                        </a>
                                    </div>
                                    <div class="border-t border-white/10 my-1"></div>
                                    <div class="py-1">
                                        <a href="<?php echo url('public/logout.php'); ?>"
                                            class="flex items-center gap-3 px-4 py-2 text-base text-aurora-pink hover:bg-white/10 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                class="w-5 h-5">
                                                <path fill-rule="evenodd"
                                                    d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Cerrar sesión
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-4">
                                <a href="<?php echo url('public/login.php'); ?>"
                                    class="text-base font-semibold leading-6 text-white hover:text-gray-300 transition">Iniciar
                                    sesión</a>
                                <a href="<?php echo url('public/registro.php'); ?>"
                                    class="rounded-full bg-primary px-4 py-2 text-base font-semibold text-white shadow-sm hover:bg-aurora-pink/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition">Registrarse</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="md:hidden">
                        <label for="mobile-menu-toggle"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                            <span class="sr-only">Abrir menú principal</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="hidden peer-checked/mobile:block w-full max-w-7xl mx-auto mt-2 animate-fade-in">
        <div class="bg-surface/80 backdrop-blur-lg border border-white/10 rounded-3xl p-4 space-y-2">
            <a href="<?php echo url('public/index.php'); ?>"
                class="text-gray-300 hover:bg-white/10 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Proyectos</a>
            <a href="<?php echo url('public/buscar.php'); ?>"
                class="text-gray-300 hover:bg-white/10 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Búsqueda</a>
            <a href="<?php echo url('public/categorias.php'); ?>"
                class="text-gray-300 hover:bg-white/10 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Categorías</a>

            <?php if (isLoggedIn()): ?>
                <div class="border-t border-white/10 pt-4 mt-4">
                    <div class="flex items-center px-2 mb-3">
                        <div class="flex-shrink-0">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-base font-semibold overflow-hidden bg-primary">
                                <?php if ($hasPhoto): ?>
                                    <img src="<?php echo $photoUrl; ?>" alt="Foto de perfil" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span
                                        class="text-white"><?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium leading-none text-white">
                                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                            </div>
                            <div class="text-sm font-medium leading-none text-gray-400 mt-1">
                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="<?php echo url('dashboard/shared/perfil.php'); ?>"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/10 hover:text-white">Mi
                            Perfil</a>
                        <a href="<?php echo url('dashboard/' . (isAdmin() ? 'admin' : 'user') . '/index.php'); ?>"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/10 hover:text-white">Dashboard</a>
                        <a href="<?php echo url('public/logout.php'); ?>"
                            class="block rounded-md px-3 py-2 text-base font-medium text-aurora-pink hover:bg-aurora-pink/10 hover:text-white">Cerrar
                            Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="border-t border-white/10 pt-4 mt-4 grid grid-cols-2 gap-4">
                    <a href="<?php echo url('public/login.php'); ?>"
                        class="text-center rounded-full border border-surface-light px-4 py-2 text-base font-semibold text-white shadow-sm hover:bg-surface-light/50 transition">Iniciar
                        sesión</a>
                    <a href="<?php echo url('public/registro.php'); ?>"
                        class="text-center rounded-full bg-primary px-4 py-2 text-base font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Registrarse</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</header>