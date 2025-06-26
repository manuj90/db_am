function toggleDropdown(dropdownId) {
	const dropdown = document.getElementById(dropdownId);
	dropdown.classList.toggle('hidden');

	document.addEventListener('click', function (event) {
		if (!event.target.closest('.relative')) {
			dropdown.classList.add('hidden');
		}
	});
}

function toggleMobileMenu() {
	const mobileMenu = document.getElementById('mobileMenu');
	mobileMenu.classList.toggle('hidden');
}

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

function isValidEmail(email) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

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
        </div>`;

	container.appendChild(notification);

	setTimeout(() => {
		notification.style.opacity = '0';
		notification.style.transform = 'translateX(120%)';
		setTimeout(() => notification.remove(), 300);
	}, duration);
}

function handleScroll() {
	const scrollToTopBtn = document.getElementById('scrollToTop');
	if (!scrollToTopBtn) return;
	if (window.pageYOffset > 300) {
		scrollToTopBtn.classList.remove('hidden');
	} else {
		scrollToTopBtn.classList.add('hidden');
	}
}

function scrollToTop() {
	window.scrollTo({
		top: 0,
		behavior: 'smooth'
	});
}

// =================== PERFILES ===================
function initProfilePage() {
	const fileInput = document.getElementById('foto_perfil');
	if (!fileInput) return;

	const uploadBtn = document.getElementById('upload-button');
	const uploadStatus = document.getElementById('upload-status');
	const avatarContainer = document.getElementById('avatar-container');

	fileInput.addEventListener('change', function () {
		if (this.files.length > 0) {
			uploadBtn.disabled = false;
			if (uploadStatus)
				uploadStatus.innerHTML = `<span class="text-gray-400">${this.files[0].name}</span>`;
		} else {
			uploadBtn.disabled = true;
			if (uploadStatus) uploadStatus.innerHTML = '';
		}
	});

	uploadBtn.addEventListener('click', function () {
		const file = fileInput.files[0];
		if (!file || !uploadStatus || !avatarContainer) return;

		const formData = new FormData();
		formData.append('file', file);
		formData.append('upload_type', 'profile');
		formData.append('csrf_token', window.CSRF_TOKEN || '');

		uploadStatus.innerHTML = `<span class="text-blue-400">Subiendo...</span>`;
		uploadBtn.disabled = true;

		fetch(window.API_UPLOAD_URL || 'api/upload.php', {
			method: 'POST',
			body: formData
		})
			.then((r) => (r.ok ? r.json() : r.json().then((d) => Promise.reject(d))))
			.then((data) => {
				if (data.success) {
					uploadStatus.innerHTML = `<span class="text-green-400">${data.message}</span>`;
					avatarContainer.innerHTML = `<img src="${data.file_url}" alt="Foto de perfil" class="w-full h-full rounded-full object-cover">`;
					setTimeout(() => window.location.reload(), 1500);
				} else {
					uploadStatus.innerHTML = `<span class="text-red-400">Error: ${data.message}</span>`;
					fileInput.value = '';
				}
			})
			.catch((err) => {
				console.error('Error en la subida:', err);
				uploadStatus.innerHTML = `<span class="text-red-400">${
					err.message || 'Error de red.'
				}</span>`;
			})
			.finally(() => {
				uploadBtn.disabled = false;
			});
	});

	const newPasswordInput = document.getElementById('new_password');
	const confirmPasswordInput = document.getElementById('confirm_password');
	if (newPasswordInput && confirmPasswordInput) {
		const checkPasswords = () => {
			if (
				newPasswordInput.value &&
				confirmPasswordInput.value &&
				newPasswordInput.value !== confirmPasswordInput.value
			) {
				confirmPasswordInput.classList.add('ring-2', 'ring-red-500');
			} else {
				confirmPasswordInput.classList.remove('ring-2', 'ring-red-500');
			}
		};
		newPasswordInput.addEventListener('input', checkPasswords);
		confirmPasswordInput.addEventListener('input', checkPasswords);
	}

	document.querySelectorAll('form').forEach((form) => {
		form.addEventListener('submit', function (e) {
			const action = this.querySelector('input[name="action"]')?.value;
			if (action === 'change_password') {
				const newPassword = this.querySelector('#new_password').value;
				const confirmPassword = this.querySelector('#confirm_password').value;
				if (newPassword !== confirmPassword) {
					e.preventDefault();
					showNotification('Las nuevas contraseñas no coinciden', 'error');
					return;
				}
				if (newPassword.length < 6) {
					e.preventDefault();
					showNotification(
						'La nueva contraseña debe tener al menos 6 caracteres',
						'error'
					);
					return;
				}
			}

			if (action === 'update_profile') {
				const nombre = this.querySelector('#nombre').value.trim();
				const apellido = this.querySelector('#apellido').value.trim();
				const email = this.querySelector('#email').value.trim();
				if (nombre.length < 2 || apellido.length < 2) {
					e.preventDefault();
					showNotification(
						'El nombre y apellido deben tener al menos 2 caracteres',
						'error'
					);
					return;
				}
				if (!isValidEmail(email)) {
					e.preventDefault();
					showNotification('El formato del email no es válido', 'error');
					return;
				}
			}
		});
	});

	const flashMessages = document.querySelectorAll(
		'.bg-green-500/10, .bg-red-500/10'
	);
	flashMessages.forEach((message) => {
		setTimeout(() => {
			message.style.transition = 'opacity 0.5s ease';
			message.style.opacity = '0';
			setTimeout(() => message.remove(), 500);
		}, 5000);
	});
}

// =================== CREAR PROYECTO ===================
function initCreateProjectPage() {
	const form = document.getElementById('createProjectForm');
	if (!form) return;

	const submitBtn = document.getElementById('submitBtn');
	const submitText = document.getElementById('submitText');
	const descripcionTextarea = document.getElementById('descripcion');
	const charCount = document.getElementById('char-count');

	function updateCharCount() {
		const count = descripcionTextarea.value.length;
		charCount.textContent = count + '/5000';

		if (count < 20) {
			charCount.className = 'text-xs text-red-500';
		} else if (count > 4500) {
			charCount.className = 'text-xs text-yellow-600';
		} else {
			charCount.className = 'text-xs text-gray-500';
		}
	}

	descripcionTextarea.addEventListener('input', updateCharCount);
	updateCharCount();

	form.addEventListener('submit', function (e) {
		let isValid = true;
		const errores = [];

		const titulo = document.getElementById('titulo').value.trim();
		if (titulo.length < 5) {
			errores.push('El título debe tener al menos 5 caracteres');
			isValid = false;
		}

		const descripcion = document.getElementById('descripcion').value.trim();
		if (descripcion.length < 20) {
			errores.push('La descripción debe tener al menos 20 caracteres');
			isValid = false;
		}

		const categoria = document.getElementById('categoria').value;
		if (!categoria) {
			errores.push('Debe seleccionar una categoría');
			isValid = false;
		}

		const usuario = document.getElementById('usuario').value;
		if (!usuario) {
			errores.push('Debe seleccionar un autor');
			isValid = false;
		}

		if (!isValid) {
			e.preventDefault();
			let errorContainer = document.getElementById('validation-errors');
			if (!errorContainer) {
				errorContainer = document.createElement('div');
				errorContainer.id = 'validation-errors';
				errorContainer.className =
					'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6';
				form.parentNode.insertBefore(errorContainer, form);
			}

			errorContainer.innerHTML = `
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <strong>Por favor corrige los siguientes errores:</strong>
                                <ul class="list-disc list-inside mt-2">
                                    ${errores
																			.map((error) => `<li>${error}</li>`)
																			.join('')}
                                </ul>
                            </div>
                        </div>`;

			errorContainer.scrollIntoView({ behavior: 'smooth' });
			return;
		}

		submitBtn.disabled = true;
		submitBtn.innerHTML = `
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l-3-2.647z"></path>
                </svg>
                <span>Creando proyecto...</span>`;
	});

	const campos = {
		titulo: {
			min: 5,
			max: 200,
			message: 'El título debe tener entre 5 y 200 caracteres'
		},
		descripcion: {
			min: 20,
			max: 5000,
			message: 'La descripción debe tener entre 20 y 5000 caracteres'
		},
		cliente: {
			min: 0,
			max: 100,
			message: 'El cliente no puede exceder los 100 caracteres'
		}
	};

	Object.keys(campos).forEach(function (fieldName) {
		const field = document.getElementById(fieldName);
		const config = campos[fieldName];
		if (field) {
			field.addEventListener('input', function () {
				const length = this.value.length;
				const isValid = length >= config.min && length <= config.max;
				this.classList.remove('border-red-500', 'border-green-500');
				if (length > 0) {
					this.classList.add(isValid ? 'border-green-500' : 'border-red-500');
				}
				let errorDiv = this.parentNode.querySelector('.dynamic-error');
				if (!isValid && length > 0) {
					if (!errorDiv) {
						errorDiv = document.createElement('p');
						errorDiv.className = 'dynamic-error text-red-500 text-xs mt-1';
						this.parentNode.appendChild(errorDiv);
					}
					errorDiv.textContent = config.message;
				} else if (errorDiv) {
					errorDiv.remove();
				}
			});
		}
	});

	let autoSaveTimeout;
	const autosaveFields = ['titulo', 'descripcion', 'cliente'];
	autosaveFields.forEach(function (fieldName) {
		const field = document.getElementById(fieldName);
		if (field) {
			field.addEventListener('input', function () {
				clearTimeout(autoSaveTimeout);
				autoSaveTimeout = setTimeout(function () {
					console.log('Auto-guardando borrador...');
				}, 3000);
			});
		}
	});

	let formModificado = false;
	form.addEventListener('input', function () {
		formModificado = true;
	});
	form.addEventListener('submit', function () {
		formModificado = false;
	});
	window.addEventListener('beforeunload', function (e) {
		if (formModificado) {
			e.preventDefault();
			e.returnValue =
				'¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
			return e.returnValue;
		}
	});

	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && e.key === 's') {
			e.preventDefault();
			form.submit();
		}
	});

	const selects = document.querySelectorAll('select');
	selects.forEach(function (select) {
		select.addEventListener('change', function () {
			if (this.value) {
				this.classList.remove('border-red-500');
				this.classList.add('border-green-500');
			}
		});
	});

	const categoriaSelect = document.getElementById('categoria');
	categoriaSelect.addEventListener('change', function () {
		const selectedOption = this.options[this.selectedIndex];
		const description = selectedOption.textContent.split(' - ')[1];

		let tooltip = document.getElementById('categoria-tooltip');
		if (description && description !== selectedOption.textContent) {
			if (!tooltip) {
				tooltip = document.createElement('div');
				tooltip.id = 'categoria-tooltip';
				tooltip.className =
					'text-xs text-blue-600 mt-1 p-2 bg-blue-50 rounded border border-blue-200';
				this.parentNode.appendChild(tooltip);
			}
			tooltip.textContent = `💡 ${description}`;
		} else if (tooltip) {
			tooltip.remove();
		}
	});

	document.getElementById('titulo').focus();
}

// =================== EDITAR PROYECTO ===================
function initEditProjectPage() {
	const form = document.querySelector('form');
	const uploadForm = document.getElementById('uploadMediaForm');
	if (!form || !uploadForm) return; // No es la página de edición

	function openEliminarModal() {
		const modal = document.getElementById('modalEliminar');
		if (modal) {
			modal.classList.remove('hidden');
			modal.classList.add('flex');
		}
	}
	function openDuplicarModal() {
		const modal = document.getElementById('modalDuplicar');
		if (modal) {
			modal.classList.remove('hidden');
			modal.classList.add('flex');
		}
	}
	function closeEliminarModal() {
		const modal = document.getElementById('modalEliminar');
		if (modal) {
			modal.classList.add('hidden');
			modal.classList.remove('flex');
		}
	}
	function closeDuplicarModal() {
		const modal = document.getElementById('modalDuplicar');
		if (modal) {
			modal.classList.add('hidden');
			modal.classList.remove('flex');
		}
	}

	const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
	if (btnConfirmarEliminar) {
		btnConfirmarEliminar.addEventListener('click', function () {
			const f = document.createElement('form');
			f.method = 'POST';
			f.action = '';
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'eliminar_proyecto';
			input.value = '1';
			const token = document.createElement('input');
			token.type = 'hidden';
			token.name = 'csrf_token';
			token.value = window.CSRF_TOKEN || '';
			f.appendChild(input);
			f.appendChild(token);
			document.body.appendChild(f);
			f.submit();
		});
	}

	const btnConfirmarDuplicar = document.getElementById('btnConfirmarDuplicar');
	if (btnConfirmarDuplicar) {
		btnConfirmarDuplicar.addEventListener('click', function () {
			const f = document.createElement('form');
			f.method = 'POST';
			f.action = '';
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'duplicar_proyecto';
			input.value = f.dataset.projectId || '';
			const token = document.createElement('input');
			token.type = 'hidden';
			token.name = 'csrf_token';
			token.value = window.CSRF_TOKEN || '';
			f.appendChild(input);
			f.appendChild(token);
			document.body.appendChild(f);
			f.submit();
		});
	}

	document
		.getElementById('btnCancelarEliminar')
		?.addEventListener('click', closeEliminarModal);
	document
		.getElementById('btnCancelarDuplicar')
		?.addEventListener('click', closeDuplicarModal);

	document.getElementById('modalEliminar')?.addEventListener('click', (e) => {
		if (e.target === e.currentTarget) closeEliminarModal();
	});
	document.getElementById('modalDuplicar')?.addEventListener('click', (e) => {
		if (e.target === e.currentTarget) closeDuplicarModal();
	});

	const inputArchivos = uploadForm.querySelector(
		'input[name="nuevos_archivos[]"]'
	);
	if (inputArchivos) {
		inputArchivos.addEventListener('change', function () {
			const maxSize = 10 * 1024 * 1024;
			const allowedTypes = [
				'image/jpeg',
				'image/png',
				'image/gif',
				'image/webp',
				'video/mp4',
				'video/webm'
			];
			let hasErrors = false;

			Array.from(this.files).forEach((file) => {
				if (file.size > maxSize) {
					alert(`El archivo "${file.name}" es demasiado grande. Máximo 10MB.`);
					hasErrors = true;
				}
				if (!allowedTypes.includes(file.type)) {
					alert(`El archivo "${file.name}" no es un tipo permitido.`);
					hasErrors = true;
				}
			});

			if (hasErrors) {
				this.value = '';
				document.getElementById('previsualizacion')?.classList.add('hidden');
			} else {
				mostrarPrevisualizacion(this);
			}
		});
	}

	uploadForm.addEventListener('submit', function (e) {
		e.preventDefault();
		const input = uploadForm.querySelector('input[name="nuevos_archivos[]"]');
		const status = document.getElementById('upload-status');
		const button = document.getElementById('upload-btn');
		if (!input || input.files.length === 0) {
			return;
		}

		button.disabled = true;
		if (status) status.textContent = 'Subiendo...';

		const descInputs = uploadForm.querySelectorAll(
			'input[name^="descripcion_archivo"]'
		);
		const uploads = Array.from(input.files).map((file, idx) => {
			const formData = new FormData();
			formData.append('file', file);
			formData.append('upload_type', 'project');
			formData.append('project_id', uploadForm.dataset.projectId || '');
			if (descInputs[idx]) {
				formData.append('descripcion', descInputs[idx].value);
			}
			formData.append('csrf_token', window.CSRF_TOKEN || '');

			return fetch(window.API_UPLOAD_URL, { method: 'POST', body: formData })
				.then((r) =>
					r.ok
						? r.json()
						: r.json().then((d) => Promise.reject(d.message || 'Error'))
				)
				.then((data) => {
					if (!data.success) throw new Error(data.message);
				});
		});

		Promise.all(uploads)
			.then(() => {
				if (status) status.textContent = 'Archivos subidos correctamente';
				setTimeout(() => window.location.reload(), 1500);
			})
			.catch((err) => {
				if (status) status.textContent = 'Error: ' + err.message;
			})
			.finally(() => {
				button.disabled = false;
			});
	});

	let autoSaveTimeout;
	['titulo', 'descripcion', 'cliente'].forEach((campo) => {
		const el = document.getElementById(campo);
		if (el) {
			el.addEventListener('input', function () {
				clearTimeout(autoSaveTimeout);
				autoSaveTimeout = setTimeout(
					() => console.log('Auto-guardando borrador...'),
					2000
				);
			});
		}
	});

	const checkboxPublicado = document.getElementById('publicado');
	if (checkboxPublicado) {
		const info = checkboxPublicado.parentNode.nextElementSibling;
		const actualizarEstadoPublicado = () => {
			if (checkboxPublicado.checked) {
				info.innerHTML =
					'✅ El proyecto será <strong>publicado</strong> y visible al público';
				info.className = 'text-sm text-green-600 mt-1';
			} else {
				info.innerHTML =
					'⚠️ El proyecto permanecerá como <strong>borrador</strong> (no visible al público)';
				info.className = 'text-sm text-yellow-600 mt-1';
			}
		};
		checkboxPublicado.addEventListener('change', actualizarEstadoPublicado);
		actualizarEstadoPublicado();
	}

	let formModificado = false;
	if (form) {
		form.addEventListener('input', () => (formModificado = true));
		form.addEventListener('submit', () => (formModificado = false));
	}
	window.addEventListener('beforeunload', function (e) {
		if (formModificado) {
			e.preventDefault();
			e.returnValue =
				'¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
			return e.returnValue;
		}
	});

	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && e.key === 's') {
			e.preventDefault();
			form.submit();
		}
		if (e.key === 'Escape') {
			closeEliminarModal();
			closeDuplicarModal();
		}
	});
}

// =================== INICIALIZACIÓN ===================
document.addEventListener('DOMContentLoaded', () => {
	window.addEventListener('scroll', handleScroll);
	initProfilePage();
	initCreateProjectPage();
	initEditProjectPage();
});
