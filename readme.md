# Drago DataGrid

Drago DataGrid is a Nette component for rendering Bootstrap 5 tables with filtering, sorting, pagination and row actions.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Tests](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- Nette Framework
- Dibi
- Latte
- Bootstrap 5
- Naja

## Installation

```bash
composer require drago-ex/datagrid
```

## Frontend Assets

Add the Composer package as a local npm dependency:

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

Import the assets:

```js
import naja from 'naja';
import DataGrid from 'drago-datagrid';
import 'drago-datagrid/styles/datagrid';

naja.initialize();
new DataGrid().initialize(naja);
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
- `setFilterSelect(array $items)` - exact match select
- `setFilterDate()` - date filter in `YYYY-MM-DD` format

Date filters also support range values in the form `YYYY-MM-DD|YYYY-MM-DD`; either side can be empty.
Both days are included, also for DATETIME columns.

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

Edit and delete have shortcuts with a primary and a danger button:

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
	class: 'ajax btn btn-xs btn-success',
	condition: fn(array $row): bool => !$row['active'],
);
```

The name only identifies the action (letters, digits, `-` and `_`); every action link leads to the grid's `action!` signal and keeps the current filters, page and sorting. The optional `condition` receives the row as `array<string, mixed>` and decides whether the row shows the action. Actions are displayed in the order they are added. The grid is not redrawn after an action, because opening a form or a dialog changes nothing in it; a callback that changes data calls `$grid->redrawDataGrid()`.

## Row Click

The whole row can run an action; its button is then not displayed:

```php
$grid->setRowClickAction('edit');
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
```

The action must be added, otherwise rendering fails.

## Hiding Actions

`setVisible()` hides an action in all rows, typically by the user's permission:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
	->setVisible($this->isWriteAllowed());
```

A hidden action has no button and no row click, and the action column disappears when no action is left. It only decides the display; the `action!` signal itself is guarded by the presenter's authorization.

## Action Display

Show row actions only on hover:

```php
$grid->setAutoHideActions();
```

## Upgrading from 1.x

- `addAction($label, $signal, $class, $callback, $condition)` is now `addAction($name, $label, $callback, $class, $condition)` and returns the `Action`; the callback is required.
- Action names have no `!`: `'edit!'` becomes `'edit'`, also in `setRowClickAction()`.
- `edit` and `delete` are no longer moved to the end, actions keep the order they are added in.
- The natural sort also orders by the text before the number; before, only the first number decided.
- The grid is no longer redrawn after every action; call `redrawDataGrid()` in callbacks that change data.

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

Translated values include column labels, action labels, filter labels, reset button and pagination text; the bundled `cs` translations also cover the default `Edit` and `Delete` labels.

## AJAX

The grid is built for Naja. Filtering, sorting, pagination, page size changes and row actions are handled through AJAX and keep browser history updated.

Text filters are submitted by pressing Enter. Select and date filters are submitted automatically when their value changes.

## Security

- Cell values are escaped unless a formatter returns `Nette\Utils\Html`.
- Filters use parameterized Dibi queries.
- Filter values come from the URL, so invalid values are ignored: a date that is not `YYYY-MM-DD`, an array instead of a single value, or a select value that is not one of the configured options.
- Page size is limited to the offered values (20, 50, 100 and All); other values from the URL use the default.
- Sort direction is normalized to `ASC` or `DESC` before it is applied to SQL.
- Empty filter values are removed from persistent parameters.
