<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid;

use App\Core\Permission\Datagrid\Column\Column;
use App\Core\Permission\Datagrid\Column\ColumnDate;
use App\Core\Permission\Datagrid\Column\ColumnText;
use App\Core\Permission\Datagrid\Filter\FilterControl;
use App\Core\Permission\Datagrid\PageSize\PageSizeControl;
use App\Core\Permission\Datagrid\Paginator\PaginatorControl;
use Closure;
use Dibi\Fluent;
use LogicException;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Control;
use Nette\Utils\Paginator;

/**
 * DataGrid component for displaying tabular data with sorting, pagination, and row actions.
 * @property-read DataGridTemplate $template
 */
class DataGrid extends Control
{
	private ?Fluent $source = null;
	private ?string $primaryKey = null;
	private array $columns = [];
	private array $actions = [];
	private Paginator $paginator;

	#[Parameter]
	public ?string $column = null;

	#[Parameter]
	public string $order = Options::OrderAsc;

	#[Parameter]
	public int $page = Options::DefaultPage;

	#[Parameter]
	public int $itemsPerPage = Options::DefaultItemsPerPage;

	/** Aktuální hodnoty filtrů */
	private array $filterValues = [];

	/** Celkový počet záznamů */
	private int $totalItems = 0;

	public function __construct()
	{
		$this->paginator = new Paginator();
	}


	protected function createComponentFilters(): FilterControl
	{
		$control = new FilterControl;
		$control->setColumns($this->columns);

		$control->onFilterChanged(function(array $filters): void {
			$this->page = Options::DefaultPage; // reset na první stránku
			$this->filterValues = $filters;     // uložíme hodnoty pro další render
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
			if ($column !== null) $this->column = $column;
			if ($order !== null) $this->order = $order;
			$this->redrawControl('dataGrid');
		});

		if ($this->paginator->getItemCount() > 0) {
			$control->setPaginator(
				$this->paginator->getPage(),
				$this->paginator->getItemsPerPage(),
				$this->paginator->getItemCount()
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

	public function setDataSource(Fluent $source): self
	{
		$this->source = $source;
		return $this;
	}

	public function setPrimaryKey(string $primaryKey): self
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}

	public function addColumnText(
		string   $name,
		string   $label,
		bool     $sortable = true,
		?Closure $formatter = null
	): ColumnText
	{
		$column = new ColumnText($name, $label, $sortable, $formatter);
		$this->addColumn($column);
		return $column;
	}

	public function addColumnDate(
		string   $name,
		string   $label,
		bool     $sortable = true,
		string   $format = Options::DateFormat,
		?Closure $formatter = null,
	): self
	{
		$this->addColumn(new ColumnDate($name, $label, $sortable, $format, $formatter));
		return $this;
	}

	private function addColumn(Column $column): void
	{
		if (isset($this->columns[$column->name])) {
			throw new LogicException("Column '{$column->name}' already exists.");
		}
		$this->columns[$column->name] = $column;
	}

	public function addAction(string $label, string $signal, ?string $class = null, ?callable $callback = null): self
	{
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
			$this->order = $this->order === Options::OrderAsc ? Options::OrderDesc : Options::OrderAsc;
		} else {
			$this->column = $column;
			$this->order = Options::OrderAsc;
		}

		$this->redrawControl('dataGrid');
	}

	#[Requires(ajax: true)]
	public function handleAction(string $signal, int $id): void
	{
		foreach ($this->actions as $action) {
			if ($action->signal === $signal) {
				$action->execute($id);
				$this->redrawControl('dataGrid');
				return;
			}
		}
	}

	public function render(): void
	{
		if ($this->source === null) {
			throw new \LogicException('Data source is not set.');
		}

		if ($this->actions !== [] && $this->primaryKey === null) {
			throw new \LogicException('Primary key must be set when using actions.');
		}

		$data = clone $this->source;

		// Aplikujeme filtry
		if (!empty($this->filterValues)) {
			foreach ($this->columns as $colName => $col) {
				if ($col->filter !== null && isset($this->filterValues[$colName])) {
					$col->filter->apply($data, $colName, $this->filterValues[$colName]);
				}
			}
		}

		if ($this->column !== null && isset($this->columns[$this->column])) {
			$order = $this->order;
			$column = $this->column;
			$data->orderBy(
				"CAST(REGEXP_SUBSTR($column, '[0-9]+') AS UNSIGNED) $order"
			);
		}

		$allRows = $data->fetchAll();

		$this->totalItems = count($allRows);

		if ($this->itemsPerPage > 0) {
			$this->paginator->setItemsPerPage($this->itemsPerPage);
			$this->paginator->setPage($this->page);

			$offset = $this->paginator->getOffset();
			$length = $this->paginator->getLength();
			$pageRows = array_slice($allRows, $offset, $length);
		} else {
			// zobraz všechny položky
			$pageRows = $allRows;
			$this->paginator->setItemsPerPage($this->totalItems);
			$this->paginator->setPage(1);
		}

		$this->paginator->setItemCount($this->totalItems);

		if (!empty($pageRows)) {
			$dbColumns = array_keys((array)$pageRows[0]);
			foreach ($this->columns as $colName => $_) {
				if (!in_array($colName, $dbColumns, true)) {
					throw new \LogicException("Column '$colName' does not exist in data source.");
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

		if ($this->getComponent('paginator', false)) {
			$this['paginator']->setPaginator(
				$this->paginator->getPage(),
				$this->paginator->getItemsPerPage(),
				$this->paginator->getItemCount()
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
