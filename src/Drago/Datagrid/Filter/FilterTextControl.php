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

/**
 * DataGrid filter component.
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


	/**
	 * Registers filter change callback.
	 */
	public function onFilterChanged(callable $callback): void
	{
		$this->onFilterChanged = $callback;
	}


	/**
	 * Sets grid columns.
	 */
	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}


	/**
	 * Sets current filter values.
	 */
	public function setValues(array $values): void
	{
		$this->values = $values;
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
						->setDefaultValue($this->values[$name] ?? '');
				}
			}
		}

		$form->addSubmit('submit', 'Filter');
		$form->addSubmit('reset', 'Reset');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			if ($form['reset']->isSubmittedBy()) {
				$form->reset();
				$this->onFilterChanged && ($this->onFilterChanged)([]);
				return;
			}

			$this->onFilterChanged && ($this->onFilterChanged)((array) $values);
		};

		return $form;
	}


	/**
	 * Renders filter component.
	 */
	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/Filter.latte');
		$this->template->render();
	}
}
