<?PHP
 
require_once ($MM_GLOBALS ['home'] . "include/utilities.inc");

/**
 * class query
 *
 * Queries are used to submit seraches to the brain. A query is basically a serch
 * string, but also maintains state information about a search until it is
 * resolved.
 */
class query extends _brainObject {
	
	/**
	 *
	 * @var string Search text for query
	 */
	var $_text;
	
	/**
	 *
	 * @var string Email address of the query requestor
	 */
	var $_email;
	
	/**
	 *
	 * @var string Status of query
	 */
	var $_status;
	
	/**
	 * Class constructor
	 *
	 * Creates the object, and can optionally accept configuration and
	 * sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 *
	 * TODO: Initialize the status from fundamentals
	 */
	function query() {
		_brainObject::_brainObject ();
		
		// Initialize the query
		$this->_status = "NEW";
	}
	
	/**
	 * Set the text of the query
	 *
	 * @param string $queryText
	 *        	of query
	 * @return string of query
	 */
	function setQueryText($queryText) {
		$this->_text = $queryText;
		$this->logMsg ( "setQueryText", MM_ALL );
		return $this->_text;
	}
	
	/**
	 * Get the text of a query
	 *
	 * Returns the text query string
	 *
	 * @return string text
	 */
	function getQueryText() {
		return $this->_text;
	}
	
	/**
	 * Set the requestor's email address for the query
	 *
	 * @param string $email
	 *        	address
	 * @return string address
	 * @deprecated
	 *
	 */
	function setEmail($email) {
		$this->_email = $email;
		$this->logMsg ( sprintf ( "setEmail", $email ), MM_ALL );
		return $this->_email;
	}
	
	/**
	 * Get the requestor's email address
	 *
	 * @return string address
	 */
	function getEmail() {
		return $this->_email;
	}
	
	/**
	 * Set a unique ID for this query
	 *
	 * Sets a unique OID for this query object to the requested ID. This
	 * is only used for dealing with existing objects, since IDs for
	 * new objects are generated automagically in the databse.
	 *
	 * @param integer $queryId        	
	 * @return integer ID
	 */
	function setQueryId($queryId) {
		return $this->_setReferenceId ( $queryId );
	}
	
	/**
	 * Get the ID of this query
	 *
	 * Returns a unique OID for this thought object. The OID is
	 * automatically set by calling the keyReference object.
	 * If an OID is passed as a parameter, that OID is
	 * set as the ID for the current instance.
	 *
	 * @return integer ID
	 */
	function getQueryId() {
		return $this->_getReferenceId ();
	}
	
	/**
	 * Set the datestamp of this query
	 *
	 * Sets the datestamp on this query object to the specified date and
	 * time. If no argument is specified, the datestamp is set to the
	 * current date and time.
	 *
	 * @param datetime $interacted        	
	 * @return datetime if successful, else NULL.
	 */
	function setCreateDate($interacted = "") {
		return $this->setInteracted ( $interacted );
	}
	
	/**
	 * Get the datestamp of this query
	 *
	 * Returns the datestamp from this object. The datestamp specifies the
	 * creation time of the object, or the last time it was updated.
	 *
	 * @return datetime of object
	 */
	function getCreateDate() {
		return $this->getInteracted ();
	}
	
