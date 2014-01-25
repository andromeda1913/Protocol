<?PHP
 

/**
 * Mindmeld utilities library
 */
require_once ($MM_GLOBALS ['includes'] . 'utilities.inc');

/**
 * Methods pertaining to answers
 *
 * The thought object represents an instance of a knowledge solution. It is made
 * up of a unique ID, a short summary, and a detail. The detail is generally an
 * XML or HTML file.
 */
class thought extends _brainObject {
	
	/**
	 *
	 * @var mixed Thought summary text
	 */
	var $_summary;
	
	/**
	 *
	 * @var mixed Thought detail text
	 */
	var $_detail;
	
	/**
	 *
	 * @var string Type of datasource
	 */
	var $dstype;
	
	/**
	 *
	 * @var string Name of datasource
	 */
	var $dsname;
	
	/**
	 *
	 * @var string Datasource access instructions
	 */
	var $datasource;
	
	/**
	 *
	 * @var integer User confidence level
	 */
	var $confidence;
	
	/**
	 *
	 * @var integer Certification level
	 */
	var $certification;
	
	/**
	 *
	 * @var string Agent who certified answer
	 */
	var $certifier;
	
	/**
	 *
	 * @var integer Learn/relearn state
	 */
	var $learn;
	
	/**
	 *
	 * @var integer Review counter
	 */
	var $review;
	
	/**
	 *
	 * @var boolean Status of display filter
	 */
	var $displayRaw;
	
	/**
	 * Class constructor
	 *
	 * Creates the object, and can optionally accept configuration and
	 * sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 */
	function thought() {
		_brainObject::_brainObject ();
	}
	
	/**
	 * Set the summary text of a thought
	 *
	 * @param string $arg        	
	 * @return string summary
	 */
	function setSummary($arg) {
		$this->_summary = trim ( $arg );
		$this->logMsg ( sprintf ( "setSummary (%s)", $this->_summary ), MM_ALL );
		return $this->_summary;
	}
	
	/**
	 * Set the confidence level
	 *
	 * User confidence in accuracy of answer from 0 to 100%. Passing no argument
	 * resets the confidence.
	 *
	 * @param integer $arg        	
	 * @return integer confidence
	 */
	function setConfidence($arg = 50) {
		if ($arg < 0 or $arg > 100) {
			$this->logMsg ( sprintf ( "Requested confidence out of bounds (%s)", $arg ), MM_ERROR );
			return NULL;
		} else {
			$this->confidence = $arg;
		}
		$this->logMsg ( sprintf ( "setConfidence (%s)", $this->confidence ), MM_ALL );
		return $this->confidence;
	}
	
	/**
	 * Set the certification level
	 *
	 * Certification level of thought. There are four certification levels:
	 * Set to 0 for uncertified
	 * Set to 1 for minimum certification (yellow ribbon).
	 * Set to 2 for moderate certification (red ribbon).
	 * Set to 3 for USDA PRIME all-out full certification (blue ribbon).
	 *
	 * @param integer $arg
	 *        	level
	 * @return integer certification level
	 */
	function setCertification($arg = 0) {
		$allowedValues = array (
				0,
				1,
				2,
				3 
		);
		if (! in_array ( $arg, $allowedValues )) {
			$this->certification = 0;
		} else {
			$this->certification = $arg;
		}
		$this->logMsg ( sprintf ( "setCertification (%s)", $this->certification ), MM_ALL );
		return $this->certification;
	}
	
	/**
	 * Set the certifier
	 *
	 * Not currently used.
	 *
	 * @param mixed $arg        	
	 * @return mixed certifier, if successful
	 */
	function setCertifier($arg = NULL) {
		$this->certifier = $arg;
		$this->logMsg ( sprintf ( "setCertifier (%s)", $this->certifier ), MM_ALL );
		return $this->certifier;
	}
	
	/**
	 * Set the learn level
	 *
	 * Force Mindmeld to learn or relearn all or part of a thought during the next dream.
	 * Set to 0 means take no action.
	 * Set to 1 to force Mindmeld to learn/relearn the detail, preserving existing neurons.
	 * Set to 2 to force Mindmeld to learn/relearn the entire thought, preserving existing neurons.
	 * Set to 3 to force Mindmeld to learn/relearn the entire thought, replacing existing neurons.
	 *
	 * @param mixed $learn        	
	 * @return integer Learn level
	 */
	function setLearn($arg = 0) {
		$allowedValues = array (
				0,
				1,
				2,
				3 
		);
		if (! in_array ( $arg, $allowedValues )) {
			$this->learn = 0;
		} else {
			$this->learn = $arg;
		}
		$this->logMsg ( sprintf ( "setLearn (%s)", $this->learn ), 5 );
		return $this->learn;
	}
	
	/**
	 * Set the review value
	 *
	 * Number of times this has been marked for review since last update.
	 *
	 * @param integer $arg
	 *        	0 resets, 1 increments, -1 decrements.
	 * @return integer review level
	 */
	function setReview($arg = NULL) {
		switch ($arg) {
			case - 1 : // Decrement current value
				$this->review --;
				break;
			case 0 : // Reset value
				$this->review = 0;
				break;
			case 1 : // Increment current value
				$this->review ++;
				break;
			default :
				return NULL;
				break;
		}
		
		$this->logMsg ( sprintf ( "setReview (%s)", $this->review ), MM_ALL );
		return $this->review;
	}
	
	/**
	 * Set the datasource type
	 *
	 * @param string $arg        	
	 * @return string Datasource type, as set
	 */
	function setDsType($arg = NULL) {
		$allowedValues = array (
				'LOCAL',
				'SQL' 
		); // Add HTTP, MTTP later
		if (! in_array ( $arg, $allowedValues )) {
			$this->dstype = 'LOCAL';
		} else {
			$this->dstype = $arg;
		}
		$this->logMsg ( sprintf ( "setDsType (%s)", $this->dstype ), MM_ALL );
		return $this->dstype;
	}
	
	/**
	 * Set the datasource name
	 *
	 * Links the datasource to an existing datasource in the datasource
	 * table, if necessary.
	 *
	 * @param string $arg        	
	 * @return string Datasource name, as set
	 */
	function setDsName($arg = NULL) {
		$this->dsname = $arg;
		$this->logMsg ( sprintf ( "setDsName (%s)", $this->dsname ), MM_ALL );
		return $this->dsname;
	}
	
