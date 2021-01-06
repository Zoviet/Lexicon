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
 * Speller: класс обработки преобразованных слов с помощью Яндекс Спеллера.  * 
 */

namespace Lexicon;

class Speller {	
	
	/*
	Ошибки класса
	*/	
	protected const ERRORS = array(
		'S01' => 'Не передано слово или строка для проверки орфографии',
		'S02' => 'Ошибка получения данных от Яндекс Спеллера',
	);
	
	/**
	* Свойства
	* @var  protected string string Принимаемая строка для обработки.
	* @var  public string result возвращаемая строка.
	* @var  public array data Ответ сервера 
	* 
	*/	
	protected $string;
	public $result;
	public $data;
	
	/**	
	 * 	Конструктор 	 
	 * @param string данные для обработки      
     * @return void
	*/
	
	public function __construct($string) {
		mb_internal_encoding('UTF-8');
		if (!is_string($string) or empty($string)) {
			throw new \Exception(self::ERRORS['S01']);
		} else {
			$this->string = trim($string);
			$this->result = self::check($this->string);
		}
	}
	
	/**	
	 * Получение данных от Яндекс Спеллера
	*/
	
	public static function get_data($string) {
		$results = NULL;
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://speller.yandex.net/services/spellservice.json/checkText");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "text=".$string."&format=plain&lang=ru&options=512");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			curl_close($ch);
			$results = json_decode($output); 
		} catch (\Exception $e) {
			throw new \Exception(self::ERRORS['S02'].' :'.$e->getMessage());
			error_log($e->getMessage(), 0);		
		}
		return $results;
	}
	
	/**	
	 * Выборка одного правильного варианта
	*/
	
	public static function check($word) {
		$data = self::get_data($word);
		if (!empty($data->s[0])) {
			$word = $data->s[0];
		}	
		return $word;
	}
		
}
