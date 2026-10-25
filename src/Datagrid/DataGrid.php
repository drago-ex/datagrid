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
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\Container;
use Nette\Localization\Translator;
use Nette\Utils\Paginator as UtilsPaginator;


/**
 * DataGrid component for displaying tabular data
 * with filtering, sorting, pagination, and row actions.
 *
 * @property-read DataGridTemplate $template
 */
class DataGrid extends Control
{
	#[Persistent] public ?string $column = null;

	#[Persistent] public string $order = Options::OrderAsc;

	#[Persistent] public int $page = Options::DefaultPage;

	#[Persistent] public int $itemsPerPage = Options::DefaultItemsPerPage;

	/** @var array<string, mixed> Persistent current filter values */
	#[Persistent] public array $filterValues = [];

	private bool $autoHideActions = false;
	private ?string $rowClickAction = null;
	private ?Fluent $source = null;
	private ?string $primaryKey = null;

	/** @var array<string, Column> */
	private array $columns = [];

	/** @var array<string, Action> */
	private array $actions = [];

	private string $filterMode = Options::FilterModeTop;
	private UtilsPaginator $paginator;
	private int $totalItems = 0;
	private ?Translator $translator = null;
	private ?string $historyUrl = null;


	public function __construct()
	{
		$this->paginator = new UtilsPaginator;
	}


	public function setAutoHideActions(bool $autoHide = true): self
	{
		$this->autoHideActions = $autoHide;
		return $this;
	}


	/**
	 * A click on the row runs the action with this name; its button is then not rendered.
	 */
	public function setRowClickAction(string $name): self
	{
		$this->rowClickAction = $name;
		return $this;
	}


	public function setTranslator(?Translator $translator): self
	{
		$this->translator = $translator;
		return $this;
	}


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
	 * Adds a row action; actions are rendered in the order they are added.
	 * The grid is not redrawn after the action, the callback calls redrawDataGrid() when it changes data.
	 * @param callable(int): void $callback Receives the row ID
	 * @param callable(array<string, mixed>): bool|null $condition Decides whether the row shows the action
	 * @throws InvalidConfigurationException
	 */
	public function addAction(
		string $name,
		string $label,
		callable $callback,
		?string $class = null,
		?callable $condition = null,
	): Action
	{
		if (!preg_match('#^[a-zA-Z0-9_-]+$#D', $name)) {
			throw new InvalidConfigurationException("Action name '$name' may contain only letters, digits, '-' and '_'.");
		}
		if (isset($this->actions[$name])) {
			throw new InvalidConfigurationException("Action '$name' already exists.");
		}

		$action = new Action($name, $label, $callback, $class);
		if ($condition !== null) {
			$action->setCondition($condition);
		}
		return $this->actions[$name] = $action;
	}


	/**
	 * Adds the 'edit' action with a primary button.
	 * @param callable(int): void $callback Receives the row ID
	 * @throws InvalidConfigurationException
	 */
	public function addActionEdit(callable $callback, string $label = 'Edit'): Action
	{
		return $this->addAction('edit', $label, $callback, 'ajax btn btn-xs btn-primary');
	}


	/**
	 * Adds the 'delete' action with a danger button.
	 * @param callable(int): void $callback Receives the row ID
	 * @throws InvalidConfigurationException
	 */
	public function addActionDelete(callable $callback, string $label = 'Delete'): Action
	{
		return $this->addAction('delete', $label, $callback, 'ajax btn btn-xs btn-danger');
	}


	/**
	 * Sorts by the column; the current page, filters and page size stay in the persistent parameters.
	 */
	public function handleSort(string $column, string $sortOrder = Options::OrderAsc): void
	{
		if (!isset($this->columns[$column]) || !$this->columns[$column]->sortable) {
			return;
		}
		if (!in_array($sortOrder, [Options::OrderAsc, Options::OrderDesc], true)) {
			return;
		}

		$this->column = $column;
		$this->order = $sortOrder;

		$this->redrawDataGrid();
	}


