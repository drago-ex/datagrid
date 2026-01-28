export default class DataGridFilter {
	initialize(naja) {
		const applyFilters = (doc) => {
			const inputs = doc.querySelectorAll('[data-items-filter]');
			if (inputs) {
				for (let input of inputs) {
					input.addEventListener('input', (e) => {
						naja.uiHandler.submitForm(e.target.form).then();
					});
				}
			}
		}

		applyFilters(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => applyFilters(e.detail.snippet));
	}
}
