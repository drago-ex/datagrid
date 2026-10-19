<?php

/**
 * Test: DataGrid persistent parameters
 */

declare(strict_types=1);

use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Options;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function loadState(array $params): DataGrid
{
	$grid = new DataGrid;
	$grid->loadState($params);
	return $grid;
}


test('Valid parameters from the URL are kept', function () {
	$grid = loadState(['page' => 3, 'itemsPerPage' => 50, 'order' => Options::OrderDesc, 'filterValues' => ['name' => 'John']]);

	Assert::same(3, $grid->page);
	Assert::same(50, $grid->itemsPerPage);
	Assert::same(Options::OrderDesc, $grid->order);
	Assert::same(['name' => 'John'], $grid->getFilterValues());
});


test('"All" is an allowed page size', function () {
	Assert::same(0, loadState(['itemsPerPage' => 0])->itemsPerPage);
});


test('A page size that the grid does not offer falls back to the default', function () {
	Assert::same(Options::DefaultItemsPerPage, loadState(['itemsPerPage' => 1_000_000])->itemsPerPage);
	Assert::same(Options::DefaultItemsPerPage, loadState(['itemsPerPage' => -5])->itemsPerPage);
});


test('An invalid page and order fall back to defaults', function () {
	$grid = loadState(['page' => -2, 'order' => 'DROP TABLE']);
	Assert::same(Options::DefaultPage, $grid->page);
	Assert::same(Options::OrderAsc, $grid->order);
});


test('Filter values that are not scalars are dropped', function () {
	$grid = loadState(['filterValues' => ['name' => 'John', 'created' => ['2024-01-01']]]);
	Assert::same(['name' => 'John'], $grid->getFilterValues());
});
