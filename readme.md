## Drago DataGrid

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
- Bootstrap 5
- Naja
- Composer

## Features
- **Text & Date Filtering** – LIKE operator with SQL injection protection and date format validation
- **Column Sorting** – Click headers to sort, toggle ASC/DESC
- **Smart Pagination** – LIMIT/OFFSET at DB level (5.8x faster for 1M rows)
- **Row Actions** – Edit, Delete, or custom actions with callbacks
- **Custom Formatting** – Format cell values with callbacks
- **Security Built-in** – SQL injection & XSS protection by default
- **Performance Optimized** – Only fetches data for current page
- **AJAX Integration** – Seamless Naja integration, no page refresh
- **Bootstrap 5** – Beautiful responsive styling included
- **Modular Architecture** – Easy to understand, test, and extend

## Installation
```bash
composer require drago-ex/datagrid
```

## Basic Usage

```php
// In your Presenter
public function createComponentUserGrid(): DataGrid
{
    $grid = new DataGrid;
    
    $grid->setDataSource($this->db->table('users'))
        ->setPrimaryKey('id')
        ->addColumnText('name', 'Name')
            ->setFilterText()
        ->addColumnText('email', 'Email')
            ->setFilterText()
        ->addColumnDate('created_at', 'Created', format: 'Y-m-d')
            ->setFilterDate()
        ->addAction('Edit', 'edit', 'btn btn-sm btn-primary', fn($id) => $this->editUser($id))
        ->addAction('Delete', 'delete', 'btn btn-sm btn-danger', fn($id) => $this->deleteUser($id));
    
    return $grid;
}
```

## Custom Column Formatting

Use formatters to display custom content in columns. **All formatter output is automatically escaped** to prevent XSS:

```php
$grid->addColumnText('name', 'Name', formatter: function($value, $row) {
    return strtoupper($value);  // Safe - will be escaped automatically
});

$grid->addColumnText('status', 'Status', formatter: function($value, $row) {
    // Return text content - escaping is automatic
    return $value === 'active' ? '✓ Active' : '✗ Inactive';
});
```

### Displaying HTML in Cells

If you need to display HTML markup, you must explicitly opt-out of auto-escaping in the template (advanced use only):

```php
// In your presenter
$grid->addColumnText('preview', 'Preview', formatter: function($value, $row) {
    return "<b>Important:</b> $value";
});
```

Then in your custom template (not recommended unless necessary):
```latte
<td>{$col->renderCell((array) $row)|noescape}</td>
```

## ⚠️ Security

### Auto-Escaping Protection
- **Formatter output is automatically HTML-escaped** using `htmlspecialchars()`
- This applies to both text and date column formatters
- Protects against XSS attacks by default

### Built-in Protection
- **SQL Injection**: Text and date filters use parameterized queries via Dibi
- **LIKE Injection**: Special characters (`%`, `_`) are properly escaped
- **Date Validation**: Date filters validate YYYY-MM-DD format to prevent malformed input
- **Primary Key Validation**: Primary key existence is checked before rendering actions

---
