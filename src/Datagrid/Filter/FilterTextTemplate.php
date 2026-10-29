<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Filter;

use Nette\Bridges\ApplicationLatte\Template;


class FilterTextTemplate extends Template
{
	public bool $hasActiveFilters = false;

	/** @var array<string, mixed> */
	public array $filterValues = [];
}
