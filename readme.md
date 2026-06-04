# Drago DataGrid

Drago DataGrid is a powerful and extendable tabular data component built on top of the Nette Framework.
It provides high-performance filtering, sorting, pagination, and row actions with flexible Latte templates for rendering Bootstrap 5 styled tables.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements
- PHP >= 8.3
- Nette Framework
- Dibi
- Latte
- Bootstrap
- Naja
- Composer

## Installation
```bash
composer require drago-ex/datagrid
```

### Frontend Assets

Since the package is installed via Composer, add the following to your `package.json` to make the `drago-datagrid` alias available in your bundler:

```json
{
  "type": "module",
  "dependencies": {
    "drago-datagrid": "file:vendor/drago-ex/datagrid"
  }
}
```

Then run:

```bash
npm install
```

After that, you can import the assets directly from the Composer-installed package:

```js
import naja from 'naja';
import DataGrid from 'drago-datagrid';
import 'drago-datagrid/datagrid.scss';

naja.initialize();

new DataGrid().initialize(naja);
```

## Features
- **Text, Date & Select Filtering** - Advanced filtering with SQL injection protection
- **Filter Display Modes** - Render filters above the table or inline under column headers
- **Column Sorting** - Click headers to sort, toggle ASC/DESC
- **Smart Pagination** - LIMIT/OFFSET at DB level with AJAX history synchronization
- **Row Actions** - Edit, Delete, or custom actions with callbacks and per-row conditions
- **Custom Formatting** - Format cell values with auto-escaping or rich HTML support
- **Localization** - Full support for `Nette\Localization\Translator`
- **Built-in Security** - SQL injection & XSS protection by default
- **AJAX Integration** - Seamless Naja support, no page reload
- **Bootstrap 5** - Beautiful responsive styling
- **Row-Click Actions** - Trigger edit or custom signals by clicking anywhere on the row
- **Auto-hide Actions** - Keep your UI clean by showing buttons only on row hover
- **Action Sorting** - Smart sorting that keeps standard actions (Edit/Delete) at the end
- **Filter Optimizations** - Automatic trimming and prevention of redundant empty submissions

## Quick Start

### 1. Create Component in Presenter

```php
use Drago\Datagrid\DataGrid;
use Dibi\Connection;

class UserPresenter extends Presenter
{
	public function __construct(private Connection $db) {}

	// Create the grid component
	protected function createComponentGrid(): DataGrid
	{
		$grid = new DataGrid;

		// Optional: Set translator for localization
		$grid->setTranslator($this->translator);

		// Set data source (Dibi Fluent query)
		$grid->setDataSource($this->db->select('*')->from('users'));

		// Add columns to display
		$grid->addColumnText('name', 'Name');
		$grid->addColumnText('email', 'Email');
		$grid->addColumnDate('created_at', 'Created', format: 'Y-m-d H:i');

		return $grid;
	}
}
```

### 2. Render in Template

```latte
{control grid}
```

---

## Building Your DataGrid

### Step 1: Set Data Source

```php
$grid = new DataGrid;
$grid->setDataSource($this->db->select('*')->from('products'));
```

The data source must be a `Dibi\Fluent` query object. The grid will apply filtering, sorting, and pagination to it automatically.

### Step 2: Add Columns

Define what columns to display:

```php
// Text column (sortable by default)
$grid->addColumnText('name', 'Product Name');

// Text column without sorting
$grid->addColumnText('sku', 'SKU', sortable: false);

// Date column with custom format
$grid->addColumnDate('updated_at', 'Last Updated', format: 'd.m.Y');
```

**Column Parameters:**
- `name` - Database column name
- `label` - Header label (automatically translated if translator is set)
- `sortable` - Enable click-to-sort (default: true)
- `formatter` - Optional callback to format cell values

### Step 3: Add Filters (Optional)

Enable filtering on specific columns:

```php
$grid->addColumnText('name', 'Product Name')
	->setFilterText();  // Search by name

$grid->addColumnText('status', 'Status')
	->setFilterSelect([
		'active' => 'Active',
		'inactive' => 'Inactive'
	]); // Dropdown filter

$grid->addColumnDate('created_at', 'Created')
	->setFilterDate();  // Date range filter
```

**Filter Types:**
- `setFilterText()` - LIKE search
- `setFilterSelect(array $items)` - Dropdown with predefined values
- `setFilterDate()` - Single date or date range (YYYY-MM-DD)

