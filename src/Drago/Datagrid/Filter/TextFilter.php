<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use Dibi\Fluent;

/**
 * Text-based DataGrid filter.
 */
class TextFilter implements Filter
{
	/**
	 * Applies text filter condition.
	 */
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if ($value === null || $value === '') {
			return;
		}

		$fluent->where('%n LIKE %~like~', $column, $value);
	}


	/**
	 * Returns input type identifier.
	 */
	public function getInputType(): string
	{
		return 'text';
	}
}
