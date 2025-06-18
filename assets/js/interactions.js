// Funciones de interacción (likes, favoritos, comentarios)

// Calificar proyecto
async function rateProject(projectId, rating) {
	try {
		const response = await fetch('/api/calificacion.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `id_proyecto=${projectId}&estrellas=${rating}`
		});

		const result = await response.json();

		if (result.success) {
			// Actualizar estrellas visuales
			updateStarsDisplay(rating);
			showNotification('Calificación guardada', 'success');
		} else {
			showNotification(result.message || 'Error al calificar', 'error');
		}
	} catch (error) {
		showNotification('Error de conexión', 'error');
	}
}

// Actualizar display de estrellas
function updateStarsDisplay(rating) {
	const stars = document.querySelectorAll('.star-btn');
	stars.forEach((star, index) => {
		if (index < rating) {
			star.classList.add('text-yellow-400');
			star.classList.remove('text-gray-300');
		} else {
			star.classList.add('text-gray-300');
			star.classList.remove('text-yellow-400');
		}
	});
}

// Toggle favorito
async function toggleFavorite(projectId) {
	try {
		const response = await fetch('/api/favorito.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `id_proyecto=${projectId}`
		});

		const result = await response.json();

		if (result.success) {
			const btn = document.getElementById('favoriteBtn');
			if (result.isFavorite) {
				btn.className = 'btn btn-accent';
				btn.innerHTML = `
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                  </svg>
                  Quitar de Favoritos
              `;
				showNotification('Agregado a favoritos', 'success');
			} else {
				btn.className = 'btn bg-white border border-gray-300 hover:bg-gray-50';
				btn.innerHTML = `
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                  </svg>
                  Agregar a Favoritos
              `;
				showNotification('Removido de favoritos', 'success');
			}
		} else {
			showNotification(result.message || 'Error al procesar favorito', 'error');
		}
	} catch (error) {
		showNotification('Error de conexión', 'error');
	}
}

// Enviar comentario
async function submitComment(event) {
	event.preventDefault();

	const form = event.target;
	const formData = new FormData(form);

	try {
		const response = await fetch('/api/comentario.php', {
			method: 'POST',
			body: formData
		});

		const result = await response.json();

		if (result.success) {
			// Limpiar formulario
			form.reset();
			document.getElementById('charCount').textContent = '0/1000';

			// Agregar comentario a la lista
			addCommentToList(result.comment);
			showNotification('Comentario enviado', 'success');
		} else {
			showNotification(result.message || 'Error al enviar comentario', 'error');
		}
	} catch (error) {
		showNotification('Error de conexión', 'error');
	}
}

// Agregar comentario a la lista visual
function addCommentToList(comment) {
	const commentsList = document.getElementById('commentsList');
	const commentDiv = document.createElement('div');
	commentDiv.className = 'border border-gray-200 rounded-lg p-4';

	commentDiv.innerHTML = `
      <div class="flex items-start space-x-4">
          <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold">
              ${comment.nombre.charAt(0).toUpperCase()}
          </div>
          <div class="flex-1">
              <div class="flex items-center space-x-2 mb-2">
                  <h4 class="font-semibold text-gray-900">
                      ${comment.nombre} ${comment.apellido}
                  </h4>
                  <span class="text-sm text-gray-500">
                      hace un momento
                  </span>
              </div>
              <p class="text-gray-700">
                  ${comment.contenido}
              </p>
          </div>
      </div>
  `;

	commentsList.insertBefore(commentDiv, commentsList.firstChild);
}
