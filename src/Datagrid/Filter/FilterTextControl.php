<?php

declare(strict_types=1);

namespace Drago\Datagrid\Filter;

use Closure;
use Drago\Datagrid\Column\Column;
use Drago\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use stdClass;


/**
 * DataGrid filter component.
 *
 * @property-read FilterTextTemplate $template
 */
final class FilterTextControl extends Control
{
	/** @var Closure(array<string, mixed>): void|null */
	private ?Closure $onFilterChanged = null;

	/** @var Closure(): void|null */
	private ?Closure $onReset = null;

	/** @var Closure(string): void|null */
	private ?Closure $onClearFilter = null;

	private string $filterMode = Options::FilterModeTop;

	/** @var array<string, Column> */
	private array $columns = [];

	/** @var array<string, mixed> */
	private array $values = [];

	private bool $hasActiveFilters = false;
	private ?Translator $translator = null;


	public function setTranslator(?Translator $translator): void
	{
		$this->translator = $translator;
	}


	/**
	 * @param callable(array<string, mixed>): void $callback
	 */
	public function onFilterChanged(callable $callback): void
	{
		$this->onFilterChanged = $callback;
	}


	public function setFilterMode(string $mode): void
	{
		$this->filterMode = $mode;
	}


	public function getFormId(): string
	{
		$path = $this->lookupPath('Nette\Application\UI\Presenter');
		return 'dg-filter-' . str_replace(['-', ':'], '_', $path);
	}


	/**
	 * @param callable(): void $callback
	 */
	public function onReset(callable $callback): void
	{
		$this->onReset = $callback;
	}


	/**
	 * @param callable(string): void $callback
	 */
	public function onClearFilter(callable $callback): void
	{
		$this->onClearFilter = $callback;
	}


	public function handleResetFilters(): void
	{
		$this->values = [];
		$this->hasActiveFilters = false;

		if ($this->onReset) {
			($this->onReset)();
		}
	}


	public function handleClearFilter(string $name): void
	{
		if (!isset($this->columns[$name]) || $this->columns[$name]->filter === null) {
			return;
		}

		unset($this->values[$name]);
		$this->setValues($this->values);

		if ($this->onClearFilter) {
			($this->onClearFilter)($name);
		}
	}


	/**
	 * @param array<string, Column> $columns
	 */
	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}


	/**
	 * @param array<string, mixed> $values
	 */
	public function setValues(array $values): void
	{
		$this->values = $values;
		$this->hasActiveFilters = false;

		foreach ($values as $value) {
			if ($value !== null && $value !== '') {
				$this->hasActiveFilters = true;
				break;
			}
		}
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->setMethod($form::Get);
		$form->setTranslator($this->translator);

		foreach ($this->columns as $column) {
			if ($column->filter !== null) {

				$type = $column->filter->getInputType();
				$name = $column->name;

				if ($type === 'text') {
					$form->addText($name, $column->label)
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('class', 'form-control form-control-sm datagrid-control')
						->setHtmlAttribute('data-items-filter')
						->setHtmlAttribute('placeholder', 'Search...')
						->setHtmlAttribute('autocomplete', 'off');

				} elseif ($type === 'select') {
					$items = $column->filter instanceof SelectFilter ? $column->filter->items : [];
					$form->addSelect($name, $column->label, $items)
						->setPrompt('All')
						->setDefaultValue($this->getSelectDefaultValue($name, $items))
						->setHtmlAttribute('class', 'form-select form-select-sm datagrid-control')
						->setHtmlAttribute('data-items-filter');

				} elseif ($type === 'date') {
					$form->addText($name, $column->label)
						->setHtmlType('date')
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('class', 'form-control form-control-sm datagrid-control')
						->setHtmlAttribute('data-items-filter');
				}
			}
		}

		$form->onSuccess[] = function (Form $form, stdClass $values): void {
			/** @var array<string, mixed> $valuesArray */
			$valuesArray = (array) $values;

			// Trim all string values
			$valuesArray = array_map(fn($v) => is_string($v) ? trim($v) : $v, $valuesArray);

			// Remove empty values to keep persistent parameters and URL clean
			$valuesArray = array_filter($valuesArray, fn($v) => $v !== null && $v !== '');

			$this->setValues($valuesArray);

			if ($this->onFilterChanged) {
				($this->onFilterChanged)($valuesArray);
			}
		};

		return $form;
	}


	/**
	 * @param array<int|string, string> $items
	 */
	private function getSelectDefaultValue(string $name, array $items): int|string|null
	{
		$value = $this->values[$name] ?? null;

		if ($value === null || $value === '') {
			return null;
		}

		if ((is_int($value) || is_string($value)) && array_key_exists($value, $items)) {
			return $value;
		}

		return null;
	}


	public function render(): void
	{
		$template = $this->template;
		if ($this->translator !== null) {
			$template->setTranslator($this->translator);
		}

		if ($this->filterMode === Options::FilterModeInline) {
			$this['form']->getElementPrototype()->id = $this->getFormId();
		}

		$templateFile = $this->filterMode === Options::FilterModeInline
			? __DIR__ . '/FilterInline.latte'
			: __DIR__ . '/Filter.latte';
		$this->template->setFile($templateFile);
		$this->template->hasActiveFilters = $this->hasActiveFilters;
		$this->template->filterValues = $this->values;
		$this->template->render();
	}
}
