# Drago DataGrid

Drago DataGrid is a Nette component for rendering Bootstrap 5 tables with filtering, sorting, pagination, row actions, bulk actions for chosen rows, a change of a state right in the table and an export to CSV.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Tests](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- Nette Framework
- Dibi
- Latte
- Bootstrap 5 with [compostrap](https://github.com/compostrap/compostrap) (styles of the table, the bar of bulk actions, badges)
- Bootstrap Icons
- Naja

## Installation

```bash
composer require drago-ex/datagrid
```

## Assets

Add the Composer package as a local npm dependency (Drago Project installs it by itself):

```json
{
  "type": "module",
  "dependencies": {
    "drago-datagrid": "file:vendor/drago-ex/datagrid"
  }
}
```

Install dependencies:

```bash
npm install
```

Import the assets (Drago Project already initializes Naja in `assets/core/base.js`; there leave out `naja.initialize()`,
which would fail when called twice):

```js
import naja from 'naja';
import DataGrid from 'drago-datagrid';
import { RowSelection } from 'compostrap/selection';

naja.initialize();
new DataGrid().initialize(naja);

// the checkboxes of the rows and the bar of the bulk actions
RowSelection.init();
```

Date filters open the calendar of compostrap; its library `vanilla-calendar-pro` comes with the npm dependencies of the package:

```js
import { Datepicker } from 'compostrap/datepicker';

Datepicker.init();
```

The grid has no styles of its own, it prints the classes of compostrap: its theme, tooltip and elements
(Drago Project with Backend UI already imports them):

```scss
@import 'compostrap/src/scss/theme';
@import 'compostrap/src/scss/tooltip';
@import 'compostrap/src/scss/elements';
```

and the modules of the grid, the selection and the date filter:

```scss
@import 'compostrap/src/scss/datagrid';
@import 'compostrap/src/scss/selection';
@import 'compostrap/src/scss/datepicker';
```

## Basic Usage

```php
use Dibi\Connection;
use Drago\Datagrid\DataGrid;
use Nette\Application\UI\Presenter;

final class ProductPresenter extends Presenter
{
	public function __construct(
		private readonly Connection $db,
	) {
	}

	protected function createComponentGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setDataSource(
			$this->db->select('*')->from('products')
		);

		$grid->addColumnText('name', 'Name')->setFilterText();
		$grid->addColumnText('status', 'Status')->setFilterSelect([
			'active' => 'Active',
			'inactive' => 'Inactive',
		]);
		$grid->addColumnDate('created_at', 'Created', format: 'd.m.Y')->setFilterDate();

		return $grid;
	}
}
```

Render the component in Latte:

```latte
{control grid}
```

## Data Source

The grid expects a `Dibi\Fluent` data source. Filtering, sorting and pagination are applied to a cloned query during rendering.

```php
$grid->setDataSource(
	$this->db->select('*')->from('products')->orderBy('id DESC')
);
```

The data source controls the initial order. The grid keeps its sorting indicator neutral until a user selects a sortable column. The first click sorts that column ascending, and subsequent clicks toggle between ascending and descending order.

For stable pagination, always use a deterministic initial order. When ordering by a non-unique value, add the primary key as the final sorting criterion.

```php
$grid->setDataSource(
	$this->db->select('*')
		->from('products')
		->orderBy('name ASC')
		->orderBy('id ASC')
);
```

## Columns

```php
$grid->addColumnText('name', 'Product Name');
$grid->addColumnText('sku', 'SKU', sortable: false);
$grid->addColumnDate('updated_at', 'Updated', format: 'd.m.Y');
```

Column arguments:
- `name` - database column name
- `label` - column label
- `sortable` - enables header sorting, default is `true`
- `formatter` - optional callback for rendering cell values

Natural sorting puts `Article 4` before `Article 40`; it orders by the text before the first number, then by the number and then by the whole value:

```php
$grid->addColumnText('title', 'Title')->setNaturalSort();
```

Natural sorting uses `REGEXP_SUBSTR()`, which needs MySQL 8 or MariaDB 10.0.5 and newer.

Column text alignment can be changed with Bootstrap alignment helpers:

```php
$grid->addColumnText('id', 'ID')->alignRight();
$grid->addColumnText('status', 'Status')->alignCenter();
$grid->addColumnText('name', 'Name')->alignLeft();
```

Alignment affects only table rendering. It does not validate or convert the column value.

## Filters

```php
$grid->addColumnText('name', 'Name')->setFilterText();

$grid->addColumnText('status', 'Status')->setFilterSelect([
	'active' => 'Active',
	'inactive' => 'Inactive',
]);

$grid->addColumnDate('created_at', 'Created')->setFilterDate();
```

Filter types:
- `setFilterText()` - LIKE search
- `setFilterSelect(array $items, bool $translate = true)` - exact match select
- `setFilterDate()` - one day, chosen in a calendar
- `setFilterDate(range: true)` - a range of days, chosen as the first and the last day in a calendar of two months

The value of a date filter is `YYYY-MM-DD`; a range is two dates (`YYYY-MM-DD – YYYY-MM-DD` as the calendar writes them, or `YYYY-MM-DD|YYYY-MM-DD`, where either side can be empty). Both days are included, also for DATETIME columns. A range is filtered once both days are chosen.

Active filters can be cleared individually with the small clear button next to the filter input. The reset button clears all filters at once.

## Filter Modes

Filters can be rendered in two modes:

```php
$grid->setFilterMode('top');
```

`top` is the default mode. Filters are displayed in a toolbar above the table.

```php
$grid->setFilterMode('inline');
```

`inline` displays filters in a second table header row under the related columns.

Both modes use the same filter definitions and AJAX behavior.

## Row Actions

Set the primary key before adding actions:

```php
$grid->setPrimaryKey('id');
```

The actions of a row are a menu under a button of three dots at the end of the row. Edit and delete have shortcuts
with an icon (pencil, trash); delete is red and separated from the actions before it:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));
```

Other actions get a name, a label and a callback that receives the row ID:

```php
$grid->addAction(
	name: 'activate',
	label: 'Activate',
	callback: fn(int $id) => $this->activate($id),
	condition: fn(array $row): bool => !$row['active'],
	icon: 'bi bi-check2-circle',
);
```

The name only identifies the action (letters, digits, `-` and `_`; `select` is reserved); every action link leads to the grid's `action!` signal and keeps the current filters, page and sorting. The optional `condition` receives the row as `array<string, mixed>` and decides whether the row shows the action. Actions are displayed in the order they are added. The grid is not redrawn after an action, because opening a form or a dialog changes nothing in it; a callback that changes data calls `$grid->redrawDataGrid()`.

Further arguments: `icon` (classes of an icon font, shown before the label), `danger: true` (a red item, separated from the items before it) and `class` (the class of the menu item instead of `ajax dropdown-item`).

## Bulk Actions

Bulk actions work with the rows a user chooses. Once the grid has one, every row gets a checkbox, the header a
checkbox that chooses all rows of the page, and a bar with the buttons of the actions appears while a row is chosen.
They can stand next to the row actions or replace them, which saves the column of the three dots.

```php
$grid->setPrimaryKey('id');

