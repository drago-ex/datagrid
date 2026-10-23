# Drago DataGrid

[English](readme.md) | Čeština

Drago DataGrid je komponenta pro Nette, která vykresluje tabulky Bootstrapu 5 s filtrováním, řazením, stránkováním a akcemi řádků.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Tests](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Nette Framework
- Dibi
- Latte
- Bootstrap 5
- Naja

## Instalace

```bash
composer require drago-ex/datagrid
```

## Assety frontendu

Přidejte balíček z Composeru jako lokální závislost npm:

```json
{
  "type": "module",
  "dependencies": {
    "drago-datagrid": "file:vendor/drago-ex/datagrid"
  }
}
```

Nainstalujte závislosti:

```bash
npm install
```

Importujte assety:

```js
import naja from 'naja';
import DataGrid from 'drago-datagrid';
import 'drago-datagrid/styles/datagrid';

naja.initialize();
new DataGrid().initialize(naja);
```

## Základní použití

```php
use Dibi\Connection;
use Drago\Datagrid\DataGrid;
use Nette\Application\UI\Presenter;

final class ProductPresenter extends Presenter
{
	public function __construct(
		private readonly Connection $db,
	) {
	}

	protected function createComponentGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setDataSource(
			$this->db->select('*')->from('products')
		);

		$grid->addColumnText('name', 'Name')->setFilterText();
		$grid->addColumnText('status', 'Status')->setFilterSelect([
			'active' => 'Active',
			'inactive' => 'Inactive',
		]);
		$grid->addColumnDate('created_at', 'Created', format: 'd.m.Y')->setFilterDate();

		return $grid;
	}
}
```

Vykreslete komponentu v Latte:

```latte
{control grid}
```

## Zdroj dat

Grid očekává zdroj dat `Dibi\Fluent`. Filtrování, řazení a stránkování se při vykreslení použije na klon dotazu.

```php
$grid->setDataSource(
	$this->db->select('*')->from('products')->orderBy('id DESC')
);
```

Počáteční pořadí určuje zdroj dat. Grid nechává ukazatel řazení neutrální, dokud uživatel nevybere řaditelný sloupec. První kliknutí sloupec seřadí vzestupně, další kliknutí přepínají mezi vzestupným a sestupným pořadím.

Pro stabilní stránkování vždy použijte jednoznačné počáteční pořadí. Při řazení podle hodnoty, která není jedinečná, přidejte jako poslední kritérium primární klíč.

```php
$grid->setDataSource(
	$this->db->select('*')
		->from('products')
		->orderBy('name ASC')
		->orderBy('id ASC')
);
```

## Sloupce

```php
$grid->addColumnText('name', 'Product Name');
$grid->addColumnText('sku', 'SKU', sortable: false);
$grid->addColumnDate('updated_at', 'Updated', format: 'd.m.Y');
```

Argumenty sloupce:
- `name` - název sloupce v databázi
- `label` - popisek sloupce
- `sortable` - zapne řazení v hlavičce, výchozí je `true`
- `formatter` - volitelný callback pro vykreslení hodnot buněk

Přirozené řazení dá `Article 4` před `Article 40`; řadí podle textu před prvním číslem, potom podle čísla a nakonec podle celé hodnoty:

```php
$grid->addColumnText('title', 'Title')->setNaturalSort();
```

Přirozené řazení používá `REGEXP_SUBSTR()`, které vyžaduje MySQL 8 nebo MariaDB 10.0.5 a novější.

Zarovnání textu ve sloupci lze změnit pomocníky pro zarovnání z Bootstrapu:

```php
$grid->addColumnText('id', 'ID')->alignRight();
$grid->addColumnText('status', 'Status')->alignCenter();
$grid->addColumnText('name', 'Name')->alignLeft();
```

Zarovnání ovlivňuje jen vykreslení tabulky. Hodnotu sloupce neověřuje ani nepřevádí.

## Filtry

```php
$grid->addColumnText('name', 'Name')->setFilterText();

$grid->addColumnText('status', 'Status')->setFilterSelect([
	'active' => 'Active',
	'inactive' => 'Inactive',
]);

$grid->addColumnDate('created_at', 'Created')->setFilterDate();
```

Typy filtrů:
- `setFilterText()` - hledání přes LIKE
- `setFilterSelect(array $items)` - výběr s přesnou shodou
- `setFilterDate()` - filtr data ve formátu `YYYY-MM-DD`

Filtry data podporují i rozsah ve tvaru `YYYY-MM-DD|YYYY-MM-DD`; kterákoli strana může být prázdná.
Oba dny se započítají, i u sloupců DATETIME.

Aktivní filtry lze smazat jednotlivě malým tlačítkem vedle pole filtru. Tlačítko pro reset smaže všechny filtry najednou.

## Režimy filtrů

Filtry lze vykreslit ve dvou režimech:

```php
$grid->setFilterMode('top');
```

`top` je výchozí režim. Filtry se zobrazí v liště nad tabulkou.

```php
$grid->setFilterMode('inline');
```

`inline` zobrazí filtry v druhém řádku hlavičky tabulky pod příslušnými sloupci.

Oba režimy používají stejné definice filtrů a stejné chování přes AJAX.

## Akce řádků

Před přidáním akcí nastavte primární klíč:

```php
$grid->setPrimaryKey('id');
```

Úprava a mazání mají zkratky s tlačítkem primary a danger:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));
```

Ostatní akce dostanou název, popisek a callback, který přijme ID řádku:

```php
$grid->addAction(
	name: 'activate',
	label: 'Activate',
	callback: fn(int $id) => $this->activate($id),
	class: 'ajax btn btn-xs btn-success',
	condition: fn(array $row): bool => !$row['active'],
);
```

Název akci jen identifikuje (písmena, číslice, `-` a `_`); každý odkaz akce vede na signál gridu `action!` a zachová aktuální filtry, stránku a řazení. Volitelná `condition` dostane řádek jako `array<string, mixed>` a rozhoduje, zda řádek akci zobrazí. Akce se zobrazují v pořadí, v jakém byly přidány. Grid se po akci nepřekresluje, protože otevření formuláře nebo dialogu v něm nic nemění; callback, který mění data, zavolá `$grid->redrawDataGrid()`.

## Kliknutí na řádek

Akci může spustit celý řádek; její tlačítko se pak nezobrazí:

```php
$grid->setRowClickAction('edit');
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
```

Akce musí být přidaná, jinak vykreslení selže.

## Skrytí akcí

`setVisible()` skryje akci ve všech řádcích, typicky podle oprávnění uživatele:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
	->setVisible($this->isWriteAllowed());
```

