<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

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

	private bool $visible = true;


	/** @param callable(int): void $callback Receives the row ID */
	public function __construct(
		/** @var string Action name, used in the link and in setRowClickAction() */
		public readonly string $name,
		/** @var string Action label displayed in the UI */
		public readonly string $label,
		callable $callback,
		/** @var string|null CSS class of the link, default 'ajax text-decoration-none' */
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
	 * Shows or hides the action in all rows, e.g. by the user's permission. It only decides
	 * whether the button is rendered; the signal itself is guarded by the presenter's authorization.
	 * @return $this
	 */
	public function setVisible(bool $visible = true): self
	{
		$this->visible = $visible;
		return $this;
	}


	/** The action is shown only in rows its condition accepts. */
	public function hasCondition(): bool
	{
		return $this->condition !== null;
	}


	/**
	 * Evaluates whether this action should be visible, for the given row when one is passed.
	 * @param array<string, mixed>|null $row
	 */
	public function isVisible(?array $row = null): bool
	{
		return $this->visible && ($row === null || $this->condition === null || ($this->condition)($row));
	}


	/** Executes the action for the row with the given ID. */
	public function execute(int $id): void
	{
		($this->callback)($id);
	}
}
