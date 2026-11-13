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
	private ?string $stateName = null;

	/** @var (Closure(int, int|string): void)|null */
	private ?Closure $stateCallback = null;

	private bool $stateEnabled = true;

	/** @var (Closure(array<string, mixed>): array{string, string})|null */
	private ?Closure $stateLink = null;


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
	 * Shows the values as light colored badges that are only read (a state such as Published or Draft; setState()
	 * lets the user change it):
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
	 * Shows the state of a row (published or draft, active or not) as its badge with a small arrow, which opens
	 * a menu of the states and changes the state at once, without opening a form. The grid sends the choice to
	 * its state! signal, which calls the callback with the row ID and the chosen value; the callback saves it
	 * and calls redrawDataGrid().
	 * @param string $name Identifies the state in the link (letters, digits, '-' and '_'); with drago-ex/permission
	 * it is the privilege the change needs
	 * @param callable(int, int|string): void $callback Receives the row ID and the chosen value (a key of $states)
	 * @param array<int|string, array{string, string}> $states value => [label, color], as in setBadges()
	 * @param bool $enabled false shows the badge only, e.g. for a user without the permission
	 * @param bool $translate false for labels that are data, which the translator does not know
	 */
	public function setState(
		string $name,
		callable $callback,
		array $states,
		bool $enabled = true,
		bool $translate = true,
	): self
	{
		if (!preg_match('#^[a-zA-Z0-9_-]+$#D', $name)) {
			throw new \InvalidArgumentException("State name '$name' may contain only letters, digits, '-' and '_'.");
		}

		$this->stateName = $name;
		$this->stateCallback = $callback(...);
		$this->stateEnabled = $enabled;
		return $this->setBadges($states, $translate);
	}


	/** The name of the state menu of this column, null without one. */
	public function getStateName(): ?string
	{
		return $this->stateName;
	}


	public function isStateEnabled(): bool
	{
		return $this->stateName !== null && $this->stateEnabled;
	}


	/** Calls the callback of the state menu for the row; false for a value that is not one of the states. */
	public function changeState(int $id, string $value): bool
	{
		if ($this->stateCallback === null || !isset($this->badges[$value])) {
			return false;
		}

		// the callback gets the key as the array holds it: '1' is 1, 'draft' stays a string
		($this->stateCallback)($id, array_key_first([$value => true]));
		return true;
	}


	/**
	 * Set by the grid before it renders: returns the link of the state! signal and the HTML id for a row.
	 * @param Closure(array<string, mixed>): array{string, string} $link
	 */
	public function setStateLink(Closure $link): static
	{
		$this->stateLink = $link;
		return $this;
	}


	/**
	 * The value for the CSV export; a state is its label, without the items of its menu.
	 * @param array<string, mixed> $row
	 */
	public function exportCell(array $row): string
	{
		$badge = $this->findBadge($row);
		return $badge !== null ? $this->badgeLabel($badge[1][0]) : parent::exportCell($row);
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

		$badge = $this->findBadge($row);
		if ($badge !== null) {
			[$key, [$label, $color]] = $badge;
			if ($this->isStateEnabled() && $this->stateLink !== null) {
				return $this->renderState($row, $key);
			}

			return (string) Html::el('span', ['class' => "badge badge-subtle theme-$color"])
				->setText($this->badgeLabel($label));
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
	 * The state of a row that can be changed: its badge is the button of a menu with the states (datagrid-state
	 * of compostrap), the current one marked. The script of the package posts the value of the chosen item to
	 * the link in its data-datagrid-state. The buttons have no name: they stand inside the bulk form.
	 * @param array<string, mixed> $row
	 */
	private function renderState(array $row, string $current): string
	{
		assert($this->stateLink !== null);
		[$link, $id] = ($this->stateLink)($row);
		[$label, $color] = $this->badges[$current];

		$button = Html::el('button', [
			'type' => 'button',
			'class' => "badge badge-subtle theme-$color datagrid-state",
			'id' => $id,
			'data-bs-toggle' => 'dropdown',
			'data-bs-popper-config' => '{"strategy":"fixed"}',
			'aria-expanded' => 'false',
			'title' => $this->translator !== null ? (string) $this->translator->translate('Change the state') : 'Change the state',
		])
			->setText($this->badgeLabel($label))
			->addHtml(Html::el('i', ['class' => 'bi bi-chevron-down', 'aria-hidden' => 'true']));

		$menu = Html::el('ul', ['class' => 'dropdown-menu']);
		foreach ($this->badges as $value => [$itemLabel]) {
			$active = (string) $value === $current;
			$menu->addHtml(Html::el('li')->addHtml(
				Html::el('button', [
					'type' => 'button',
					'class' => 'dropdown-item' . ($active ? ' active' : ''),
					'aria-current' => $active ? 'true' : null,
					'data-datagrid-state' => $active ? null : $link,
					'value' => (string) $value,
				])->setText($this->badgeLabel($itemLabel)),
			));
		}

		return (string) Html::el('div', ['class' => 'dropdown'])
			->addHtml($button)
			->addHtml($menu);
	}


	/**
	 * The badge of the value of the row with its key, null for a value without one; a bool of the database
	 * is the key 1 or 0.
	 * @param array<string, mixed> $row
	 * @return array{string, array{string, string}}|null
	 */
	private function findBadge(array $row): ?array
	{
		$value = $row[$this->name] ?? '';
		$key = is_bool($value) ? (string) (int) $value : (is_scalar($value) ? (string) $value : null);
		return $key !== null && isset($this->badges[$key]) ? [$key, $this->badges[$key]] : null;
	}


	private function badgeLabel(string $label): string
	{
		return $this->translateBadges && $this->translator !== null
			? (string) $this->translator->translate($label)
			: $label;
	}
}
