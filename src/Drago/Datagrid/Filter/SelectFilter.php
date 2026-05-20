<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Filter;

use Dibi\Fluent;


/**
 * Select/dropdown DataGrid filter.
 */
class SelectFilter implements Filter
{
	/** @var array<int|string, string> */
	public array $items;


	/**
	 * @param array<int|string, string> $items Dropdown options
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}


	/**
	 * Applies exact-match filter condition.
	 */
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if ($value === null || $value === '') {
			return;
		}

		$fluent->where('%n = %s', $column, $value);
	}


	/**
	 * Returns input type identifier.
	 */
	public function getInputType(): string
	{
		return 'select';
	}
}
