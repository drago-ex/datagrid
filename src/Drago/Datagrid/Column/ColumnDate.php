<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Column;

use App\Core\Permission\Datagrid\Options;
use Closure;


/**
 * DataGrid column for displaying date values.
 * Supports custom formatting and optional cell formatting callback.
 */
class ColumnDate extends Column
{
	/**
	 * @param string $name Column key
	 * @param string $label Column label
	 * @param bool $sortable Whether column is sortable
	 * @param string $format Date format
	 * @param Closure|null $formatter Optional callback to format cell value
	 */
	public function __construct(
		string $name,
		string $label,
		bool $sortable = true,
		public readonly string $format = Options::DateFormat,
		?Closure $formatter = null,
	) {
		parent::__construct($name, $label, $sortable, $formatter);
	}


	/**
	 * Renders the cell for this column.
	 * Returns formatted date or empty string if value is null/invalid.
	 */
	public function renderCell(array $row): string
	{
		$value = $row[$this->name] ?? null;
		if ($value === null) {
			return '';
		}

		$timestamp = is_numeric($value) ? (int)$value : strtotime((string)$value);
		if (!$timestamp) {
			return '';
		}

		$formatted = date($this->format, $timestamp);

		if ($this->formatter !== null) {
			$formatted = ($this->formatter)($formatted, $row);
		}

		return $formatted;
	}
}
