<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Column;

use Closure;
use Drago\Datagrid\Filter\Filter;


/**
 * Base DataGrid column definition.
 */
abstract class Column
{
	public const string AlignLeft = 'start';
	public const string AlignCenter = 'center';
	public const string AlignRight = 'end';

	private bool $naturalSort = false;
	private string $align = self::AlignLeft;


	public function __construct(
		public readonly string $name,
		public readonly string $label,
		public readonly bool $sortable,
		public readonly ?Closure $formatter,
		public ?Filter $filter = null,
	) {
	}


	public function setFilter(Filter $filter): static
	{
		$this->filter = $filter;
		return $this;
	}


	public function setNaturalSort(bool $enable = true): static
	{
		$this->naturalSort = $enable;
		return $this;
	}


	public function isNaturalSort(): bool
	{
		return $this->naturalSort;
	}


	/**
	 * Sets Bootstrap text alignment for this column.
	 */
	public function setAlign(string $align): static
	{
		if (!in_array($align, [self::AlignLeft, self::AlignCenter, self::AlignRight], true)) {
			throw new \InvalidArgumentException(sprintf('Invalid column alignment "%s".', $align));
		}

		$this->align = $align;
		return $this;
	}


	public function alignLeft(): static
	{
		return $this->setAlign(self::AlignLeft);
	}


	public function alignCenter(): static
	{
		return $this->setAlign(self::AlignCenter);
	}


	public function alignRight(): static
	{
		return $this->setAlign(self::AlignRight);
	}


	public function getAlignClass(): string
	{
		return 'text-' . $this->align;
	}


	/**
	 * Renders a cell value for a given row.
	 * @param array<string, mixed> $row
	 */
	abstract public function renderCell(array $row): string;
}
