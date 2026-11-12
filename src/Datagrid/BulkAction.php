<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid;

use Closure;


/**
 * Action for the rows chosen by their checkboxes. Its button stands in the bar of the selection and leads
 * to the grid's bulk! signal, which calls the callback with the IDs of the chosen rows.
 */
class BulkAction
{
	/** @var Closure(list<int>): void */
	private Closure $callback;

	private bool $visible = true;


	/** @param callable(list<int>): void $callback Receives the IDs of the chosen rows */
	public function __construct(
		/** @var string Action name, used in the link and as the privilege of drago-ex/permission */
		public readonly string $name,
		/** @var string Label of the button */
		public readonly string $label,
		callable $callback,
		/** @var string|null Icon classes (e.g. 'bi bi-check2-circle' of Bootstrap Icons), shown before the label */
		public readonly ?string $icon = null,
		/** @var bool A destructive action: a red button */
		public readonly bool $danger = false,
		/** @var bool The action is for one row only: its button is disabled while more rows are chosen */
		public readonly bool $single = false,
		/** @var bool false sends the form without AJAX, for an action that answers with a file */
		public readonly bool $ajax = true,
	) {
		$this->callback = $callback(...);
	}


	/**
	 * Shows or hides the button, e.g. by the user's permission. A hidden action is not run by a signal either.
	 * @return $this
	 */
	public function setVisible(bool $visible = true): self
	{
		$this->visible = $visible;
		return $this;
	}


	public function isVisible(): bool
	{
		return $this->visible;
	}


	/**
	 * Executes the action for the chosen rows.
	 * @param list<int> $ids
	 */
	public function execute(array $ids): void
	{
		($this->callback)($ids);
	}
}
