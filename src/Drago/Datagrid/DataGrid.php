<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;

use Closure;
use Dibi\Fluent;
use Drago\Datagrid\Column\Column;
use Drago\Datagrid\Column\ColumnDate;
use Drago\Datagrid\Column\ColumnText;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Datagrid\Exception\InvalidDataSourceException;
use Drago\Datagrid\Filter\FilterTextControl;
use Drago\Datagrid\PageSize\PageSizeControl;
use Drago\Datagrid\Paginator\PaginatorControl;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Control;
use Nette\Utils\Paginator as UtilsPaginator;


/**
 * DataGrid component for displaying tabular data
 * with filtering, sorting, pagination, and row actions.
 *
 * @property-read DataGridTemplate $template
 */
class DataGrid extends Control
{
	private ?Fluent $source = null;

	private ?string $primaryKey = null;

	private array $columns = [];

	private array $actions = [];

	private UtilsPaginator $paginator;

	/** Current filter values */
	private array $filterValues = [];

	/** Total number of records */
	private int $totalItems = 0;

	#[Parameter]
	public ?string $column = null;

	#[Parameter]
	public string $order = Options::OrderAsc;

	#[Parameter]
	public int $page = Options::DefaultPage;

	#[Parameter]
	public int $itemsPerPage = Options::DefaultItemsPerPage;


	public function __construct()
	{
		$this->paginator = new UtilsPaginator();
	}


	protected function createComponentFilters(): FilterTextControl
	{
		$control = new FilterTextControl();
		$control->setColumns($this->columns);

		$control->onFilterChanged(function (array $filters): void {
			$this->page = Options::DefaultPage; // reset to first page
			$this->filterValues = $filters; // store values for next render
			$this->redrawControl('dataGrid');
		});

		$control->setValues($this->filterValues ?? []);

		return $control;
	}


	protected function createComponentPaginator(): PaginatorControl
	{
		$control = new PaginatorControl();
		$control->onPageChanged(function (int $page, ?string $column, ?string $order): void {
			$this->page = $page;
			if ($column !== null) {
				$this->column = $column;
			}
			if ($order !== null) {
				$this->order = $order;
			}
			$this->redrawControl('dataGrid');
		});

		if ($this->paginator->getItemCount() > 0) {
			$control->setPaginator(
				$this->paginator->getPage(),
				$this->paginator->getItemsPerPage(),
				$this->paginator->getItemCount(),
			);
			$control->setSorting($this->column, $this->order);
		}

		return $control;
	}


	protected function createComponentPageSize(): PageSizeControl
	{
		$control = new PageSizeControl();
		$control->setTotalItems($this->totalItems);
		$control->setCurrentPageSize($this->itemsPerPage);

		$control->onPageChanged(function (int $page, int $itemsPerPage): void {
			$this->page = $page;
			$this->itemsPerPage = $itemsPerPage;
			$this->redrawControl('dataGrid');
		});

		return $control;
	}


	/**
	 * Sets the data source for the DataGrid.
	 */
	public function setDataSource(Fluent $source): self
	{
		$this->source = $source;
		return $this;
	}


