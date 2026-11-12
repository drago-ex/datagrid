<?php declare(strict_types=1);

/**
 * Test: DataGrid bulk actions, badges and the CSV export
 */

use Dibi\Connection;
use Drago\Datagrid\BulkAction;
use Drago\Datagrid\Column\ColumnDate;
use Drago\Datagrid\Column\ColumnText;
use Drago\Datagrid\CsvExport;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Datagrid\Options;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/**
 * A grid attached to a presenter whose request posted the given fields.
 * @param array<string, mixed> $post
 */
function bulkGrid(array $post = []): DataGrid
{
	$presenter = new class extends Presenter {
	};
	$presenter->setParent(null, 'Test');
	$presenter->injectPrimary(new Request(new UrlScript('http://example.com/'), $post, method: 'POST'), new Response);

	$grid = (new DataGrid)
		->setDataSource((new Connection(['driver' => 'dummy']))->select('*')->from('items'))
		->setPrimaryKey('id');
	$presenter->addComponent($grid, 'grid');

	return $grid;
}


final class CzechTranslator implements Translator
{
	public function translate(string|Stringable $message, mixed ...$parameters): string
	{
		return ['Title' => 'Název', 'Status' => 'Stav', 'Published' => 'Publikováno'][(string) $message] ?? (string) $message;
	}
}


test('The shortcuts add the edit, delete and export bulk actions', function () {
	$grid = bulkGrid();
	$edit = $grid->addBulkActionEdit(fn(int $id) => null);
	$delete = $grid->addBulkActionDelete(fn(array $ids) => null, 'Remove');
	$export = $grid->addBulkActionExport();

	Assert::same(['edit', 'Edit', 'bi bi-pencil', true, false, true], [$edit->name, $edit->label, $edit->icon, $edit->single, $edit->danger, $edit->ajax]);
	Assert::same(['delete', 'Remove', 'bi bi-trash', false, true, true], [$delete->name, $delete->label, $delete->icon, $delete->single, $delete->danger, $delete->ajax]);
	Assert::same(['export', 'Export', 'bi bi-download', false, false, false], [$export->name, $export->label, $export->icon, $export->single, $export->danger, $export->ajax]);
});


test('A bulk action calls its callback with the IDs of the chosen rows', function () {
	$called = [];
	$action = new BulkAction('publish', 'Publish', function (array $ids) use (&$called): void {
		$called[] = $ids;
	});
	$action->execute([3, 5]);

	Assert::same([[3, 5]], $called);
});


test('The signal takes the IDs from the POST field, each once, and drops what is not a positive number', function () {
	$called = [];
	$grid = bulkGrid(['ids' => ['5', '3', '5', '0', '-2', 'x', '1.5', ['7']]]);
	$grid->addBulkAction('publish', 'Publish', function (array $ids) use (&$called): void {
		$called[] = $ids;
	});
	$grid->handleBulk('publish');

	Assert::same([[5, 3]], $called);
});


test('An unknown or hidden bulk action and a signal without rows do not run the callback', function () {
	$fail = fn(array $ids) => throw new LogicException('Must not be called.');

	$grid = bulkGrid(['ids' => ['1']]);
	$grid->addBulkAction('publish', 'Publish', $fail)->setVisible(false);
	Assert::noError(fn() => $grid->handleBulk('publish'));
	Assert::noError(fn() => $grid->handleBulk('drop'));

	$empty = bulkGrid();
	$empty->addBulkAction('publish', 'Publish', $fail);
	Assert::noError(fn() => $empty->handleBulk('publish'));
});


test('An action for one row runs only with one chosen row and gets its ID', function () {
	$called = [];
	$callback = function (int $id) use (&$called): void {
		$called[] = $id;
	};

	$more = bulkGrid(['ids' => ['1', '2']]);
	$more->addBulkActionEdit($callback);
	$more->handleBulk('edit');
	Assert::same([], $called);

	$one = bulkGrid(['ids' => ['2']]);
	$one->addBulkActionEdit($callback);
	$one->handleBulk('edit');
	Assert::same([2], $called);
});


test('A bulk action has a plain name used only once and needs a primary key', function () {
	$grid = bulkGrid();
	$grid->addBulkAction('publish', 'Publish', fn(array $ids) => null);

	Assert::exception(
		fn() => $grid->addBulkAction('publish', 'Publish', fn(array $ids) => null),
		InvalidConfigurationException::class,
		"Bulk action 'publish' already exists.",
	);
	Assert::exception(
		fn() => $grid->addBulkAction('publish!', 'Publish', fn(array $ids) => null),
		InvalidConfigurationException::class,
		"Bulk action name 'publish!' may contain only letters, digits, '-' and '_'.",
	);

	$noKey = (new DataGrid)->setDataSource((new Connection(['driver' => 'dummy']))->select('*')->from('items'));
	$noKey->addBulkAction('publish', 'Publish', fn(array $ids) => null);
	Assert::exception(
		fn() => $noKey->render(),
		InvalidConfigurationException::class,
		'Primary key must be set when using actions.',
	);
});


test('The row click that chooses rows needs a bulk action and its name is not a row action', function () {
	$grid = bulkGrid()->setRowClickAction(Options::RowClickSelect);
	Assert::exception(
		fn() => $grid->render(),
		InvalidConfigurationException::class,
		'The row click chooses rows for bulk actions, add one by addBulkAction().',
	);
	Assert::exception(
		fn() => bulkGrid()->addAction('select', 'Select', fn(int $id) => null),
		InvalidConfigurationException::class,
		"Action name 'select' is reserved for setRowClickAction().",
	);
});


