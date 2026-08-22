const toggleButton = document.querySelector('[data-sidebar-toggle]');

if (toggleButton) {
	toggleButton.addEventListener('click', () => {
		document.body.classList.toggle('is-sidebar-open');
	});

	document.addEventListener('click', (event) => {
		const clickedInsideSidebar = event.target.closest('.sidebar');
		const clickedToggle = event.target.closest('[data-sidebar-toggle]');

		if (!clickedInsideSidebar && !clickedToggle) {
			document.body.classList.remove('is-sidebar-open');
		}
	});
}
