<footer class="mt-24 mb-8">
    <div class="max-w-7xl mx-auto px-4">
        <div
            class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20 px-8 py-10 text-center">

            <div class="flex justify-center mb-6">
                <a href="<?php echo url('public/index.php'); ?>">
                    <img class="h-16 w-aut invert" src="<?php echo asset('images/logo/LogoFull.png'); ?>"
                        alt="Ganymede Logo">
                </a>
            </div>

            <div class="w-1/3 mx-auto border-t border-white/10"></div>

            <div class="mt-6">
                <p class="text-sm text-gray-400">
                    &copy; <?php echo date('Y'); ?> - DMN4AP - Díaz Funes / Scagni
                </p>
            </div>
        </div>
    </div>
</footer>

<button id="scrollToTop"
    class="hidden fixed bottom-6 right-6 bg-surface/50 backdrop-blur-lg border border-white/10 text-primary p-3 rounded-full shadow-lg hover:bg-surface/80 hover:text-white hover:scale-110 transition-all duration-300 z-40"
    onclick="scrollToTop()" title="Volver arriba">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
    </svg>
</button>

<script>
    window.addEventListener('scroll', function () {
        const scrollToTopBtn = document.getElementById('scrollToTop');
        if (window.pageYOffset > 300) {
            scrollToTopBtn.classList.remove('hidden');
        } else {
            scrollToTopBtn.classList.add('hidden');
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function showNotification(message, type = 'info', duration = 3000) {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        let bgColor = 'bg-accent';
        if (type === 'success') bgColor = 'bg-green-500';
        if (type === 'error') bgColor = 'bg-red-500';
        if (type === 'warning') bgColor = 'bg-yellow-500';

        const notification = document.createElement('div');
        notification.className = `p-4 rounded-lg shadow-lg text-white max-w-sm transform transition-all duration-300 ${bgColor}`;

        notification.innerHTML = `
        <div class="flex items-center">
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 flex-shrink-0 text-white/70 hover:text-white">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    `;

        container.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(120%)';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    <?php if (hasFlashMessage('success')): ?>
        document.addEventListener('DOMContentLoaded', () => showNotification('<?php echo addslashes(getFlashMessage('success')); ?>', 'success'));
    <?php endif; ?>
    <?php if (hasFlashMessage('error')): ?>
        document.addEventListener('DOMContentLoaded', () => showNotification('<?php echo addslashes(getFlashMessage('error')); ?>', 'error'));
    <?php endif; ?>
    <?php if (hasFlashMessage('warning')): ?>
        document.addEventListener('DOMContentLoaded', () => showNotification('<?php echo addslashes(getFlashMessage('warning')); ?>', 'warning'));
    <?php endif; ?>
    <?php if (hasFlashMessage('info')): ?>
        document.addEventListener('DOMContentLoaded', () => showNotification('<?php echo addslashes(getFlashMessage('info')); ?>', 'info'));
    <?php endif; ?>
</script>

</body>

</html>