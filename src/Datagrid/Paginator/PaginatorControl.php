<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Paginator;

use Closure;
use Drago\Datagrid\Options;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Nette\Utils\Paginator as UtilsPaginator;


/**
 * Paginator UI control for DataGrid.
 * Handles page changes, sorting, and rendering of pagination component.
 *
 * @property-read PaginatorTemplate $template
 */
final class PaginatorControl extends Control
{
	private UtilsPaginator $paginator;

	/** @var Closure(int, ?string, ?string): void|null */
	private ?Closure $onPageChanged = null;

	private ?string $column = null;

	private string $order = Options::OrderAsc;

	private ?Translator $translator = null;


	public function __construct()
	{
		$this->paginator = new UtilsPaginator;
	}


	public function setTranslator(?Translator $translator): void
	{
		$this->translator = $translator;
	}


	public function setPaginator(int $page, int $itemsPerPage, int $itemCount): void
	{
		$this->paginator->setPage($page);
		$this->paginator->setItemsPerPage($itemsPerPage);
		$this->paginator->setItemCount($itemCount);
	}


	/**
	 * Registers a callback invoked when the page changes.
	 * @param callable(int, ?string, ?string): void $callback
	 */
	public function onPageChanged(callable $callback): void
	{
		$this->onPageChanged = $callback;
	}


	public function handlePage(int $page, ?string $column = null, ?string $order = null): void
	{
		$this->paginator->setPage($page);

		if ($column !== null) {
			$this->column = $column;
		}
		if ($order !== null) {
			$this->order = $order;
		}

		if ($this->onPageChanged) {
			($this->onPageChanged)($page, $this->column, $this->order);
		}
		$this->redrawControl();
	}


	public function setSorting(?string $column, string $order): void
	{
		$this->column = $column;
		$this->order = $order;
	}


	public function render(): void
	{
		$template = $this->template;
		if ($this->translator !== null) {
			$template->setTranslator($this->translator);
		}
		$template->setFile(__DIR__ . '/Paginator.latte');
		$template->paginator = $this->paginator;
		$template->order = $this->order;
		$template->column = $this->column;
		$template->render();
	}
}
