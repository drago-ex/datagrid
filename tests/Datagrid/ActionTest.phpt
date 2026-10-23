<?php

/**
 * Test: DataGrid row actions
 */

declare(strict_types=1);

use Dibi\Connection;
use Drago\Datagrid\Action;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createGrid(): DataGrid
{
	return (new DataGrid)
		->setDataSource((new Connection(['driver' => 'dummy']))->select('*')->from('items'))
		->setPrimaryKey('id');
}


test('The edit and delete shortcuts add actions with default labels and buttons', function () {
	$grid = createGrid();
	$edit = $grid->addActionEdit(fn(int $id) => null);
	$delete = $grid->addActionDelete(fn(int $id) => null, 'Remove');

	Assert::same(['edit', 'Edit', 'ajax btn btn-xs btn-primary'], [$edit->name, $edit->label, $edit->class]);
	Assert::same(['delete', 'Remove', 'ajax btn btn-xs btn-danger'], [$delete->name, $delete->label, $delete->class]);
});


test('An action calls its callback with the row ID', function () {
	$ids = [];
	$action = new Action('approve', 'Approve', function (int $id) use (&$ids): void {
		$ids[] = $id;
	});
	$action->execute(5);

	Assert::same([5], $ids);
});


test('The condition decides whether the row shows the action', function () {
	$action = createGrid()->addAction(
		'approve',
		'Approve',
		fn(int $id) => null,
		condition: fn(array $row): bool => $row['approved'] === 0,
	);

	Assert::true($action->isVisible(['approved' => 0]));
	Assert::false($action->isVisible(['approved' => 1]));
});


test('A hidden action is not shown in any row, whatever its condition says', function () {
	$action = createGrid()->addAction(
		'approve',
		'Approve',
		fn(int $id) => null,
		condition: fn(array $row): bool => true,
	)->setVisible(false);

	Assert::false($action->isVisible());
	Assert::false($action->isVisible(['approved' => 0]));
	Assert::true($action->setVisible()->isVisible(['approved' => 0]));
});


test('Without a row only the visibility decides, the condition is not asked', function () {
	$action = createGrid()->addAction(
		'approve',
		'Approve',
		fn(int $id) => null,
		condition: fn(array $row): bool => throw new LogicException('Must not be called.'),
	);

	Assert::true($action->isVisible());
});


test('The action name is not a signal, so the old "edit!" is refused', function () {
	Assert::exception(
		fn() => createGrid()->addAction('edit!', 'Edit', fn(int $id) => null),
		InvalidConfigurationException::class,
		"Action name 'edit!' may contain only letters, digits, '-' and '_'.",
	);
});


test('An action name can be used only once', function () {
	$grid = createGrid();
	$grid->addActionEdit(fn(int $id) => null);

	Assert::exception(
		fn() => $grid->addAction('edit', 'Edit', fn(int $id) => null),
		InvalidConfigurationException::class,
		"Action 'edit' already exists.",
	);
});


test('The row click must name an added action', function () {
	$grid = createGrid()->setRowClickAction('edit');

	Assert::exception(
		fn() => $grid->render(),
		InvalidConfigurationException::class,
		"Row click action 'edit' is not added by addAction().",
	);
});


test('An unknown action from the URL is ignored', function () {
	$grid = createGrid();
	$grid->addActionEdit(fn(int $id) => throw new LogicException('Must not be called.'));

	Assert::noError(fn() => $grid->handleAction('drop', 1));
});
