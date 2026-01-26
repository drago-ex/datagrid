<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Column;

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
	) {
	}


	abstract public function renderCell(array $row): string;
}