	/**
	 * Executes a row action; the grid state (filters, page, sorting) comes from the persistent
	 * parameters, which every link of the grid carries.
	 */
	public function handleAction(string $name, int $id): void
	{
		$action = $this->actions[$name] ?? null;
		if ($action === null || $id <= 0) {
			return;
		}

		// a hidden action (setVisible) or one its row condition leaves out has no button, so it is not run
		// by a signal sent directly either; the row is loaded only for an action with a condition
		if (!$action->isVisible()) {
			return;
		}

		if ($action->hasCondition()) {
			$row = $this->findRow($id);
			if ($row === null || !$action->isVisible($row)) {
				return;
			}
		}

		// Opening a form or a dialog changes nothing in the grid, so the callback calls
		// redrawDataGrid() itself when the action changes the data.
		$action->execute($id);
	}


	/**
	 * The row of the ID from the data source, for the condition of an action; null when it does not exist.
	 * @return array<string, mixed>|null
	 */
	private function findRow(int $id): ?array
	{
		if ($this->source === null || $this->primaryKey === null) {
			return null;
		}

		$row = (clone $this->source)
			->where('%n = ?', $this->primaryKey, $id)
			->fetch();

		return $row === null ? null : (array) $row;
	}


	/**
	 * Persistent parameters come from the URL, so values the grid does not offer are replaced
	 * with defaults: an unbounded page size could load the whole table.
	 * @param array<string, mixed> $params
	 */
	public function loadState(array $params): void
	{
		parent::loadState($params);

		if (!in_array($this->itemsPerPage, Options::PageSizes, true)) {
			$this->itemsPerPage = Options::DefaultItemsPerPage;
		}
		if ($this->page < 1) {
			$this->page = Options::DefaultPage;
		}
		if (!in_array($this->order, [Options::OrderAsc, Options::OrderDesc], true)) {
			$this->order = Options::OrderAsc;
		}

		$this->filterValues = array_filter($this->filterValues, is_scalar(...));
	}


	/**
	 * Tells whether a signal (as returned by Presenter::getSignal()) only changes what a grid shows:
	 * sorting, the filters with their form, the paginator and the page size. Row actions are not
	 * read-only. Meant for ACL checks such as the isReadOnlySignal() hook of drago-ex/permission.
	 *
	 * The class of the receiver is found without creating any component: the check runs at startup, before
	 * the action, whose state the component factories may need. An attached component gives its class, one not
	 * created yet the return type of its createComponent<Name>() factory; a "sort" signal of another component
	 * stays a write.
	 */
	public static function isReadOnlySignal(Presenter $presenter, string $receiver, string $signal): bool
	{
		$path = explode('-', $receiver);
		$isForm = end($path) === 'form';
		if ($isForm) {
			array_pop($path);
		}

		if ($receiver === '' || ($signal !== 'sort' && !in_array(end($path), ['filters', 'paginator', 'pageSize'], true))) {
			return false;
		}

		$class = self::findComponentClass($presenter, $path);
		if ($class === null) {
			return false;
		}

		if ($isForm) {
			return is_a($class, FilterTextControl::class, true);
		}

		return ($signal === 'sort' && is_a($class, self::class, true))
			|| is_a($class, FilterTextControl::class, true)
			|| is_a($class, PaginatorControl::class, true)
			|| is_a($class, PageSizeControl::class, true);
	}


	/**
	 * Class of the component at the path, without creating it: an attached component gives its class,
	 * one not created yet the declared return type of the factory of its parent; null when unknown.
	 * @param list<string> $path
	 * @return class-string|null
	 */
	private static function findComponentClass(Presenter $presenter, array $path): ?string
	{
		$node = $presenter;
		$class = $presenter::class;
		foreach ($path as $name) {
			$child = $node?->getComponents()[$name] ?? null;
			if ($child !== null) {
				$node = $child instanceof Container ? $child : null;
				$class = $child::class;
				continue;
			}

			$method = 'createComponent' . ucfirst($name);
			if (!method_exists($class, $method)) {
				return null;
			}

			$type = (new \ReflectionMethod($class, $method))->getReturnType();
			if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
				return null;
			}

			/** @var class-string $class */
			$class = $type->getName();
			$node = null;
		}

