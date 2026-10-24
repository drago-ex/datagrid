<?php

/**
 * Test: DataGrid persistent parameters
 */

declare(strict_types=1);

use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
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


/** Presenter with an "articles" control holding a grid, a "menu" control and a "paginator" form. */
function presenterTree(): Presenter
{
	$articles = new class extends Control {
	};
	$articles->addComponent(new DataGrid, 'grid');
	$articles->addComponent(new Form, 'delete');

	$presenter = new class extends Presenter {
	};
	$presenter->setParent(null, 'Test');
	$presenter->injectPrimary(new Request(new UrlScript('http://example.com/')), new Response);
	$presenter->addComponent($articles, 'articles');
	$presenter->addComponent(new class extends Control {
	}, 'menu');
	$presenter->addComponent(new Form, 'paginator');
	return $presenter;
}


test('Sorting, filters, paging and the page size are read-only signals, row actions are not', function () {
	$presenter = presenterTree();
	foreach ([
		['articles-grid', 'sort'],
		['articles-grid-filters', 'resetFilters'],
		['articles-grid-filters', 'clearFilter'],
		['articles-grid-filters-form', 'submit'],
		['articles-grid-paginator', 'page'],
		['articles-grid-pageSize', 'setPageSize'],
	] as [$receiver, $signal]) {
		Assert::true(DataGrid::isReadOnlySignal($presenter, $receiver, $signal), "$receiver:$signal");
	}

	Assert::false(DataGrid::isReadOnlySignal($presenter, 'articles-grid', 'action'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'articles-delete', 'submit'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'articles', 'delete'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, '', 'sort'));
});


test('Signals of other components named like the grid ones are not read-only', function () {
	$presenter = presenterTree();
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'menu', 'sort'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'paginator', 'page'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'unknown', 'sort'));
	Assert::false(DataGrid::isReadOnlySignal($presenter, 'unknown-filters-form', 'submit'));
});