$grid->addBulkActionEdit(fn(int $id) => $this->handleEdit($id));
$grid->addBulkAction('publish', 'Publish', fn(array $ids) => $this->publish($ids), icon: 'bi bi-check2-circle');
$grid->addBulkActionExport();
$grid->addBulkActionDelete(fn(array $ids) => $this->delete($ids));
```

- `addBulkAction(name, label, callback, icon, danger, single, ajax)` - the callback receives the IDs of the chosen rows (`list<int>`). `danger: true` makes the button red, `single: true` makes it an action for one row (the button is disabled while more rows are chosen), `ajax: false` sends the request without AJAX for an action that answers with a file.
- `addBulkActionEdit(callback)` - the action `edit` for one chosen row; the callback receives its ID.
- `addBulkActionDelete(callback)` - the action `delete` with a red button.
- `addBulkActionExport()` - the action `export`: the chosen rows as a CSV file, see [Export to CSV](#export-to-csv).

The buttons stand in the order the actions are added. Every button sends the checkboxes to the grid's `bulk!` signal by POST and keeps the current filters, page and sorting. As with row actions, the grid is not redrawn by itself: a callback that changes data calls `$grid->redrawDataGrid()`, which also clears the selection.

`setVisible()` hides a bulk action, typically by the user's permission; without a visible bulk action the grid has no checkboxes:

```php
$grid->addBulkActionDelete(fn(array $ids) => $this->delete($ids))
	->setVisible($this->isAllowed('delete'));
```

## Row Click

A click on a row can do one of two things.

It runs a row action; the item of that action is then not in the menu of the row:

```php
$grid->setRowClickAction('edit');
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
```

Or it chooses the row for the bulk actions, like a click on its checkbox, so one row is chosen without aiming at
the checkbox:

```php
use Drago\Datagrid\Options;

$grid->setRowClickAction(Options::RowClickSelect); // 'select'
$grid->addBulkActionEdit(fn(int $id) => $this->handleEdit($id));
```

The named row action, or a bulk action for the choosing, must be added, otherwise rendering fails. A click on
a link, a button or a field in the row keeps its own meaning.

## Hiding Actions

`setVisible()` hides an action in all rows, typically by the user's permission:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
	->setVisible($this->isWriteAllowed());
```

A hidden action has no item in the menu and no row click, and the action column disappears when no action is left. The grid
also refuses the `action!` signal of a hidden action, or of a row its condition leaves out, and the `bulk!` signal of
a hidden bulk action, so they cannot be sent directly; the access itself is still checked by the presenter's authorization.

## Action Display

Show the menu of a row only under the mouse (and with the keyboard focus in the row):

```php
$grid->setAutoHideActions();
```

## Export to CSV

The grid exports its rows in two ways, which can be used together:

```php
// the button "Export" under the table: all rows of the current filters, not only the shown page
$grid->setExport('articles.csv');

// the button "Export" in the bar of the bulk actions: only the chosen rows
$grid->addBulkActionExport();
```

