<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use Nette\Bridges\ApplicationLatte\Template;

/**
 * Latte template for DataGrid filter component.
 */
class FilterTextTemplate extends Template
{
	public bool $hasActiveFilters = false;
}
