<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Paginator;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Paginator;


/** Template for PaginatorControl. */
class PaginatorTemplate extends Template
{
	public Paginator $paginator;
}
