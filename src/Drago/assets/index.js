import "./datagrid.scss";
import DataGridFilter from "./datagrid.js";
import DataGridPage from "./page-items.js";

export { DataGridFilter, DataGridPage };

export default class DataGrid {
	initialize(naja) {
		new DataGridFilter().initialize(naja);
		new DataGridPage().initialize(naja);
	}
}