Skrytá akce nemá tlačítko ani kliknutí na řádek a sloupec akcí zmizí, když žádná akce nezbude. Rozhoduje jen o zobrazení; samotný signál `action!` hlídá autorizace presenteru.

## Zobrazení akcí

Zobrazení akcí řádku jen při najetí myší:

```php
$grid->setAutoHideActions();
```

## Formátování hodnot

Jednoduché hodnoty se escapují automaticky:

```php
$grid->addColumnText('price', 'Price', formatter: function (mixed $value, array $row): string {
	return number_format((float) $value, 2) . ' CZK';
});
```

Pro vykreslení HTML vraťte `Nette\Utils\Html`:

```php
use Nette\Utils\Html;

$grid->addColumnText('status', 'Status', formatter: function (mixed $value, array $row): Html {
	return Html::el('span')
		->class($value === 'active' ? 'badge bg-success' : 'badge bg-secondary')
		->setText((string) $value);
});
```

## Lokalizace

Grid podporuje `Nette\Localization\Translator`:

```php
$grid->setTranslator($this->translator);
```

Překládají se popisky sloupců, akcí a filtrů, tlačítko pro reset a texty stránkování; dodané překlady `cs` pokrývají i výchozí popisky `Edit` a `Delete`.

## AJAX

Grid je postavený pro Naja. Filtrování, řazení, stránkování, změny velikosti stránky a akce řádků se zpracovávají přes AJAX a udržují historii prohlížeče aktuální.

Textové filtry se odesílají klávesou Enter. Výběrové a datumové filtry se odešlou automaticky při změně hodnoty.

## Bezpečnost

- Hodnoty buněk se escapují, pokud formatter nevrátí `Nette\Utils\Html`.
- Filtry používají parametrizované dotazy Dibi.
- Hodnoty filtrů pocházejí z URL, takže neplatné hodnoty se ignorují: datum, které není `YYYY-MM-DD`, pole místo jedné hodnoty nebo hodnota výběru, která není mezi nastavenými možnostmi.
- Velikost stránky je omezená na nabízené hodnoty (20, 50, 100 a Vše); ostatní hodnoty z URL použijí výchozí.
- Směr řazení se před použitím v SQL normalizuje na `ASC` nebo `DESC`.
- Prázdné hodnoty filtrů se odstraní z persistentních parametrů.
