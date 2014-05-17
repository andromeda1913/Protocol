<?PHP
global $MM_GLOBALS;

require_once ($MM_GLOBALS ['home'] . 'include/utilities.inc');
require_once ($MM_GLOBALS ['includes'] . 'cortex.inc.php');
require_once ($MM_GLOBALS ['includes'] . 'sensory.inc.php');

/*
 * Mind Class 2014 pashkovdenis@gmail.com
 */
class mind extends _brainobject {
	public $mode_read;
	public $user;
	public $meta;
	public $flowManager;
	private $session;
	private $mode = 1;
	public static $MODE_FAQ = 1;
	public static $MODE_DIALOG = 2;
	public $variant = false;
	private $current_flow = null;
	private $flow = null;
	
	// Construct :
	public function __construct() {
		_brainObject::_brainObject ();
		$this->flowManager = null;
	}
	public function setVariant($v = false) {
		$this->variant = $v;
		return $this;
	}
	public function setSessionId($id) {
		$this->session = $id;
		return $this;
	}
	public function setMode($mode) {
		$this->mode = $mode;
		return $this;
	}
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}
	function learnThought($summary, $detail, $displayRaw = FALSE) {
		$this->logMsg ( ">>> learnThought", MM_ALL );
		$returnCode = NULL;
		
		$thought = new thought ();
		$summary = $thought->setSummary ( $summary );
		$detail = $thought->setDetail ( $detail );
		$displayRaw = $thought->setDisplayRaw ( $displayRaw );
		$thoughtId = $thought->setThoughtId ( $thought->insertThought () );
		
		if (! isset ( $thoughtId )) {
			$this->logMsg ( "Unable to write thought to database", MM_STOP );
			return NULL;
		}
		
		$symbol = new symbol ();
		$symbolList = $symbol->parseSymbols ( "$summary $detail", ' ', MM_NO_HTML_SYMBOLS, MM_FREQ_WEIGHTING );
		
		$symbolList = $thought->pluginMgr->executePlugins ( 'learnThought_addSymbols_prefilter', $symbolList );
		
		$addedSymbols = $symbol->insertSymbol ( $symbolList );
		
		$defDom = $this->readParam ( 'default_tsneuron_dominance' );
		$rein = $this->readParam ( 'tsneuron_reinforcement' );
		$freqWt = $this->readParam ( 'new_tsneuron_freq_weighting' );
		
		foreach ( $symbolList as $rawSymbol => $freq ) {
			
			$neuron = new tsNeuron ();
			$neuron->linkToThought ( $thoughtId );
			$neuron->linkToSymbol ( $rawSymbol );
			
			$wtDom = $defDom + ($freq - 1) * ($freqWt * $rein);
			$this->logMsg ( sprintf ( "Freq is %s, weight param is %s", $freq, $freqWt ), MM_INFO );
			$this->logMsg ( sprintf ( "Setting dominance to %s", $wtDom ), MM_INFO );
			$neuron->setDominance ( $wtDom );
			
			$addedNeuron = $neuron->insertNeuron ();
			$this->logMsg ( "Added tsNeuron", MM_INFO );
		}
		$this->current_flow->attachThought ( $thoughtId );
		$this->logMsg ( sprintf ( "<<< learnThought (%s)", $thoughtId ), MM_ALL );
		return $thoughtId;
	}
	
	/*
	 * Learn new Thought For Out Needs 2014 pashkovdenis@gmail.com
	 */
	function learnThoughtPreweighted($summary, $detail, $misc = NULL, $sumMult = 1, $detMult = 1, $miscMult = 1, $displayRaw = FALSE) {
		global $MM_GLOBALS;
		$summary = mb_strtolower ( $summary );
		$detail = mb_strtolower ( $detail );
		
		$this->logMsg ( ">>> learnThought", MM_ALL );
		$returnCode = NULL;
		$raw = $detail;
		$detail = strip_tags ( $detail );
		$detail = substr ( $detail, 0, 700 );
		$summary = strip_tags ( $summary );
		
		// Commands :
		foreach ( $MM_GLOBALS ["commands"] as $c ) {
			$c->setDbo ( $this->dbconn );
			$c->setInput ( $detail );
			if (! $c->execute ())
				return false;
		}
		$thought = new thought ();
		$summary = $thought->setSummary ( $summary );
		$detail = $thought->setDetail ( $detail );
		$displayRaw = $thought->setDisplayRaw ( $displayRaw );
		$thoughtId = $thought->setThoughtId ( $thought->insertThought () );
		
		if (! isset ( $thoughtId )) {
			$this->logMsg ( "Unable to write thought to database", MM_ERROR );
			return NULL;
		}
		
		// Sysmbol
		if (! function_exists ( 'createNeurons' )) {
			function createNeurons($symbolList, $multiplier = 1, $thought) {
				$thoughtId = $thought->getThoughtId ();
				$defDom = $thought->readParam ( 'default_tsneuron_dominance' );
				$rein = $thought->readParam ( 'tsneuron_reinforcement' );
				$freqWt = $thought->readParam ( 'new_tsneuron_freq_weighting' );
				
				foreach ( $symbolList as $rawSymbol => $freq ) {
					$neuron = new tsNeuron ();
					$neuron->linkToThought ( $thoughtId );
					$neuron->linkToSymbol ( $rawSymbol );
					$wtDom = ($defDom + ($freq - 1) * ($freqWt * $rein)) * $multiplier;
					$thought->logMsg ( sprintf ( "Freq is %s, weight param is %s", $freq, $freqWt ), MM_INFO );
					$thought->logMsg ( sprintf ( "Setting dominance to %s", $wtDom ), MM_INFO );
					$neuron->setDominance ( $wtDom );
					$addedNeuron = $neuron->insertNeuron ();
					$thought->logMsg ( "Added tsNeuron", MM_INFO );
				}
			}
		}
		
		// Symbol
		$symbol = new symbol ();
		$symbolList = $symbol->parseSymbols ( $summary, ' ', MM_NO_HTML_SYMBOLS, MM_FREQ_WEIGHTING );
		$symbolList = $thought->pluginMgr->executePlugins ( 'learnThought_addSummarySymbols_prefilter', $symbolList );
		
		$addedSymbols = $symbol->insertSymbol ( $symbolList );
		createNeurons ( $symbolList, $sumMult, $thought );
		
		$symbolList = $symbol->parseSymbols ( $detail, ' ', MM_NO_HTML_SYMBOLS, MM_FREQ_WEIGHTING );
		$symbolList = $thought->pluginMgr->executePlugins ( 'learnThought_addDetailSymbols_prefilter', $symbolList );
		
		$addedSymbols = $symbol->insertSymbol ( $symbolList );
		createNeurons ( $symbolList, $detMult, $thought );
		
		if (isset ( $misc )) {
			$symbolList = $symbol->parseSymbols ( $misc, ' ', MM_NO_HTML_SYMBOLS, MM_FREQ_WEIGHTING );
			$symbolList = $thought->pluginMgr->executePlugins ( 'learnThought_addMiscSymbols_prefilter', $symbolList );
			$addedSymbols = $symbol->insertSymbol ( $symbolList );
			createNeurons ( $symbolList, $detMult, $thought );
		}
		// Update as Dialog
		if ($this->mode == self::$MODE_DIALOG)
			$this->dbconn->query ( "UPDATE Thought SET session_id='{$this->session}' , learn='1',  is_variant='" . ($this->variant ? 1 : 0) . "'  WHERE thoughtId='{$thoughtId}'  " );
			
			// Create Map For It :
		$map_id = (new Map ())->setString ( $detail )->createMap ();
		
		// $this->current_flow->attachThought($thoughtId);
		$this->dbconn->query ( "UPDATE Thought SET map_id='{$map_id}' WHERE  thoughtId='{$thoughtId}'    " );
		$this->dbconn->query ( "UPDATE Thought SET flow_id='{$this->current_flow->id}' ,position='{$this->current_flow->position}' WHERE  thoughtId='{$thoughtId}'    " );
		return $thoughtId;
	}
	function updateLearnedThought($thoughtId, $summary, $detail) {
		$this->logMsg ( ">>> updateLearnedThought", MM_ALL );
		
		$returnCode = NULL;
		
		$thought = new thought ();
		$newId = $thought->updateThought ( $thoughtId, $summary, $detail );
		
		if (! isset ( $newId )) {
			$this->logMsg ( "Unable to write thought to database", MM_STOP );
		} else {
			
			// Parse the summary and detail into symbols
			$symbol = new symbol ();
			$symbolList = $symbol->parseSymbols ( "$summary $detail", ' ', TRUE, TRUE );
			$addedSymbols = $symbol->insertSymbol ( $symbolList );
			$dbconn = &$symbol->dbconn;
			
			// Create the tsneurons one at a time
			$defDom = $this->readParam ( 'default_tsneuron_dominance' );
			$rein = $this->readParam ( 'tsneuron_reinforcement' );
			$freqWt = $this->readParam ( 'new_tsneuron_freq_weighting' );
			
			foreach ( $symbolList as $rawSymbol => $freq ) {
				
				// Check for existing neuron
				$qSymbol = $dbconn->qstr ( $rawSymbol );
				$sql = "SELECT thought, symbol FROM TsNeuron WHERE thought = $thoughtId " . "AND RTRIM(symbol) = $qSymbol";
				$result = $dbconn->Execute ( $sql );
				if ($result->RowCount () != 0) {
					$this->logMsg ( "tsNeuron already exists", MM_INFO );
				} else {
					// Create a neuron object and write it to the database
					$neuron = new tsNeuron ();
					$neuron->linkToThought ( $thoughtId );
					$neuron->linkToSymbol ( $rawSymbol );
					
					// Get the default dominance
					$wtDom = $defDom + ($freq - 1) * ($freqWt * $rein);
					$this->logMsg ( sprintf ( "Freq is %s, weight param is %s", $freq, $freqWt ), MM_INFO );
					$this->logMsg ( sprintf ( "Setting dominance to %s", $wtDom ), MM_INFO );
					$neuron->setDominance ( $wtDom );
					
					$addedNeuron = $neuron->insertNeuron ();
					$this->logMsg ( "Added tsNeuron", MM_INFO );
				}
			}
			$returnCode = $thoughtId;
		}
		$this->logMsg ( sprintf ( "<<< updateLearnedThought (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	function learnSymbol($thoughtId = NULL, $symbol = NULL) {
		$this->logMsg ( sprintf ( ">>> learnSymbol (%s,%s)", $thoughtId, $symbol ), MM_ALL );
		
		$returnCode = NULL;
		
		if (! isset ( $symbol ) || ! isset ( $thoughtId )) {
			$this->logMsg ( "learnSymbol is missing arguments", MM_ERROR );
		} else {
			$newThought = new thought ();
			$newThought->setThoughtId ( $thoughtId );
			if ($newThought->readThoughtQuick ( $thoughtId, FALSE ) != $thoughtId) {
				$this->logMsg ( sprintf ( "Unable to find thought %s", $thoughtId ), MM_WARN );
			} else {
				// Create a symbol object
				$newSymbol = new symbol ();
				$newSymbol->setSymbol ( $symbol );
				$insertSymbol = $newSymbol->insertSymbol ();
				if ($insertSymbol === NULL) {
					$this->logMsg ( sprintf ( "Unable to insert symbol '%s'", $symbol ), MM_ERROR );
				} else {
					$this->logMsg ( sprintf ( "Created symbol '%s', linking to thought '%s'", $symbol, $thoughtId ), MM_INFO );
					$newNeuron = new tsNeuron ();
					$newNeuron->linkToThought ( $thoughtId );
					$newNeuron->linkToSymbol ( $insertSymbol );
					$insertNeuron = $newNeuron->insertNeuron ();
					$returnCode = $insertNeuron;
				}
			}
		}
		
		$this->logMsg ( sprintf ( "<<< learnSymbol (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	function reinforceMemory($thoughtId = NULL, $queryId = NULL, $reinforcement = "UNKNOWN") {
		$this->logMsg ( sprintf ( ">>> reinforceMemory (%s, %s, %s)", $thoughtId, $queryId, $reinforcement ), MM_ALL );
		
		$returnCode = NULL;
		
		switch ($reinforcement) {
			
			case "POSITIVE" :
				
				// Validate we have a valid queryId and thoughtId
				if (! isset ( $queryId ) || ! isset ( $thoughtId )) {
					$this->logMsg ( sprintf ( "Missing query (%s) or thought (%s)", $queryId, $thoughtId ), MM_ERROR );
				} else {
					$dbConn = &$this->dbconn;
					
					// Setup and perform query
					$attrib = array (
							'%THOUGHTID%' => $thoughtId,
							'%QUERYID%' => $queryId,
							'%REINFORCEMENT%' => $this->readParam ( 'tsneuron_reinforcement' ) 
					);
					$recordSet = $this->getAction ( 'reinforce memory', $attrib );
					$numUpdated = $dbConn->Affected_Rows ();
					
					$attrib = array (
							'%THOUGHTID%' => $thoughtId,
							'%QUERYID%' => $queryId 
					);
					$recordSet = $this->getAction ( 'show missing symbols', $attrib );
					
					while ( ! $recordSet->EOF ) {
						
						$stimulus = $recordSet->fields [0];
						
						$newSymbol = new symbol ();
						$stimulus = $newSymbol->setSymbol ( $stimulus );
						
						$newSymbol->insertSymbol ();
						
						$this->logMsg ( sprintf ( "Added symbol '%s'", trim ( $stimulus ) ), MM_INFO );
						// $this->learnSymbol( $thoughtId, $stimulus );
						$recordSet->Movenext ();
					}
					
					$attrib = array (
							'%THOUGHTID%' => $thoughtId,
							'%QUERYID%' => $queryId 
					);
					$recordSet = $this->getAction ( 'show missing neurons', $attrib );
					
					while ( ! $recordSet->EOF ) {
						
						$stimulus = $recordSet->fields [0];
						$newNeuron = new tsNeuron ();
						
						$neuronId = $newNeuron->insertNeuron ( $thoughtId, $stimulus );
						$this->logMsg ( sprintf ( "Adding neuron '%s'", trim ( $neuronId ) ), MM_INFO );
						
						$recordSet->Movenext ();
					}
					
					$returnCode = $numUpdated;
				}
				
				$decisionInfo = new decision ();
				$decisionInfo->updateDecision ( $queryId, $thoughtId, 'ACCEPTED' );
				
				break;
			
			case "NEGATIVE" :
				$dbConn = $this->dbconn;
				
				if (! $queryId) {
					
					// No query or thought, so run a generic decision cleanu
					$this->logMsg ( "Weakening unused memories", MM_INFO );
					
					$recordSet = $this->getAction ( 'weaken unused memories' );
				} else {
					
					if ($thoughtId) {
						
						// Have a thought, so weaken a specific memory
						$this->logMsg ( sprintf ( "Weakening memory for thought %s and query %s", $thoughtId, $queryId ), MM_INFO );
						
						// Setup and perform query
						$attrib = array (
								'%THOUGHTID%' => $thoughtId,
								'%QUERYID%' => $queryId 
						);
						$recordSet = $this->getAction ( 'weaken memories for a thought', $attrib );
					} else {
						
						// No thought specified, so weaken all unsuccessful decisions associated
						// with the query.
						$this->logMsg ( sprintf ( "Weakening useless memories for query %s", $queryId ), MM_INFO );
						
						// Setup and perform query
						$attrib = array (
								'%QUERYID%' => $queryId 
						);
						$recordSet = $this->getAction ( 'weaken memories for a query', $attrib );
					}
				}
				
				// $numUpdated = $dbConn->Affected_Rows();
				$numUpdated = 1;
				$returnCode = $numUpdated;
				$decisionInfo = new decision ();
				$decisionInfo->updateDecision ( $queryId, $thoughtId, 'REJECTED' );
				
				break;
			
			default :
				// Don't need to do anything
				$decisionInfo = new decision ();
				$decisionInfo->updateDecision ( $queryId, $thoughtId, 'UNSURE' );
				
				$returnCode = NULL;
				break;
		}
		
		$this->logMsg ( sprintf ( "<<< reinforceMemory (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	// Some Data
	public function getCommandsReposne() {
		global $MM_GLOBALS;
		$str = "";
		foreach ( $MM_GLOBALS ["commands"] as $c ) {
			$str .= " " . $c->raw;
		}
		return $str;
	}
	
	/*
	 * Ask For Query
	 */
	function ask($queryText = NULL, $email = 'UNKNOWN', $status = 'ACTIVE', $metaquery = '') {
		global $MM_GLOBALS;
		$queryText = mb_strtolower ( $queryText );
		
		$this->logMsg ( ">>> ask", MM_ALL );
		
		$returnCode = NULL;
		$symbols = explode ( " ", strtoupper ( $queryText ) );
		
		if (! isset ( $queryText )) {
			$this->logMsg ( "Attempt to insert improperly-defined query", MM_ERROR );
		} else {
			
			// Create a query and write it to the database
			$query = new query ();
			$queryText = $query->setQueryText ( $queryText );
			$queryId = $query->setQueryId ( $query->insertQuery ( $queryText, NULL, $_COOKIE ['search'] ) );
			
			// Parse Commands
			foreach ( $MM_GLOBALS ["commands"] as $c ) {
				$c->setDbo ( $this->dbconn );
				$c->setInput ( $queryText );
				if (! $c->execute ( $queryId ))
					return false;
			}
			
			if (isset ( $queryId )) {
				
				// Parse query into stimuli
				$stimulus = new symbol ();
				$stimulusList = $stimulus->parseSymbols ( $queryText );
				
				// PLUGIN HOOK: Manipulate parsed stimulus list
				$stimulusList = $this->pluginMgr->executePlugins ( 'queryStimuli_prefilter', $stimulusList );
				
				// Create each stimulus and write it into the database.
				foreach ( $stimulusList as $rawStimulus ) {
					
					// build stimulus object.
					$newStimulus = new stimulus ();
					$stimulusText = $newStimulus->setStimulus ( $rawStimulus );
					$stimulusQuery = $newStimulus->linkToQuery ( $queryId );
					
					// Write stimulus to database.
					$addedStimulus = $newStimulus->insertStimulus ();
					if ($addedStimulus === NULL) {
						$this->logMsg ( sprintf ( "Error inserting stimulus '%s'", $stimulusText ), MM_ERROR );
						$addedStimulus = $stimulusText;
					} else {
						$this->logMsg ( sprintf ( "Added stimulus '%s' to query %s", $addedStimulus, $queryId ), MM_INFO );
					}
				}
				
				$dbConn = $this->dbconn;
				$attrib = array (
						'%QUERYID%' => $queryId,
						'%THOUGHTSTATUS%' => $status 
				);
				
				$record = $this->getAction ( 'think', $attrib );
				$returnCode = $queryId;
			}
		}
		$this->logMsg ( sprintf ( "<<< ask (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
	
	/*
	 * Get Answers From System pashkovdenis@gmail.com 2014
	 */
	function answers($queryId = NULL, $status = 'ACTIVE', $cache = TRUE, $forceUpdate = FALSE, $COMMANDS = TRUE) {
		global $MM_GLOBALS;
		
		$this->logMsg ( sprintf ( ">>> answers (%s)", $queryId ), MM_ALL );
		
		$answers = NULL;
		$dbConn = $this->dbconn;
		if (! isset ( $queryId ) or ! isset ( $status )) {
			$this->logMsg ( sprintf ( "Either queryId (%s) or status (%s) is unset.", $queryId, $status ), MM_INFO );
			return NULL;
		}
		
		$attrib = array (
				'%QUERYID%' => $queryId,
				'%STATUS%' => $status 
		);
		$recordSet = $this->getAction ( 'fetch answers', $attrib, $this->readParam ( 'max_results_returned' ) );
		
		$record = 0;
		
		while ( ! $recordSet->EOF ) {
			
			$thoughtId = $recordSet->fields [0];
			$summary = trim ( $recordSet->fields [1] );
			$strength = $recordSet->fields [2];
			$status = $recordSet->fields [3];
			$map_id = $recordSet->fields ["map_id"];
			
			$detail = $recordSet->fields ["detail"];
			
			if ($COMMANDS) {
				foreach ( $MM_GLOBALS ["commands"] as $c ) {
					$c->setDbo ( $this->dbconn );
					$c->setOutput ( $summary );
					$summary = $c->execute ( $thoughtId );
				}
			}
			 
			
			
			
			// Get List of  maps  for  extracting : 
			$maps =  Map::getGeneralMaps($detail);  
			
 		 	if (count($maps)){

		 		foreach($maps as $mid){
		 	  
		 		 $map =  new Map(); 
		 		 $map ->id =   $mid ;   
		 		 $ar = $map->extract ( $detail, $mid );
		 		 $ex = array_shift ( $ar );
		 		 
		 		 if (count ( $ex ) > 0)
		 		 	$detail = join ( " ", $ex );
		 		
		 		}
		 		
		 	}
			
		 	
		  
		 	 
			
			$answers [$record] = array (
					$thoughtId,
					$summary,
					$strength,
					$status,
					$detail,
					$recordSet->fields [5],
					"map_id" => $map_id 
			);
			$record ++;
			$recordSet->MoveNext ();
		}
		
		return $answers;
	}
	
	/**
	 * Create symbols and neurons from an existing thought detail.
	 *
	 * @param string $summary
	 *        	text
	 * @param string $detail
	 *        	text or URL.
	 * @return integer of new thought, NULL on error.
	 */
	function relearnThought($thoughtId, $detail) {
		set_time_limit ( 360 );
		
		global $ADODB_FETCH_MODE;
		$dbconn = $this->dbconn;
		$thought = new thought ();
		
		// Strip tags from the detail
		$detail = preg_replace ( "/<[^<>]*>/", ' ', $detail );
		
		// Strip non-symbol characters
		$detail = trim ( eregi_replace ( " {2,}", " ", eregi_replace ( "[^a-zA-Z0-9\']", " ", $detail ) ) );
		
		// Build the symbol list
		$newSymbols = array_unique ( split ( "[^a-zA-Z0-9\']", strtoupper ( $detail ) ) );
		
		// Fetch the symbols and the neurons, if they exist, then build or reinforce
		// as necessary.
		$sql = "SELECT Symbol.symbol FROM Symbol, TsNeuron " . "WHERE Thought = $thoughtId AND Symbol.symbol = TsNeuron.Symbol " . "ORDER BY Symbol.symbol";
		
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$result = $dbconn->Execute ( $sql );
		$ADODB_FETCH_MODE = ADODB_FETCH_DEFAULT;
		
		// Create an array of all existing TsNeurons linked to this thought
		$existingTsNeurons = array ();
		while ( ! $result->EOF ) {
			$existingTsNeurons [] = $result->fields [0];
			$result->MoveNext ();
		}
		
		// Array of new TsNeurons to be added
		$addTsNeurons = array_diff ( $newSymbols, $existingTsNeurons );
		
		// Array of existing TsNeurons to be updated
		$updateTsNeurons = array_intersect ( $existingTsNeurons, $newSymbols );
		
		// Create an array of Symbols to be added
		// Check addTsNeurons against existing symbols to see what symbols need to be added
		$where = "(";
		foreach ( $addTsNeurons as $testSymbol ) {
			$where .= ",'$testSymbol'";
		}
		$where .= ")";
		$where = preg_replace ( "/\(,/", "(", $where );
		
		$sql = "SELECT symbol FROM Symbol WHERE symbol in $where";
		$result = $dbconn->Execute ( $sql );
		
		// Array of Symbols that DON'T need to be added
		$existingSymbols = $result->fields;
		
		// Array of Symbols that NEED to be added
		$addSymbols = array ();
		foreach ( $addTsNeurons as $symbol ) {
			if (! in_array ( $symbol, $existingSymbols )) {
				$addSymbols [] = $symbol;
			}
		}
		
		// Add symbols that don't exist
		$symbol = new symbol ();
		$symbol->insertSymbol ( $addSymbols );
		
		// Add tsNeurons and symbols that need to be added
		foreach ( $addTsNeurons as $tsNeuron ) {
			$neuron = new tsNeuron ();
			$neuron->insertNeuron ( $thoughtId, $tsNeuron );
		}
		
		// Update existing tsNeurons
		// foreach( $updateTsNeurons as $tsNeuron ) {
		// }
	}
	function relearnDetail($thoughtId, $detail) {
		$this->logMsg ( ">>> learnDetail", MM_ALL );
		
		set_time_limit ( 360 );
		
		$returnCode = NULL;
		
		// Create a thought object and write it to the database
		$thought = new thought ();
		
		// Remove any non-alphanumerics except apostrophes
		$symbolList = split ( "[^a-zA-Z0-9\']", eregi_replace ( " {2,}", " ", eregi_replace ( "[^a-zA-Z0-9\']", " ", $detail ) ) );
		
		// Create the symbols and tsneuron, one at a time, and then
		// write each set into the database.
		$neurons = 0;
		foreach ( $symbolList as $rawSymbol ) {
			
			// Create a symbol object and write it to the database
			$symbol = new symbol ();
			$requestedSymbol = $symbol->setSymbol ( $rawSymbol );
			
			if ($requestedSymbol != "") {
				$addedSymbol = $symbol->insertSymbol ();
				
				if (! isset ( $addedSymbol )) {
					$this->logMsg ( sprintf ( "Error inserting symbol '%s' (it may already exist)" ), $requestedSymbol, MM_WARN );
					// The following allows neurons to be added even if the
					// symbol is a duplicate and throws an error.
					$addedSymbol = $requestedSymbol;
				} else {
					$this->logMsg ( sprintf ( "Added symbol '%s'", $addedSymbol ), MM_INFO );
				}
				
				// Create a neuron object and write it to the database
				$neuron = new tsNeuron ();
				$neuron->linkToThought ( $thoughtId );
				$neuron->linkToSymbol ( $requestedSymbol );
				$addedNeuron = $neuron->insertNeuron ();
				$this->logMsg ( "Added tsNeuron", MM_INFO );
				
				// Count the added neurons
				$neurons ++;
			}
		}
		$returnCode = $thoughtId;
		$this->logMsg ( sprintf ( "<<< relearnDetail (%s)", $returnCode ), MM_ALL );
		return $returnCode;
	}
}
?>
