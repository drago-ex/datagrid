<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Paginator;

use Closure;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Nette\Utils\Paginator as UtilsPaginator;


/**
 * Paginator UI control for DataGrid.
 * Handles page changes and renders the pagination.
 *
 * @property-read PaginatorTemplate $template
 */
final class PaginatorControl extends Control
{
	private UtilsPaginator $paginator;

	/** @var Closure(int): void|null */
	private ?Closure $onPageChanged = null;

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
	 * @param callable(int): void $callback
	 */
	public function onPageChanged(callable $callback): void
	{
		$this->onPageChanged = $callback(...);
	}


	/**
	 * Sorting and filters are persistent parameters of the grid, so the links keep them.
	 */
	public function handlePage(int $page): void
	{
		$this->paginator->setPage($page);

		if ($this->onPageChanged) {
			($this->onPageChanged)($page);
		}
		$this->redrawControl();
	}


	public function render(): void
	{
		$template = $this->template;
		if ($this->translator !== null) {
			$template->setTranslator($this->translator);
		}
		$template->setFile(__DIR__ . '/Paginator.latte');
		$template->paginator = $this->paginator;
		$template->render();
	}
}
