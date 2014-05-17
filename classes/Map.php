<?php
class Map extends _Abstract {
	private $break_points = [ 
			" ",
			".",
			",",
			"@",
			"?",
			"!",
			"(",
			")",
			"{",
			"}" 
	];
	public $id;
	private $user;
	private $word_string = [ ];
	private $strong = 0.0;
	public static $TYPE_MAPE_EXTRACT = 1;
	public static $TYPE_MAPE_TRANSFORM = 2;
	
	// Some dCusntructor :
	public function __construct($user = 0) {
		parent::__construct ();
		$this->user = $user;
		$this->table = "maps";
	}
	
	// setString
	public function setString($string) {
		$string = strtolower ( trim ( $string ) );
		$words = explode ( " ", $string );
		$words_objects = [ ];
		foreach ( $words as $w )
			if ($w != "")
				$words_objects [] = new Word ( $w );
		if (count ( $words_objects ) > 0)
			$this->word_string = $words_objects;
		
		return $this;
	}
	public function getWords() {
		return $this->word_string;
	}
	
	// Create new Map From object String
	public function createMap() {
		if (count ( $this->word_string ) == 0)
			throw new \Exception ( "Empty input" );
	 
		
		$this->id = $this->generalInsert ( [ 
				"date" => time (),
				"user" => $this->user,
				"type" => self::$TYPE_MAPE_EXTRACT,
				"strength" => $this->strong,
				"length" => count ( $this->word_string ) 
		] );
		
		// insert symbols
		$symbols = [ ];
		$multi  = 1; 
		
		foreach ( $this->word_string as $p => $word ) {
			$to = null;
			if (isset ( $this->word_string [$p + 1] ))
				$to = $this->word_string [$p + 1]->id;
			$s = new MapSymbol ( $word->id, $to, $this->id , $multi);
			$s->createPoint ();
			$multi ++  ;  
		}
		
		
		
		return $this->id;
	}
	// Teach Map For Select Specified Words from String
	public function setSelectionForMap($mapid = null, $str) {
		$map_id = $mapid == null ? $this->id : $mapid;
		$words = explode ( " ", $str );
		$c = count ( $words );
		
		if ($c) {
			$this->updateBypost ( [ 
					"id" => $map_id,
					"select_length" => $c 
			] );
			$mapS = new MapSymbol ();
			foreach ( $words as $index => $w )
				$mapS->learn ( new Word ( $w ), $map_id );
		} else {
			echo "Count of Symbols is Null ";
		}
	}
	private function cleanArray($array) {
		$newArray = array ();
		foreach ( $array as $key => $val ) {
			if (isset ( $array [$val] ) && $array [$val] == $key) {
				if (! isset ( $newArray [$key] ) && ! isset ( $newArray [$val] )) {
					$newArray [$key] = $val;
				}
				unset ( $array [$key], $array [$val] );
			}
		}
		return array_merge ( $array, $newArray );
	}
	
	
/*__________________
 * Extract   
 * From Map  String :  
 * __________________
 * 
 */
	public function extract($string, $mapid) {
		
		if ($mapid == 0 || empty ( $mapid ))
			return $string;
		
		$words = explode ( " ", $string );
		$word_object = [ ];
		$total = 0;
		foreach ( $words as $w )
			$word_object [] = new Word ( trim ( $w ) );
		
		$result = [ ];
		$count = count ( $words );
		$extracted = [ ];
		$maps = $this->setSql ( "SELECT * FROM maps  WHERE  id = '{$mapid}' ORDER by length DESC  " )->loadList ();
 		foreach ( $maps as $map ) {
			$learned = $this->setSql ( "SELECT * FROM map_points WHERE learn = 1 AND  map='{$map->id}' ORDER BY strength   DESC  " )->loadList ();
		
			  
			foreach ( $learned as $l ) {
				$ex = [ ];
				$points = 0; 
 				
				foreach ( $word_object as $i => $word ) {
					  
					 
					if ($word->is2 ( $l->word )) {
			    		$ex [] = $words [$i];
						$points += $l->strength;
						$total += $points + $i;
					}
				}
				$extracted [ ] = $ex;
			}	
  
		 	 
			
			$total = 0;
			$t = [ ];
			foreach ( $extracted as $p => $word ) {
				$total += $p;
				$t [] = array_shift ( $word );
			}
			
	 
			
			$result [$total] = $t;
		}
		
		
		
	 
		
		arsort ( $result, SORT_ASC );
		$esc = strlen ( $string ) / 100 * $total;
		
		foreach ( $result as $index => $wordArrays ) {
			if ($index < $esc) {
				unset ( $result [$index] );
				continue;
			}
			$result [$index] = array_unique ( $wordArrays );
			foreach ( $result [$index] as $k => $v )
				if ($v == "")
					unset ( $result [$index] [$k] );
		}
		
		foreach ( $result as $k => $r )
			$result [$k] = array_reverse ( $r );
		
		return $result;
	}
	
		 
	
	
	 /*
	  * Static MEthods THat   Will REturn   MAps :   
	  * Generalized Maps : 
	  * pashkovdenis@gmail.com   
	  *   
	  */  
	
	public static function  getGeneralMaps($string){
 		$maps  = [] ; 
		$words_ids = [];   
		$len =  strlen($string);   		
 		$string =  trim(mb_strtolower($string));  
		$words_strings =  explode(" ",  $string);     
		$min =  (int)  ((count($words_strings)/100) * 50)  ;  
 		$maps_count =  (new self())->setSql("SELECT FLOOR(AVG(length)) as c  FROM  maps ORDER BY length DESC     ")->load()->c; 
  	 	$min= $maps_count/2; 
  		foreach($words_strings as $sw)   
			 $words_ids[] =   (new Word($sw))->id ;   
			if  (count($words_ids)){
			 $sql =  "SELECT *  FROM maps as m LEFT JOIN  map_points as mp on mp.map =  m.id  WHERE mp.word IN (".join(",",$words_ids).")    GROUP BY mp.map  having COUNT(mp.id) > {$min}   ORDER BY m.select_length ASC               "; 
	
			 $maps2 = (new self())->setSql($sql)->loadList() ;  
 	   	 	foreach ($maps2 as $M) 
		 		$maps[] =  $M->map; 
		 }
		return $maps ; 
	}
	
	
	
	
	
	
	
	
}