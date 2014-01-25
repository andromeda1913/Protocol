<?PHP
 
class p50_dream_forgetThoughts extends plugin {
	
    /**
    * Dream-time utility to remove any thoughts with a status of "DELETED" from
 	* the database..
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		/**
		* Main learning library
		*/
		include_once( $dream->mmconfig['home'] . 'include/cortex.inc.php');
		
		$forget = new thought();
		$numRemoved = count( $forget->removeThought() );
		
		$msg = "Purged $numRemoved deleted thoughts";
		$event = new mmEvent;
		$event->dream( get_class( $this ), $msg  );
		$dream->logMsg( sprintf ( "<<< forgetThoughts (%s)", $numRemoved ), MM_ALL );
		
		return NULL;
	}
}
?>