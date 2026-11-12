<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid;

use Drago\Datagrid\Column\Column;
use Nette\Bridges\ApplicationLatte\Template;


class DataGridTemplate extends Template
{
	public DataGrid $control;

	/** @var list<array<string, mixed>> */
	public array $rows = [];

	/** @var array<string, Column> */
	public array $columns = [];

	public ?string $columnName = null;

	public string $order = 'ASC';

	/** @var list<Action> */
	public array $actions = [];

	/** @var list<BulkAction> Bulk actions the user may run */
	public array $bulkActions = [];

	/** A click on a row chooses it for the bulk actions. */
	public bool $rowClickSelect = false;

	/** The button that exports all rows of the current filters. */
	public bool $exportButton = false;

	public bool $autoHideActions = false;
	public ?string $rowClickAction = null;

	/** The row click action, whose row condition decides which rows are clickable. */
	public ?Action $rowClick = null;

	public ?string $primaryKey = null;

	public int $page = Options::DefaultPage;

	public int $itemsPerPage = Options::DefaultItemsPerPage;

	public int $totalItems = 0;

	/** @var array<string, mixed> */
	public array $filters = [];

	public bool $hasFilters = false;
	public string $filterMode = Options::FilterModeTop;
	public string $filterFormId = '';
}
