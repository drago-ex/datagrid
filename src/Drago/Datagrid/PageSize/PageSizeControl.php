<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\PageSize;

use App\Core\Permission\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

final class PageSizeControl extends Control
{
	/** @var callable|null Callback invoked on page change */
	private $onPageChanged = null;

	/** Celkový počet položek v DataGrid */
	private int $totalItems = 0;

	/** Aktuální počet položek na stránku */
	private int $currentPageSize = Options::DefaultItemsPerPage;

	/**
	 * Registrace callbacku při změně počtu položek na stránku
	 */
	public function onPageChanged(callable $callback): void
	{
		$this->onPageChanged = $callback;
	}

	/**
	 * Nastavení celkového počtu položek (DataGrid)
	 */
	public function setTotalItems(int $totalItems): void
	{
		$this->totalItems = $totalItems;
	}

	/**
	 * Nastavení aktuálního počtu položek na stránku
	 */
	public function setCurrentPageSize(int $size): void
	{
		$this->currentPageSize = $size;
	}

	/**
	 * Vytvoření formuláře pro výběr počtu položek
	 */
	protected function createComponentForm(): Form
	{
		$form = new Form;

		$form->addSelect('pageSize', 'Items per page', items: [
			20 => '20',
			50 => '50',
			100 => '100',
			0  => 'All',
		])
			->setDefaultValue($this->currentPageSize)
			->setHtmlAttribute('data-items-page');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$size = (int) $values->pageSize;
			if ($size === 0) {
				$size = $this->totalItems;
			}

			if ($this->onPageChanged) {
				($this->onPageChanged)(Options::DefaultPage, $size);
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
