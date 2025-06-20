function showNotification(message, type = 'info', duration = 4000) {
	// Crear contenedor de notificaciones si no existe
	let container = document.getElementById('notification-container');
	if (!container) {
		container = document.createElement('div');
		container.id = 'notification-container';
		container.className = 'fixed top-4 right-4 z-50 space-y-2';
		document.body.appendChild(container);
	}

	// Crear notificación
	const notification = document.createElement('div');
	notification.className = `notification transform transition-all duration-300 translate-x-full opacity-0`;

	// Estilos según el tipo
	const styles = {
		success: 'bg-green-500 text-white',
		error: 'bg-red-500 text-white',
		warning: 'bg-yellow-500 text-white',
		info: 'bg-blue-500 text-white'
	};

	// Iconos según el tipo
	const icons = {
		success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>`,
		error: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>`,
		warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>`,
		info: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
             </svg>`
	};

	notification.innerHTML = `
      <div class="flex items-center space-x-3 px-4 py-3 rounded-lg shadow-lg max-w-sm ${styles[type]}">
          <div class="flex-shrink-0">
              ${icons[type]}
          </div>
          <div class="flex-1">
              <p class="text-sm font-medium">${message}</p>
          </div>
          <button onclick="closeNotification(this)" class="flex-shrink-0 ml-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
          </button>
      </div>
  `;

	// Agregar al contenedor
	container.appendChild(notification);

	// Animar entrada
	setTimeout(() => {
		notification.classList.remove('translate-x-full', 'opacity-0');
		notification.classList.add('translate-x-0', 'opacity-100');
	}, 10);

	// Auto-remover después del tiempo especificado
	setTimeout(() => {
		closeNotification(notification);
	}, duration);
}

/**
 * Cerrar notificación
 * @param {HTMLElement} element - Elemento de notificación o botón dentro de ella
 */
function closeNotification(element) {
	// Encontrar el elemento de notificación
	const notification = element.classList.contains('notification')
		? element
		: element.closest('.notification');

	if (notification) {
		// Animar salida
		notification.classList.add('translate-x-full', 'opacity-0');
		notification.classList.remove('translate-x-0', 'opacity-100');

		// Remover del DOM después de la animación
		setTimeout(() => {
			notification.remove();
		}, 300);
	}
}

function showLoadingNotification(message = 'Cargando...') {
	const container =
		document.getElementById('notification-container') ||
		(() => {
			const cont = document.createElement('div');
			cont.id = 'notification-container';
			cont.className = 'fixed top-4 right-4 z-50 space-y-2';
			document.body.appendChild(cont);
			return cont;
		})();

	const loading = document.createElement('div');
	loading.className =
		'notification transform transition-all duration-300 translate-x-full opacity-0';
	loading.id = 'loading-notification';

	loading.innerHTML = `
      <div class="flex items-center space-x-3 px-4 py-3 rounded-lg shadow-lg max-w-sm bg-blue-500 text-white">
          <div class="flex-shrink-0">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
          </div>
          <div class="flex-1">
              <p class="text-sm font-medium">${message}</p>
          </div>
      </div>
  `;

	container.appendChild(loading);

	// Animar entrada
	setTimeout(() => {
		loading.classList.remove('translate-x-full', 'opacity-0');
		loading.classList.add('translate-x-0', 'opacity-100');
	}, 10);

	// Retornar función para cerrar
	return () => {
		const loadingElement = document.getElementById('loading-notification');
		if (loadingElement) {
			closeNotification(loadingElement);
		}
	};
}

function showConfirmModal(title, message, onConfirm, onCancel = null) {
	// Crear overlay
	const overlay = document.createElement('div');
	overlay.className =
		'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
	overlay.id = 'confirm-modal-overlay';

	overlay.innerHTML = `
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-200 scale-95 opacity-0">
          <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
              <p class="text-gray-600 mt-2">${message}</p>
          </div>
          <div class="flex space-x-3 justify-end">
              <button id="cancel-btn" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                  Cancelar
              </button>
              <button id="confirm-btn" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                  Confirmar
              </button>
          </div>
      </div>
  `;

	document.body.appendChild(overlay);

	// Animar entrada
	const modal = overlay.querySelector('div');
	setTimeout(() => {
		modal.classList.remove('scale-95', 'opacity-0');
		modal.classList.add('scale-100', 'opacity-100');
	}, 10);

	// Event listeners
	overlay.querySelector('#confirm-btn').addEventListener('click', () => {
		closeModal();
		if (onConfirm) onConfirm();
	});

	overlay.querySelector('#cancel-btn').addEventListener('click', () => {
		closeModal();
		if (onCancel) onCancel();
	});

	// Cerrar con Escape
	document.addEventListener('keydown', function escapeHandler(e) {
		if (e.key === 'Escape') {
			closeModal();
			if (onCancel) onCancel();
			document.removeEventListener('keydown', escapeHandler);
		}
	});

	// Cerrar al hacer click en overlay
	overlay.addEventListener('click', (e) => {
		if (e.target === overlay) {
			closeModal();
			if (onCancel) onCancel();
		}
	});

	function closeModal() {
		const modal = overlay.querySelector('div');
		modal.classList.add('scale-95', 'opacity-0');
		modal.classList.remove('scale-100', 'opacity-100');

		setTimeout(() => {
			overlay.remove();
		}, 200);
	}
}
