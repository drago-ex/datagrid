<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Column;

use Drago\Datagrid\Filter\TextFilter;


/**
 * DataGrid column for displaying text values.
 * Supports optional cell formatting callback.
 */
class ColumnText extends Column
{
	public function setFilterText(): self
	{
		$this->setFilter(new TextFilter);
		return $this;
	}


	/**
	 * Enable or disable natural numeric sorting for this column.
	 */
	public function setNaturalSort(bool $enable = true): static
	{
		parent::setNaturalSort($enable);
		return $this;
	}


	/**
	 * Renders the cell for this column.
	 * Applies optional formatter and escapes HTML characters.
	 */
	public function renderCell(array $row): string
	{
		$value = $row[$this->name] ?? '';

		if ($this->formatter) {
			return (string) ($this->formatter)($value, $row);
		}

		return htmlspecialchars((string) $value);
	}
}