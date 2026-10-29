<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Filter;

use DateTimeImmutable;
use Dibi\Fluent;
use function is_string;


/**
 * Date-based DataGrid filter.
 * Supports filtering by a specific date (YYYY-MM-DD) or a range (YYYY-MM-DD|YYYY-MM-DD, either side optional).
 * Values come from the URL, so invalid ones are ignored.
 */
class DateFilter implements Filter
{
	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if (!is_string($value) || $value === '') {
			return;
		}

		if (!str_contains($value, '|')) {
			if ($this->parseDate($value) !== null) {
				$fluent->where('DATE(%n) = %s', $column, $value);
			}
			return;
		}

		[$fromDate, $toDate] = array_map('trim', explode('|', $value, 2));
		$from = $fromDate === '' ? null : $this->parseDate($fromDate);
		$to = $toDate === '' ? null : $this->parseDate($toDate);
		if (($fromDate !== '' && $from === null) || ($toDate !== '' && $to === null)) {
			return;
		}

		// Comparing with the start of the next day keeps the whole last day for DATETIME columns too.
		if ($from !== null) {
			$fluent->where('%n >= %s', $column, $from->format('Y-m-d'));
		}
		if ($to !== null) {
			$fluent->where('%n < %s', $column, $to->modify('+1 day')->format('Y-m-d'));
		}
	}


	/**
	 * Returns input type identifier.
	 */
	public function getInputType(): string
	{
		return 'date';
	}


	private function parseDate(string $date): ?DateTimeImmutable
	{
		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
			return null;
		}

		return new DateTimeImmutable($date);
	}
}
