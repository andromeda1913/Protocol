<?php
 
 
/*
 * Map Symbol  Map  :   
 * 2014 :   
 * pashkovdenis@gmail.com   
 * 
 */


class MapSymbol extends _Abstract {
	private $word_id;
	private $to_word;
	private $map;
	private $strength = 0.0;
	private $iterator = 0;
	public function __construct($word_id = null, $to_word = null, $map = NULL) {
		parent::__construct ();
		$this->table = "map_points";
		$this->word_id = $word_id;
		$this->to_word = $to_word;
		$this->map = $map;
		// random
		$this->strength = rand ( 0, 10 ) / 10;
	}
	public function createPoint() {
		return $this->generalInsert ( [ 
				"word" => $this->word_id,
				"to" => $this->to_word,
				"map" => $this->map,
				"strength" => $this->strength 
		] );
	}
	
	// learn new symbol ;
	public function learn($word, $map) {
		$point = $this->setSql ( "SELECT * FROM map_points WHERE map='{$map}' AND word = '{$word->id}' " )->load ();
		if (isset ( $point->id ))
			$this->setSql ( "UPDATE map_points SET strength = '" . ($point->strength + (($this->iterator / 10) * strlen ( $word->word ))) . "' , learn=1  WHERE id = '{$point->id}'  " )->exec ();
		
		$this->iterator ++;
	}
}