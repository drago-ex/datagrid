<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use Dibi\Fluent;


interface Filter
{
	public function apply(Fluent $fluent, string $column, mixed $value): void;
	public function getInputType(): string;
}
