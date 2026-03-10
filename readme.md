## Drago DataGrid

Drago DataGrid is a powerful and extendable tabular data component built on top of the Nette Framework.
It provides high-performance filtering, sorting, pagination, and row actions with flexible Latte templates for rendering Bootstrap 5 styled tables.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements
- PHP >= 8.3
- Nette Framework 3.2+
- Dibi 5.0+
- Latte 3.1+
- Bootstrap 5.3+
- Naja 3.2+
- Composer

## Features
- **Text & Date Filtering** – LIKE operator with SQL injection protection
- **Column Sorting** – Click headers to sort, toggle ASC/DESC
- **Smart Pagination** – LIMIT/OFFSET at DB level (5.8x faster for large datasets)
- **Row Actions** – Edit, Delete, or custom actions with callbacks
- **Custom Formatting** – Format cell values with auto-escaping
- **Built-in Security** – SQL injection & XSS protection by default
- **AJAX Integration** – Seamless Naja support, no page reload
- **Bootstrap 5** – Beautiful responsive styling

## Installation
```bash
composer require drago-ex/datagrid
```

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

That's it! You have a working data grid with sorting and pagination.

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
- `label` - Header label shown to user
- `sortable` - Enable click-to-sort (default: true)
- `formatter` - Optional callback to format cell values (optional)

### Step 3: Add Filters (Optional)

Enable filtering on specific columns:

```php
$grid->addColumnText('name', 'Product Name')
	->setFilterText();  // Allows user to search by name

$grid->addColumnText('category', 'Category')
	->setFilterText();  // LIKE search

$grid->addColumnDate('created_at', 'Created')
	->setFilterDate();  // Date range filter (YYYY-MM-DD format)
```

**Filter Types:**
- `setFilterText()` - LIKE search with SQL injection protection
- `setFilterDate()` - Single date or date range (YYYY-MM-DD or YYYY-MM-DD|YYYY-MM-DD)

### Step 4: Add Row Actions (Optional)

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

**Action Parameters:**
- `label` - Button text
- `signal` - Signal name (internal identifier)
- `class` - CSS classes for styling
- `callback` - Function executed when clicked, receives row ID

---

## Formatting Cell Values

### Simple Formatting

Format cell output (automatically escaped):

```php
$grid->addColumnText('status', 'Status', formatter: function($value, $row) {
	return match($value) {
		'active' => '✓ Active',
		'inactive' => '✗ Inactive',
		default => 'Unknown'
	};
});

$grid->addColumnText('price', 'Price', formatter: function($value, $row) {
	return number_format((float)$value, 2) . ' CZK';
});
```

### With Full Row Data

Formatters receive the entire row, so you can use related data:

```php
$grid->addColumnText('author_name', 'Author', formatter: function($value, $row) {
	return $row['author_name'] . ' (' . $row['author_email'] . ')';
});
```

### Custom Date Formatting

```php
// Date column with formatter
$grid->addColumnDate('created_at', 'Created', format: 'Y-m-d', 
	formatter: function($value, $row) {
		// $value is already formatted (Y-m-d), add time info
		return $value . ' (' . date('H:i', strtotime($row['created_at'])) . ')';
	}
);
```

---

## Complete Example

```php
use Drago\Datagrid\DataGrid;
use Dibi\Connection;

class UserPresenter extends Presenter
{
	public function __construct(private Connection $db) {}

	protected function createComponentUserGrid(): DataGrid
	{
		$grid = new DataGrid;

		// Data source - Dibi Fluent query
		$grid->setDataSource($this->db->select('*')->from('users'));

		// Columns with filters
		$grid->addColumnText('name', 'Name')
			->setFilterText();

		$grid->addColumnText('email', 'Email')
			->setFilterText();

		$grid->addColumnDate('created_at', 'Registered', format: 'd.m.Y')
			->setFilterDate();

		// Custom formatting
		$grid->addColumnText('status', 'Status', formatter: function($value, $row) {
			return $value === 'active' ? '✓ Active' : '✗ Inactive';
		});

		// Actions (requires primary key)
		$grid->setPrimaryKey('id')
			->addAction('Edit', 'edit', 'btn btn-sm btn-primary', fn($id) => $this->editUser($id))
			->addAction('Delete', 'delete', 'btn btn-sm btn-danger', fn($id) => $this->deleteUser($id));

		return $grid;
	}

	private function editUser($id): void
	{
		// Your edit logic...
	}

	private function deleteUser($id): void
	{
		// Your delete logic...
	}
}
```

---

## Security

### Auto-Escaping Protection
**All cell values and formatter output are automatically HTML-escaped** to prevent XSS attacks. This is enabled by default and cannot be disabled for regular columns.

```php
// Safe - automatically escaped
$grid->addColumnText('description', 'Description', formatter: function($value) {
	return strtoupper($value);  // Output will be escaped
});
```

### SQL Injection Protection
- Text and date filters use **parameterized queries** via Dibi
- Special characters (`%`, `_`) in LIKE searches are properly escaped
- Date filters validate format before executing query

### Other Protections
- **Primary Key Validation** - Primary key existence is checked before rendering actions
- **CSRF Protection** - Handled automatically by Nette Framework
- **Input Validation** - Date filter validates YYYY-MM-DD format

---
