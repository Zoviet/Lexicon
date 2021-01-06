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
 * Примеры использования для обработки фраз, утверждений и высказываний
 */

require_once __DIR__.'/Lexicon/Semantic.php';
require 'vendor/autoload.php';


$semantic = new \Lexicon\Semantic();

$string = 'Программное обеспечение (P) позволяет чуть более рационально организовать распорядок дня и освободить до 70% времени.'; //строка для обработки

$semantic->text($string)-> //установка текста для обработки 
	//Базовая обработка текста: 
	remove_braces()-> //удаление кавычек из строки
	words()-> //создание массива слов
	remove_all()->// удаление числительных, предлогов и т д
	remover(array('%','.'))-> //удаление пользовательских символов
	explore()-> //разбор текста на массив по частям речи
	stemming(); //удаление окончаний из массива частей речи по словарю

echo '
-------------------------Массив слов-----------------------------------
';

var_dump($semantic->words);

echo '
-------------------------Массив частей речи-----------------------------------
';

var_dump($semantic->result);

echo '
-------------------------Выделение смысловых частей-----------------------------------
';

$semantic2 = new \Lexicon\Semantic();
echo 'Предикт:'. $semantic2->text($string)->predict(); //выделение значимого глагола
echo ' | Субъект:'. $semantic2->subject(); //выделение значимого существительного
echo ' | Отношение:'. $semantic2->definition(); //выделение значимого прилагательного
echo 'Новый субъект:'. $semantic2->to_noun(); //преобразование значимого прилагательного в существительное, метод найдет его сам
echo ' | Новое отношение:'. $semantic2->to_adj(); //и наоборот

echo '
-------------------------Глубокий анализ-----------------------------------
';

var_dump($semantic2->analise(2)); //поиск значимых частей глубже, чем одним проходом

?>
