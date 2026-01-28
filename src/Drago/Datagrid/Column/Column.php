<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Column;

use App\Core\Permission\Datagrid\Filter\Filter;
use Closure;


/**
 * Abstract base class for a DataGrid column.
 * Defines column properties and requires a renderCell() method.
 */
abstract class Column
{
	public function __construct(
		public readonly string $name,
		public readonly string $label,
		public readonly bool $sortable,
		public readonly ?Closure $formatter,
		public ?Filter $filter = null,
	) {
	}


	public function setFilter(Filter $filter): static
	{
		$this->filter = $filter;
		return $this;
	}


	abstract public function renderCell(array $row): string;
}
