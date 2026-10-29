<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on the Nette Framework
 */

namespace Drago\Datagrid\PageSize;

use Nette\Bridges\ApplicationLatte\Template;


class PageSizeTemplate extends Template
{
	/** @var array<int, string> */
	public array $items;

	public int $currentSize;

	public int $totalItems;
}
