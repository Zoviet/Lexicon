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
 * Lexicon: класс грамматических преобразований слов и предложений. * 
 * 
 * Класс использует грамматические правила, а не словари. Исключение: для работы методов выделения уровней предметов и методов из текста используется Yandex.Speller. Класс хорошо подходит для работы со сложными, но хорошо формально структурированными текстами и высказываниями, а также со словами, для которых не существует словарных синонимов - например, сложными научными терминами.  
 * 
 * 
 * Функционал: 
 * 
 * 1. Выделение в тексте частей речи 
 * 2. Удаление окончаний у слов по двум алгоритмам (Мартина Поттера и по словарю окончаний)
 * 3. Преобразование текста в массив слов
 * 4. Очистка текста от незначимых частей речи
 * 5. Поиск в тексте объекта, подлежащего и определения
 * 6. Образование словоформ от стэмов слов
 * 7. Преобразование прилагательных, являющимися определениями в тексте, в существительные
 * 8. Преобразование существительных, являющихся субъектами в тексте, в прилагательные 
 * 9. Преобразование слов из одной части речи в другую
 * 10. Добавление оконечных суффиксов к стэмам
 * 12. Образование возможных суффиксальных словоформ от слова * 
 * 
 */

namespace Lexicon;
require 'Speller.php';

use \Stem\LinguaStemRu;

class Semantic {	
	
	/*
	Ошибки класса
	*/	
	protected const ERRORS = array(
		'S01' => 'Для семантической обработки необходимо передать строку',
	);
	
	/*
	Знаки алфавита
	* 	
	*/
	private const ALPHABET = 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя';
	
	/*
	Группы окончаний:
	* 	
	*/	
	private const LEMMS = [
		/*
		Прилагательные
		*/	
		'ADJS' => array ('ее','ие','ые','ое','ими','ыми','ей','ий','ый','ой','ем','им','ым','ом', 'его','ого','ему','ому','их','ых','ую','юю','ая','яя','ою','ею','кий','кый','тий','тый','вий','вый','кие','кые','тые','вие','вые','вший'),
		/*
		Причастия
		*/	
		'PARTS' => array ('ивш','ывш','ующ','ем','нн','вш','ющ','ущи','ющи','ящий','щих','щие','ляя'), 
		/*
		Глаголы
		*/	
		'VERBS' => array ('ила','ыла','ена','ейте','уйте','ите','или','ыли','ей','уй','ил','ыл','им','ым','ен', 'ило','ыло','ено','ят','ует','уют','ит','ыт','ены','ить','ыть','ишь','ую','ю','ла','на','ете','йте', 'ли','й','л','ем','н','ло','ет','ют','ны','ть','ешь','нно'),
		/*
		Существительные
		*/	
		'NOUNS' => array ('а','ев','ов','ье','иями','ями','ами','еи','ии','и','ией','ей','ой','ий','й','иям','ям','ием','ем','ам','ом','о','у','ах','иях','ях','ы','ь','ию','ью','ю','ия','ья','я','ок', 'мва', 'яна', 'ровать','ег','ги','га','сть','сти','ики','ик'),
		/*
		Наречия
		*/	
		'ADVS' => array ('чно', 'еко', 'соко', 'боко', 'роко', 'имо', 'мно', 'жно', 'жко','ело','тно','льно','здо','зко','шо','хо','но','сегодня','завтра','вчера'),
		/*
		Числительные
		*/	
		'NUMS' => array ('чуть','много','мало','еро','вое','рое','еро','сти','одной','двух','рех','еми','яти','ьми','ати','дного','сто','ста','тысяча','тысячи','две','три','одна','умя','тью','мя','тью','мью','тью','одним'),
		/*
		Союзы
		*/	
		'UNIS' => array ('более','менее','очень','крайне','скоре','некотор','кажд','други','котор','когд','однак', 'если','чтоб','хот','смотря','как','также','так','зато','что','или','потом','эт','тог','тоже','словно',	'ежели','кабы','коли','ничем','чем'),
		/*
		Предлоги
		*/	
		'PRES' => array ('в','на','по','из','и','до')
    ];
    
