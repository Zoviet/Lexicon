<?php
/**
 * Lexicon: Библиотека для автоматической генерации иерархии понятийных уровней.
 *
 * @copyright   Copyright (c) 2021, Zoviet <alexandr@asustem.ru>
 * @version 0.1
 * @link http://github.com/Zoviet/Lexicon
 * @author Zoviet (Alexandr Pavlov  / @Zoviet)
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @site https://Zoviet.github.io/
 */

/**
 * Примеры использования* 
 */

require_once __DIR__.'/Lexicon/Semantic.php';
require 'vendor/autoload.php';

/**
 * Статические методы (для обработки слов)
 *  
*/

$word = 'курица';

var_dump (\Lexicon\Semantic::add_suffix('лис','ADJS',TRUE));




$semantic = new \Lexicon\Semantic();
$methods = get_class_methods($semantic);

foreach ($methods as $method) {
				$gr = new \ReflectionMethod($semantic, $method);
				$params = $gr->getParameters();
				$doc = $gr->getDocComment();
				if ($gr->isStatic()) {
					echo $method.' | '.$doc;
				}
			}





?>
