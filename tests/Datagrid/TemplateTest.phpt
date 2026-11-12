<?php declare(strict_types=1);

/**
 * Test: DataGrid templates compile
 */

use Latte\Engine;
use Latte\Essential\TranslatorExtension;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Bridges\FormsLatte\FormsExtension;
use Nette\Utils\Finder;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Every template of the grid is valid Latte', function () {
	$latte = new Engine;
	$latte->addExtension(new UIExtension(null));
	$latte->addExtension(new FormsExtension);
	$latte->addExtension(new TranslatorExtension(null));

	$files = Finder::findFiles('*.latte')->from(__DIR__ . '/../../src')->collect();
	Assert::true($files !== []);

	foreach ($files as $file) {
		Assert::noError(fn() => $latte->compile((string) $file));
	}
});
