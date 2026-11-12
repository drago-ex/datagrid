<?php declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Datagrid\Column;

use Closure;
use Drago\Datagrid\Filter\SelectFilter;
use Drago\Datagrid\Filter\TextFilter;
use Nette\Utils\Html;


/**
 * DataGrid column for displaying text values.
 * Supports optional cell formatting callback.
 */
class ColumnText extends Column
{
	/** @var array<int|string, array{string, string}> */
	private array $badges = [];

	private bool $translateBadges = true;
	private ?string $switchName = null;

	/** @var (Closure(int, bool): void)|null */
	private ?Closure $switchCallback = null;

	private int|string $switchOn = 1;

	/** @var array{string, string} Labels of the switch: on, off */
	private array $switchLabels = ['On', 'Off'];

	private bool $switchEnabled = true;

	/** @var (Closure(array<string, mixed>): array{string, string})|null */
	private ?Closure $switchLink = null;


	public function setFilterText(): self
	{
		$this->setFilter(new TextFilter);
		return $this;
	}


	/**
	 * @param array<int|string, string> $items
	 * @param bool $translate false for data such as names from the database, which the translator does not know
	 */
	public function setFilterSelect(array $items, bool $translate = true): self
	{
		$this->setFilter(new SelectFilter($items, $translate));
		return $this;
	}


	/**
	 * Shows the values as light colored badges that are only read (a state such as Published or Draft):
	 * value => [label, color], where the color is a theme color (primary, secondary, success, danger, warning, info).
	 * A value without a badge is printed as a text. The formatter, if any, is used only for such values.
	 * @param array<int|string, array{string, string}> $badges
	 * @param bool $translate false for labels that are data, which the translator does not know
	 */
	public function setBadges(array $badges, bool $translate = true): self
	{
		$this->badges = $badges;
		$this->translateBadges = $translate;
		return $this;
	}


	/**
	 * Shows a value with two states (published or draft, active or not) as a switch that changes it at once,
	 * without opening a form. The grid sends the change to its switch! signal, which calls the callback with
	 * the row ID and the new state; the callback saves it and calls redrawDataGrid().
	 * @param string $name Identifies the switch in the link (letters, digits, '-' and '_'); with drago-ex/permission
	 * it is the privilege the change needs
	 * @param callable(int, bool): void $callback Receives the row ID and whether the switch is on
	 * @param bool|int|string $on The value of the column that means "on"
	 * @param array{string, string} $labels The text next to the switch: on, off (translated)
	 * @param bool $enabled false shows the state only, e.g. for a user without the permission
	 */
	public function setSwitch(
		string $name,
		callable $callback,
		bool|int|string $on = true,
		array $labels = ['On', 'Off'],
		bool $enabled = true,
	): self
	{
		if (!preg_match('#^[a-zA-Z0-9_-]+$#D', $name)) {
			throw new \InvalidArgumentException("Switch name '$name' may contain only letters, digits, '-' and '_'.");
		}

		$this->switchName = $name;
		$this->switchCallback = $callback(...);
		$this->switchOn = is_bool($on) ? (int) $on : $on;
		$this->switchLabels = $labels;
		$this->switchEnabled = $enabled;
		return $this;
	}


	/** The name of the switch of this column, null without one. */
	public function getSwitchName(): ?string
	{
		return $this->switchName;
	}


	public function isSwitchEnabled(): bool
	{
		return $this->switchName !== null && $this->switchEnabled;
	}


	/** Calls the callback of the switch for the row. */
	public function toggle(int $id, bool $on): void
	{
		if ($this->switchCallback !== null) {
			($this->switchCallback)($id, $on);
		}
	}


	/**
	 * Set by the grid before it renders: returns the link of the switch! signal and the HTML id for a row.
	 * @param Closure(array<string, mixed>): array{string, string} $link
	 */
	public function setSwitchLink(Closure $link): static
	{
		$this->switchLink = $link;
		return $this;
	}


	/**
	 * Renders the cell for this column.
	 * Applies optional formatter and escapes HTML characters.
	 * If the formatter returns a Nette\Utils\Html object, it is rendered directly.
	 * @param array<string, mixed> $row
	 */
	public function renderCell(array $row): string
	{
		$value = $row[$this->name] ?? '';

		if ($this->switchName !== null) {
			return $this->renderSwitch($row, is_scalar($value) && (string) (is_bool($value) ? (int) $value : $value) === (string) $this->switchOn);
		}

		$badge =is_scalar($value) ? ($this->badges[(string) $value] ?? null) : null;
		if ($badge !== null) {
			[$label, $color] = $badge;
			if ($this->translateBadges && $this->translator !== null) {
				$label = (string) $this->translator->translate($label);
			}

			return (string) Html::el('span', ['class' => "badge badge-subtle theme-$color"])->setText($label);
		}

		if ($this->formatter) {
			$output = ($this->formatter)($value, $row);
		} else {
			$output = $value;
		}

		if ($output instanceof Html) {
			return (string) $output;
		}

		return htmlspecialchars((string) $output, ENT_QUOTES, 'UTF-8');
	}


	/**
	 * The switch of a row: a checkbox with the state as its label. The script of the package sends a change
	 * to the link in data-datagrid-switch; a switch that cannot be changed is disabled and has no link.
	 * @param array<string, mixed> $row
	 */
	private function renderSwitch(array $row, bool $on): string
	{
		$label = $this->switchLabels[$on ? 0 : 1];
		if ($this->translator !== null) {
			$label = (string) $this->translator->translate($label);
		}

		[$link, $id] = $this->switchEnabled && $this->switchLink !== null ? ($this->switchLink)($row) : [null, null];
		$input = Html::el('input', [
			'type' => 'checkbox',
			'role' => 'switch',
			'class' => 'form-check-input',
			'id' => $id,
			'checked' => $on,
			'disabled' => $link === null,
			'autocomplete' => 'off',
			'data-datagrid-switch' => $link,
		]);

		return (string) Html::el('div', ['class' => 'form-check form-switch datagrid-switch'])
			->addHtml($input)
			->addHtml(Html::el('label', ['class' => 'form-check-label', 'for' => $id])->setText($label));
	}
}