		return $class;
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
		$data = clone $this->validateConfiguration();

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
	 * Redraws the DataGrid component and its subcomponents in AJAX requests
	 * while preserving filters, sorting, and pagination.
	 * @throws InvalidLinkException
	 */
	public function redrawDataGrid(): void
	{
		if (!$this->getPresenter()->isAjax()) {
			return;
		}

		if ($this->historyUrl !== null) {
			$this->getPresenter()->payload->postGet = true;
			$this->getPresenter()->payload->url = $this->historyUrl;
			$this->historyUrl = null;
		} else {
			$this->getPresenter()->payload->url = $this->getPresenter()->link('//this');
		}

		$this->redrawControl('dataGrid');

		foreach ([$this['paginator'], $this['filters'], $this['pageSize']] as $component) {
			$component->redrawControl();
		}
	}


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
			$this->historyUrl = $this->link('//this', ['filterValues' => []]);
			$this->redrawDataGrid();
		});

		$filter->onClearFilter(function (string $name): void {
			unset($this->filterValues[$name]);
			$this->page = 1;
			$this->historyUrl = $this->link('//this', ['filterValues' => $this->filterValues]);
			$this->redrawDataGrid();
		});

		return $filter;
	}


	protected function createComponentPaginator(): PaginatorControl
	{
		$control = new PaginatorControl;
		$control->setTranslator($this->translator);
		$control->onPageChanged(function (int $page): void {
			$this->page = $page;
			$this->redrawDataGrid();
		});

		if ($this->paginator->getItemCount() > 0) {
			$control->setPaginator($this->paginator->getPage(), $this->paginator->getItemsPerPage(), $this->paginator->getItemCount());
		}

		return $control;
	}


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
	 * Validates configuration before rendering and returns the data source.
	 * @throws InvalidConfigurationException
	 * @throws InvalidDataSourceException
	 */
	private function validateConfiguration(): Fluent
	{
		if ($this->source === null) {
			throw new InvalidDataSourceException('Data source is not set.');
		}
		if ($this->actions !== [] && $this->primaryKey === null) {
			throw new InvalidConfigurationException('Primary key must be set when using actions.');
		}
		if ($this->rowClickAction !== null && !isset($this->actions[$this->rowClickAction])) {
			throw new InvalidConfigurationException("Row click action '{$this->rowClickAction}' is not added by addAction().");
		}

		return $this->source;
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


	private function applySorting(Fluent $data): void
	{
		if ($this->column === null || !isset($this->columns[$this->column])) {
			return;
		}
		$columnObj = $this->columns[$this->column];
		$order = in_array($this->order, [Options::OrderAsc, Options::OrderDesc], true)
			? $this->order
			: Options::OrderAsc;

		$data->removeClause('ORDER BY');
		$columnObj->applySorting($data, $order);
	}


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

		// A hidden action has no button and no row click; the row click action has no button of its own.
		$rowClickAction = $this->rowClickAction !== null && $this->actions[$this->rowClickAction]->isVisible()
			? $this->rowClickAction
			: null;
		$template->actions = array_values(array_filter(
			$this->actions,
			fn(Action $action): bool => $action->name !== $this->rowClickAction && $action->isVisible(),
		));
		$template->autoHideActions = $this->autoHideActions;
		$template->rowClickAction = $rowClickAction;
		$template->rowClick = $rowClickAction !== null ? $this->actions[$rowClickAction] : null;
		$template->primaryKey = $this->primaryKey;
		$template->page = $this->paginator->getPage();
		$template->itemsPerPage = $this->paginator->getItemsPerPage();
		$template->totalItems = $this->totalItems;
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
			$this->totalItems,
		);

		$this['pageSize']->setTotalItems($this->totalItems);
		$this['pageSize']->setCurrentPageSize($this->itemsPerPage);

		$template->render();
	}
}
