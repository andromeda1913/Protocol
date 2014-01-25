<?php
session_start ();
include_once './defines.php';
mb_internal_encoding ( 'UTF-8' );

/*
 * Bootstrap Class For The Engine 2014 pashkovdenis@gmail.com
 */ 



class Bootstrap {
	private $mind;
	private $mindmeld;
	private $baseDir;
	
	// Create Mind Engine
	public function __construct() {
		global    $MM_GLOBALS  ;  
		
		$this->mindmeld = new mindmeld ( NULL, $MM_GLOBALS ['dbType'], $MM_GLOBALS ['dbHost'], NULL, $MM_GLOBALS ['dbName'], $MM_GLOBALS ['dbUser'], $MM_GLOBALS ['dbPass'] );
		$this->mind = new mind ();
	}
	
	/*
	 * Ask pashkovdenis@gmail.com 2014
	 */
	public function ask($string = "") {
		$result = [ ];
		$queryId = $this->mind->ask ( $string );
		if ($queryId) {
			$results = $this->mind->answers ( $queryId );
			foreach ( $results as $answer )
				$result [] = $answer [4];
			return $result;
		} else
			$result [] = $this->mind->getCommandsReposne ();
		return false;
	}
	
	/*
	 * LEarn pashkovdenis@gmail.com 2014
	 */
	public function learn($string = "") {
		return $this->mind->learnThoughtPreweighted ( $string, $string, "" );
	}
}  

 

