<?php declare(strict_types=1);

namespace Drago\Datagrid;

use Dibi\Row;
use Drago\Datagrid\Column\Column;
use Nette\Localization\Translator;


/**
 * Writes rows of the grid as CSV: the exportable columns with their labels in the first line and the texts
 * the grid shows in the cells. The file starts with the UTF-8 mark, so a spreadsheet reads the accents.
 */
final readonly class CsvExport
{
	/** @var list<Column> */
	private array $columns;


	/** @param array<string, Column> $columns */
	public function __construct(
		array $columns,
		private string $delimiter = ';',
		private ?Translator $translator = null,
	) {
		$this->columns = array_values(array_filter($columns, fn(Column $column): bool => $column->isExportable()));
	}


	/** @param iterable<array<string, mixed>|Row> $rows */
	public function write(iterable $rows): string
	{
		$stream = fopen('php://memory', 'w+');
		assert($stream !== false);
		fwrite($stream, "\u{FEFF}");

		$this->writeLine($stream, array_map(
			fn(Column $column): string => $this->translator === null
				? $column->label
				: (string) $this->translator->translate($column->label),
			$this->columns,
		));

		foreach ($rows as $row) {
			$row = (array) $row;
			$this->writeLine($stream, array_map(
				fn(Column $column): string => self::escapeFormula($column->exportCell($row)),
				$this->columns,
			));
		}

		rewind($stream);
		return (string) stream_get_contents($stream);
	}


	/**
	 * @param resource $stream
	 * @param list<string> $cells
	 */
	private function writeLine($stream, array $cells): void
	{
		fputcsv($stream, $cells, $this->delimiter, '"', '', "\r\n");
	}


	/** A spreadsheet runs a cell that starts like a formula, so such a text gets an apostrophe before it. */
	private static function escapeFormula(string $value): string
	{
		return $value !== '' && str_contains("=+-@\t\r", $value[0]) && !is_numeric($value)
			? "'" . $value
			: $value;
	}
}
