const toggleButton = document.querySelector('[data-sidebar-toggle]');
const sectionToggles = document.querySelectorAll('[data-menu-toggle]');
const mediaQueryMobile = window.matchMedia('(max-width: 900px)');
const userMenu = document.querySelector('[data-user-menu]');
const userMenuToggle = document.querySelector('[data-user-menu-toggle]');

function syncAccordionStateForViewport() {
	const isMobile = mediaQueryMobile.matches;
	sectionToggles.forEach((toggle) => {
		const section = toggle.closest('[data-menu-section]');
		if (!section) {
			return;
		}

		if (!isMobile) {
			section.classList.add('is-open');
			toggle.setAttribute('aria-expanded', 'true');
			return;
		}

		const opened = section.classList.contains('is-open');
		toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
	});
}

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

if (userMenu && userMenuToggle) {
	userMenuToggle.addEventListener('click', () => {
		const isOpen = userMenu.classList.toggle('is-open');
		userMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	});

	document.addEventListener('click', (event) => {
		if (!userMenu.contains(event.target)) {
			userMenu.classList.remove('is-open');
			userMenuToggle.setAttribute('aria-expanded', 'false');
		}
	});
}

sectionToggles.forEach((toggle) => {
	toggle.addEventListener('click', () => {
		if (!mediaQueryMobile.matches) {
			return;
		}

		const section = toggle.closest('[data-menu-section]');
		if (!section) {
			return;
		}

		section.classList.toggle('is-open');
		const opened = section.classList.contains('is-open');
		toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
	});
});

syncAccordionStateForViewport();

if (typeof mediaQueryMobile.addEventListener === 'function') {
	mediaQueryMobile.addEventListener('change', syncAccordionStateForViewport);
} else if (typeof mediaQueryMobile.addListener === 'function') {
	mediaQueryMobile.addListener(syncAccordionStateForViewport);
}