    /*
     * Суффиксы
     * 
     */ 
    private const SUFFIX = [
		/*
		 * Образующие прилагательные
		 */
		'ADJS' => array('оватеньк','еватеньк','ехоньк','охоньк','ешеньк','ошеньк','ическ','тельн','остьн','альн','отан','оньк','еньк','евит','оват','овит','овск','енск','инск','ческ', 'еват','еск','ляв','льн','чат','янн','ист', 'озн', 'ивн', 'чив','лив','ов','ев','ив','ав','яв','ев','ск','ин','ич','яч','ущ','ан','ой','ый','ий','н'),
		
		/*
		 * Образующие существительные
		 */
		'NOUNS' => array('','мость','льник','льщик','чанин','ность','итель','ство','ость','ация','яция','льня','овец','овеч','евич','анец','янец','анин','янин','ина','тор','ник','ант','ние','нье','ент','льн','чик','щик','изм','ура','лка','ист','ец','ач','аж','ло','ие','ье','ка','ин'),
				
    ];
    
    /*
	Интерпретация результатов
	* 
	* Части речи	
	*/    
    public const TYPES = [
		'UNKN' => 'Не определено',
		'PRES' => 'Предлог',
		'NUMS' => 'Числительное',
		'ADVS' => 'Наречие',
		'NOUNS' => 'Существительное',
		'VERBS' => 'Глагол',
		'PARTS' => 'Причастие',
		'ADJS' => 'Прилагательное',
		'UNIS' => 'Союзы'
    ];
    
    /*
	* Члены предложения
	*/   
    
    public const PARTS = [
		'UNKN' => 'Не определено',
		'SUBJ' => 'Подлежащее',
		'ADDN' => 'Дополнение',
		'PRED' => 'Сказуемое',
		'DFN' => 'Определение',
		'CIRC' => 'Обстоятельство'
    ];
    
        
	/**
	* Свойства
	* @var  protected string string Принимаемая строка для обработки.
	* @var  public string words Слова строки.
	* @var  public array result Массив слов по частям речи (в зависимости от глубины обработки: лишенных оснований или нет).
	* @var  private int deep Текущая глубина анализа
	* 
	*/	
	protected $string;
	public $words = array();
	public $result = array();
	private $deep = 0; 
		
	/**	
	 * 	Конструктор 	 
	 * @param string данные для обработки      
     * @return void
	*/
		
	public function __construct($string=NULL) {
		mb_internal_encoding('UTF-8');
		if (!empty($string)) {
			$this->text($string);
		}
	}
	
	/**	
	 * Запуск методов конвейера через запрос свойств    
     * @return void
	*/
		
	public function __get($name) {
		call_user_func(array($this, $name));		
	}
	
	/**	
	 * Установка текста для анализа    
     * @return void
	*/
	
	public function text($string) {
		if (!is_string($string)) {
			throw new \Exception(self::ERRORS['S01']);
		} else {
			$this->words = array();
			$this->result = array();
			$this->string = trim($string);
		}
		return $this;
	}
	
	/**	
	 * Отбрасывание из строки скобок вместе с содержимым
	*/
	
	public function remove_braces() {
		$this->string = preg_replace('#\(.*?\)#is','',$this->string);
		return $this;
	}
	
	/**	
	 * Преобразование в массив слов
	*/
	
	public function words($array=NULL) {
		$this->words = (empty($array)) ? str_word_count($this->string,1,self::ALPHABET) : $array;	
		return $this;
	}
	
	/**	
	 * Очистка массива слов от предлогов
	*/
	
	public function remove_pres() {
		$this->remover(self::LEMMS['PRES']);
		return $this;
	}
	
