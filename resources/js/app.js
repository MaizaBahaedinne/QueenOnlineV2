const toggleButton = document.querySelector('[data-sidebar-toggle]');
const sectionToggles = document.querySelectorAll('[data-menu-toggle]');
const mediaQueryMobile = window.matchMedia('(max-width: 900px)');
const userMenu = document.querySelector('[data-user-menu]');
const userMenuToggle = document.querySelector('[data-user-menu-toggle]');

function humanizeFieldName(fieldName) {
	if (!fieldName) {
		return 'Champ';
	}

	const normalized = fieldName
		.replace(/[_-]+/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();

	if (!normalized) {
		return 'Champ';
	}

	return normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function ensureModalFieldLabels() {
	const fields = document.querySelectorAll('.modal-card form input, .modal-card form select, .modal-card form textarea');

	fields.forEach((field, index) => {
		const tagName = field.tagName.toLowerCase();
		const type = (field.getAttribute('type') || '').toLowerCase();

		if (tagName === 'input' && ['hidden', 'submit', 'button', 'reset'].includes(type)) {
			return;
		}

		if (tagName === 'button') {
			return;
		}

		if (field.closest('label')) {
			return;
		}

		if (field.id) {
			const safeId = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
				? CSS.escape(field.id)
				: field.id.replace(/"/g, '\\"');
			const existingLabel = document.querySelector(`label[for="${safeId}"]`);
			if (existingLabel) {
				return;
			}
		}

		const previous = field.previousElementSibling;
		if (previous && (
			previous.classList.contains('auto-field-label')
			|| previous.tagName.toLowerCase() === 'label'
		)) {
			return;
		}

		if (!field.id) {
			const fallbackName = field.getAttribute('name') || `${tagName}-${index}`;
			field.id = `modal-field-${fallbackName.replace(/[^a-zA-Z0-9_-]/g, '-')}-${index}`;
		}

		let labelText = field.getAttribute('data-label') || field.getAttribute('placeholder') || humanizeFieldName(field.getAttribute('name') || '');

		if (tagName === 'select' && (!labelText || labelText.toLowerCase() === 'champ')) {
			labelText = humanizeFieldName(field.getAttribute('name') || 'Selection');
		}

		const label = document.createElement('label');
		label.className = 'auto-field-label';
		label.setAttribute('for', field.id);
		label.textContent = labelText;

		field.parentNode.insertBefore(label, field);
	});
}

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
ensureModalFieldLabels();

if (typeof mediaQueryMobile.addEventListener === 'function') {
	mediaQueryMobile.addEventListener('change', syncAccordionStateForViewport);
} else if (typeof mediaQueryMobile.addListener === 'function') {
	mediaQueryMobile.addListener(syncAccordionStateForViewport);
}
