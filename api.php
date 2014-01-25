<?php 

  include "Bootstrap.php"  ;  
  $boot =  new Bootstrap() ;  
 
  
   if (isset($_GET["search"])){
   	 $req =  urldecode($_GET["search"]) ; 
   	 $result  =  $boot->ask($req) ;  
   	 if (is_array($result) && count($result) > 0  ){  
   	 	
   	 	echo $result[0] ;  
   	 	
   	 }else{
   	 	echo "Nothing Found" ;  
   	 } 
   	 
   	
   }
    
   
   //  Learn  
   if (isset($_POST['text'])){
   		$boot->learn($_POST['text']) ;  
   		echo "Ok"  ; 
   	 
   }