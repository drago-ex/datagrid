<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;

use Closure;
use Dibi\Fluent;
use Dibi\Row;
use Drago\Datagrid\Column\Column;
use Drago\Datagrid\Column\ColumnDate;
use Drago\Datagrid\Column\ColumnText;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Datagrid\Exception\InvalidDataSourceException;
use Drago\Datagrid\Filter\FilterTextControl;
use Drago\Datagrid\PageSize\PageSizeControl;
use Drago\Datagrid\Paginator\PaginatorControl;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\Localization\Translator;
use Nette\Utils\Paginator as UtilsPaginator;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * DataGrid component for displaying tabular data
 * with filtering, sorting, pagination, and row actions.
 *
 * @property-read DataGridTemplate $template
 */
class DataGrid extends Control
{
	/** Persistent sorting column */
	#[Persistent] public ?string $column = null;

	/** Persistent sorting order (ASC/DESC) */
	#[Persistent] public string $order = Options::OrderAsc;

	/** Persistent current page */
	#[Persistent] public int $page = Options::DefaultPage;

	/** Persistent items per page */
	#[Persistent] public int $itemsPerPage = Options::DefaultItemsPerPage;

	/** @var array<string, mixed> Persistent current filter values */
	#[Persistent] public array $filterValues = [];

	private bool $autoHideActions = false;
	private ?string $rowClickAction = null;
	private ?Fluent $source = null;
	private ?string $primaryKey = null;

	/** @var array<string, Column> */
	private array $columns = [];

	/** @var list<Action> */
	private array $actions = [];

	private string $filterMode = Options::FilterModeTop;
	private UtilsPaginator $paginator;
	private int $totalItems = 0;
	private ?Translator $translator = null;


	public function __construct()
	{
		$this->paginator = new UtilsPaginator;
	}


	/**
	 * Sets whether action buttons should be visible only on row hover.
	 */
	public function setAutoHideActions(bool $autoHide = true): self
	{
		$this->autoHideActions = $autoHide;
		return $this;
	}


	/**
	 * Sets the signal name for row click action.
	 */
	public function setRowClickAction(string $signal): self
	{
		$this->rowClickAction = $signal;
		return $this;
	}


	/**
	 * Sets the translator for the DataGrid and its components.
	 */
	public function setTranslator(?Translator $translator): self
	{
		$this->translator = $translator;
		return $this;
	}


	/**
	 * Returns the translator.
	 */
	public function getTranslator(): ?Translator
	{
		return $this->translator;
	}


	/**
	 * Sets the filter display mode ('top' or 'inline').
	 */
	public function setFilterMode(string $mode): self
	{
		$this->filterMode = $mode;
		return $this;
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
	): ColumnText
	{
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
	): ColumnDate
	{
		$column = new ColumnDate($name, $label, $sortable, $format, $formatter);
		$this->addColumn($column);
		return $column;
	}


	/**
	 * Adds a row action with optional callback and optional visibility condition.
	 * @param callable(array<string, mixed>): bool|null $condition
	 */
	public function addAction(
		string $label,
		string $signal,
		?string $class = null,
		?callable $callback = null,
		?callable $condition = null,
	): self
	{
		$action = new Action($label, $signal, $class);
		if ($callback !== null) {
			$action->addCallback($callback);
		}
		if ($condition !== null) {
			$action->setCondition($condition);
		}
		$this->actions[] = $action;
		return $this;
	}


	/**
	 * Handles sorting when a column header is clicked.
	 */
	public function handleSort(string $column, int $page, string $sortOrder = Options::OrderAsc): void
	{
		if (!isset($this->columns[$column]) || !$this->columns[$column]->sortable) {
			return;
		}
		if ($page < 1) {
			return;
		}
		if (!in_array($sortOrder, [Options::OrderAsc, Options::OrderDesc], true)) {
			return;
		}

		$this->page = $page;
		$this->column = $column;
		$this->order = $sortOrder;

		$this->redrawDataGrid();
	}


	/**
	 * Handles execution of row actions while preserving current grid state.
	 * @param array<string, mixed> $filters
	 */
	public function handleAction(
		string $signal,
		int $id,
		array $filters = [],
		int $page = Options::DefaultPage,
		int $itemsPerPage = Options::DefaultItemsPerPage,
		?string $column = null,
		?string $order = null,
	): void
	{
		if ($id <= 0 || $page < 1 || $itemsPerPage < 1) {
			return;
		}
		if ($order !== null && !in_array($order, [Options::OrderAsc, Options::OrderDesc], true)) {
			return;
		}

		$validSignals = array_map(fn(Action $a) => $a->signal, $this->actions);
		if (!in_array($signal, $validSignals, true)) {
			return;
		}

		if (!empty($filters)) {
			$this->filterValues = $filters;
		}
		$this->page = $page;
		$this->itemsPerPage = $itemsPerPage;
		if ($column !== null && isset($this->columns[$column])) {
			$this->column = $column;
		}
		if ($order !== null) {
			$this->order = $order;
		}

		foreach ($this->actions as $action) {
			if ($action->signal === $signal) {
				$action->execute($id);
				$this->redrawDataGrid();
				return;
			}
		}
	}


