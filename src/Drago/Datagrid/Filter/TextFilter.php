<?php

declare(strict_types=1);

namespace App\Core\Permission\Datagrid\Filter;

use Dibi\Fluent;


class TextFilter implements Filter
{

	public function apply(Fluent $fluent, string $column, mixed $value): void
	{
		if ($value === null || $value === '') {
			return;
		}

		$fluent->where('%n LIKE %~like~', $column, $value);
	}


	public function getInputType(): string
	{
		return 'text';
	}
}
