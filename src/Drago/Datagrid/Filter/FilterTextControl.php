<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use App\Core\Permission\Datagrid\Column\Column;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use stdClass;

/**
 * DataGrid filter component.
 *
 * @property-read FilterTextTemplate $template
 */
final class FilterTextControl extends Control
{
	/** @var callable|null Invoked when filter values change */
	private $onFilterChanged = null;

	/** @var Column[] */
	private array $columns = [];

	/** @var array<string, mixed> Current filter values */
	private array $values = [];

	/** Whether any filter is currently active */
	private bool $hasActiveFilters = false;

	/**
	 * Registers filter change callback.
	 */
	public function onFilterChanged(callable $callback): void
	{
		$this->onFilterChanged = $callback;
	}

	/**
	 * Sets grid columns.
	 *
	 * @param Column[] $columns
	 */
	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}

	/**
	 * Sets current filter values and detects active filters.
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

	/**
	 * Builds filter form from column definitions.
	 */
	protected function createComponentForm(): Form
	{
		$form = new Form;

		foreach ($this->columns as $column) {
			if ($column->filter !== null) {
				$type = $column->filter->getInputType();
				$name = $column->name;

				if ($type === 'text') {
					$form->addText($name, $column->label)
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('data-items-filter')
						->setHtmlAttribute('placeholder', 'Search...');
				}
			}
		}

		$form->addSubmit('reset', 'Reset');
		$form->onSuccess[] = function (Form $form, stdClass $values): void {
			$resetButton = $form['reset'];
			if ($resetButton instanceof SubmitButton) {
				if ($resetButton->isSubmittedBy()) {
					$form->reset();
					$this->values = [];
					$this->hasActiveFilters = false;
					if ($this->onFilterChanged) {
						($this->onFilterChanged)([]);
					}
					return;
				}
			}

			// Apply filters
			$this->setValues((array) $values);

			if ($this->onFilterChanged) {
				($this->onFilterChanged)((array) $values);
			}
		};

		return $form;
	}

	/**
	 * Renders filter component.
	 */
	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/Filter.latte');
		$this->template->hasActiveFilters = $this->hasActiveFilters;
		$this->template->render();
	}
}