	/**
	 * Set the datasource information
	 *
	 * Provides information about how to acquire data from an external
	 * source. For SQL, this would be the query, for HTTP, it would be
	 * the URL.
	 *
	 * @param string $arg        	
	 * @return string Datasource information, as set
	 */
	function setDatasource($arg = NULL) {
		$this->datasource = $arg;
		$this->logMsg ( sprintf ( "setDatasource (%s)", $this->datasource ), MM_ALL );
		return $this->datasource;
	}
	
	/**
	 * Set the raw display value
	 *
	 * Force Mindmeld to bypass display filtering (security).
	 * Set to 0 (FALSE) for normal (filtered) display.
	 * Set to 1 (TRUE) for raw (unfiltered) display
	 *
	 * @param boolean $arg
	 *        	FALSE for filtered (normal) display, TRUE for
	 *        	unfiltered (raw) display.
	 * @return boolean display value
	 */
	function setDisplayRaw($arg = FALSE) {
		if ($arg) {
			$this->displayRaw = 1;
			$return = 'TRUE';
		} else {
			$this->displayRaw = 0;
			$return = 'FALSE';
		}
		
		$this->logMsg ( sprintf ( "setDisplayRaw (%s)", $return ), MM_ALL );
		return $this->displayRaw;
	}
	
	/**
	 * Return the summary text for a thought.
	 *
	 * @return mixed Summary
	 */
	function getSummary() {
		return $this->_summary;
	}
	
	/**
	 * Set the detail text of a thought
	 *
	 * @param string $arg
	 *        	text.
	 * @return mixed detail, if successful
	 */
	function setDetail($arg) {
		$this->_detail = $arg;
		$this->logMsg ( "_setDetail", MM_ALL );
		return $this->_detail;
	}
	
	/**
	 * Return the detail text of a thought.
	 *
	 * @param
	 *        	mixed	Thought detail
	 */
	function getDetail() {
		return $this->_detail;
	}
	
	/**
	 * Set the unique ID of a thought
	 *
	 * Sets a unique OID for this thought object to the requested ID. This
	 * is only used for dealing with existing objects, since thoughtIDs for
	 * new objects are generated automagically in the databse.
	 *
	 * @param integer $thoughtId        	
	 * @return integer ID
	 */
	function setThoughtId($thoughtId) {
		return $this->_setReferenceId ( $thoughtId );
	}
	
	/**
	 * Get the ID of a thought
	 *
	 * Returns a unique OID for this thought object.
	 *
	 * @return integer ID
	 */
	function getThoughtId() {
		return $this->_getReferenceId ();
	}
	
	/**
	 * Write a thought to the database.
	 *
	 * Writes the current thought object into the database. Uses adodb's
	 * insertSQL function. insertThought should always generate a new
	 * thoughtID to make sure the new record is in fact, new.
	 *
	 * The datestamp is set automatically.
	 *
	 * If the database does not support transactions, the returned ID
	 * may not be correct.
	 *
	 * @return integer of written thought, else NULL
	 *        
	 * @todo Allow bulk inserts via an array of thoughts
	 */
	function insertThought() {
		$this->logMsg ( ">>> insertThought", MM_ALL );
		
		$returnCode = NULL;
		
		if ($this->getSummary () == NULL) {
			
			// No summary provided
			$this->logMsg ( "Attempt to insert empty summary", MM_WARN );
		} else {
			$this->logMsg ( sprintf ( "Summary is '%s'", $this->getSummary () ), MM_INFO );
			
			// Connect to the database and execute the query
			$dbConn = &$this->dbconn;
			
			// Select an empty record from the database to use as a
			// template for the insert.
			$record = $dbConn->Execute ( "SELECT * FROM Thought WHERE thoughtId = -1" );
			
			// Set defaults
			if (! isset ( $this->status ))
				$this->setStatus ( "ACTIVE" );
			if (! isset ( $this->confidence ))
				$this->setConfidence ();
			if (! isset ( $this->certification ))
				$this->setCertification ();
			if (! isset ( $this->certifier ))
				$this->setCertifier ();
			if (! isset ( $this->learn ))
				$this->setLearn ();
			if (! isset ( $this->review ))
				$this->setReview ();
			if (! isset ( $this->displayRaw ))
				$this->setDisplayRaw ();
			if (! isset ( $this->dstype ))
				$this->setDsType ();
			if (! isset ( $this->dsname ))
				$this->setDsName ();
			if (! isset ( $this->datasource ))
				$this->setDatasource ();
				
				// Update the values for the new record to the values of the
				// object. New thoughtId is generated automatically in database.
			$newRecord = array ();
			$newRecord ['summary'] = $this->getSummary ();
			$newRecord ['detail'] = $this->getDetail ();
			$newRecord ['status'] = $this->getStatus ();
			$newRecord ['interacted'] = $this->setInteracted ();
			$newRecord ['confidence'] = $this->confidence;
			$newRecord ['certification'] = $this->certification;
			$newRecord ['certifier'] = $this->certifier;
			$newRecord ['learn'] = $this->learn;
			$newRecord ['review'] = $this->review;
			$newRecord ['displayRaw'] = $this->displayRaw;
			$newRecord ['dstype'] = $this->dstype;
			$newRecord ['dsname'] = $this->dsname;
			$newRecord ['datasource'] = $this->datasource;
			
			// Insert the new record in place of the old.
			$insertSQL = $dbConn->GetInsertSQL ( $record, $newRecord );
			
			$dbConn->Execute ( $insertSQL );
			
			// Fetch the record we just inserted so we can get the ID:
			$dbtimestamp = $dbConn->DBTimeStamp ( $newRecord ['interacted'] );
			$quotedSummary = $dbConn->qstr ( $newRecord ['summary'] );
			$sql = "SELECT thoughtId FROM Thought " . " WHERE summary = $quotedSummary" . " AND interacted = $dbtimestamp";
			$recordSet = $dbConn->Execute ( $sql );
			
			// Got a thought ID
			$newThoughtId = $this->setThoughtId ( $recordSet->fields [0] );
			
			// If thought was created successfully, return the ID.
			if (isset ( $newThoughtId )) {
				$event = new mmEvent ();
				$event->addThought ( $newThoughtId );
			}
		}
		
		$this->logMsg ( sprintf ( "<<< insertThought (%s)", $newThoughtId ), MM_ALL );
		return $newThoughtId;
	}
	
