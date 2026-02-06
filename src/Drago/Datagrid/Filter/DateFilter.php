<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Filter;

use Dibi\Fluent;


/**
 * Date-based DataGrid filter.
 * Supports filtering by date range or specific date.
 */
class DateFilter implements Filter
{
	/**
	 * Applies date filter condition.
	 * Supports format: YYYY-MM-DD or range: YYYY-MM-DD|YYYY-MM-DD
	 */
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if ($value === null || $value === '') {
			return;
		}

		$value = (string) $value;

		// Check if it's a date range (format: YYYY-MM-DD|YYYY-MM-DD)
		if (str_contains($value, '|')) {
			[$fromDate, $toDate] = explode('|', $value, 2);
			$fromDate = trim($fromDate);
			$toDate = trim($toDate);

			if ($fromDate !== '' && $toDate !== '') {
				$fluent->where('%n BETWEEN %s AND %s', $column, $fromDate, $toDate);
			} elseif ($fromDate !== '') {
				$fluent->where('%n >= %s', $column, $fromDate);
			} elseif ($toDate !== '') {
				$fluent->where('%n <= %s', $column, $toDate);
			}
		} else {
			// Single date
			$fluent->where('DATE(%n) = %s', $column, $value);
		}
	}


	/**
	 * Returns input type identifier.
	 */
	public function getInputType(): string
	{
		return 'date';
	}
}
