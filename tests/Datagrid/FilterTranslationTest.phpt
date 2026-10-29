<?php declare(strict_types=1);

/**
 * Test: DataGrid select filter and the translator
 */

use Drago\Datagrid\Column\ColumnText;
use Drago\Datagrid\Filter\FilterTextControl;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\SelectBox;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nette\Localization\Translator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/** Translates by a dictionary and remembers the messages it was asked for. */
final class RecordingTranslator implements Translator
{
	/** @var list<string> */
	public array $asked = [];


	public function translate(string|Stringable $message, mixed ...$parameters): string
	{
		$this->asked[] = (string) $message;
		return ['Category' => 'Kategorie', 'All' => 'Vše', 'Draft' => 'Koncept'][(string) $message] ?? (string) $message;
	}
}


function filterSelect(ColumnText $column, Translator $translator): SelectBox
{
	$control = new FilterTextControl;
	$control->setTranslator($translator);
	$control->setColumns([$column->name => $column]);

	$presenter = new class extends Presenter {
	};
	$presenter->setParent(null, 'Test');
	$presenter->injectPrimary(new Request(new UrlScript('http://example.com/')), new Response);
	$presenter->addComponent($control, 'filters');

	$select = $control['form'][$column->name];
	assert($select instanceof SelectBox);
	return $select;
}


test('The page size "All" is translated by the control, the numbers stay', function () {
	$control = new Drago\Datagrid\PageSize\PageSizeControl;
	$control->setTranslator(new class implements Nette\Localization\Translator {
		public function translate(string|Stringable $message, mixed ...$parameters): string
		{
			return $message === 'All' ? 'Vše' : (string) $message;
		}
	});
	$items = (fn() => $this->getPageSizeItems())->call($control);
	Assert::same('Vše', $items[0]);
	Assert::same('20', $items[20]);
	Assert::same('All', (fn() => $this->getPageSizeItems())->call(new Drago\Datagrid\PageSize\PageSizeControl)[0]);
});


test('Items of a select filter are translated by default', function () {
	$translator = new RecordingTranslator;
	$select = filterSelect((new ColumnText('status', 'Status', true, null))->setFilterSelect(['draft' => 'Draft']), $translator);

	Assert::contains('>Koncept</option>', (string) $select->getControl());
});


test('Items marked as data are not sent to the translator, the caption and the prompt still are', function () {
	$translator = new RecordingTranslator;
	$column = (new ColumnText('category_id', 'Category', true, null))->setFilterSelect([1 => 'News', 2 => 'Draft'], translate: false);
	$select = filterSelect($column, $translator);
	$html = (string) $select->getControl() . $select->getLabel();

	Assert::contains('>Vše</option>', $html);
	Assert::contains('>Draft</option>', $html);
	Assert::contains('Kategorie', $html);
	Assert::notContains('News', $translator->asked);
	Assert::notContains('Draft', $translator->asked);
});
