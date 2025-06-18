// Funciones generales del sitio

// Toggle dropdown
function toggleDropdown(dropdownId) {
	const dropdown = document.getElementById(dropdownId);
	dropdown.classList.toggle('hidden');

	// Cerrar al hacer click fuera
	document.addEventListener('click', function (event) {
		if (!event.target.closest('.relative')) {
			dropdown.classList.add('hidden');
		}
	});
}

// Toggle mobile menu
function toggleMobileMenu() {
	const mobileMenu = document.getElementById('mobileMenu');
	mobileMenu.classList.toggle('hidden');
}

// Formatear fecha relativa
function timeAgo(dateString) {
	const date = new Date(dateString);
	const now = new Date();
	const seconds = Math.floor((now - date) / 1000);

	const intervals = {
		año: 31536000,
		mes: 2592000,
		semana: 604800,
		día: 86400,
		hora: 3600,
		minuto: 60
	};

	for (let [unit, secondsInUnit] of Object.entries(intervals)) {
		const interval = Math.floor(seconds / secondsInUnit);
		if (interval >= 1) {
			return `hace ${interval} ${unit}${interval > 1 ? 's' : ''}`;
		}
	}

	return 'hace un momento';
}

// Función para realizar peticiones AJAX
async function makeRequest(url, method = 'GET', data = null) {
	const options = {
		method: method,
		headers: {
			'Content-Type': 'application/json'
		}
	};

	if (data) {
		options.body = JSON.stringify(data);
	}

	try {
		const response = await fetch(url, options);
		return await response.json();
	} catch (error) {
		console.error('Error en la petición:', error);
		throw error;
	}
}

// Mostrar notificaciones
function showNotification(message, type = 'info') {
	const notification = document.createElement('div');
	notification.className = `fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm ${
		type === 'success'
			? 'bg-green-500'
			: type === 'error'
			? 'bg-red-500'
			: type === 'warning'
			? 'bg-yellow-500'
			: 'bg-blue-500'
	}`;

	notification.textContent = message;
	document.body.appendChild(notification);

	// Remover después de 3 segundos
	setTimeout(() => {
		notification.remove();
	}, 3000);
}