`setExport(fileName, delimiter, button)` sets the name of the downloaded file and the delimiter for both; `button: false` sets them without showing the button under the table. Without it the file is `export.csv`.

The file holds the columns of the grid with their labels in the first line and the texts the grid shows in the cells (after the formatter, without HTML), in the current sorting. It is UTF-8 with the byte order mark and the cells are separated by a semicolon, so a spreadsheet with a decimal comma (e.g. the Czech one) opens it by a double click; pass `delimiter: ','` for a comma. A text that starts like a formula (`=`, `+`, `-`, `@`) gets an apostrophe before it, so a spreadsheet does not run it.

A column that makes no sense in a file, e.g. one with buttons, is left out:

```php
$grid->addColumnText('comments', 'Comments', formatter: $this->formatComments(...))
	->setExportable(false);
```

## Changing a State

A state of a row (published or draft, active or not) can be changed right in the table, without opening a form
and without a bulk action. The state is shown as its badge with a small arrow, which opens a menu of the states:

```php
$grid->addColumnText('status', 'Status')
	->setState(
		name: 'publish',
		callback: fn(int $id, int|string $state) => $this->publish($id, $state === 'published'),
		states: [
			'published' => ['Published', 'success'],
			'draft' => ['Draft', 'secondary'],
		],
		enabled: $this->isAllowed('publish'),
	);
```

- `name` identifies the state in its link (letters, digits, `-` and `_`).
- `callback` receives the row ID and the chosen value; it saves it and calls `$grid->redrawDataGrid()`.
- `states` map the values of the column to a label and a color of the theme, as [badges](#badges) do; a column
  with `true` and `false` uses the keys `1` and `0`. There can be more than two states.
- `enabled: false` shows the badge only: it has no menu and the grid refuses its signal.

A choice is sent to the grid's `state!` signal by POST and keeps the current filters, page and sorting; a value
that is not one of the states is refused. The column can still be sorted and filtered, and the export writes the
label of the state.

## Badges

A state of a row (published, draft, active) reads best as a light colored badge. `setBadges()` maps the values of
a column to a label and a color of the theme (`primary`, `secondary`, `success`, `danger`, `warning`, `info`):

```php
$grid->addColumnText('status', 'Status')
	->setBadges([
		'published' => ['Published', 'success'],
		'draft' => ['Draft', 'secondary'],
	])
	->setFilterSelect(['published' => 'Published', 'draft' => 'Draft']);
```

The badge is only read, it is not a button: a state is changed by `setState()` (above) or by an action. A value without a badge is printed as
a text (through the formatter, when the column has one). Labels are translated; pass `translate: false` for labels
that are data. The export writes the label.

A badge that does something on a click stays possible with a formatter that returns `Nette\Utils\Html`, see below.

## Formatting Values

Simple values are escaped automatically:

```php
$grid->addColumnText('price', 'Price', formatter: function (mixed $value, array $row): string {
	return number_format((float) $value, 2) . ' CZK';
});
```

Return `Nette\Utils\Html` when you want to render HTML:

```php
use Nette\Utils\Html;

$grid->addColumnText('status', 'Status', formatter: function (mixed $value, array $row): Html {
	return Html::el('span')
		->class($value === 'active' ? 'badge bg-success' : 'badge bg-secondary')
		->setText((string) $value);
});
```

## Localization

The grid supports `Nette\Localization\Translator`:

```php
$grid->setTranslator($this->translator);
```

Translated values include column labels, action labels, labels of badges, filter labels, reset button, the texts of the selection and pagination text; the bundled `cs` translations also cover the default `Edit`, `Delete` and `Export` labels.

Options of a select filter are translated too. Options that are data, such as names from the database, are left
untranslated with `translate: false`, so the translator does not report them as missing:

```php
$grid->addColumnText('category_id', 'Category')
	->setFilterSelect($categories, translate: false);
```

## AJAX

The grid is built for Naja. Filtering, sorting, pagination, page size changes, row actions and bulk actions are handled through AJAX and keep browser history updated; an export is a plain download.

Text filters are submitted by pressing Enter. Select and date filters are submitted automatically when their value changes.

## Security

- Cell values are escaped unless a formatter returns `Nette\Utils\Html`.
- Filters use parameterized Dibi queries.
- Filter values come from the URL, so invalid values are ignored: a date that is not `YYYY-MM-DD`, an array instead of a single value, or a select value that is not one of the configured options.
- Page size is limited to the offered values (20, 50, 100 and All); other values from the URL use the default.
- Sort direction is normalized to `ASC` or `DESC` before it is applied to SQL.
- Empty filter values are removed from persistent parameters.
- A bulk action takes only positive whole numbers as row IDs, each once; a hidden action is not run, and a state that is not enabled or a value that is not one of its states is not changed.
- Signals are checked by the presenter's authorization. With [drago-ex/permission](https://github.com/drago-ex/permission) a row action, a bulk action and a change of a state need the privilege named like them (`edit`, `delete`, `export`, `publish`), the button of the export `export`.
