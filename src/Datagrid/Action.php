<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;

use Closure;


/**
 * Row action. Its link always leads to the grid's action! signal, which calls the callback
 * with the row ID; the name only identifies the action, it is not a signal of its own.
 */
class Action
{
	/** @var Closure(int): void */
	private Closure $callback;

	/** @var (Closure(array<string, mixed>): bool)|null */
	private ?Closure $condition = null;


	/**
	 * @param string $name Action name, used in the link and in setRowClickAction()
	 * @param string $label Action label displayed in the UI
	 * @param callable(int): void $callback Receives the row ID
	 * @param string|null $class CSS class of the link, default 'ajax text-decoration-none'
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $label,
		callable $callback,
		public readonly ?string $class = null,
	) {
		$this->callback = $callback(...);
	}


	/**
	 * Sets a condition callback that decides whether the action is visible for a given row.
	 * @param callable(array<string, mixed>): bool $condition
	 * @return $this
	 */
	public function setCondition(callable $condition): self
	{
		$this->condition = $condition(...);
		return $this;
	}


	/**
	 * Evaluates whether this action should be visible for the given row.
	 * @param array<string, mixed> $row
	 */
	public function isVisible(array $row): bool
	{
		return $this->condition === null || ($this->condition)($row);
	}


	/**
	 * Executes the action for the row with the given ID.
	 */
	public function execute(int $id): void
	{
		($this->callback)($id);
	}
}
