<?php
/*
 * This Class Provides   Flow Functionality :  
 * Protocol Engine   
 * pashkovdenis@gmail.com   
 * 2014  
 * 
 */

class FlowManager {
	
	public  $pdo  ; 
	private $sessions =  [];    
	
	public function __construct($db){ 
			$this->pdo =   $db ; 
 			$s = $this->pdo->query("SELECT * FROM training_sessions"); 
 			 while ( ! $s->EOF ) {
	 			  $this->sessions[$s->fields[0]] =   new TrainingSession($s->fields[0] , $this); 
 	 			 $s->moveNext(); 
			 }
 	}
 	 
 	
  	public function getSessionLists(){
 		return $this->sessions ;  		
 	}
  
	public function startSession($name="Noname"){
		$id  = new TrainingSession(false, $this,  $name) ;    
		$this->sessions[$id->id] = $id  ; 
		return $id; 
	}
  
	
	// Append New Data Into the Session   :   
	public function appendData($session_id=1, $input='' ,  $out='', $varianrs =  [] ){
		$session   =   $this->sessions[$session_id] ;  
		$object  = new TrainingSet( false, $this->pdo) ; 
		$object->user_ask  = $input   ; 
		$object->session_id  = $session_id ; 
		$object->system_answer =  $out ;   
		if (count($varianrs)) 
			$object->has_variant = true  ;  
		$object->variants = $varianrs ; 
		$object->save();  
		$session->addSet($object);
		return $this ; 
	}
	
	 
	public function editData($session_id =1 ,$set_id =1 , $data =  [] , $variants =  []    ){
		$session   =   $this->sessions[$session_id] ;   
		$set =  $session->getSet($set_id); 
		if ($set){
			$set->user_ask =  $data["user_ask"];  
			$set->system_answer =  $data["system_answer"]; 
			$set->variants =  $variants  ;  
			$set->save();  
			  
		}
		 return $this;  
	}
	
	
	// return specified  Set for   Edit
	public function closeSession(){
		foreach($this->sessions as $s)
			$s->learn();
		return $this ;
	}
	  	
	
}
