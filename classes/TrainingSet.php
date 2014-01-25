<?php
 /*
 * Single Trainng Set   
 * Protocol  Engine  
 * Pashkovdenis@gmail.com   
 * 2014    ;  
 * 
 */

 class TrainingSet {
	
		public  $id ;  
		public  $session_id ;   
		public  $user_ask  ; 
		public  $system_answer ;  
		public  $variants  =  []  ;     
		public  $has_variant  = false ; 
		private $pdo  ; 
 	  
		public function __construct($set_id =false , $dbo )  {  
			if (!$set_id){
				// insert new one  
				$dbo->query("INSERT INTO training_set SET user_input=''   ");  
				$set =   $this->pdo->query("SELECT * FROM training_set ORDER BY id DESC  LIMIT 1");  
				$set_id =  $set->fields[0]["id" ] ; 
				
			} 
			$this->id  =   $set_id ;  
			$this->pdo  =   $dbo ;   
		  	$set =   $this->pdo->query("SELECT * FROM training_set WHERE id='{$this->id}' LIMIT 1");   
		    $this->session_id  =  $set->fields[0]["session_id"] ;  
		    $this->user_ask  =  $set->fields[0]["user_input"];  
		    $this->system_answer =   $set->fields[0]["system_answer"] ;  
			$variants  =  $this->pdo->query("SELECT * FROM training_set  WHERE for_set ='{$this->id}'  ");  
		 	if (count($variants->fields)) 
				 $this->has_variant  = true ;   
			 	while(!$variants->EOF){
					 $this->variants[$variants->fields["user_input"]] =   $variants->fields["system_answer"] ;  
					$variants->moveNext(); 
				}
		     
		}
	  
	 
		public function save(){
		    	$has_v  =  0 ;   
			 	if (count($this->has_variant))  
			 		$has_v = 1; 
			 	   $this->pdo->query("UPDATE training_set SET user_input='{$this->user_ask}'  , system_answer='{$this->system_answer}'  , has_variants='{$has_v}' ,  for_set=''   WHERE id = '{$this->id}'  ");
				 if ($has_v){
			 	 	$this->pdo->query("DELETE FROM training_set WHERE for_set ='{$this->id}'  ");   
			 	 	foreach ($this->variants as $user_in=>$answer){
			 	 		$this->pdo->query("INSERT INTO training_set SET user_input='{$user_in}'  , system_answer='{$answer}' , for_set='{$this->id}' "); 
			 	 	 }
			 	 }
			 return $this ; 
		}
		

		 
		
		
		/*
		 * Insert Data  into  Mind  
		 * 2014  
		 * pashkovdenis@gmail.com 
		 * 
		 */
		 
		public function learn(){
			 $mind = new mind() ; 
			 $mind->setMode(mind::$MODE_DIALOG)  ;
			 $mind->setSessionId($this->session_id) ;  
			
			if ($this->user_ask  && $this->system_answer)
			$mind->learnThoughtPreweighted (  $this->user_ask, $this->system_answer) ;  
			  
			if (count($this->variants)){
				$mind->setVariant(true) ;  
				foreach($this->variants as $req => $answer){  
					if ($req && $answer)
					$mind->learnThoughtPreweighted (  $req, $answer) ;
				 }	
			 }
			 return $this;  
	 	}
		  
		
		 
	
	}

