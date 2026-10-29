<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Column;

use Closure;
use Dibi\Fluent;
use Drago\Datagrid\Filter\Filter;
use function sprintf;


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
	 * Orders the query by this column. The natural sort orders by the text before the first
	 * number, then by the number and then by the whole value, so 'Article 4' comes before
	 * 'Article 40' and values without a number keep their alphabetical place.
	 * REGEXP_SUBSTR() needs MySQL 8 or MariaDB 10.0.5+.
	 */
	public function applySorting(Fluent $data, string $order): void
	{
		if ($this->naturalSort) {
			$data->orderBy("REGEXP_SUBSTR(%n, '^[^0-9]*') {$order}", $this->name)
				->orderBy("CAST(REGEXP_SUBSTR(%n, '[0-9]+') AS UNSIGNED) {$order}", $this->name);
		}

		$data->orderBy("%n {$order}", $this->name);
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
