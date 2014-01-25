<?php

/*
 * Learn New Thigns By  Input   
 * pashkovdenis@gmail.com  
 * 2014 
 * 
 */
 
class Learn implements commandInterface {
 	
	private $dbo;
	private $input;
	private $out = true;
	public $raw = " ";
	public $separators =  ["{break}" , "{are}" ,"{was}" ,"{has}" ,"{ineed}"]; 
	
	public function setDBo($dbo) {
		$this->dbo = $dbo;
		return $this;
	}
	 
	/*
	 * Set User Input Here   
	 * pashkovdenis@gmail.com  
	 * 2014 
	 *    
	 * 
	 */
	 
 	public function setInput($string) { 
		 
		$this->input = $string;
	  	$string =   mb_strtolower($string); 
		$raw_words  =   explode(" ", $string);   
		$separator  = false;    
		$founded =  0 ;    
		if (strstr($string,  "learn")){
			 $_SESSION["learn"] =  1;    
		}
	 
  		 foreach($raw_words as $w){ 
		 	$word = new Word($w) ;  
		 	foreach($this->separators as $s) 
		 		if ($word->is2(  (new Word($s))->id  )){
		 			$separator =   $w ;  
		 		  	$founded  ++   ;  	
		 		}
		  }   
		   
		 if ($separator && $founded == 1 &&  isset($_SESSION["learn"]) ){
		 	   $parts  = explode($separator, $string) ;  
				$summary =    $parts[0];  
					unset($parts[0]); 
			 	   $detail  =  join(" ", $parts);  
				  $mind   = new mind() ;  
	 	 	      $mind->learnThoughtPreweighted( $summary,  $detail) ;  
	 		 	unset( $_SESSION["learn"]); 
		 }
		 
		 
		
		return $this;
	}
	  
	
	//  set Output   
	public function setOutput($thoudId = true) {
		$this->out = $thoudId;
		return $this;
	}
	
	
	public function execute($queryID = null) {
		return $this->out;
	}
}