	/**
	 * Returns current filter values.
	 * @return array<string, mixed>
	 */
	public function getFilterValues(): array
	{
		return $this->filterValues;
	}


	/**
	 * Renders the DataGrid with current filters, sorting, and pagination.
	 * @throws InvalidColumnException
	 * @throws InvalidConfigurationException
	 * @throws InvalidDataSourceException
	 */
	public function render(): void
	{
		$this->validateConfiguration();
		$data = clone $this->source;

		$this['filters']->setValues($this->filterValues);

		$this->applyFilters($data);
		$this->applySorting($data);
		$this->calculateTotalItems($data);

		$pageRows = $this->fetchPageRows($data);
		$this->validatePrimaryKeyExists($pageRows);
		$this->validateColumns($pageRows);
		$this->renderTemplate($pageRows);
	}


	/**
	 * Creates filter component and sets callback for filter changes.
	 */
	protected function createComponentFilters(): FilterTextControl
	{
		$filter = new FilterTextControl;
		$filter->setTranslator($this->translator);
		$filter->setColumns($this->columns);
		$filter->setValues($this->filterValues);
		$filter->setFilterMode($this->filterMode);
		$filter->onFilterChanged(function (array $values): void {
			$this->filterValues = $values;
			$this->page = 1;
			$this->redrawDataGrid();
		});

		$filter->onReset(function (): void {
			$this->filterValues = [];
			$this->page = 1;
			$this->redrawDataGrid();
		});

		return $filter;
	}


	/**
	 * Creates paginator component and sets callback for page changes.
	 */
	protected function createComponentPaginator(): PaginatorControl
	{
		$control = new PaginatorControl;
		$control->setTranslator($this->translator);
		$control->onPageChanged(function (int $page, ?string $column, ?string $order): void {
			$this->page = $page;
			if ($column !== null) {
				$this->column = $column;
			}
			if ($order !== null) {
				$this->order = $order;
			}
			$this->redrawDataGrid();
		});

		if ($this->paginator->getItemCount() > 0) {
			$control->setPaginator($this->paginator->getPage(), $this->paginator->getItemsPerPage(), $this->paginator->getItemCount());
			$control->setSorting($this->column, $this->order);
		}

		return $control;
	}


	/**
	 * Creates page size selector component.
	 */
	protected function createComponentPageSize(): PageSizeControl
	{
		$control = new PageSizeControl;
		$control->setTranslator($this->translator);
		$control->setTotalItems($this->totalItems);
		$control->setCurrentPageSize($this->itemsPerPage);

		$control->onPageChanged(function (int $page, int $itemsPerPage): void {
			$this->page = $page;
			$this->itemsPerPage = $itemsPerPage;
			$this->redrawDataGrid();
		});

		return $control;
	}


	/**
	 * Adds a column internally and validates uniqueness.
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
	 * Validates configuration before rendering.
	 * @throws InvalidConfigurationException
	 * @throws InvalidDataSourceException
	 */
	private function validateConfiguration(): void
	{
		if ($this->source === null) {
			throw new InvalidDataSourceException('Data source is not set.');
		}
		if ($this->actions !== [] && $this->primaryKey === null) {
			throw new InvalidConfigurationException('Primary key must be set when using actions.');
		}
	}


	/**
	 * Validates that primary key exists in fetched rows.
	 * @param list<array<string, mixed>|Row> $pageRows
	 * @throws InvalidColumnException
	 */
	private function validatePrimaryKeyExists(array $pageRows): void
	{
		if ($this->primaryKey === null || $pageRows === []) {
			return;
		}

		$firstRow = (array) $pageRows[0];
		if (!array_key_exists($this->primaryKey, $firstRow)) {
			throw new InvalidColumnException(
				sprintf(
					'Primary key "%s" not found in data source. Ensure the column is selected in your query and the name is correct.',
					$this->primaryKey,
				),
			);
		}
	}


	/**
	 * Applies current filter values to the data source.
	 */
	private function applyFilters(Fluent $data): void
	{
		if (empty($this->filterValues)) {
			return;
		}
		foreach ($this->columns as $colName => $col) {
			if ($col->filter !== null && isset($this->filterValues[$colName])) {
				$col->filter->apply($data, $colName, $this->filterValues[$colName]);
			}
		}
	}


