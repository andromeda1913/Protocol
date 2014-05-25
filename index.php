<?php
 
 ini_set("display_errors","1");  
 error_reporting(E_ALL);  
 //   

 include "Bootstrap.php"  ;  
   $boot =  new Bootstrap() ;  
  
if (isset ( $_POST ["req"] ))  
 $answer = 	$boot->ask( $_POST ["req"]  )  ;  

 foreach($answer as $an) 
echo  "<p>" .  $an . "</p>"  ; 

 
if (isset ( $_POST ["learn"] )) {
	 $boot->learn(  $_POST ["learn"] ) ;  
	 
} 




?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title>Test</title>
</head>
<body>

	<h3>Req :</h3>
	<form method='post'>
		<input type='hidden' name='req' value='1' /> <input type='text'
			name='req' />

	 

	</form>

	<hr>
	
	
	
	<h3>Learn :</h3>

	<form method='post'>
	        <input type='hidden' name='learn' value='1' />
			<textarea name='learn'></textarea>
		<br>

		<button type='submit'>Learn</button>


	</form>

	<hr>
	<p>Tex:</p>

</body>
</html>