	/**	
	 * Очистка массива слов от союзов
	*/
	
	public function remove_unis() {
		$this->remover(self::LEMMS['UNIS']);
		return $this;
	}
	
	/**	
	 * Очистка массива слов от числительных
	*/
	
	public function remove_nums() {
		$this->remover(self::LEMMS['NUMS']);
		return $this;
	}
	
	/**	
	 * Очистка массива слов от союзов, числительных и предлогов
	*/
	
	public function remove_all() {
		$this->remove_nums()->remove_pres()->remove_unis();
		return $this;
	}
		
	/**	
	 * Очистка массива слов от элементов, входящих в переданный массив
	*/
	
	public function remover($array) {
		if (isset($this->words)) { 
			foreach ($this->words as $key=>$value) {
				if (in_array($value,$array)) unset($this->words[$key]);
			}
			$this->words = array_values($this->words);
		} 
		return $this;
	}	
	
	/**	
	 * Разбор массива слов на части речи
	*/
	
	public function explore() {
		if (empty($this->words)) $this->words();
		foreach ($this->words as $word) {	
			$this->result[self::test_word($word)][] = $word;
		}
		return $this;
	}
	
	/**	
	 * Базовая обработка слова
	*/
	
	public static function prepare_word($word) {
		$word = trim(mb_strtolower($word));
		return str_replace('ё', 'е', $word);
	}
	
	/**	
	 * Определение части речи слова, вторым параметром передается необходимость интерпретации в текстовом виде
	*/
	
	public static function test_word($word,$interpretate=FALSE) {
		$result[0] = 'UNKN'; //результат по умолчанию: 'не определено'
		$word = self::prepare_word($word);
		$lenght = mb_strlen($word);
		foreach (self::LEMMS as $name=>$set) {			
			foreach ($set as $lemma) {
				$lemma_len = mb_strlen($lemma);
				$ver = round(($lemma_len/$lenght)*100); //доверительная вероятность каждого результата				
				switch ($name) {					
					case 'PARTS': //причастие
						if (mb_strpos($word,$lemma)>=(round(2*$lenght)/5)) {
							$result[$ver] = $name; //результаты храним в массиве, где длина совпадения леммы = доверительной вероятности
							break 2;
						}
					break;
					case 'UNIS': //союзы
						if (mb_substr($word,0,$lemma_len)==$lemma) {
							$result[$ver] = $name;
							break 2;
						}
					break;
					case 'PRES': //предлоги	
						if ($word == $lemma) {						
							$result[$ver] = $name;
							break 2;
						}
					break;
					default: //во всех остальных случаях					
						if ($word == $lemma or mb_substr($word,-mb_strlen($lemma)) == $lemma) {	
							$result[$ver] = $name;
							break;
						}
				}
			}
		}								
		ksort($result); //выбираем результат с наибольшей доверительной вероятностью		
		$result = array_pop($result); 
		$result = ($interpretate) ? self::TYPES[$result] : $result;
		return $result;	
	}	

	/**	
	 * Избавление слов из массива слов по типам от окончаний по словарю по массиву типов
	*/
	
	protected function remove_endings() {
		if(empty($this->result)) $this->explore();
		foreach ($this->result as $type=>$set) {
			foreach ($set as $key=>$word) {
				$this->result[$type][$key] = self::remove_ending($word,$type);				
			}
		}
		return $this;
	}
	
	/**	
	 * Избавление слов из массива слов по типам от окончаний по словарю по массиву слов
	*/
	
	protected function remove_words_endings() {
		if(empty($this->words)) $this->words();
		foreach ($this->words as $key=>$word) {
			$this->words[$key] = self::remove_ending($word);	
		}
		return $this;
	}
	
	/**	
	 * Обертка для методов избавления от окончаний, не чувствительная к типу обработки
	*/
	
	public function stemming() {
		if(!empty($this->result)) {
			$this->remove_endings();
		} else {
			$this->remove_words_endings();
		}
		return $this;
	}
	