	/**
	 * Update a thought in the database
	 *
	 * Fetches a thought from the database given a thoughtID, updates the
	 * thought with any non-NULL values from the object, and then writes
	 * the changed thought back into the databsae.
	 *
	 * Basically, set only the thought attributes that need to be changed
	 * before calling updateThought. The datestamp is set automatically.
	 *
	 * @param
	 *        	integer		ID of affected thought
	 * @param
	 *        	mixed		Summary of affected thought
	 * @param
	 *        	mixed		Detail of affected thought
	 * @param
	 *        	mixed		Status of affected thought
	 * @return integer of updated thought, else NULL
	 *        
	 * @todo Allow bulk updates via an array of thoughts
	 */
	function updateThought($thoughtId = NULL, $thoughtSummary = NULL, $thoughtDetail = NULL, $thoughtStatus = NULL) {
		$this->logMsg ( ">>> updateThought", MM_ALL );
		
		$returnCode = 0;
		
		// Get thought values from object if not passed. Else set object values from them.
		$thoughtId == NULL ? ($thoughtId = $this->getThoughtId ()) : ($this->setThoughtId ( $thoughtId ));
		$thoughtSummary == NULL ? ($thoughtSummary = $this->getSummary ()) : ($this->setSummary ( $thoughtSummary ));
		$thoughtDetail == NULL ? ($thoughtDetail = $this->getDetail ()) : ($this->setDetail ( $thoughtDetail ));
		$thoughtStatus == NULL ? ($thoughtStatus = $this->getStatus ()) : ($this->setStatus ( $thoughtStatus ));
		
		// Connect to the database and execute the query
		$dbConn = $this->dbconn;
		$record = &$dbConn->Execute ( "SELECT * FROM Thought WHERE thoughtId = $thoughtId" );
		
		$updateRecord = array ();
		$updateRecord ["thoughtId"] = $thoughtId;
		if ($thoughtSummary != NULL)
			$updateRecord ["summary"] = $thoughtSummary;
		if ($thoughtDetail != NULL)
			$updateRecord ["detail"] = $thoughtDetail;
		if ($thoughtStatus != NULL)
			$updateRecord ["status"] = $thoughtStatus;
		$updateRecord ["interacted"] = $this->setInteracted ();
		
		$fields = array (
				'confidence',
				'certification',
				'certifier',
				'learn',
				'review',
				'displayRaw',
				'dstype',
				'dsname',
				'datasource' 
		);
		foreach ( $fields as $field ) {
			if (isset ( $this->$field ))
				$updateRecord [$field] = $this->$field;
		}
		
		// Insert the new record in place of the old.
		$updateSQL = $dbConn->GetUpdateSQL ( $record, $updateRecord );
		$dbConn->Execute ( $updateSQL );
		
		// If sql executed successfully, return the ID.
		if ($updateSQL) {
			$updThoughtId = $this->getThoughtId ();
			$event = new mmEvent ();
			$event->changeThought ( $updThoughtId );
		} else {
			$updThoughtId = NULL;
		}
		
		$this->logMsg ( sprintf ( "<<< updateThought (%s)", $updThoughtId ), MM_ALL );
		return $updThoughtId;
	}
	
