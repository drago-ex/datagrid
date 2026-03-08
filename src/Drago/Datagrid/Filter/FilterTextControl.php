<?php

declare(strict_types=1);

namespace Drago\Datagrid\Filter;

use Closure;
use Drago\Datagrid\Column\Column;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use stdClass;

/**
 * DataGrid filter component.
 *
 * @property-read FilterTextTemplate $template
 */
final class FilterTextControl extends Control
{
	private ?Closure $onFilterChanged = null;
	private ?Closure $onReset = null;

	/** @var Column[] */
	private array $columns = [];

	/** @var array<string, mixed> */
	private array $values = [];

	private bool $hasActiveFilters = false;


	public function onFilterChanged(callable $callback): void
	{
		$this->onFilterChanged = $callback;
	}


	public function onReset(callable $callback): void
	{
		$this->onReset = $callback;
	}


	public function handleResetFilters(): void
	{
		$this->values = [];
		$this->hasActiveFilters = false;

		if ($this->onReset) {
			($this->onReset)();
		}
	}


	/**
	 * @param Column[] $columns
	 */
	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}


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
		$form->setMethod(Form::GET);

		foreach ($this->columns as $column) {
			if ($column->filter !== null) {

				$type = $column->filter->getInputType();
				$name = $column->name;

				if ($type === 'text') {
					$form->addText($name, $column->label)
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('data-items-filter')
						->setHtmlAttribute('placeholder', 'Search...')
						->setHtmlAttribute('autocomplete', 'off');

				} elseif ($type === 'date') {
					$form->addText($name, $column->label)
						->setHtmlType('date')
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('data-items-filter');
				}
			}
		}

		$form->onSuccess[] = function (Form $form, stdClass $values): void {

			$valuesArray = (array) $values;

			$this->setValues($valuesArray);

			if ($this->onFilterChanged) {
				($this->onFilterChanged)($valuesArray);
			}
		};

		return $form;
	}


	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/Filter.latte');
		$this->template->hasActiveFilters = $this->hasActiveFilters;
		$this->template->render();
	}
}
