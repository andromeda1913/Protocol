<?PHP
 
class p50_dream_resolveQueries extends plugin {
	
    /**
    * Dream-time utility to resolve all queries, manage statuses, clean up.
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		$queryAge = $dream->readParam( 'stale_query_age' );
		
		// Connect to the database and execute the query
		$dbconn = $dream->dbconn;
		$dbconn->Query("UPDATE flow SET position=0 , open=0"); 
		 
		// These status queries must execute in the specified order
		
		// Set queries containing "unsure" decisions to "open pending"
		$attrib = array( 	'%QSTATUS%' => 'OPEN_PENDING',
							'%DSTATUS%' => 'UNSURE',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by decision', $attrib );
		
		// Set queries containing "rejected" decisions to "closed no"
		$attrib = array( 	'%QSTATUS%' => 'CLOSED_NO',
							'%DSTATUS%' => 'REJECTED',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by decision', $attrib );
		
		// Set queries containing "accepted" decisions to "closed yes"
		$attrib = array( 	'%QSTATUS%' => 'CLOSED_YES',
							'%DSTATUS%' => 'ACCEPTED',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by decision', $attrib );
		
		// Abort stale "new" queries
		$attrib = array( 	'%OLDSTATUS%' => 'NEW',
							'%NEWSTATUS%' => 'CLOSED_ABORTED',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by query', $attrib );
		
		// Close stale "open" queries
		$attrib = array( 	'%OLDSTATUS%' => 'OPEN',
							'%NEWSTATUS%' => 'CLOSED',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by query', $attrib );
		
		// Remove null or empty decisions.
		// This MUST be done before inserting the query history.
		$record = $dream->getAction( 'clear empty decisions' );
		
		// Move all closed queries and decisions to query_history
		$record = $dream->getAction( 'insert query history' );
		
		// Delete all closed queries and decisions, and stimuli
		$record = $dream->getAction( 'clear stimuli' );
		
		$record = $dream->getAction( 'clear decisions' );
		
		$record = $dbconn->Execute( "DELETE FROM Query WHERE status LIKE 'CLOSED%'" );
		
		$event = new mmEvent;
		$event->dream( get_class( $this ) );
		return NULL;
	}
}
	
 /**
* Dream-time utility to follow up on all queries with a status of "PENDING."
*/
class p50_dream_followupPending extends plugin {
	
    /**
    * Dream-time utility to follow up on all queries with a status of "PENDING."
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		$queryAge = $dream->readParam( 'stale_query_age' );
		
		// Connect to the database and execute the query
		$dbconn = $dream->dbconn;
		
		// These status queries must execute in the specified order
		
		// Change pending status to closed.
		$attrib = array( 	'%OLDSTATUS%' => 'OPEN_PENDING',
							'%NEWSTATUS%' => 'CLOSED_PENDING',
							'%DELAY%' => $queryAge );
		$record = $dream->getAction( 'update query states by query', $attrib );
		
		$event = new mmEvent;
		$event->dream( get_class( $this ) );
		return NULL;
	}
}

/**
* Dream-time utility to negatively reinforce neurons that were rejected in 
* searches
*/
class p50_dream_reinforceNeurons extends plugin {
	
    /**
    * Dream-time utility to negatively reinforce neurons that were rejected in 
 	* searches.
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		/**
		* High-level mind library
		*/
		include_once( $dream->mmconfig['home'] . 'include/mind.inc.php');
		
		$mind = new mind();
		$mind->reinforceMemory( NULL, NULL, "NEGATIVE" );
		
		$event = new mmEvent;
		$event->dream( get_class( $this ) );
		return NULL;
	}
}
?>