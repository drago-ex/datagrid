<?php

/**
 * Test: DataGrid columns
 */

declare(strict_types=1);

use Drago\Datagrid\Column\ColumnDate;
use Drago\Datagrid\Column\ColumnText;
use Drago\Datagrid\Filter\DateFilter;
use Drago\Datagrid\Filter\TextFilter;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Text column escapes values and custom formatter output', function () {
	$column = new ColumnText('title', 'Title', true, null);
	Assert::same('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $column->renderCell(['title' => '<script>alert("x")</script>']));

	$formatted = new ColumnText('title', 'Title', true, fn(string $value): string => '<b>' . $value . '</b>');
	Assert::same('&lt;b&gt;Hello&lt;/b&gt;', $formatted->renderCell(['title' => 'Hello']));
	Assert::same('', $column->renderCell([]));
});


test('Text column renders explicit Html objects as markup', function () {
	$column = new ColumnText('title', 'Title', true, fn() => Html::el('strong')->setText('Hello'));
	Assert::same('<strong>Hello</strong>', $column->renderCell(['title' => 'ignored']));
});


test('Date column formats valid values and safely handles missing or invalid values', function () {
	$column = new ColumnDate('created', 'Created', true);
	Assert::same('2024-02-03', $column->renderCell(['created' => '2024-02-03 12:00:00']));
	Assert::same('', $column->renderCell([]));
	Assert::same('', $column->renderCell(['created' => 'not a date']));
});


test('Columns expose alignment and filter configuration', function () {
	$text = new ColumnText('title', 'Title', true, null);
	Assert::same('text-start', $text->getAlignClass());
	Assert::same('text-center', $text->alignCenter()->getAlignClass());
	Assert::same('text-end', $text->alignRight()->getAlignClass());
	Assert::true($text->setNaturalSort()->isNaturalSort());
	Assert::type(TextFilter::class, $text->setFilterText()->filter);
	Assert::type(DateFilter::class, (new ColumnDate('created', 'Created'))->setFilterDate()->filter);
	Assert::exception(fn() => $text->setAlign('invalid'), InvalidArgumentException::class);
});


test('A column orders by its value', function () {
	$fluent = (new Dibi\Connection(['driver' => 'dummy']))->select('*')->from('items');
	(new ColumnText('title', 'Title', true, null))->applySorting($fluent, 'DESC');
	Assert::contains('ORDER BY [title] DESC', preg_replace('~\s+~', ' ', (string) $fluent));
});


test('The natural sort orders by the text before the number, the number and the whole value', function () {
	$fluent = (new Dibi\Connection(['driver' => 'dummy']))->select('*')->from('items');
	(new ColumnText('title', 'Title', true, null))->setNaturalSort()->applySorting($fluent, 'ASC');
	Assert::contains(
		"ORDER BY REGEXP_SUBSTR([title], '^[^0-9]*') ASC , CAST(REGEXP_SUBSTR([title], '[0-9]+') AS UNSIGNED) ASC , [title] ASC",
		preg_replace('~\s+~', ' ', (string) $fluent),
	);
});
