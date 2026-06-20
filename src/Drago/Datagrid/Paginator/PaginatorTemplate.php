<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid\Paginator;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Paginator;


/**
 * Template for PaginatorControl.
 * Holds paginator state and current sorting for rendering.
 */
class PaginatorTemplate extends Template
{
	public Paginator $paginator;

	public ?string $column = null;

	public string $order = 'ASC';
}
