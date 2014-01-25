<?php 

 /*
 * Basic Interface  For Commands :  
 * Protocol@2014   
 *  
 */


interface commandInterface {
 
	public function setDBo($dbo);   
    public function setInput($string);  
	public function setOutput($thoudId);   
	public function execute($queryID= null );  
 }