	/**
	 * Fetch minimal thought information from the database
	 *
	 * Fetches the thought with the specified ID from the database and
	 * loads it into a thought object for local manipulation. The "quick"
	 * version improves performance by not loading the thought detail.
	 *
	 * @param integer $requst
	 *        	ID
	 * @param boolean $cache
	 *        	disable query caching
	 * @return integer of successfully-retrieved thought, else NULL
	 *        
	 * @todo Allow bulk reads via an array of thoughts
	 */
	function readThoughtQuick($request = NULL, $cache = TRUE) {
		$this->logMsg ( sprintf ( ">>> readThoughtQuick (%s)", $request ), MM_ALL );
		
		$returnCode = NULL;
		$returned = NULL;
		
		$dbConn = $this->dbconn;
		
		// Setup and perform query
		$sql = "SELECT thoughtId, summary, interacted, status, dstype, dsname, datasource " . "FROM Thought WHERE thoughtId = $request";
		
		// Use the SQL cache if available
		if ($cache && is_writeable ( $ADODB_CACHE_DIR )) {
			// Cache result set
			$this->logMsg ( sprintf ( "Caching recordset to %s", $ADODB_CACHE_DIR ), MM_ALL );
			// @todo Cachetime should be a fundamental.
			$recordSet = &$dbConn->CacheExecute ( 180, $sql );
		} else {
			$recordSet = &$dbConn->Execute ( $sql );
		}
		
		if ($recordSet->EOF) {
			// Requested thought ID does not exist in the database.
			$this->logMsg ( sprintf ( "Thought #%s was not found in the database", $request ), MM_NOTICE );
		} else {
			// Return requested thought
			$returned = $recordSet->fields [0];
			if (method_exists ( $this, 'thought' )) {
				$this->setSummary ( $recordSet->fields [1] );
				$this->setInteracted ( $recordSet->fields [2] );
				$this->setStatus ( $recordSet->fields [3] );
				$this->setDsType ( $recordSet->fields [4] );
				$this->setDsName ( $recordSet->fields [5] );
				$this->setDatasource ( $recordSet->fields [6] );
				$this->setThoughtId ( $returned );
			}
		}
		$returnCode = $returned;
		$this->logMsg ( sprintf ( "<<< readThoughtQuick (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Fetch all thought information from the database
	 *
	 * Fetches the thought with the specified ID from the database and
	 * loads it into a thought object for local manipulation.
	 *
	 * @param integer $request
	 *        	ID
	 * @param boolean $cache
	 *        	disable query caching
	 * @return integer of successfully-retrieved thought, else NULL
	 *        
	 * @todo Allow bulk reads via an array of thoughts
	 */
	function readThoughtFull($request = NULL, $cache = TRUE) {
		global $ADODB_CACHE_DIR;
		
		$this->logMsg ( sprintf ( ">>> readThoughtFull (%s)", $request ), MM_ALL );
		$returnCode = NULL;
		$returned = NULL;
		$dbConn = $this->dbconn;
		
		// Read Full And Create Stimulus.
		if (isset ( $_SESSION ["symbols"] )) {
			foreach ( $_SESSION ["symbols"] as $s ) {
				$c = mysql_fetch_object ( mysql_query ( "SELECT COUNT(*) as c FROM  TsNeuron WHERE  symbol LIKE '{$s}' AND thought='{$request}' " ) );
				if ($c->c <= 0) {
					
					$s = strtoupper ( $s );
					$summar = mysql_fetch_object ( mysql_query ( "SELECT * FROM Thought WHERE thoughtId='{$request}' " ) )->summary;
					
					(new mind ())->learnSymbol ( $request, $s );
				}
			}
			unset ( $_SESSION ["symbols"] );
		}
		
		// Setup and perform query
		$fields = "thoughtId, summary, detail, interacted, status, confidence, certification, certifier, " . "learn, review, displayRaw, dstype, dsname, datasource";
		$sql = "SELECT $fields FROM Thought WHERE thoughtId = $request";
		
		if ($cache && is_writeable ( $ADODB_CACHE_DIR )) {
			// Cache result set
			$this->logMsg ( sprintf ( "Caching recordset to %s", $ADODB_CACHE_DIR ), MM_ALL );
			$recordSet = $dbConn->CacheExecute ( 180, $sql );
		} else {
			$recordSet = $dbConn->Execute ( $sql );
		}
		
		if ($recordSet->EOF) {
			// Requested thought ID does not exist in the database.
			$this->logMsg ( sprintf ( "Thought #%s was not found in the database", $request ), MM_NOTICE );
		} else {
			// Return requested thought
			$returned = $recordSet->fields [0];
			$this->setSummary ( $recordSet->fields [1] );
			$this->setDetail ( $recordSet->fields [2] );
			$this->setInteracted ( $recordSet->fields [3] );
			$this->setStatus ( $recordSet->fields [4] );
			$this->setConfidence ( $recordSet->fields [5] );
			$this->setCertification ( $recordSet->fields [6] );
			$this->setCertifier ( $recordSet->fields [7] );
			$this->setLearn ( $recordSet->fields [8] );
			$this->setReview ( $recordSet->fields [9] );
			$this->setDisplayRaw ( $recordSet->fields [10] );
			$this->setDsType ( $recordSet->fields [11] );
			$this->setDsName ( $recordSet->fields [12] );
			$this->setDatasource ( $recordSet->fields [13] );
			$this->setThoughtId ( $returned );
		}
		$returnCode = $returned;
		$this->logMsg ( sprintf ( "<<< readThoughtFull (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Remove a thought and all of its attached neurons from the database.
	 *
	 * @param integer $thoughtId
	 *        	thought that will be removed.
	 * @return array of thoughts removed, else NULL
	 *        
	 * @todo Allow bulk deletes via an array of thoughts
	 */
	function removeThought($thoughtId = NULL) {
		$this->logMsg ( ">>> removeThought", MM_ALL );
		
		$removed = NULL;
		
		// If no thoughtId is passed, get a list of all thoughts
		// marked for deletion.
		$dbConn = $this->dbconn;
		$thoughtList = &$dbConn->Execute ( "SELECT thoughtId FROM Thought WHERE status = 'DELETED'" );
		
		$attempted = 0; // How many are we trying to remove
		$removed = 0; // How many have we actually removed
		$purgeList = array ();
		while ( ! $thoughtList->EOF ) {
			$attempted ++;
			$thoughtId = $thoughtList->fields [0];
			
			// Select the record from the database to update
			$this->logMsg ( sprintf ( "Removing thought #%s", $thoughtId ), MM_INFO );
			
			// Verify the thought exists and is marked for deletion. If so, delete all traces of a thought
			// and then delete the thought itself.
			$sql = "SELECT thoughtId FROM Thought WHERE thoughtId = $thoughtId AND status = 'DELETED'";
			$result = $dbConn->Execute ( $sql );
			if ($result->RecordCount () == 1) {
				$dbConn->StartTrans ();
				$dbConn->Execute ( "DELETE FROM TsNeuron WHERE thought = $thoughtId" );
				$dbConn->Execute ( "DELETE FROM TtNeuron WHERE thought1 = $thoughtId OR thought2 = $thoughtId" );
				$dbConn->Execute ( "DELETE FROM Decision WHERE thought = $thoughtId" );
				$dbConn->Execute ( "DELETE FROM Thought WHERE thoughtId = $thoughtId" );
				if ($dbConn->CompleteTrans ()) {
					$removed ++;
					$purgeList [] = $thoughtId;
					$event = new mmEvent ();
					$event->removeThought ( $thoughtId );
				}
			}
			
			$thoughtList->MoveNext ();
		}
		$this->logMsg ( sprintf ( "Attempted %s, removed %s", $attempted, $removed ), MM_INFO );
		
		$this->logMsg ( sprintf ( "<<< removeThought (%s)", $removed ), MM_ALL );
		return $purgeList;
	}
	
	/**
	 * Get a list related objects
	 *
	 * Returns a list of objects related to this thought..
	 *
	 * @param integer $thoughtId
	 *        	thought that will be removed.
	 * @return integer of written neuron, else NULL
	 */
	function showRelations($thoughtId = NULL) {
		$this->logMsg ( ">>> showRelations", MM_ALL );
		
		$returnCode = FALSE;
		
		isset ( $thoughtId ) ? $this->setThoughtId ( $thoughtId ) : $thoughtId = $this->getThoughtId ();
		
		if ($thoughtId == NULL) {
			$this->logMsg ( sprintf ( "No thought specified for removal" ), MM_WARN );
			$returnCode = FALSE;
		} else {
			
			// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			
			// Select the record from the database to update
			$attrib = array (
					'%THOUGHTID%' => $thoughtId 
			);
			
			// Fetch related tsNeurons
			$tsNeuronRecords = &$dbConn->Execute ( "SELECT symbol, dominance, enabled, interacted " . "FROM TsNeuron WHERE thought = $thoughtId" );
			
			$this->logMsg ( sprintf ( "%s related tsNeurons", count ( $tsNeuronRecords ) ), MM_INFO );
			
			// Fetch related ttNeurons
			$ttNeuronRecords = &$dbConn->Execute ( "SELECT thought1, thought2, dominance, enabled, interacted " . "FROM TtNeuron WHERE thought1 = $thoughtId OR thought2 = $thoughtId" );
			$this->logMsg ( sprintf ( "%s related ttNeurons", count ( $ttNeuronRecords ) ), MM_INFO );
			
			// Fetch related decisions
			$decisionRecords = &$dbConn->Execute ( "SELECT query, status, visited " . "FROM Decision WHERE thought = $thoughtId" );
			$this->logMsg ( sprintf ( "%s related decisions", count ( $decisionRecords ) ), MM_INFO );
		}
		
		$recordCount = count ( $tsNeurons ) + count ( $ttNeurons ) + count ( $decisionRecords );
		$relatedRecords = array (
				"tsneuron" => $tsNeuronRecords,
				"ttneuron" => $ttNeuronRecords,
				"decision" => $decisionRecords 
		);
		
		$this->logMsg ( sprintf ( "<<< showRelations (%s)", $recordCount ), MM_ALL );
		return $relatedRecords;
	}
}







/**
 * Methods pertaining to symbols
 *
 * The symbol object represents an atom of searchable information. It can consist
 * of a short text mixed (no spaces or special characters) or eventually, any
 * MIME type.
 */
class symbol extends _brainObject {
	
	/**
	 *
	 * @var mixed Atomic symbol mixed (Must be unique)
	 */
	var $_symbol;
	
	/**
	 *
	 * @var mixed Symbol reality text (eventually MIME type)
	 */
	var $_reality;
	
	/**
	 *
	 * @var integer Number of linked tsNeurons
	 */
	var $_tsnCount;
	
	/**
	 *
	 * @var boolean Noise flag (TRUE means symbol is a noiseword
	 */
	var $_noiseWord;
	
	/*
	 * Word Id For   The Symbol  
	 */
	
	var $word_id = null ;   
	
	 
	function symbol() {
		_brainObject::_brainObject ();
	}
	
	 
	function setSymbol($symbol) {
		$this->logMsg ( sprintf ( ">>> setSymbol (%s)", $symbol ), MM_ALL );
		$newSymbol = $symbol;
		// Remove non-permitted characters
		$newSymbol = stripslashes ( strtoupper ( trim ( $newSymbol ) ) );
		$newSymbol = preg_replace ( "/[^a-zA-Z0-9\-]/", "", $newSymbol );
		$this->_symbol = $newSymbol;
		$this->logMsg ( sprintf ( "<<< setSymbol (%s)", $newSymbol ), MM_ALL );
		return $newSymbol;
	}
	
	 
	function getSymbol() {
		return $this->_symbol;
	}
	
 
	function setReality($reality) {
		$this->_reality = $reality;
		$this->logMsg ( "setReality", MM_ALL );
		return true;
	}
	
 
	function getReality() {
		return $this->_reality;
	}
	
 
	function insertSymbol($symbolList = NULL ) {
		$this->logMsg ( ">>> insertSymbol", MM_ALL );
		
		$returnCode = NULL;
		
		// If symbol not passed, try to pull one from the symbol object.
		if (! $symbolList) {
			$symbolList = $this->getSymbol ();
			if (! $symbolList) {
				$this->logMsg ( "No symbol to insert!", MM_WARN );
				return NULL;
			}
		}
		
		if (! is_array ( $symbolList )) {
			
			$symbol = &$symbolList;
			
			// Insert a single symbol
			$this->logMsg ( sprintf ( "Inserting single symbol (%s)", $symbol ), MM_INFO );
			
			// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			
			// Select an empty record from the database to use as a
			// template for the insert.
			$record = $dbConn->Execute ( "SELECT * FROM Symbol WHERE symbol = '$symbol'" );
			
			// Check for existing neuron
			$recordSet = &$record;
			if (! $recordSet->EOF) {
				// Symbol exists. No need to rewrite.
				$this->logMsg ( sprintf ( "Ignoring existing symbol (%s)", $symbol ), MM_INFO );
			} else {
				// Update the values for the new record to the values of the
				// object.
				$newRecord = array ();
				$newRecord ['symbol'] = $symbol;
				$newRecord ['interacted'] = thought::setInteracted ();
				
				// Insert the new record in place of the old.
				$insertSQL = $dbConn->GetInsertSQL ( $record, $newRecord );
				if ($dbConn->Execute ( $insertSQL ) === FALSE) {
					// Error in SQL execution
					$this->logMsg ( sprintf ( "%s", $dbConn->ErrorMsg () ), MM_ERR );
				} else {
					// If sql executed successfully, return the ID.
					if ($insertSQL != NULL)
						$returnCode = $symbol;
				}
			}
		} else {
			// Do an array update
			
			$this->logMsg ( "Inserting array of symbols", MM_INFO );
			$addedSymbols = array ();
			
			// Strip out the frequency data
			$symbolList = array_keys ( $symbolList );
			
	 
			$dbConn = $this->dbconn;
	 
			$list = '';
			foreach ( $symbolList as $symbol ) {
				$list .= $dbConn->qstr ( $symbol ) . ", ";
			}
			$list = substr ( $list, 0, - 2 );
			
			$results = $dbConn->Execute ( "SELECT symbol FROM Symbol WHERE symbol in ( $list )" );
			$resultsArray = $results->GetArray ();
			$existingSyms = array ();
			foreach ( $resultsArray as $symArray ) {
				$existingSyms [] = $symArray [0];
			}
			
			// Strip out existing symbols
			$symbolList = array_diff ( $symbolList, $existingSyms );
			 	
			// Cycle through the array inserting the remaining symbols
			foreach ( $symbolList as $symbol ) {
				$this->logMsg ( sprintf ( "Inserting array symbol '%s'", $symbol ), MM_INFO );
				
				if (trim ( $symbol ) == "" || $symbol == NULL) {
					$this->logMsg ( "Ignoring empty array symbol", MM_NOTICE );
				} else { 
					
					$word =  new Word($symbol);  
				 
					$record = $dbConn->Execute ( "SELECT * FROM Symbol WHERE symbol = '$symbol'" );
					
					// Check for existing neuron
					$recordSet = &$record;
					if (! $recordSet->EOF) {
						// Symbol exists. No need to rewrite.
						$this->logMsg ( sprintf ( "Ignoring existing symbol (%s)", $symbol ), MM_INFO );
					} else {
					 
						$newRecord = array ();
						$newRecord ['symbol'] = $symbol;
						$t = new thought ();
						$newRecord ['interacted'] = $t->setInteracted ();
						$newRecord ['word_id'] = $word->id  ; 
						// Insert the new record in place of the old.
						$insertSQL = $dbConn->GetInsertSQL ( $record, $newRecord );
					  	if ($dbConn->Execute ( $insertSQL ) === FALSE) {
							// Error in SQL execution
							$this->logMsg ( sprintf ( "%s", $dbConn->ErrorMsg () ), MM_ERROR );
						} else {
							$addedSymbols [] = $symbol;
						}
					}
				}
			}
		}
		
		if (isset ( $addedSymbols )) {
			$this->logMsg ( sprintf ( "<<< insertSymbol (%s)", count ( $addedSymbols ) ), MM_ALL );
			return $addedSymbols;
		} else {
			$this->logMsg ( sprintf ( "<<< insertSymbol (%s)", $returnCode ), MM_ALL );
			return $returnCode;
		}
	}
	
  
	function updateSymbol() {
		$this->logMsg ( ">>> updateSymbol", MM_ALL );
		
		$returnCode = 0;
		
		// Connect to the database and execute the query
		$dbConn = $this->dbconn;
		$sql = "SELECT * FROM Symbol WHERE symbol = '" . $this->getSymbol () . "'";
		$record = &$dbConn->Execute ( $sql );
		
		// Update the values for the new record to the values of the
		// object. 
		$word  = new Word($this->getSymbol());   
		
		$updateRecord = array ();
		if ($this->getReality () != NULL)
			$updateRecord ["reality"] = $this->getReality ();
		    $updateRecord ["interacted"] = $this->setInteracted ();
		    $updateRecord["word_id"] =  $word->id ;  
		
		// Insert the new record in place of the old.
		$updateSQL = $dbConn->GetUpdateSQL ( $record, $updateRecord );
		$dbConn->Execute ( $updateSQL );
		
		// If sql executed successfully, return the ID.
		if ($updateSQL != NULL)
			$returnCode = $this->getSymbol ();
		
		$this->logMsg ( sprintf ( "<<< updateSymbol (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Fetch a symbol from the database
	 *
	 * Fetches the symbol with the specified symbol mixed from the database
	 * and loads it into a symbol object for local manipulation.
	 *
	 * @param mixed $symbol
	 *        	text
	 * @return integer of retrieved symbol, else NULL
	 */
	function readSymbol($symbol) {
		$this->logMsg ( sprintf ( ">>> readSymbol (%s)", $symbol ), MM_ALL );
		$requestedSymbol = $symbol;
		$fetchedSymbol = "";
		
		$dbConn = $this->dbconn;
		
		// Setup and perform query
		$recordSet = &$dbConn->Execute ( "SELECT * FROM Symbol WHERE symbol = '$requestedSymbol'" );
		
		if ($recordSet->EOF) {
			// Requested symbol does not exist in the database.
			$this->logMsg ( sprintf ( "Symbol '%s' was not found in the database", $requestedSymbol ), MM_ERROR );
		} else {
			// Return requested symbol
			$fetchedSymbol = $recordSet->fields ["symbol"];
			$this->setSymbol ( $fetchedSymbol );
			$this->setInteracted ( $recordSet->fields ["interacted"] );
			$this->setReality ( $recordSet->fields ["reality"] );
			$this->word_id =  $recordSet->fields ["word_id"]  ;
			
			
		} 
		
		
		$this->logMsg ( sprintf ( "<<< readSymbol (%s)", $fetchedSymbol ), MM_ALL );
		return $fetchedSymbol;
	}
}

/**
 * Generic neuron object
 *
 * The neuron object classes link other cortex objects (thoughts and symbols)
 * together. They are effectively many-to-many tables. This class is private,
 * since it will be accessed only from real (ts, tt, or ss) neuron classes.
 */
class _neuron extends _brainobject {
	
	/**
	 *
	 * @var mixed Key that links the neuron to the first cortex object.
	 */
	var $_primary;
	
	/**
	 *
	 * @var mixed that links the neuron to the second cortex object
	 */
	var $_secondary;
	
	/**
	 *
	 * @var integer of this neuron compared with other similar neurons
	 */
	var $_dominance;
	
	/**
	 *
	 * @var integer Strength of this the relationship between connected objeds
	 */
	var $_spatiality;
	
	/**
	 * Class constructor
	 *
	 * Creates the object, and can optionally accept configuration and
	 * sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 */
	function _neuron() {
		_brainobject::_brainobject ();
	}
	
	/**
	 * Link neuron to first cortex object
	 *
	 * Links one side of the neuron to a cortex object.
	 *
	 * @param mixed $primary
	 *        	ID for the linked object
	 * @return mixed of primary link
	 *        
	 * @todo Allow bulk links to a given object using an array
	 */
	function _setPrimary($primary) {
		$this->_primary = $primary;
		$this->logMsg ( sprintf ( "_setPrimary (%s)", $primary ), MM_ALL );
		return $this->_getPrimary ();
	}
	
	/**
	 * Return a reference to first cortex object
	 *
	 * Returns key to the first object referenced by the neuron object.
	 *
	 * @return mixed for the linked object
	 */
	function _getPrimary() {
		return $this->_primary;
	}
	
	/**
	 *
	 *
	 *
	 * Link neuron to second cortex object
	 *
	 * Links the second side of the neuron to a cortex object.
	 *
	 * @param
	 *        	mixed| mixed	$secondary unique ID for the linked object
	 * @return mixed mixed	value of secondary link
	 *        
	 * @todo Allow bulk links to a given object using an array
	 */
	function _setSecondary($secondary) {
		$this->_secondary = $secondary;
		$this->logMsg ( sprintf ( "_setSecondary (%s)", $secondary ), MM_ALL );
		return $this->_getSecondary ();
	}
	
	/**
	 * Return reference to second cortex object
	 *
	 * Returns key to the second object referenced by the neuron object.
	 *
	 * @return mixed for the linked object
	 */
	function _getSecondary() {
		return $this->_secondary;
	}
	
	/**
	 * Set the dominance of a neuron
	 *
	 * Sets the strength of this neuron compared with other neurons of the
	 * same type.
	 *
	 * @param mixed $dominance
	 *        	dominance value
	 * @return mixed actual dominance
	 *        
	 * @todo Default neural dominance to fundamental value
	 * @todo Allow bulk update via array
	 */
	function setDominance($dominance = "") {
		// if ( $dominance == "" )
		// Initialize from fundamental values table
		$this->_dominance = $dominance;
		$this->logMsg ( sprintf ( "setDominance (%s)", $dominance ), MM_ALL );
		return $this->_dominance;
	}
	
	/**
	 * Get dominance of a neuron
	 *
	 * Returns the strength of this neuron compared with other neurons of the
	 * same type.
	 *
	 * @return mixed actual dominance
	 *        
	 * @todo Allow fetch by primary,secondary
	 */
	function getDominance() {
		return $this->_dominance;
	}
	
	/**
	 * Set the spatiality of a neuron
	 *
	 * Sets the strength of the relationship between the linked objects.
	 * Initializes value to zero if no value is passed.
	 *
	 * @param mixed $spatiality        	
	 * @return mixed of neuron object
	 */
	function setSpatiality($spatiality = 0) {
		$this->_spatiality = $spatiality;
		$this->logMsg ( sprintf ( "setSpatiality (%s)", $spatiality ), MM_ALL );
		return $spatiality;
	}
	
	/**
	 * Get the spatiality of a neuron
	 *
	 * Returns the strength of the relationship between the linked objects.
	 *
	 * @return mixed of object
	 */
	function getSpatiality() {
		return $this->_spatiality;
	}
	
	/**
	 * Adjust the dominance of a neuron
	 *
	 * Adjusts the strength of this neuron compared with other neurons of the
	 * same type.
	 *
	 * @param mixed $adjustment
	 *        	of dominance adjustment
	 * @return mixed actual dominance
	 *        
	 * @todo Allow bulk adjustment via an array
	 */
	function adjustDominance($adjustment) {
		return $this->setDominance ( $this->getDominance () + $adjustment );
	}
}

/**
 * Neuron that links a thought with a symbol
 *
 * The tsNeuron object links thoughts to symbols in the cortex. This is the
 * fundamental relationship in the brain.
 */
class tsNeuron extends _neuron {
	
	/**
	 * Class constructor
	 *
	 * Creates the object, and can optionally accept configuration and
	 * sqlActions objects as arguments. It will load the default
	 * configuration and sqlActions objects if they are not provided.
	 */
	function tsNeuron() {
		_neuron::_neuron ();
	}
	
	/**
	 * Link the neuron to a thought
	 *
	 * Links one side of the neuron to a thought
	 *
	 * @param mixed $thoughtId
	 *        	of thought that will be linked
	 * @return mixed of linked thought
	 *        
	 * @todo Allow bulk linking to a thought via an array of symbols
	 */
	function linkToThought($thoughtId) {
		return $this->_setPrimary ( $thoughtId );
	}
	
	/**
	 * Get the thoughtId of the linked thought
	 *
	 * Returns key to the thought referenced by the neuron object.
	 *
	 * @return mixed
	 */
	function getThought() {
		return $this->_getPrimary ();
	}
	
	/**
	 * Link the neuron to a symbol
	 *
	 * Links one side of the neuron to a symbol
	 *
	 * @param mixed $symbol
	 *        	to link to
	 * @return mixed symbol
	 *        
	 * @todo Allow bulk linking of thoughts to a given symbol
	 */
	function linkToSymbol($symbol) {
		return $this->_setSecondary ( $symbol );
	}
	
	/**
	 * Get a linked symbol
	 *
	 * @return mixed symbol
	 */
	function getSymbol() {
		return $this->_getSecondary ();
	}
	
	/**
	 * Writes a neuron to the database.
	 *
	 * Writes the current thought object into the database. Uses adodb's
	 * insertSQL function. The datestamp is set automatically.
	 *
	 * If the database does not support transactions, the returned ID
	 * may not be correct.
	 *
	 * @param integer $thoughtId        	
	 * @param mixed $symbol        	
	 * @return integer of written neuron, else NULL
	 */
	function insertNeuron($thoughtId = NULL, $symbol = NULL) {
		$this->logMsg ( sprintf ( ">>> insertNeuron %s, %s", $thoughtId, $symbol ), MM_ALL );
		
		$returnCode = 0;
		
		if (! isset ( $symbol ))
			$symbol = $this->getSymbol ();
		if (! isset ( $thoughtId ))
			$thoughtId = $this->getThought ();
		
		$currentThought = $this->linkToThought ( $thoughtId );
		$currentSymbol = trim ( $this->linkToSymbol ( $symbol ) );
		
		if ($currentThought == NULL || $currentThought == 0 || $currentSymbol == NULL || $currentSymbol == "") {
			// $this->logMsg( "Attempt to insert improperly-defined neuron", MM_ERROR );
		} else {
			
			// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			
			// If no thoughtId, set it to an impossible number.
			if ($thoughtId == NULL)
				$thoughtId = - 1;
				
				// Select an empty record from the database to use as a
				// template for the insert.
			$record = $dbConn->Execute ( "SELECT * FROM TsNeuron
										WHERE thought = $thoughtId AND symbol = '$currentSymbol'" );
			
			// Check for existing neuron
			$recordSet = &$record;
			if (! $recordSet->EOF) {
				// Neuron exists. No need to rewrite.
				$this->logMsg ( sprintf ( "Ignoring existing tsNeuron (%s,%s)", $thoughtId, $currentSymbol ), MM_INFO );
			} else {
				// Fetch the noiseword status of the linked symbol.
				$symbolRecord = $dbConn->Execute ( "SELECT noiseWord FROM Symbol WHERE symbol = '$currentSymbol'" );
				
				if ($symbolRecord->EOF) {
					$this->logMsg ( "Error running query", MM_ERROR );
				} else {
					$enabled = $symbolRecord->fields [0];
				}
				
				if (! isset ( $enabled ) || $enabled == 1) {
					$enabled = 1;
				} else {
					$enabled = 0;
				}
				$this->logMsg ( sprintf ( "Symbol enabled: '%s'", $enabled ), MM_INFO );
				
				// Set the default dominance
				if (! $defDominance = $this->getDominance ()) {
					$defDom = $this->readParam ( 'default_tsneuron_dominance' );
					$defDominance = $this->setDominance ( $defDom );
				}
				$this->logMsg ( sprintf ( "Dominance set to %s", $defDominance ), MM_INFO );
				
				// Update the values for the new record to the values of the
				// object. New thoughtId is generated automatically in database.
				$newRecord = array ();
				$newRecord ['thought'] = $this->getThought ();
				$newRecord ['symbol'] = $this->getSymbol ();
				$newRecord ['spatiality'] = $this->setSpatiality ( 0 );
				$newRecord ['dominance'] = $this->getDominance ();
				$newRecord ['enabled'] = $this->setEnabled ( $enabled );
				$newRecord ['interacted'] = $this->setInteracted ();
				
				$insertSQL = $dbConn->GetInsertSQL ( $record, $newRecord );
				if ($dbConn->Execute ( $insertSQL ) === FALSE) {
					// Error in SQL execution
					$returnCode = NULL;
					$this->logMsg ( sprintf ( "%s", $dbConn->ErrorMsg () ), MM_ERROR );
				} else {
					// If sql executed successfully, return the ID.
					if ($insertSQL != NULL)
						$returnCode = $this->getThought ();
				}
			}
		}
		isset ( $newRecord ['symbol'] ) ? ($retSymbol = $newRecord ['symbol']) : ($retSymbol = NULL);
		$this->logMsg ( sprintf ( "<<< insertNeuron (%s, '%s')", $returnCode, $retSymbol ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Update a neuron in the database
	 *
	 * Fetches a neuron from the database given a thoughtID, updates the
	 * neuron with any non-NULL values from the object, and then writes
	 * the changed neuron back into the databsae.
	 *
	 * @return integer of written neuron, else NULL
	 */
	function updateNeuron() {
		$this->logMsg ( ">>> updateNeuron", MM_ALL );
		
		$returnCode = 0;
		
		// Connect to the database and execute the query
		$dbConn = $this->dbconn;
		
		// Select the record from the database to update
		$sql = "SELECT * FROM TsNeuron	WHERE thought = " . $this->getThought () . " AND symbol = '" . $this->getSymbol () . "'";
		
		$record = $dbConn->Execute ( $sql );
		
		// Update the values for the record to the values of the object
		$updateRecord = array ();
		// if ( $this->getThought() != NULL ) $updateRecord["thought"] = $this->getThought();
		if ($this->getSymbol () != NULL)
			$updateRecord ["symbol"] = $this->getSymbol ();
		if ($this->getSpatiality () != NULL)
			$updateRecord ["spatiality"] = $this->getSpatiality ();
		if ($this->getDominance () != NULL)
			$updateRecord ["dominance"] = $this->getDominance ();
		if ($this->getEnabled () != NULL)
			$updateRecord ["enabled"] = $this->getEnabled ();
		$updateRecord ["interacted"] = $this->setInteracted ();
		
		// Update the database record
		$updateSQL = $dbConn->GetUpdateSQL ( $record, $updateRecord );
		$dbConn->Execute ( $updateSQL );
		
		// If sql executed successfully, return the ID.
		if ($updateSQL != NULL)
			$returnCode = $this->getThought ();
		
		$this->logMsg ( sprintf ( "<<< updateNeuron (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/**
	 * Removes one or more neurons from the database
	 *
	 * Deletes one or more neurons from the database, given one of the
	 * following scenarios:
	 *
	 * Delete all neurons with attached to a given thought
	 * Delete all neurons attached to a given symbol
	 * Delete the neuron that links the given thought with the given
	 * symbol.
	 *
	 * @param integer $thoughtId
	 *        	linked to this thought
	 * @param mixed $symbol
	 *        	linked to this symbol
	 * @return integer of neurons deleted, or FALSE if error
	 */
	function removeNeuron($thoughtId = NULL, $symbol = NULL) {
		$this->logMsg ( ">>> removeNeuron", MM_ALL );
		
		$returnCode = FALSE;
		$NullThoughtId = - 1;
		$NullSymbol = '[:GARBAGESYMBOLTHATCANTEXIST:]';
		
		if (! $thoughtId && $symbol) {
			$this->logMsg ( sprintf ( "Foolish attempt to delete all neurons from database! ABORTING!" ), MM_ERROR );
			$returnCode = FALSE;
		} else {
			
			// Set null thoughtID to garbage number to force the query to work.
			if (! $thoughtId)
				$thoughtId = $NullThoughtId;
			if (! $symbol)
				$symbol = $NullSymbol;
				
				// Connect to the database and execute the query
			$dbConn = $this->dbconn;
			
			// Select the record from the database to update
			$attrib = array (
					'%THOUGHTID%' => $thoughtId,
					'%SYMBOL%' => $symbol,
					'%NULLTHOUGHTID%' => $NullThoughtId,
					'%NULLSYMBOL%' => $NullSymbol 
			);
			
			// Fetch info for the neurons we're going to delete.
			$neuronList = $this->getAction ( 'test removeable tsNeurons', $attrib );
			$numberDeleted = count ( $neuronList );
			
			if ($numberDeleted > 0) {
				$success = $this->getAction ( 'remove tsNeurons', $attrib );
				! $success ? ($returnCode = FALSE) : ($returnCode = $numberDeleted);
			}
			
			$this->close;
		}
		
		$this->logMsg ( sprintf ( "<<< removeNeuron (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
}
?>
