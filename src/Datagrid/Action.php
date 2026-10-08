<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Datagrid;


class Action
{
	/** @var list<callable(int): void> */
	private array $callbacks = [];

	/** @var (callable(array<string, mixed>): bool)|null */
	private $condition;


	/**
	 * @param string $label Action label displayed in the UI
	 * @param string $signal Signal used for triggering this action
	 * @param string|null $class Optional CSS class for the action link/button
	 */
	public function __construct(
		public readonly string $label,
		public readonly string $signal,
		public readonly ?string $class = null,
	) {
	}


	/**
	 * Sets a condition callback that decides whether the action is visible for a given row.
	 * @param callable(array<string, mixed>): bool $condition
	 * @return $this
	 */
	public function setCondition(callable $condition): self
	{
		$this->condition = $condition;
		return $this;
	}


	/**
	 * Evaluates whether this action should be visible for the given row.
	 * @param array<string, mixed> $row
	 */
	public function isVisible(array $row): bool
	{
		if ($this->condition === null) {
			return true;
		}
		return ($this->condition)($row);
	}


	/**
	 * Adds a callback to be executed when the action is triggered.
	 * @param callable(int): void $callback Callback function receiving the row ID
	 * @return $this Fluent interface
	 */
	public function addCallback(callable $callback): self
	{
		$this->callbacks[] = $callback;
		return $this;
	}


	/**
	 * Executes all registered callbacks with the given row ID.
	 * @param int $id ID of the row this action is executed for
	 */
	public function execute(int $id): void
	{
		foreach ($this->callbacks as $callback) {
			$callback($id);
		}
	}
}
