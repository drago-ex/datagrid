<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Column;


/**
 * DataGrid column for displaying text values.
 * Supports optional cell formatting callback.
 */
class ColumnText extends Column
{
	/**
	 * Renders the cell for this column.
	 * Applies optional formatter and escapes HTML characters.
	 */
	public function renderCell(array $row): string
	{
		$value = $row[$this->name] ?? '';

		if ($this->formatter !== null) {
			$value = ($this->formatter)($value, $row);
		}

		return htmlspecialchars((string) $value, ENT_QUOTES);
	}
}
