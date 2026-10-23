# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/datagrid`
- **Type**: library
- **Purpose**: A simple datagrid for sorting and filtering data.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: latte/latte (^3.1), tracy/tracy (^2.11), dibi/dibi (^5.0), nette/application (^3.2), nette/forms (^3.2)
- **License**: MIT

## Repository Layout
- `src/`: package source code.
- `tests/`: automated tests.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- `Drago\Datagrid\DataGrid`: persistent parameters come from the URL; `loadState()` replaces unsupported page sizes (`Options::PageSizes`), pages and orders with defaults and drops non-scalar filter values.
- Filters (`Filter\TextFilter`, `DateFilter`, `SelectFilter`) ignore invalid input instead of throwing; date ranges include the whole last day; select values must be configured options.
- Sorting is built by `Column::applySorting()`; the natural sort orders by the text before the first number, the number and the whole value, using `REGEXP_SUBSTR()` (MySQL 8, MariaDB 10.0.5+). The SQL of filters is tested with Dibi's `dummy` driver in `tests/Datagrid/FilterTest.phpt`.
- Row actions (`Action`) are identified by a name without `!`; all of them go through the `action!` signal, whose links carry only the name and the row ID, the rest of the grid state comes from the persistent parameters (also for sorting and the paginator). Actions render in the order they are added; `setRowClickAction()` must name an added action. `Action::setVisible(false)` hides an action (button and row click) in all rows, e.g. by permission; it does not refuse the signal.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.3), phpstan/phpstan-nette (^2.0).

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
