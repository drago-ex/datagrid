<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use App\Core\Permission\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Tracy\Debugger;

final class PageSizeControl extends Control
{
	/** @var callable|null Callback invoked on page change */
	private $onPageChanged = null;


	/**
	 * Register a callback to be called when the page changes.
	 */
	public function onPageChanged(callable $callback): void
	{
		$this->onPageChanged = $callback;
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->addSelect('pageSize', '', [
			20 => '20',
			50 => '50',
			100 => '100',
			0 => 'All',
		])
			->setDefaultValue(Options::DefaultItemsPerPage)
			->setHtmlAttribute('data-items-page');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			if ($this->onPageChanged) {
				($this->onPageChanged)(Options::DefaultPage, $values->pageSize);
			}
		};

		return $form;
	}


	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/PageSize.latte');
		$this->template->render();
	}
}
