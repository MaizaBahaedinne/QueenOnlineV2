const toggleButton = document.querySelector('[data-sidebar-toggle]');
const sectionToggles = document.querySelectorAll('[data-menu-toggle]');

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

sectionToggles.forEach((toggle) => {
	toggle.addEventListener('click', () => {
		const section = toggle.closest('[data-menu-section]');
		if (!section) {
			return;
		}

		section.classList.toggle('is-open');
	});
});