### Step 4: Choose Filter Mode (Optional)

Filters can be rendered in two modes:

```php
// Default: filters are displayed in a toolbar above the table
$grid->setFilterMode('top');

// Inline: filters are displayed in a second header row under column labels
$grid->setFilterMode('inline');
```

Use `top` when you want a wider toolbar layout. Use `inline` when each filter should stay directly under its column.

### Step 5: Add Row Actions (Optional)

Add Edit/Delete buttons for each row:

```php
// First, set primary key (required for actions)
$grid->setPrimaryKey('id');

// Add action buttons
$grid->addAction('Edit', 'edit', 'btn btn-sm btn-primary', function($id) {
	$this->redirect('edit', $id);
});

$grid->addAction('Delete', 'delete', 'btn btn-sm btn-danger', function($id) {
	$this->db->table('products')->get($id)->delete();
	$this->redirect('this');
});
```

Actions can also be displayed conditionally per row with the `condition` argument.

---

## Formatting Cell Values

### Simple Formatting (Auto-escaped)

```php
$grid->addColumnText('price', 'Price', formatter: function($value, $row) {
	return number_format((float)$value, 2) . ' CZK';
});
```

### Rich HTML Formatting

If you want to render HTML in a cell (e.g., badges), return a `Nette\Utils\Html` object. These are **not** escaped.

```php
use Nette\Utils\Html;

$grid->addColumnText('status', 'Status', formatter: function($value, $row) {
	$color = $value === 'active' ? 'success' : 'danger';
	return Html::el('span')
		->class("badge bg-$color")
		->setText(ucfirst($value));
});
```

---

## Localization

DataGrid fully supports `Nette\Localization\Translator`. When set, it automatically translates:
- Column headers
- Action labels
- Filter labels and prompts
- Pagination info (Showing X–Y of Z items)
- System buttons (Reset, Previous, Next, etc.)

```php
$grid->setTranslator($this->translator);
```

---

## Advanced Layout & UX

### Row-Click Actions
Instead of having an explicit "Edit" button, you can make the entire row clickable to trigger a signal. This is fully compatible with Naja AJAX.

```php
$grid->setPrimaryKey('id');
$grid->setRowClickAction('edit!');

$grid->addAction('Edit', 'edit!', 'ajax btn btn-primary', function($id) {
	// This will be called on row click
});
```
*Note: If the signal in `addAction` matches the `setRowClickAction`, the button will be automatically hidden from the actions column to save space.*

### Hover-to-Show Actions
You can keep the actions column clean and show the buttons only when the user hovers over a row.

```php
$grid->setAutoHideActions(true);
```

### Conditional Actions
You can hide or show an action for each row with the `condition` argument:

```php
$grid->addAction(
	'Activate',
	'activate!',
	'ajax btn btn-sm btn-success',
	callback: fn(int $id) => $this->activate($id),
	condition: fn(array $row): bool => !$row['active'],
);
```

### Action Button Sorting
DataGrid automatically sorts your action buttons. "Special" actions (like Permissions or View) are always displayed first, while "Standard" actions (`edit!` and `delete!`) are always placed at the end of the list, ensuring a consistent UI.

### Filter Display Modes
The default filter mode is `top`, which renders filters above the table in a toolbar:

```php
$grid->setFilterMode('top');
```

Inline mode renders filters directly inside the table header:

```php
$grid->setFilterMode('inline');
```

Both modes use the same filter definitions and AJAX behavior.

### Filter Optimizations
The grid includes built-in optimizations for filtering:
- **Automatic Trimming**: All input values are trimmed on both client and server sides.
- **Redundant Request Prevention**: If a user presses Enter in a filter but the value hasn't changed (or contains only whitespace), no AJAX request is sent.
- **Clean URLs**: Empty filters are automatically removed from persistent parameters, keeping your browser's address bar clean.

---

## Security

### XSS Protection
**All cell values are automatically HTML-escaped** unless they are instances of `Nette\Utils\Html`. This provides a perfect balance between security and flexibility.

### SQL Injection Protection
- Filters use **parameterized queries** via Dibi
- Special characters in LIKE searches are properly escaped
- Input data is validated before execution

### AJAX & History
DataGrid uses Naja for seamless AJAX updates. It automatically synchronizes the browser URL and history, ensuring that filters, sorting, and pagination are preserved even after a page refresh or using the back button.

---
