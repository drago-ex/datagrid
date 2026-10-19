<?php

/**
 * Test: DataGrid filters
 */

declare(strict_types=1);

use Dibi\Connection;
use Dibi\Fluent;
use Drago\Datagrid\Filter\DateFilter;
use Drago\Datagrid\Filter\SelectFilter;
use Drago\Datagrid\Filter\TextFilter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/** Builds the SQL of a query without a database server. */
function query(callable $apply): string
{
	$fluent = (new Connection(['driver' => 'dummy']))->select('*')->from('items');
	$apply($fluent);
	return preg_replace('~\s+~', ' ', (string) $fluent);
}


test('The text filter searches with escaped LIKE wildcards', function () {
	$sql = query(fn(Fluent $f) => (new TextFilter)->apply($f, 'name', '50%_off'));
	Assert::contains("[name] LIKE '%50\\%\\_off%' ESCAPE '\\'", $sql);
});


test('A single date matches the whole day', function () {
	$sql = query(fn(Fluent $f) => (new DateFilter)->apply($f, 'created', '2024-02-03'));
	Assert::contains("DATE([created]) = '2024-02-03'", $sql);
});


test('A date range includes the whole last day', function () {
	$sql = query(fn(Fluent $f) => (new DateFilter)->apply($f, 'created', '2024-02-01|2024-02-03'));
	Assert::contains("[created] >= '2024-02-01'", $sql);
	Assert::contains("[created] < '2024-02-04'", $sql);

	$sql = query(fn(Fluent $f) => (new DateFilter)->apply($f, 'created', '|2024-02-03'));
	Assert::contains("[created] < '2024-02-04'", $sql);
	Assert::notContains('>=', $sql);
});


test('Invalid filter values from the URL are ignored instead of failing', function () {
	$plain = query(fn(Fluent $f) => null);

	foreach (['not a date', '2024-13-45', '2024-02-01|garbage', ['2024-02-01']] as $value) {
		Assert::same($plain, query(fn(Fluent $f) => (new DateFilter)->apply($f, 'created', $value)));
	}

	Assert::same($plain, query(fn(Fluent $f) => (new TextFilter)->apply($f, 'name', ['a', 'b'])));
	Assert::same($plain, query(fn(Fluent $f) => (new SelectFilter(['a' => 'A']))->apply($f, 'status', ['a'])));
});


test('The select filter accepts only configured options', function () {
	$filter = new SelectFilter(['active' => 'Active', 1 => 'One']);

	Assert::contains("[status] = 'active'", query(fn(Fluent $f) => $filter->apply($f, 'status', 'active')));
	Assert::contains("[status] = '1'", query(fn(Fluent $f) => $filter->apply($f, 'status', '1')));
	Assert::notContains('WHERE', query(fn(Fluent $f) => $filter->apply($f, 'status', 'deleted')));
});
