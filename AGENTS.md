# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/datagrid`
- **Type**: library
- **Purpose**: A simple datagrid for sorting and filtering data, with row actions, bulk actions and a CSV export.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: latte/latte (^3.1), dibi/dibi (^5.0), nette/application (^3.2), nette/forms (^3.2), nette/utils (^4.0)
- **License**: MIT

## Repository Layout
- `src/`: package source code.
- `tests/`: automated tests.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- `Drago\Datagrid\DataGrid`: persistent parameters come from the URL; `loadState()` replaces unsupported page sizes (`Options::PageSizes`), pages and orders with defaults and drops non-scalar filter values.
- Filters (`Filter\TextFilter`, `DateFilter`, `SelectFilter`) ignore invalid input instead of throwing; date ranges include the whole last day; select values must be configured options. `setFilterSelect($items, translate: false)` is for data (e.g. names from the database): the top filter form wraps those items in `Html` (Nette does not translate `HtmlStringable`, the caption and the prompt still are), the inline template prints them as they are; translated items are translated in both modes. The page size select translates only "All", not the numbers.
- Sorting is built by `Column::applySorting()`; the natural sort orders by the text before the first number, the number and the whole value, using `REGEXP_SUBSTR()` (MySQL 8, MariaDB 10.0.5+). The SQL of filters is tested with Dibi's `dummy` driver in `tests/Datagrid/FilterTest.phpt`.
- Row actions (`Action`) are identified by a name without `!`; all of them go through the `action!` signal, whose links carry only the name and the row ID, the rest of the grid state comes from the persistent parameters (also for sorting and the paginator). Actions render in the order they are added; `setRowClickAction()` must name an added action. `Action::setVisible(false)` hides an action (menu item and row click) in all rows, e.g. by permission; `handleAction()` refuses a hidden action and a row its condition leaves out. The actions of a row are a menu (`dropdown`) under a button of three dots in the cell `datagrid-actions`; the button has `data-bs-popper-config='{"strategy":"fixed"}'`, or the scrolling `table-responsive` would cut the menu off. `Action.latte` renders one item (`<li><a class="ajax dropdown-item">`, an optional icon before the label); `danger: true` makes it red after a divider. `addActionEdit()`/`addActionDelete()` use `bi bi-pencil`/`bi bi-trash` (Bootstrap Icons), delete is `danger`.
- Bulk actions (`BulkAction`, `addBulkAction()`, shortcuts `addBulkActionEdit()` [single row, callback gets the ID], `addBulkActionDelete()`, `addBulkActionExport()`): with a visible one the grid is wrapped in `<form method="post" data-selection>` with a checkbox per row (`ids[]`) and the bar `selection-bar`; the markup and the script are the module `selection` of compostrap (`RowSelection`), this package has no JavaScript for it. Every button is a submit with `formaction` of the `bulk!` signal and its name (Naja sends the form by the `ajax` class of the button; the export has none, it is a download). `handleBulk()` reads the IDs from POST `ids` (positive whole numbers, each once), refuses a hidden action, no rows, and more than one row for a `single` action. The filter form is rendered before the bulk form, never inside it (its inputs join it by the `form` attribute). A redraw of the snippet `dataGrid` clears the selection, because the form is inside it.
- Row click: `setRowClickAction('edit')` runs a row action (`row-click.js`, `datagrid-row-clickable`); `setRowClickAction(Options::RowClickSelect)` (`'select'`, a name `addAction()` refuses) prints `data-selection-rows` on the form and the script of compostrap chooses the clicked row. It needs a bulk action.
- CSV export (`CsvExport`): `setExport(fileName, delimiter, button)` shows the link "Export" in the footer (`handleExport()`, all rows of the current filters and sorting, no paging), `addBulkActionExport()` exports the chosen rows; both end in `sendExport()`, which sends a `CallbackResponse`. Cells are `Column::exportCell()` = the rendered cell without HTML, so the file shows what the grid shows; `Column::setExportable(false)` leaves a column out. UTF-8 with BOM and `;` by default (opens in a Czech spreadsheet), a cell that starts like a formula gets an apostrophe. The export button is inside the snippet (the footer), so its link always carries the current filters.
- State menu: `ColumnText::setState(name, callback(int $id, int|string $value), states, enabled, translate)` shows the state of a row as its badge with an arrow (`button.badge.badge-subtle.datagrid-state` in a `dropdown`, the markup of the compostrap datagrid page) that opens a menu of the states; `states` have the format of `setBadges()` and are stored as the badges, so a state that is not enabled (no permission) is the plain badge. The items are `button.dropdown-item` without a name (they stand inside the bulk form and must not be sent with it); the current one is `active` with `aria-current` and has no link, the others carry `data-datagrid-state` with the link of the `state!` signal, which the grid gives the column before rendering (`setStateLink()`), and the value in `value`. `state.js` (a part of `DataGrid`, document-level delegation) posts `value` and returns the focus to the badge of the row after the redraw. `handleState()` carries `#[Access(parameter: 'name')]` (privilege = the name of the state) and refuses a state that is not enabled, a request without `value` and a value that is not a key of the states; the callback gets the key as PHP holds it (`1`, `'draft'`). A bool of the database matches the keys `1`/`0`. `ColumnText::exportCell()` writes the label of a badge, not the items of the menu. The packages use it instead of switches and of bulk actions Publish/Unpublish (user's choice: many switches in a table are noise, colored badges read better).
- Date filter: the field is the datepicker of compostrap (`data-bs-toggle="datepicker"`, a text input, not `type="date"`), in both filter modes; `setFilterDate(range: true)` adds `data-bs-selection-mode="multiple-ranged"` and two months. The value stays ISO. `DateFilter` reads one date, `from|to` and two dates with any separator (the datepicker writes `2026-11-01 – 2026-11-10`), in any order. `datagrid-filter.js` submits a range only when it has two dates or is empty, because the first click in the calendar gives one date and a redraw would close the calendar. `vanilla-calendar-pro` is a dependency in `package.json`: compostrap keeps it an optional peer, and a project would otherwise fail to build its assets.
- Badges: `ColumnText::setBadges([value => [label, color]])` renders `badge badge-subtle theme-<color>` (compostrap) that is only read; the label is translated by the translator the grid passes to its columns before rendering and exporting (`Column::setTranslator()`). A clickable badge stays a formatter returning `Html`.
- The table has `align-middle` (as the product table of the compostrap demo), so a cell with an image or an icon and the texts of its row stand on one line.
- The package has no styles: the classes are styled by compostrap (`datagrid`, `selection`, `datepicker`, `elements` for the cross of a filter, `tooltip`, the theme). Keep the markup equal to the datagrid page of the compostrap demo.
- Privileges for drago-ex/permission (its `Access` attribute): `handleSort()` and the classes `FilterTextControl` (covers its form), `PaginatorControl` and `PageSizeControl` carry `#[Access]` (the page is enough); `handleAction()` carries `#[Access(parameter: 'name')]`, so a row action needs the privilege named like the action (`edit`, `delete`, the names of `addActionEdit()`/`addActionDelete()`); `handleBulk()` carries the same attribute, so a bulk action needs the privilege of its name too (`edit`, `delete`, `export`, `publish`), and `handleExport()` carries `#[Access('export')]`. The attribute class is only in require-dev/suggest: PHP ignores an attribute whose class is missing, so the grid works without permission. Tested through `Privilege::forSignal()` in `DataGridAccessTest.phpt`.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.5), phpstan/phpstan-nette (^2.0), drago-ex/permission (^2.0, for the Access attribute).

## Essential Commands

```bash
composer tester                 # tester tests -s
composer phpstan                # phpstan analyse
```

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
