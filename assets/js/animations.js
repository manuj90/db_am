document.addEventListener('DOMContentLoaded', function () {
	const observerOptions = {
		threshold: 0.1,
		rootMargin: '0px 0px -50px 0px'
	};

	const observer = new IntersectionObserver(function (entries) {
		entries.forEach((entry) => {
			if (entry.isIntersecting) {
				entry.target.classList.add('visible');
			}
		});
	}, observerOptions);

	document.querySelectorAll('.scroll-animate').forEach((el) => {
		observer.observe(el);
	});
});

function showNotification(message, type = 'info') {
	const container = document.getElementById('notifications-container');
	const notification = document.createElement('div');

	const colors = {
		success: 'bg-green-500/20 border-green-500/30 text-green-100',
		error: 'bg-red-500/20 border-red-500/30 text-red-100',
		warning: 'bg-yellow-500/20 border-yellow-500/30 text-yellow-100',
		info: 'bg-aurora-blue/20 border-aurora-blue/30 text-blue-100'
	};

	notification.className = `glass-dark rounded-lg p-4 border ${colors[type]} animate-slide-up`;
	notification.innerHTML = message;

	container.appendChild(notification);

	setTimeout(() => {
		notification.style.animation = 'fadeOut 0.3s ease-out';
		setTimeout(() => container.removeChild(notification), 300);
	}, 5000);
}

function toggleLoading(show = true) {
	const overlay = document.getElementById('loading-overlay');
	if (show) {
		overlay.classList.remove('hidden');
	} else {
		overlay.classList.add('hidden');
	}
}
