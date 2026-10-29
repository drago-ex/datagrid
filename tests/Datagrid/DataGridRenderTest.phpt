<?php declare(strict_types=1);

/**
 * Test: DataGrid configuration checks and control callbacks
 */

use Dibi\Connection;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Datagrid\Exception\InvalidDataSourceException;
use Drago\Datagrid\Filter\FilterTextControl;
use Drago\Datagrid\Options;
use Drago\Datagrid\PageSize\PageSizeControl;
use Drago\Datagrid\Paginator\PaginatorControl;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/** Records the calls of the callbacks registered as [$recorder, 'record'], which is not a Closure. */
final class CallbackRecorder
{
	/** @var list<list<mixed>> */
	public array $calls = [];


	public function record(mixed ...$args): void
	{
		$this->calls[] = $args;
	}
}


test('Rendering without a data source fails before anything is rendered', function () {
	Assert::exception(
		fn() => (new DataGrid)->render(),
		InvalidDataSourceException::class,
		'Data source is not set.',
	);
});


test('Actions need a primary key', function () {
	$grid = (new DataGrid)
		->setDataSource((new Connection(['driver' => 'dummy']))->select('*')->from('items'));
	$grid->addActionEdit(fn(int $id) => null);

	Assert::exception(
		fn() => $grid->render(),
		InvalidConfigurationException::class,
		'Primary key must be set when using actions.',
	);
});


test('The page size callback may be any callable', function () {
	$recorder = new CallbackRecorder;
	$control = new PageSizeControl;
	$control->onPageChanged([$recorder, 'record']);
	$control->handleSetPageSize('50');

	Assert::same([[Options::DefaultPage, 50]], $recorder->calls);
});


test('The paginator callback may be any callable', function () {
	$recorder = new CallbackRecorder;
	$control = new PaginatorControl;
	$control->onPageChanged([$recorder, 'record']);
	$control->handlePage(3);

	Assert::same([[3]], $recorder->calls);
});


test('The filter reset callback may be any callable', function () {
	$recorder = new CallbackRecorder;
	$control = new FilterTextControl;
	$control->onReset([$recorder, 'record']);
	$control->handleResetFilters();

	Assert::same([[]], $recorder->calls);
});
