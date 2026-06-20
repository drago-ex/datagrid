<?php

/**
 * Drago Extension
 * Package built on the Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\PageSize;

use Closure;
use Drago\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;


/**
 * @property-read PageSizeTemplate $template
 */
final class PageSizeControl extends Control
{
	/** @var Closure(int, int): void|null */
	private ?Closure $onPageChanged = null;

	private int $totalItems = 0;

	private int $currentPageSize = Options::DefaultItemsPerPage;

	private ?Translator $translator = null;


	/** @return array<int, string> */
	private function getPageSizeItems(): array
	{
		return [
			20 => '20',
			50 => '50',
			100 => '100',
			0 => 'All',
		];
	}


	public function setTranslator(?Translator $translator): void
	{
		$this->translator = $translator;
	}


	/**
	 * Registers a callback executed when page size is changed.
	 * @param callable(int, int): void $callback
	 */
	public function onPageChanged(callable $callback): void
	{
		$this->onPageChanged = $callback;
	}


	public function setTotalItems(int $totalItems): void
	{
		$this->totalItems = $totalItems;
	}


	public function setCurrentPageSize(int $size): void
	{
		$this->currentPageSize = $size;
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->setMethod($form::Get);

		$form->addSelect('pageSize', 'Items per page', items: $this->getPageSizeItems())
			->setDefaultValue($this->currentPageSize)
			->setHtmlAttribute('data-items-page');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			if ($this->onPageChanged) {
				($this->onPageChanged)(Options::DefaultPage, (int) $values->pageSize);
			}
		};

		return $form;
	}


	public function handleSetPageSize(string|int $size): void
	{
		$size = (int) $size;
		if ($this->onPageChanged) {
			($this->onPageChanged)(Options::DefaultPage, $size);
		}
	}


	public function render(): void
	{
		$template = $this->template;
		if ($this->translator !== null) {
			$template->setTranslator($this->translator);
		}
		$template->setFile(__DIR__ . '/PageSize.latte');
		$this->template->items = $this->getPageSizeItems();
		$this->template->currentSize = $this->currentPageSize;
		$this->template->totalItems = $this->totalItems;
		$this->template->render();
	}
}