	/**
	 * Sets primary key used for row actions.
	 */
	public function setPrimaryKey(string $primaryKey): self
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}


	/**
	 * Adds a text column to the DataGrid.
	 * @throws InvalidColumnException
	 */
	public function addColumnText(
		string $name,
		string $label,
		bool $sortable = true,
		?Closure $formatter = null,
	): ColumnText {
		$column = new ColumnText($name, $label, $sortable, $formatter);
		$this->addColumn($column);
		return $column;
	}


	/**
	 * Adds a date column to the DataGrid.
	 * @throws InvalidColumnException
	 */
	public function addColumnDate(
		string $name,
		string $label,
		bool $sortable = true,
		string $format = Options::DateFormat,
		?Closure $formatter = null,
	): ColumnDate {
		$column = new ColumnDate($name, $label, $sortable, $format, $formatter);
		$this->addColumn($column);
		return $column;
	}


	/**
	 * Registers a column.
	 * @throws InvalidColumnException
	 */
	private function addColumn(Column $column): void
	{
		if (isset($this->columns[$column->name])) {
			throw new InvalidColumnException("Column '{$column->name}' already exists.");
		}
		$this->columns[$column->name] = $column;
	}


	/**
	 * Adds a row action.
	 */
	public function addAction(
		string $label,
		string $signal,
		?string $class = null,
		?callable $callback = null,
	): self {
		$action = new Action($label, $signal, $class);
		if ($callback !== null) {
			$action->addCallback($callback);
		}
		$this->actions[] = $action;
		return $this;
	}


	#[Requires(ajax: true)]
	public function handleSort(string $column, int $page): void
	{
		if (!isset($this->columns[$column]) || !$this->columns[$column]->sortable) {
			return;
		}

		$this->page = $page;

		if ($this->column === $column) {
			$this->order = $this->order === Options::OrderAsc
				? Options::OrderDesc
				: Options::OrderAsc;
		} else {
			$this->column = $column;
			$this->order = Options::OrderAsc;
		}

		$this->redrawControl('dataGrid');
	}


	#[Requires(ajax: true)]
	public function handleAction(string $signal, int $id, array $filters = [], int $page = Options::DefaultPage, int $itemsPerPage = Options::DefaultItemsPerPage, ?string $column = null, ?string $order = null): void
	{
		// Zachovat filter hodnoty, stránkování a řazení z parametrů
		if (!empty($filters)) {
			$this->filterValues = $filters;
		}
		$this->page = $page;
		$this->itemsPerPage = $itemsPerPage;
		if ($column !== null) {
			$this->column = $column;
		}
		if ($order !== null) {
			$this->order = $order;
		}

		foreach ($this->actions as $action) {
			if ($action->signal === $signal) {
				$action->execute($id);
				$this->redrawControl('dataGrid');
				return;
			}
		}
	}


	/**
	 * Returns current filter values.
	 */
	public function getFilterValues(): array
	{
		return $this->filterValues;
	}


	/**
	 * Renders the DataGrid
	 * @throws InvalidColumnException
	 * @throws InvalidConfigurationException
	 * @throws InvalidDataSourceException
	 */
	public function render(): void
	{
		if ($this->source === null) {
			throw new InvalidDataSourceException('Data source is not set.');
		}

		if ($this->actions !== [] && $this->primaryKey === null) {
			throw new InvalidConfigurationException('Primary key must be set when using actions.');
		}

		$data = clone $this->source;

		// Apply filters
		if (!empty($this->filterValues)) {
			foreach ($this->columns as $colName => $col) {
				if ($col->filter !== null && isset($this->filterValues[$colName])) {
					$col->filter->apply($data, $colName, $this->filterValues[$colName]);
				}
			}
		}

		if ($this->column !== null && isset($this->columns[$this->column])) {
			$data->orderBy(
				"CAST(REGEXP_SUBSTR(%n, '[0-9]+') AS UNSIGNED) {$this->order}",
				$this->column,
			);
		}

		$allRows = $data->fetchAll();
		$this->totalItems = count($allRows);

		if ($this->itemsPerPage > 0) {
			$this->paginator->setItemsPerPage($this->itemsPerPage);
			$this->paginator->setPage($this->page);

			$pageRows = array_slice(
				$allRows,
				$this->paginator->getOffset(),
				$this->paginator->getLength(),
			);
		} else {
			// show all items
			$pageRows = $allRows;
			$this->paginator->setItemsPerPage($this->totalItems);
			$this->paginator->setPage(1);
		}

		$this->paginator->setItemCount($this->totalItems);

		if (!empty($pageRows)) {
			$dbColumns = array_keys((array) $pageRows[0]);
			foreach ($this->columns as $colName => $_) {
				if (!in_array($colName, $dbColumns, true)) {
					throw new InvalidColumnException("Column '$colName' does not exist in data source.");
				}
			}
		}

		$template = $this->template;
		$template->setFile(__DIR__ . '/DataGrid.latte');
		$template->rows = $pageRows;
		$template->columns = $this->columns;
		$template->columnName = $this->column;
		$template->order = $this->order;
		$template->actions = $this->actions;
		$template->primaryKey = $this->primaryKey;
		$template->page = $this->paginator->getPage();
		$template->itemsPerPage = $this->paginator->getItemsPerPage();
		$template->totalItems = $this->paginator->getItemCount();
		$template->filters = $this->filterValues;

		if ($this->getComponent('paginator', false)) {
			$this['paginator']->setPaginator(
				$this->paginator->getPage(),
				$this->paginator->getItemsPerPage(),
				$this->paginator->getItemCount(),
			);

			$this['paginator']->setSorting(
				$this->column,
				$this->order,
			);
		}

		if ($this->getComponent('pageSize', false)) {
			$this['pageSize']->setTotalItems($this->totalItems);
			$this['pageSize']->setCurrentPageSize($this->itemsPerPage);
		}

		$template->render();
	}
}
