<?php

/*
 * word object 2014 pashkovdenis@gmail.com
 */
class Word extends _Abstract {
	public $id;
	private $word_raw;
	public static $POSIBLE_MATCH = 98;
	public $dbo;
	public static $symbols = [ 
			"!",
			"@",
			"#",
			"$",
			"%",
			"^",
			"&",
			"*",
			"(",
			")",
			",",
			"+",
			"-",
			" " 
	];
	public $word;
	private $synonims = [ ];
	
	/*
	 * New Word Conbstructor   
	 * pashkovdenis@gmail.com  
	 * 2014  
	 * 
	 */
	public function __construct($word) {
		
   
		$this->table = "words";
		$this->word = (trim ( strtolower ( $word ) ));
		$patterns = $this->setSql ( "SELECT * FROM regular  " )->loadList ();
		foreach ( $patterns as $p ) {
			if (preg_match ( $p->regular, $this->word ))
				$word = $p->word;
		}
		
		if (is_numeric ( $word ))
			$word = "[number]";
		
		foreach ( self::$symbols as $s ) {
			if ($word == $s) {
				$word = "[symbol]";
				break;
			}
			$word = str_replace ( $s, "", $word );
		}
		$this->word = $word;
		
		
		$w = $this->setSql ( " SELECT id FROM words WHERE LOWER(word) LIKE LOWER('{$word}')     ORDER BY id DESC      " )->load ();
 		if (isset ( $w->id ))
			$this->id = $w->id;
 		
 		
 		//  Try to find  in  Synonimns :   
 		if (!$this->id){
 			$w = $this->setSql ( " SELECT id FROM words WHERE  LOWER(synonims) LIKE LOWER('%,{$word},%')   ORDER BY id DESC      " )->load ();
 			if (isset ( $w->id ))
 				$this->id = $w->id;
 		 }
		 	
		
		if (! $this->id)
			$this->id = $this->generalInsert ( [ 
					"word" => $this->word,
					"synonims" => "," . $this->word . "," 
			] );
		$this->synonims = explode ( ",", $this->selectBy ( "id", $this->id )->synonims );
	}
	
 
	public function addSynonim($syn = "") {
		$obj = $this->setSql ( "SELECT * FROM words WHERE id='{$this->id}' " )->load ();
		if (empty( $obj->synonims )) 
			$obj->synonims =  ",";
		$syn = $obj->synonims . "" . $syn . ",";
		$this->updateBypost ( [ 
				"id" => $this->id,
				"synonims" => $syn 
		] );
		return $this;
	}
	public function loadWord() {
		return $this;
	}
	public function setWord($string) {
		$this->word_raw = $string;
		$this->word = $string;
		return $this;
	}
	
	public function is2($id) {
		$word = $this->setSql ( "SELECT * FROM  words WHERE id='{$id}'  " )->load ();  
	 
		similar_text(trim(mb_strtolower($word->word)), trim(mb_strtolower($this->word)), $m);  
		
	 
		if ($word->word == $this->word|| $m >=self::$POSIBLE_MATCH)
			return true;
		
		
		$syns = explode ( ",", $word->synonims );
		foreach ( $syns as $s ) {
		 
			
			if ($s != "") {
				if (in_array ( $s, $this->synonims )) {
					return true;
				}
			}
		}
	
		return false;
	}
	
	 
	
	
	// Is
	public function is($syn) {
		if ($syn == $this->word)
			return true;
		
		foreach ( $this->synonims as $s ) {
			if ($s != "" && $s == $syn) {
				return true;
			}
		}
		return false;
	}
}
 