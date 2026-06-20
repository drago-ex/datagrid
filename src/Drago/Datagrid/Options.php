<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;


/**
 * DataGrid configuration options.
 * Stores default values for pagination, sorting, and date formatting.
 */
class Options
{
	public const string DateFormat = 'Y-m-d';

	public const string OrderAsc = 'ASC';
	public const string OrderDesc = 'DESC';

	public const int DefaultPage = 1;
	public const int DefaultItemsPerPage = 20;

	public const string FilterModeTop = 'top';
	public const string FilterModeInline = 'inline';
}