	/**	
	 * Избавление слова от окончаний по словарю, второй параметр - указатель на тип слова
	 * Возвращает обрезанное слово
	*/
	
	public static function remove_ending($word,$type=FALSE) {
		$word = self::prepare_word($word);
		$w_end = '';
		if (empty($type)) $type = self::test_word($word);
		if ($type!=='UNKN' and $type!=='PARTS') { 
			foreach (self::LEMMS[$type] as $lemma) {
				if (mb_substr($word,-mb_strlen($lemma)) == $lemma) {
					$w_end = mb_substr($word,0, mb_strlen($word)-mb_strlen($lemma));
					break;
				}				
			}
			if (mb_strlen($w_end) == 0) $w_end = $word;
		} else {
			$w_end = self::stem($word);
		}
		return $w_end;
	}
	
	/**	
	 * Стемминг слова по алгоритму Мартина Портера
	*/
	
	public static function stem($word) {
		$stem = new \Stem\LinguaStemRu;
		return $stem->stem_word($word);
	}
	
	/**	
	 * Выделение значимого существительного из массива слов. 
	 * Если не найдено, возвращает NULL.	 
	*/
	
	public function subject() {
		$result = NULL;
		if(empty($this->result)) $this->explore(); //если нет никакого предварительного результата, не с окончаниями, ни без
		if (isset($this->result['NOUNS'][$this->deep])) {
			$result = $this->result['NOUNS'][$this->deep];
		}
		return $result;
	}
	
	/**	
	 * Выделение значимого прилагательного из массива слов. 
	 * Если не найдено, возвращает NULL.	 
	*/
	
	public function definition() {
		$result = NULL;
		if(empty($this->result)) $this->explore(); //если нет никакого предварительного результата, не с окончаниями, ни без
		if (isset($this->result['ADJS'][$this->deep])) {
			$count_nouns = count($this->result['NOUNS']); //количество существительных
			$count_adjs = count($this->result['ADJS']); //количество прилагательных
			$count_verbs = count($this->result['VERBS']); //количество глаголов
			$words = count($this->words); //длина фразы	
			foreach ($this->result['ADJS'] as $adj) {
				if (mb_substr($adj,-2) == 'ий' or mb_substr($adj,-2) == 'ый') {
					$result = $adj;
					break;
				}
			}
			$result = (empty($result)) ? $this->result['ADJS'][$this->deep] : $result;
		}
		return $result;
	}
	
	/**	
	 * Выделение значимого глагола из массива слов. 
	 * Если не найдено, возвращает NULL.	 
	*/
	
	public function predict() {
		$result = NULL;
		if(empty($this->result)) $this->explore(); //если нет никакого предварительного результата, не с окончаниями, ни без
		if (isset($this->result['VERBS'][$this->deep])) {	
			$result = $this->result['VERBS'][$this->deep];
		}
		return $result;
	}
	
	/**	
	 * Получение удаленного окончания слова сравнением строк (т.к. могут использоваться разные методы избавления от окончаний)
	*/
	
	protected function get_ending($word,$stem) {
		return trim(str_replace($stem,'',$word));
	}
	
	/**	
	 * Трансформация слова в другой тип
	 * Возвращает FALSE если преобразование невозможно,
	 * NULL - если преобразовать не удалось
	 * Наиболее вероятный вариант трансформации если list = false
	 * Массив всех преобразований (словоформ) если list = true
	 * word_type - прямое указание на тип обрабатываемого слова
	*/
	
	public static function transform($word,$type='ADJS',$list=FALSE) {
		$type_word = (!empty($word)) ? self::test_word($word) : NULL;
		if (($type_word == 'ADJS' or $type_word == 'NOUNS') and ($type == 'ADJS' or $type == 'NOUNS')) {
			$stem = self::remove_suffix($word,$type_word);
			$stem = self::add_suffix($stem,$type,$list);
		} else {
			$stem = FALSE;
		}
		return $stem;
	}
	
