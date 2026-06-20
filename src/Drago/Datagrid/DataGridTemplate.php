<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;

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

	public bool $autoHideActions = false;
	public ?string $rowClickAction = null;

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
