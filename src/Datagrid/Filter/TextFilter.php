<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Filter;

use Dibi\Fluent;
use function is_scalar;


/** Text-based DataGrid filter. */
class TextFilter implements Filter
{
	/**
	 * Applies text filter condition with proper escaping.
	 * Prevents SQL injection by properly escaping special LIKE characters.
	 */
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if (!is_scalar($value) || $value === '') {
			return;
		}

		// Escape special LIKE characters: %, _
		$escapedValue = str_replace(['%', '_'], ['\%', '\_'], (string) $value);
		$fluent->where('%n LIKE %s ESCAPE %s', $column, '%' . $escapedValue . '%', '\\');
	}


	/** Returns input type identifier. */
	public function getInputType(): string
	{
		return 'text';
	}
}
