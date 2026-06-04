# Drago DataGrid

Drago DataGrid is a Nette component for rendering Bootstrap 5 tables with filtering, sorting, pagination and row actions.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements
- PHP >= 8.3
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
import 'drago-datagrid/datagrid.scss';

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

A default `orderBy()` can be used for the initial render. When the user clicks a sortable column, the grid replaces the default order with the selected grid sort.

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

Natural numeric sorting can be enabled on text columns:

```php
$grid->addColumnText('code', 'Code')->setNaturalSort();
```

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

Date filters also support range values in the form `YYYY-MM-DD|YYYY-MM-DD`.

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

Add action callbacks:

```php
$grid->addAction('Edit', 'edit!', 'ajax btn btn-sm btn-primary', function (int $id): void {
	$this->redirect('edit', $id);
});

$grid->addAction('Delete', 'delete!', 'ajax btn btn-sm btn-danger', function (int $id): void {
	// Delete row
});
```

Actions can be shown or hidden per row:

```php
$grid->addAction(
	'Activate',
	'activate!',
	'ajax btn btn-sm btn-success',
	callback: fn(int $id) => $this->activate($id),
	condition: fn(array $row): bool => !$row['active'],
);
```

The `condition` callback receives the current row as `array<string, mixed>`.

## Row Click

The whole row can trigger an action:

```php
$grid->setPrimaryKey('id');
$grid->setRowClickAction('edit!');

$grid->addAction('Edit', 'edit!', 'ajax btn btn-sm btn-primary', function (int $id): void {
	$this->redirect('edit', $id);
});
```

If the action signal matches `setRowClickAction()`, the action button is hidden and only the row click is used.

## Action Display

Show row actions only on hover:

```php
$grid->setAutoHideActions();
```

Actions with signals `edit!` and `delete!` are displayed after other custom actions.

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

Translated values include column labels, action labels, filter labels, reset button and pagination text.

## AJAX

The grid is built for Naja. Filtering, sorting, pagination, page size changes and row actions are handled through AJAX and keep browser history updated.

## Security

- Cell values are escaped unless a formatter returns `Nette\Utils\Html`.
- Filters use parameterized Dibi queries.
- Empty filter values are removed from persistent parameters.
- Redundant filter submissions are skipped on the client side.
