<?php declare(strict_types=1);

namespace Drago\Datagrid\Filter;

use Nette\Bridges\ApplicationLatte\Template;


class FilterTextTemplate extends Template
{
	public bool $hasActiveFilters = false;

	/** @var array<string, mixed> */
	public array $filterValues = [];
}