	/**
	 * Write a current query to the database.
	 *
	 * Writes the current query object into the database. Uses adodb's
	 * insertSQL function.
	 *
	 * @param string $text        	
	 * @param string $status        	
	 * @param string $metaquery        	
	 * @return integer of successfully-written query, else NULL
	 */
	function insertQuery($text = '', $status = '', $metaquery = '') {
		$metaquery = session_id ();
		$this->logMsg ( ">>> insertQuery", MM_ALL );
		
		$returnCode = NULL;
		
		// Update the values for the new record to the values of the
		// object.
		$newRecord = array ();
		$newRecord ["queryid"] = $this->getQueryId ();
		$newRecord ["email"] = $this->getEmail ();
		$newRecord ["created"] = $this->setCreateDate ();
		$text ? ($newRecord ['text'] = $text) : ($newRecord ['text'] = $this->getQueryText ());
		$status ? ($newRecord ['status'] = $status) : ($newRecord ['status'] = $this->getStatus ());
		$metaquery ? ($newRecord ['metaquery'] = $metaquery) : ($newRecord ['metaquery'] = '');
		
		If (! $text) {
			// No query text provided
			$this->logMsg ( "Attempt to insert empty query", MM_WARN );
			return FALSE;
		}
		
		// Connect to the database and execute the query
		$dbconn = &$this->dbconn;
		$record = $dbconn->Execute ( "SELECT * FROM Query WHERE queryId = -1" );
		
		// Insert the new record in place of the old.
		$dbconn->StartTrans ();
		$insertSQL = $dbconn->GetInsertSQL ( $record, $newRecord );
		$dbconn->Execute ( $insertSQL );
		
		// Fetch the max query ID (if we're in a transaction, this will be the
		// query we just entered).
		$dbtimestamp = $dbconn->DBTimeStamp ( $newRecord ['created'] );
		$sql = "SELECT queryId FROM Query " . "WHERE metaquery = '$metaquery' AND created = $dbtimestamp";
		$recordSet = $dbconn->Execute ( $sql );
		$dbconn->CompleteTrans ();
		
		if (! $dbconn->CompleteTrans ()) {
			// Transaction failed.
			$this->logMsg ( "Failed to commit transaction", MM_ERROR );
			$returnCode = 0;
		} else {
			$this->logMsg ( "Transaction comitted", MM_INFO );
			// Transaction succeeded or is not supported.
			// Now verify we got a row back.
			if ($recordSet->EOF) {
				// Transaction failed.
				$this->logMsg ( "Error during validation", MM_ERROR );
				$returnCode = 0;
			} else {
				// Got a query ID
				$returnCode = $this->setQueryId ( $recordSet->fields [0] );
			}
		}
		$this->logMsg ( sprintf ( "<<< insertQuery (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Update a query in the database
	 *
	 * Fetches a query from the database given an ID, updates the
	 * query with any non-NULL values from the object, and then writes
	 * the changed query back into the databsae.
	 *
	 * @param integer $queryId
	 *        	query to update
	 * @param integer $status
	 *        	status
	 * @return integer of successful query, else 0
	 */
	function updateQuery($queryId = NULL, $status = NULL) {
		$this->logMsg ( ">>> updateQuery", MM_ALL );
		
		// Connect to the database and execute the query
		$dbConn = &$this->dbconn;
		
		if (! $queryId)
			$queryId = $this->getQueryId ();
		if (! $status)
			$status = $this->getStatus ();
		
		if (! $queryId) {
			$this->logMsg ( sprintf ( "Unable to uniquely identify query (queryId=%s)", $queryId ), MM_ERROR );
		}
		
		$record = $dbConn->Execute ( "SELECT * FROM Query WHERE queryId = $queryId" );
		
		// Update the values for the record to the values of the object
		$updateRecord = array ();
		if ($this->getQueryText ())
			$updateRecord ["text"] = $this->getQueryText ();
		if ($this->getEmail ())
			$updateRecord ["email"] = $this->getEmail ();
		if ($this->getStatus ())
			$updateRecord ["status"] = $this->getStatus ();
			// TODO: Validate datestamp is updated
			
		// Update the database recordord )
		$updateSQL = $dbConn->GetUpdateSQL ( $record, $updateRecord );
		if ($updateSQL)
			$dbConn->Execute ( $updateSQL );
		
		$retQueryId = $this->getQueryId ();
		$this->logMsg ( sprintf ( "<<< updateQuery (%s)", $retQueryId ), MM_ALL );
		return $retQueryId;
	}
	
	/**
	 * Submit a query for evaluation
	 *
	 * Submits a new query for evaluation. Creates stimuli from the query
	 * text. Calls the database "think" procedure to evaluate the stimuli
	 * and populate the decision table.
	 *
	 * @return integer ID of submitted query, else 0.
	 */
	function submitQuery() {
		$this->logMsg ( ">>> submitQuery", MM_ALL );
		
		// Verify the query is sufficently defined.
		if ($this->getQueryText () == NULL) { // Bad query
			$this->logMsg ( "Attempt to insert improperly-defined query", MM_ERROR );
		} else { // Handle the query
		         // Write query to database
			$submittedQuery = $this->insertQuery ();
			$returnCode = $submittedQuery;
			
			// Parse query into stimuli
			$stimulusList = split ( "[^a-zA-Z0-9\']", $this->getQueryText () );
			
			// Create each stimulus and write it into the database.
			foreach ( $stimulusList as $rawStimulus ) { // Add the new stimuli
			                                            
				// build stimulus object.
				$newStimulus = new stimulus ();
				$stimulusText = $newStimulus->setStimulus ( $rawStimulus );
				$stimulusQuery = $newStimulus->linkToQuery ( $submittedQuery );
				
				// Write stimulus to database.
				$addedStimulus = $newStimulus->insertStimulus ();
				if ($addedStimulus === NULL) { // Error writing stimulus to database
					$this->logMsg ( sprintf ( "Error inserting stimulus '%s'", $stimulusText ), MM_ERROR );
					$addedStimulus = $stimulusText;
				} else { // Stimulus added to database
					$this->logMsg ( sprintf ( "Added stimulus '%s' to query %s", $addedStimulus, $submittedQuery ), MM_INFO );
				}
			}
			
			// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			$attrib = array (
					'%QUERYID%' => $submittedQuery 
			);
			$record = $this->getAction ( 'think', $attrib );
		}
		$this->logMsg ( sprintf ( "<<< submitQuery (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
}

/**
 * class stimulus
 *
 * The stimulus object represents an atom of query information. It can consist
 * of a short text string (no spaces or special characters) or eventually, any
 * MIME type.
 *
 * The stimulus is matched against the enabled symbols to find thoughts that
 * are likely matches.
 */
class stimulus extends symbol {
	
	/**
	 *
	 * @var string Query which owns this stimulus
	 *     
	 * @access private
	 */
	var $_query;
	
	/**
	 * Class constructor
	 *
	 * Creates the stimulus object, and can optionally accept configuration
	 * and sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 */
	function stimulus() {
		symbol::symbol ();
	}
	
	/**
	 * Link the stimulus to a query
	 *
	 * Sets the query object that owns this stimulus.
	 *
	 * @param string $queryId
	 *        	ID
	 * @return string or 0 if error
	 */
	function _setQuery($queryId) {
		$this->_query = $queryId;
		$this->logMsg ( sprintf ( "_setQuery", $queryId ), MM_ALL );
		return $this->_query;
	}
	
	/**
	 * Get the ID of query that owns this stimulus
	 *
	 * Returns the ID of the query object that owns this stimulus.
	 *
	 * @return string
	 */
	function _getQuery() {
		return $this->_query;
	}
	
	/**
	 * Set a query stimulus
	 *
	 * Accepts a stimulus (symbol) text as an argument, removes
	 * non-permitted characters, and sets the symbol for the object.
	 *
	 * Permitted text is any alphanumeric character, a hyphen, or an
	 * apostrophe. Anything else (including backslashes) is stripped out.
	 *
	 * @param string $stimulus
	 *        	symbol string
	 * @return string text, or NULL if error
	 */
	function setStimulus($stimulus) {
		return $this->setSymbol ( $stimulus );
	}
	
	/**
	 * Get a query stimulus
	 *
	 * Returns the stimulus string for this object
	 *
	 * @return string text
	 */
	function getStimulus() {
		return $this->getSymbol ();
	}
	
	/**
	 * Link the stimulus to a query
	 *
	 * Sets the query object that owns this stimulus.
	 *
	 * @param string $queryId
	 *        	ID
	 * @return string or 0 if error
	 */
	function linkToQuery($queryId) {
		return $this->_setQuery ( $queryId );
	}
	
	/**
	 * Get the ID of the query that owns a stimulus
	 *
	 * Returns the ID of the query object that owns this stimulus.
	 *
	 * @return string
	 */
	function getQuery() {
		return $this->_getQuery ();
	}
	
	/**
	 * Write a stimulus to the database.
	 *
	 * Writes the current stimulus object into the database. Uses adodb's
	 * insertSQL function.
	 *
	 * @return string of successfully-written stimulus,
	 *         NULL if failure
	 */
	function insertStimulus() {
		$this->logMsg ( ">>> insertStimulus", MM_ALL );
		
		$returnCode = NULL;
		
		// Check for valid stimulus
		if ($this->getStimulus () == NULL || $this->getQuery () == NULL) {
			$this->logMsg ( _ ( "Attempt to insert empty stimulus" ), MM_WARN );
		} else {
			
			// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			
			// Setup and perform query
			$queryId = $this->getQuery ();
			$stimulus = "[:GARBAGETHATCANTEXIST:]";
			
			$record = $dbConn->Execute ( "SELECT * FROM Stimulus " . "WHERE query = $queryId AND stimulus = '$stimulus'" );
			
			// Update the values for the new record to the values of the
			// object.
			$newRecord = array ();
			$newRecord ["query"] = $this->getQuery ();
			$newRecord ["stimulus"] = $this->getStimulus ();
			 $newRecord["reality"] = $this->getReality();
			
			// Insert the new record in place of the old.
			$insertSQL = $dbConn->GetInsertSQL ( $record, $newRecord );
			if ($dbConn->Execute ( $insertSQL ) === FALSE) {
				// Error in SQL execution
				$returnCode = NULL;
				$this->logMsg ( sprintf ( "%s", $dbConn->ErrorMsg () ), MM_ERROR );
			} else {
				// If sql executed successfully, return the ID.
				if ($insertSQL != NULL)
					$returnCode = $this->getStimulus ();
			}
		}
		$this->logMsg ( sprintf ( "<<< insertStimulus (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
}

/**
 * class decision
 *
 * Decision objects contain the temporary results of searches. Each thought that
 * results from a search is entered into the decision list before being returned
 * to the requestor.
 */
class decision extends _brainObject {
	
	/**
	 *
	 * @var integer Query ID
	 */
	var $_query;
	
	/**
	 *
	 * @var integer Thought ID
	 */
	var $_thought;
	
	/**
	 *
	 * @var integer Ranking of a specific entry
	 */
	var $_strength;
	
	/**
	 *
	 * @var string Status of a specific entry
	 */
	var $_status;
	
	/**
	 * Class constructor
	 *
	 * Creates the decision object, and can optionally accept configuration
	 * and sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 *
	 * @param integer $queryId
	 *        	of query to link to
	 * @param integer $thoughtId
	 *        	of thought to link to
	 * @param string $status
	 *        	status
	 * @param integer $strength
	 *        	of decision
	 */
	function decision($queryId = NULL, $thoughtId = NULL, $status = NULL, $strength = NULL) {
		_brainObject::_brainObject ();
		$this->_query = $queryId;
		$this->_thought = $thoughtId;
		$this->_strength = $strength;
		$this->_status = $status;
	}
	
	/**
	 * Set the query ID for this decision
	 *
	 * @param integer $queryId
	 *        	ID
	 * @return integer ID, or 0 if error.
	 */
	function _setQuery($queryId) {
		$this->_query = $queryId;
		$this->logMsg ( sprintf ( "_setQuery", $queryId ), MM_ALL );
		return $this->_getQuery ();
	}
	
	/**
	 * Get the query ID of this decision entry
	 *
	 * @return integer ID
	 */
	function _getQuery() {
		return $this->_query;
	}
	
	/**
	 * Set the thought ID of this decision
	 *
	 * @param integer $thoughtId
	 *        	ID
	 * @return integer ID, or 0 if error.
	 */
	function _setThought($thoughtId) {
		$this->_thought = $thoughtId;
		$this->logMsg ( sprintf ( "_setThought", $thoughtId ), MM_ALL );
		return $this->getThought ();
	}
	
	/**
	 * Get the thought ID of this decision
	 *
	 * @return integer ID
	 */
	function getThought() {
		return $this->_thought;
	}
	
	/**
	 * Set the strength rating of this decision
	 *
	 * @param integer $strength
	 *        	value
	 * @return integer value
	 */
	function setStrength($strength) {
		$this->_strength = $strength;
		$this->logMsg ( sprintf ( "setStrength", $strength ), MM_ALL );
		return $this->_strength;
	}
	
	/**
	 * Gets the strength of this decision entry
	 *
	 * @return integer
	 */
	function getStrength() {
		return $this->_strength;
	}
	
	/**
	 * Set the query ID of this decision
	 *
	 * @param integer $queryId
	 *        	ID
	 * @return integer ID, or 0 if error.
	 */
	function linkToQuery($queryId) {
		return $this->_setQuery ( $queryId );
	}
	
	/**
	 * Get the query ID of this decision
	 *
	 * @return integer ID
	 *         **************************************************************
	 */
	function getQuery() {
		return $this->_getQuery ();
	}
	
	/**
	 * Set the thought ID of this decision
	 *
	 * @param integer $thoughtId
	 *        	ID
	 * @return integer ID, or 0 if error.
	 */
	function linkToThought($thoughtId) {
		return $this->_setThought ( $thoughtId );
	}
	
	/**
	 * Fetch a decision from the database
	 *
	 * Fetches the decision with the specified query ID and thought ID from
	 * the brain. Loads the decision into an object (if it exists).
	 *
	 * @param integer $queryId
	 *        	ID
	 * @param integer $thoughtId
	 *        	ID
	 * @return integer of the decision, else NULL
	 */
	function readDecision($queryId = NULL, $thoughtId = NULL) {
		$this->logMsg ( sprintf ( ">>> readDecision (%s,%s)", $queryId, $thoughtId ), MM_ALL );
		
		$returnCode = NULL;
		
		if (! isset ( $queryId ) || ! isset ( $thoughtId )) {
			// Not enought information
			$this->logMsg ( "Need to specify a query ID and a thought ID", MM_ERROR );
		} else {
			// Ready to roll...
			
			$dbConn = $this->dbconn;
			
			// Setup and perform query
			$recordSet = &$dbConn->Execute ( "SELECT * FROM Decision " . "WHERE query = '$queryId' AND thought = '$thoughtId' " );
			
			if ($recordSet->EOF) {
				// Requested symbol does not exist in the database.
				$this->logMsg ( sprintf ( "Decision with query %s and thought %s was not found in the database", $queryId, $thoughtId ), MM_ERROR );
			} else {
				// Return requested symbol
				$strength = $recordSet->fields [2];
				$returnCode = $strength;
				
				// Write to object, if it exists.
				if (method_exists ( $this, "decision" )) {
					$this->linkToQuery ( $queryId );
					$this->linkToThought ( $thoughtId );
					$this->setStrength ( $strength );
				}
			}
		}
		$this->logMsg ( sprintf ( "<<< readDecision (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Update a decision in the database
	 *
	 * Fetches a decision from the database given a query and thought ID,
	 * updates it with any non-NULL values from the object, and then writes
	 * the changed decision back into the database.
	 *
	 * @param integer $query        	
	 * @param integer $thought        	
	 * @param string $status        	
	 * @param integer $strength        	
	 * @return integer ID of decision, else 0
	 */
	function updateDecision($query = NULL, $thought = NULL, $status = NULL, $strength = NULL) {
		$this->logMsg ( ">>> updateDecision (query=%s, thought=%s, status=%s, strength=%s)", MM_ALL );
		
		$returnCode = 0;
		
		// Connect to the database and execute the query
		$dbConn = $this->dbconn;
		
		if (! $query)
			$query = $this->getQuery ();
		if (! $thought)
			$thought = $this->getThought ();
		if (! $strength)
			$strength = $this->getStrength ();
		if (! $status)
			$status = $this->getStatus ();
		
		if (! $query || ! $thought) {
			$this->logMsg ( sprintf ( "Unable to uniquely identify decision (query=%s, thought=%s)", $query, $thought ), MM_ERROR );
		} else {
			$this->logMsg ( sprintf ( "Using decision with query=%s and thought=%s", $query, $thought ), MM_INFO );
		}
		
		$record = $dbConn->Execute ( "SELECT * FROM Decision WHERE query = '$query' AND thought = '$thought' " );
		
		// Update the values for the record to the values of the object
		$updateRecord = array ();
		if ($strength)
			$updateRecord ["strength"] = $strength;
		if ($status)
			$updateRecord ["status"] = $status;
			
			// TODO: Validate datestamp is updated
			
		// Update the database record
		$updateSQL = $dbConn->GetUpdateSQL ( $record, $updateRecord );
		$dbConn->Execute ( $updateSQL );
		
		// If sql executed successfully, return the ID.
		if ($updateSQL != NULL)
			$returnCode = $query;
		
		$this->logMsg ( sprintf ( "<<< updateDecision (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
}
?>
