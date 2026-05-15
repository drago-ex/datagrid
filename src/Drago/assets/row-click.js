export default class DataGridRowClick {
	initialize(naja) {
		const applyRowClick = (doc) => {
			const rows = doc.querySelectorAll('.datagrid-row-clickable');
			for (let row of rows) {
				row.addEventListener('click', (e) => {
					// Do not trigger if clicking on interactive elements
					const target = e.target;
					if (target.tagName === 'A' || target.tagName === 'BUTTON' || target.closest('a') || target.closest('button') || target.tagName === 'INPUT') {
						return;
					}

					const url = row.getAttribute('data-row-click-url');
					if (url) {
						naja.makeRequest('GET', url);
					}
				});
			}
		};

		applyRowClick(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => applyRowClick(e.detail.snippet));
	}
}
