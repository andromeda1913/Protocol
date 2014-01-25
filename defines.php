<?php

/*
 * Protocol  Engine   
 * Mia Project 
 * pashkovdenis@gmail.com   
 * 
 */ 


date_default_timezone_set("Europe/Moscow");
 
// MM_GLOBALS 
$MM_GLOBALS = [] ; 
$MM_GLOBALS['home'] = __DIR__."/";
$MM_GLOBALS['cache'] = dirname(__FILE__).'/cache/';
$MM_GLOBALS['adodb'] =  __DIR__. '/adodb/';
$MM_GLOBALS['dbType'] = 'mysql';
$MM_GLOBALS['includes'] = __DIR__."/include/" ;
$MM_GLOBALS['dbUser'] = 'root';
$MM_GLOBALS['dbPass'] = '123123';
$MM_GLOBALS['dbHost'] = 'localhost';
$MM_GLOBALS['dbName'] = 'mindmeld';
$MM_GLOBALS['dbPort'] = '';
$MM_GLOBALS['sql_cache_dir'] = $MM_GLOBALS['cache'] ; 
$MM_GLOBALS['sqlDebug'] = false;
$MM_GLOBALS['commands'] = [] ; 
 
$_COOKIE['search'] = session_id() ; 
mysql_connect($MM_GLOBALS['dbHost'],$MM_GLOBALS['dbUser'],$MM_GLOBALS["dbPass"]) ; 
mysql_select_db($MM_GLOBALS['dbName']) ; 
 
define("HOST", "localhost") ;  
define("USER","root") ;  
define("PASS","123123");
define("DATABASE", "mindmeld"); 
define("THRESHOLD",  70) ; 
define("LOG",   0 ); 


require_once( $MM_GLOBALS['home'] . 'include/utilities.inc' );
require_once( $MM_GLOBALS['home'] . 'include/mind.inc.php' );
require_once( $MM_GLOBALS['home'] . 'classes/FlowManager.php' );  

/* * 								  
 * Load Commands From  Command Folder   
 * */    
$files = scandir($MM_GLOBALS['home']."/commands");  
require_once  $MM_GLOBALS['home']."/commands/commandInterface.php" ; 
foreach($files as $f){
 if (strstr($f, ".php")){
  
		if (strstr($f, "_command")) 
		{ 	  	require_once  $MM_GLOBALS['home']."/commands/". $f ;  
			$class =  explode("_", $f);   
			$MM_GLOBALS['commands'][]  = new $class[0];  
		 }
	}
 	
} 
 
// classes Files Requered  
$files = scandir($MM_GLOBALS['home']."/classes");  
  foreach($files as $f)
	if (strstr($f, ".php")) 
 	require_once  $MM_GLOBALS['home']."/classes/". $f ;
	 
 