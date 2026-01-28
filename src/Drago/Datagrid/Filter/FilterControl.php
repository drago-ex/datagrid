<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * DataGrid filter component.
 * Dynamically creates a form for all columns with filters.
 */
final class FilterControl extends Control
{
	/** @var callable|null Callback při změně filtru */
	private $onFilterChanged = null;

	/** Pole sloupců s instancí filtru */
	private array $columns = [];

	/** Aktuální hodnoty filtrů */
	private array $values = [];

	public function onFilterChanged(callable $callback): void
	{
		$this->onFilterChanged = $callback;
	}

	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}

	public function setValues(array $values): void
	{
		$this->values = $values;
	}

	protected function createComponentForm(): Form
	{
		$form = new Form;

		foreach ($this->columns as $column) {
			if ($column->filter !== null) {
				$type = $column->filter->getInputType();
				$name = $column->name;

				if ($type === 'text') {
					$form->addText($name, $column->label)
						->setHtmlAttribute('placeholder', $column->label)
						->setDefaultValue($this->values[$name] ?? '')
						->setHtmlAttribute('data-items-filter')
						->setHtmlAttribute('class', 'form-control');
				}

				// Můžeme později přidat i select, date, atd.
			}
		}

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			if ($this->onFilterChanged) {
				($this->onFilterChanged)((array)$values);
			}
		};

		return $form;
	}

	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/Filter.latte');
		$this->template->render();
	}
}
