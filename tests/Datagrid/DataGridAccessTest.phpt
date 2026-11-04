<?php declare(strict_types=1);

/**
 * Test: DataGrid signals marked for drago-ex/permission
 */

use Drago\Datagrid\DataGrid;
use Drago\Permission\Privilege;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/**
 * Presenter with an "articles" control holding a grid with its filters, paginator and page size.
 * @param array<string, mixed> $params parameters of the grid
 */
function grid(string $path, array $params = []): IComponent
{
	$presenter = new class extends Presenter {
	};
	$presenter->setParent(null, 'Test');
	$presenter->injectPrimary(new Request(new UrlScript('http://example.com/')), new Response);

	$articles = new class extends Control {
	};
	$presenter->addComponent($articles, 'articles');
	$articles->addComponent($grid = new DataGrid, 'grid');
	$grid->loadState($params);

	return $presenter->getComponent($path);
}


test('Sorting, filters, paging and the page size need only the page', function () {
	Assert::null(Privilege::forSignal(grid('articles-grid'), 'sort'));
	Assert::null(Privilege::forSignal(grid('articles-grid-filters'), 'resetFilters'));
	Assert::null(Privilege::forSignal(grid('articles-grid-filters'), 'clearFilter'));
	Assert::null(Privilege::forSignal(grid('articles-grid-filters-form'), 'submit'));
	Assert::null(Privilege::forSignal(grid('articles-grid-paginator'), 'page'));
	Assert::null(Privilege::forSignal(grid('articles-grid-pageSize'), 'setPageSize'));
});


test('A row action needs the privilege named like the action', function () {
	Assert::same('edit', Privilege::forSignal(grid('articles-grid', ['name' => 'edit']), 'action'));
	Assert::same('delete', Privilege::forSignal(grid('articles-grid', ['name' => 'delete']), 'action'));
	Assert::same('action', Privilege::forSignal(grid('articles-grid'), 'action'));
});


test('Signals of the control holding the grid need the privilege named like them', function () {
	Assert::same('delete', Privilege::forSignal(grid('articles'), 'delete'));
});
