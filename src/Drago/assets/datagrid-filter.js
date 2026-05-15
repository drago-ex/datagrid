export default class DataGridFilter {
	initialize(naja) {
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
						const allInputs = form.querySelectorAll('[data-items-filter]');
						let hasChanged = false;

						for (let i of allInputs) {
							if (i.value.trim() !== (i.dataset.lastValue || "")) {
								hasChanged = true;
								break;
							}
						}

						// Only submit if at least one filter value has actually changed
						if (hasChanged) {
							naja.uiHandler.submitForm(form);
						}
					}
				});
			}
		};

		applyFilters(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => applyFilters(e.detail.snippet));
	}
}
