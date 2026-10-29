<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Filter;

use Dibi\Fluent;
use function array_key_exists, is_scalar;


/**
 * Select/dropdown DataGrid filter.
 */
class SelectFilter implements Filter
{
	/** @var array<int|string, string> */
	public array $items;

	/** Whether the option labels are translated; data (e.g. names from the database) are not. */
	public bool $translate;


	/**
	 * @param array<int|string, string> $items Dropdown options
	 */
	public function __construct(array $items, bool $translate = true)
	{
		$this->items = $items;
		$this->translate = $translate;
	}


	/**
	 * Applies exact-match filter condition. Only configured options are accepted, because values come from the URL.
	 */
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if (!is_scalar($value) || $value === '' || !array_key_exists((string) $value, $this->items)) {
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
