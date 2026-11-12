/**
 * The switch of a grid column (ColumnText::setSwitch()): a change of the checkbox is sent to the link in its
 * data-datagrid-switch as POST "on" (1 or 0). The grid is drawn again by the answer, so the focus goes back
 * to the same switch; when the request fails, the switch returns to its state.
 */
export default class DataGridSwitch {
	initialize(naja) {
		document.addEventListener('change', (event) => {
			const input = event.target.closest?.('input[data-datagrid-switch]');
			if (!input) {
				return;
			}

			naja.makeRequest('POST', input.dataset.datagridSwitch, { on: input.checked ? 1 : 0 }, { history: false }).then(
				() => (input.id ? document.getElementById(input.id) : null)?.focus({ preventScroll: true }),
				() => {
					input.checked = !input.checked;
				},
			);
		});
	}
}
