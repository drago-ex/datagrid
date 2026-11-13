/**
 * The menu of a state in a grid column (ColumnText::setState()): a click on an item sends its value to the link
 * in its data-datagrid-state as POST "value". The grid is drawn again by the answer, so the focus goes back to
 * the badge of the same row. The current state has no link, a click on it only closes the menu.
 */
export default class DataGridState {
	initialize(naja) {
		document.addEventListener('click', (event) => {
			const item = event.target.closest?.('button[data-datagrid-state]');
			if (!item) {
				return;
			}

			const badgeId = item.closest('.dropdown')?.querySelector('.datagrid-state')?.id;
			naja.makeRequest('POST', item.dataset.datagridState, { value: item.value }, { history: false }).then(
				() => (badgeId ? document.getElementById(badgeId) : null)?.focus({ preventScroll: true }),
				() => {},
			);
		});
	}
}
