# DataGrid Component

A Nette Framework component for displaying tabular data with filtering, sorting, and pagination.

## Installation

```bash
composer install
```

## Usage

### Basic Example

```php
$grid = new DataGrid();
$grid->setDataSource($fluent); // Dibi Fluent query
$grid->setPrimaryKey('id');

// Add columns
$grid->addColumnText('name', 'Name')
    ->setFilterText();

$grid->addColumnText('email', 'Email')
    ->setFilterText();

$grid->addColumnDate('created_at', 'Created', true, 'Y-m-d H:i')
    ->setFilterDate();

// Add actions
$grid->addAction('Edit', 'edit', 'btn-primary', function(int $id) {
    // Handle edit
});

$grid->addAction('Delete', 'delete', 'btn-danger', function(int $id) {
    // Handle delete
});
```

### In Nette Component

```php
class MyPresenter extends Presenter
{
    protected function createComponentGrid(): DataGrid
    {
        $grid = new DataGrid();
        $grid->setDataSource($this->db->query('SELECT * FROM users'));
        $grid->setPrimaryKey('id');

        $grid->addColumnText('name', 'Name')->setFilterText();
        $grid->addColumnDate('created_at', 'Created')->setFilterDate();

        $grid->addAction('Edit', 'edit');
        $grid->addAction('Delete', 'delete');

        return $grid;
    }

    public function handleEdit(int $id): void
    {
        // Handle edit action
    }

    public function handleDelete(int $id): void
    {
        // Handle delete action
    }
}
```

In template:

```latte
{control grid}
```

## Filter Types

### Text Filter

Filters text using LIKE operator:

```php
$grid->addColumnText('name', 'Name')
    ->setFilterText();
```

### Date Filter

Filters dates. Supports:
- **Exact date**: `2025-01-15` → filters specific day
- **Date range**: `2025-01-01|2025-01-31` → filters between dates
- **From date**: `2025-01-01|` → from date onwards
- **To date**: `|2025-01-31` → until date

```php
$grid->addColumnDate('created_at', 'Created')
    ->setFilterDate();
```

## Sorting

All columns are implicitly sortable. To disable sorting:

```php
$grid->addColumnText('description', 'Description', sortable: false);
```

## Cell Formatting

```php
$grid->addColumnText('price', 'Price', formatter: function($value) {
    return number_format((float)$value, 2, ',', ' ') . ' CZK';
});

$grid->addColumnDate('deadline', 'Deadline', formatter: function($value) {
    return strtoupper($value); // Already formatted by ColumnDate
});
```

## Row Actions

```php
$grid->addAction('View', 'view', 'btn-info');
$grid->addAction('Edit', 'edit', 'btn-warning');
$grid->addAction('Delete', 'delete', 'btn-danger');
```

CSS class is optional.

## Exception Handling

The component throws custom exceptions:

```php
use Drago\Datagrid\Exception\InvalidDataSourceException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Datagrid\Exception\InvalidColumnException;

try {
    $grid->render();
} catch (InvalidDataSourceException $e) {
    // Data source is not set
} catch (InvalidConfigurationException $e) {
    // Missing primary key for actions
} catch (InvalidColumnException $e) {
    // Column does not exist in data
}
```

## Security

- All SQL queries are protected by Dibi prepared statements
- HTML in cells is automatically escaped
- Type hints throughout the codebase
- `declare(strict_types=1);` on all files

## Standards

- Nette Coding Standard
- PSR-4 autoloading
- PHP 8.3+

