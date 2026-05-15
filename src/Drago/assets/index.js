import "./datagrid.scss";
import DataGridFilter from "./datagrid-filter.js";
import DataGridPage from "./page-items.js";
import DataGridRowClick from "./row-click.js";

export default class DataGrid {
	initialize(naja) {
		new DataGridFilter().initialize(naja);
		new DataGridPage().initialize(naja);
		new DataGridRowClick().initialize(naja);
	}
}