	/**
	 * Applies current sorting to the data source.
	 */
	private function applySorting(Fluent $data): void
	{
		if ($this->column === null || !isset($this->columns[$this->column])) {
			return;
		}
		$columnObj = $this->columns[$this->column];
		$data->removeClause('ORDER BY');

		if ($columnObj->isNaturalSort()) {
			try {
				$data->orderBy("CAST(REGEXP_SUBSTR(%n, '[0-9]+') AS UNSIGNED) {$this->order}", $this->column);
				return;
			} catch (\Throwable $e) {
				Debugger::log($e, ILogger::WARNING);
			}
		}

		$data->orderBy("%n {$this->order}", $this->column);
	}


	/**
	 * Counts total items for pagination.
	 */
	private function calculateTotalItems(Fluent $data): void
	{
		$this->totalItems = $data->count();
	}


	/**
	 * Fetches current page rows based on paginator.
	 * @return list<array<string, mixed>>
	 */
	private function fetchPageRows(Fluent $data): array
	{
		$this->paginator->setItemsPerPage($this->itemsPerPage > 0 ? $this->itemsPerPage : $this->totalItems);
		$this->paginator->setPage($this->page);
		$this->paginator->setItemCount($this->totalItems);

		if ($this->itemsPerPage > 0) {
			$data->limit($this->itemsPerPage)->offset($this->paginator->getOffset());
		}

		/** @var list<array<string, mixed>> */
		return $data->fetchAll();
	}


	/**
	 * Validates that all defined columns exist in the data source.
	 * @param list<array<string, mixed>|Row> $pageRows
	 * @throws InvalidColumnException
	 */
	private function validateColumns(array $pageRows): void
	{
		if ($pageRows === []) {
			return;
		}

		$firstRow = (array) $pageRows[0];
		$dbColumns = array_keys($firstRow);

		foreach ($this->columns as $colName => $_) {
			if (!in_array($colName, $dbColumns, true)) {
				throw new InvalidColumnException("Column '$colName' does not exist in data source.");
			}
		}
	}


	/**
	 * Renders the template with all variables for the DataGrid.
	 * @param list<array<string, mixed>> $pageRows
	 */
	private function renderTemplate(array $pageRows): void
	{
		$template = $this->template;
		$template->control = $this;
		if ($this->translator !== null) {
			$template->setTranslator($this->translator);
		}
		$template->setFile(__DIR__ . '/DataGrid.latte');

		$template->rows = $pageRows;
		$template->columns = $this->columns;
		$template->columnName = $this->column;
		$template->order = $this->order;

		// Sort actions: special actions first, edit and delete last
		$actions = $this->actions;
		usort($actions, function (Action $a, Action $b): int {
			$standard = ['edit!', 'delete!'];
			$isAStandard = in_array($a->signal, $standard, true);
			$isBStandard = in_array($b->signal, $standard, true);
			if ($isAStandard === $isBStandard) {
				return 0;
			}
			return $isAStandard ? 1 : -1;
		});

		$template->actions = $actions;
		$template->autoHideActions = $this->autoHideActions;
		$template->rowClickAction = $this->rowClickAction;
		$template->primaryKey = $this->primaryKey;
		$template->page = $this->paginator->getPage();
		$template->itemsPerPage = $this->paginator->getItemsPerPage();
		$template->totalItems = $this->paginator->getItemCount();
		$template->filters = $this->filterValues;
		$hasFilters = false;
		foreach ($this->columns as $column) {
			if ($column->filter !== null) {
				$hasFilters = true;
				break;
			}
		}
		$template->hasFilters = $hasFilters;
		$template->filterMode = $this->filterMode;
		$template->filterFormId = ($hasFilters && $this->filterMode === Options::FilterModeInline)
			? $this['filters']->getFormId()
			: '';

		$this['paginator']->setPaginator(
			$this->paginator->getPage(),
			$this->paginator->getItemsPerPage(),
			$this->paginator->getItemCount(),
		);
		$this['paginator']->setSorting($this->column, $this->order);

		$this['pageSize']->setTotalItems($this->totalItems);
		$this['pageSize']->setCurrentPageSize($this->itemsPerPage);

		$template->render();
	}


	/**
	 * Redraws the DataGrid component and its subcomponents in AJAX requests
	 * while preserving filters, sorting, and pagination.
	 * @throws InvalidLinkException
	 */
	public function redrawDataGrid(): void
	{
		if (!$this->getPresenter()->isAjax()) {
			return;
		}

		$this->getPresenter()->payload->url = $this->getPresenter()->link('//this');
		$this->redrawControl('dataGrid');

		foreach ([$this['paginator'], $this['filters'], $this['pageSize']] as $component) {
			$component->redrawControl();
		}
	}
}