test('Badges show the label of a value in a light color, other values stay texts', function () {
	$column = (new ColumnText('status', 'Status', true, null))
		->setBadges(['published' => ['Published', 'success'], 1 => ['<b>On</b>', 'primary']]);

	Assert::same('<span class="badge badge-subtle theme-success">Published</span>', $column->renderCell(['status' => 'published']));
	Assert::same('<span class="badge badge-subtle theme-primary">&lt;b&gt;On&lt;/b&gt;</span>', $column->renderCell(['status' => 1]));
	Assert::same('archived', $column->renderCell(['status' => 'archived']));
	Assert::same('', $column->renderCell([]));
});


test('Badge labels are translated unless they are data', function () {
	$badges = ['published' => ['Published', 'success']];
	$translated = (new ColumnText('status', 'Status', true, null))->setBadges($badges)->setTranslator(new CzechTranslator);
	$data = (new ColumnText('status', 'Status', true, null))->setBadges($badges, translate: false)->setTranslator(new CzechTranslator);

	Assert::contains('>Publikováno<', $translated->renderCell(['status' => 'published']));
	Assert::contains('>Published<', $data->renderCell(['status' => 'published']));
});


test('The export writes the shown texts of the exportable columns, with translated labels', function () {
	$columns = [
		'title' => new ColumnText('title', 'Title', true, null),
		'status' => (new ColumnText('status', 'Status', true, null))
			->setBadges(['published' => ['Published', 'success']])
			->setTranslator(new CzechTranslator),
		'buttons' => (new ColumnText('id', 'Buttons', true, fn() => Html::el('a')->setText('Open')))->setExportable(false),
		'created' => new ColumnDate('created', 'Created', format: 'j. n. Y'),
	];

	$csv = (new CsvExport($columns, translator: new CzechTranslator))->write([
		['id' => 1, 'title' => 'Tom & "Jerry"; part 1', 'status' => 'published', 'created' => '2026-11-06 10:00:00'],
		['id' => 2, 'title' => '=SUM(A1)', 'status' => 'draft', 'created' => null],
		['id' => 3, 'title' => '-12.5', 'status' => 'draft', 'created' => null],
	]);

	Assert::same(
		"\u{FEFF}Název;Stav;Created\r\n"
		. "\"Tom & \"\"Jerry\"\"; part 1\";Publikováno;\"6. 11. 2026\"\r\n"
		. "'=SUM(A1);draft;\r\n"
		. "-12.5;draft;\r\n",
		$csv,
	);
});


test('The delimiter of the export can be changed', function () {
	$csv = (new CsvExport(['title' => new ColumnText('title', 'Title', true, null)], ','))->write([['title' => 'a,b']]);
	Assert::same("\u{FEFF}Title\r\n\"a,b\"\r\n", $csv);
});


test('A switch shows the state as a checkbox with its label and the link of the change', function () {
	$column = (new ColumnText('status', 'Status', true, null))
		->setSwitch('publish', fn(int $id, bool $on) => null, on: 'published', labels: ['Published', 'Draft'])
		->setSwitchLink(fn(array $row): array => ['/switch?id=' . $row['id'], 'grid-publish-' . $row['id']]);

	Assert::same(
		'<div class="form-check form-switch datagrid-switch">'
		. '<input type="checkbox" role="switch" class="form-check-input" id="grid-publish-5" checked autocomplete="off" data-datagrid-switch="/switch?id=5">'
		. '<label class="form-check-label" for="grid-publish-5">Published</label></div>',
		$column->renderCell(['id' => 5, 'status' => 'published']),
	);
	Assert::contains('>Draft</label>', $column->renderCell(['id' => 6, 'status' => 'draft']));
	Assert::notContains(' checked', $column->renderCell(['id' => 6, 'status' => 'draft']));
	Assert::same('Published', $column->exportCell(['id' => 5, 'status' => 'published']));
});


test('A switch of a true value is on for 1 and true, and a disabled one has no link', function () {
	$column = (new ColumnText('active', 'State', true, null))
		->setSwitch('activate', fn(int $id, bool $on) => null, enabled: false)
		->setSwitchLink(fn(array $row): array => ['/switch', 'x']);

	Assert::contains(' checked', $column->renderCell(['active' => 1]));
	Assert::contains(' checked', $column->renderCell(['active' => true]));
	Assert::notContains(' checked', $column->renderCell(['active' => 0]));
	Assert::contains(' disabled', $column->renderCell(['active' => 1]));
	Assert::notContains('data-datagrid-switch', $column->renderCell(['active' => 1]));
	Assert::false($column->isSwitchEnabled());
});


test('The switch signal calls the callback with the row ID and the posted state', function () {
	$called = [];
	$callback = function (int $id, bool $on) use (&$called): void {
		$called[] = [$id, $on];
	};

	$on = bulkGrid(['on' => '1']);
	$on->addColumnText('status', 'Status')->setSwitch('publish', $callback);
	$on->handleSwitch('publish', 4);

	$off = bulkGrid(['on' => '0']);
	$off->addColumnText('status', 'Status')->setSwitch('publish', $callback);
	$off->handleSwitch('publish', 4);
	$off->handleSwitch('other', 4);
	$off->handleSwitch('publish', 0);

	$disabled = bulkGrid(['on' => '1']);
	$disabled->addColumnText('status', 'Status')->setSwitch('publish', $callback, enabled: false);
	$disabled->handleSwitch('publish', 4);

	// without the posted state nothing is changed
	$none = bulkGrid();
	$none->addColumnText('status', 'Status')->setSwitch('publish', $callback);
	$none->handleSwitch('publish', 4);

	Assert::same([[4, true], [4, false]], $called);
});