	/**	
	 * Трансформация слова из прилагательного в существительное  
	*/
	
	public function to_noun($list=FALSE) {
		$adj = $this->definition();	
		return (!empty($adj)) ? self::transform($adj,'NOUNS',$list) : NULL;
	}
	
	/**	
	 * Трансформация слова из существительного в прилагательное
	*/
	
	public function to_adj($list=FALSE) {
		$noun = $this->subject();	
		return (!empty($noun)) ? self::transform($noun,'ADJS',$list) : NULL;
	}
	
	/**	
	 * Комплексный глубокий анализ фразы, возвращает массив  преобразованных существительных и прилагательных
	 * Принимает глубину анализа или строку или bool - в этом случае глубина принимается относительно размера словаря. 
	 * Отдает массив уровней - образованных прилагательных и предметов - образованных существительных 
	*/
	
	public function analise($deep=0) {		
		if (!is_numeric($deep)) { //автоматический
			if(empty($this->words)) $this->words();
			$this->deep = round(count($this->words)/5);			
		}
		$return = array();
		for ($this->deep;$this->deep<=$deep;$this->deep++) {
			$return['level'][$this->deep] = $this->to_adj();
			$return['subject'][$this->deep] = $this->to_noun();
		}		
		$this->deep = 0;
		$return['level'] = array_diff($return['level'], array(null));
		$return['subject'] = array_diff($return['subject'], array(null));
		return $return;
	}
		
	/**	
	 * Рекурсивное убирание суффиксов по спискам вложенности. 
	 * Возвращает либо слово, либо массив.
	 * Если не найдено, возвращает NULL.	 
	*/
	
	public static function remove_suffix($word=NULL,$type='ADJS') {		
		$stem = self::remove_ending($word,$type); //убираем окончание
		foreach (self::SUFFIX[$type] as $suffix) {
			if (mb_substr($stem,-mb_strlen($suffix)) == $suffix) {
				$stem = str_replace($suffix,'',$stem);
				$stem = self::remove_suffix(self::stem($stem));
				break;
			}
		}			
		return $stem;
	}
	
	/**	
	 * Добавление окончаний к стэмму с проверкой на существование
	 * Второй параметр - получение всего списка образованных словоформ или одного наиболее вероятного
	 * Возвращает либо слово, либо массив.
	 * Если не найдено, возвращает NULL.	 
	*/
	
	public static function add_suffix($stem,$type='ADJS',$list=FALSE) {
		$return = NULL;
		$forms = self::forms($stem,$type);	
		if (!empty($forms)) {
			foreach ($forms as $form) {
				$data = Speller::get_data($form);
				if (is_array($data) and empty($data) and $list!==TRUE) {
					return $form;
				} else {
					if (!empty($data[0]->s[0])) {						
						foreach ($data[0]->s as $word) {
							$word = explode(' ',$word)[0];
							if (self::test_word($word)==$type) {	
								$return[] = ($type=='ADJS') ? $word : self::stem($word);
							}
						}
					}
				}
			}
		}			
		if (is_array($return)) {			
			$return = array_count_values($return);			
			if (max($return)>1) arsort($return);
			if ($list!==TRUE) {
				$return = array_keys($return);
				$return = array_shift($return);
			}
		} 				
		return $return;
	}
	
	/**	
	 * Образование "грязных" словоформ по типу от стемма 
	*/
	
	public static function forms($stem,$type) {
		$forms = NULL;
		if (is_string($stem)) {
			foreach (self::SUFFIX[$type] as $suffix) { 
				if ($type=='ADJS') {
					$forms[] = $stem.$suffix.'ий';
					$forms[] = $stem.$suffix.'ый';
				} else {
					$forms[] = $stem.$suffix;
				}
			}
		}
		return $forms;
	}
	
	
}
