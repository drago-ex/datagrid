export default class DataGridPage {
	initialize(naja) {
		const dataGridPage = (doc) => {
			const itemCount = doc.querySelectorAll('[data-items-page]');
			if (itemCount) {
				for(let item of itemCount) {
					item.addEventListener('change', (e) => {
						naja.uiHandler.submitForm(e.target.form).then();
					});
				}
			}
		}
		dataGridPage(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => dataGridPage(e.detail.snippet));
	}
}