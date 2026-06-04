export default class DataGridFilter {
	initialize(naja) {
		const getFormInputs = (form) => {
			if (!form) {
				return [];
			}

			return Array.from(document.querySelectorAll('[data-items-filter]'))
				.filter((input) => input.form === form);
		};

		const submitIfChanged = (form) => {
			const allInputs = getFormInputs(form);
			let hasChanged = false;

			for (let input of allInputs) {
				if (input.value.trim() !== (input.dataset.lastValue || "")) {
					hasChanged = true;
					break;
				}
			}

			if (hasChanged) {
				naja.uiHandler.submitForm(form);
			}
		};

		const applyFilters = (doc) => {
			const inputs = doc.querySelectorAll('[data-items-filter]');
			if (!inputs) return;

			for (let input of inputs) {
				// Store the initial trimmed value to detect changes
				input.dataset.lastValue = input.value.trim();

				input.addEventListener('keydown', (e) => {
					if (e.key === 'Enter') {
						e.preventDefault();

						const form = e.target.form;
						submitIfChanged(form);
					}
				});

				input.addEventListener('change', (e) => {
					submitIfChanged(e.target.form);
				});
			}
		};

		applyFilters(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => applyFilters(e.detail.snippet));
	}
}
