<?PHP
 
define ( 'MM_FREQ_WEIGHTING', TRUE );
define ( 'MM_NO_FREQ_WEIGHTING', FALSE );
define ( 'MM_HTML_SYMBOLS', FALSE );
define ( 'MM_NO_HTML_SYMBOLS', TRUE );
define ( 'MM_PARSE_LINEAR', 'LINEAR' );
define ( 'MM_PARSE_FREQUENCY', 'FREQUENCY' );
define ( 'MM_STOP', 0 );
define ( 'MM_ERROR', 1 );
define ( 'MM_WARN', 2 );
define ( 'MM_NOTICE', 3 );
define ( 'MM_INFO', 4 );
define ( 'MM_ALL', 5 );
define ( 'MM_EVENT_ADD', 'ADD' );
define ( 'MM_EVENT_DELETE', 'DELETE' );
define ( 'MM_EVENT_CHANGE', 'CHANGE' );
define ( 'MM_EVENT_EXECUTE', 'EXECUTE' );
define ( 'MM_EVENT_MESSAGE', 'MESSAGE' );
define ( 'MM_STRENGTHEN', 'POSITIVE' );
define ( 'MM_WEAKEN', 'NEGATIVE' );
define ( 'MM_IGNORE', 'UNKNOWN' );

/**
 * Database abstraction library
 */
require_once ($MM_GLOBALS ['adodb'] . 'adodb.inc.php');

// Adjust include path for Mindmeld includes
if (preg_match ( '/^WIN/', strtoupper ( PHP_OS ) )) {
	$delim = ";";
} else {
	$delim = ":";
}

ini_set ( 'include_path', ini_get ( 'include_path' ) . $delim . $MM_GLOBALS ['home'] . 'include' );

/**
 * Plugins manager
 */
include_once ($MM_GLOBALS ['includes'] . 'plugins.inc.php');

/**
 * Base Mindmeld class
 *
 * Provides methods and properties for the fundamental Mindmeld functions.
 * Initializes and shares out the database connection, exposes fundamental
 * paramaeters and configuration items, and provides message logging.
 */
class mindmeld {
	
	/**
	 *
	 * @var array Mindmeld configuration items (Global values)
	 */
	var $mmconfig;
	
	/**
	 *
	 * @var object Database connection object.
	 */
	var $dbconn;
	
	/**
	 *
	 * @var object Provides access to all platform-specific SQL queries.
	 */
	var $sqlActions;
	
	/**
	 *
	 * @var array Fundamental Parameters
	 */
	var $mmparam;
	
	/**
	 *
	 * @var object manager object\
	 */
	var $pluginMgr;
	
	/**
	 * Class constructor
	 *
	 * Initializes the database connection and sets initial properties.
	 *
	 * @global array		Mindmeld globals array
	 * @global string	ADOdb cache directory
	 * @param boolean $debug
	 *        	debug enabled (TRUE|FALSE)
	 * @param string $dbType        	
	 * @param string $dbHost
	 *        	server
	 * @param string $dbPort
	 *        	port
	 * @param string $dbName        	
	 * @param string $dbUser        	
	 * @param string $dbPasswd
	 *        	password
	 * @return mixed connection object
	 */
	function mindmeld($sqlDebug = NULL, $dbType = NULL, $dbHost = NULL, $dbPort = NULL, $dbName = NULL, $dbUser = NULL, $dbPasswd = NULL) {
		
		// Write the globals array into a class property
		$this->mmconfig = &$GLOBALS ['MM_GLOBALS'];
		
		global $ADODB_CACHE_DIR;
		$ADODB_CACHE_DIR = $this->mmconfig ['sql_cache_dir'];
		
		// Initialize connection information
		if (! $sqlDebug)
			$sqlDebug = $this->mmconfig ['sqlDebug'];
		if (! $dbType)
			$dbType = $this->mmconfig ['dbType'];
		if (! $dbHost)
			$dbHost = $this->mmconfig ['dbHost'];
		if (! $dbPort)
			$dbPort = $this->mmconfig ['dbPort'];
		if (! $dbName)
			$dbName = $this->mmconfig ['dbName'];
		if (! $dbUser)
			$dbUser = $this->mmconfig ['dbUser'];
		if (! $dbPasswd)
			$dbPasswd = $this->mmconfig ['dbPass'];
		if ($dbPort)
			$dbHost .= ":$dbPort";
			
			// Create the connection and login to the database.
		$this->dbconn = ADONewConnection ( $dbType );
		if (! $this->dbconn) {
			$this->logMsg ( "Unable to create connection to database $dbName on $dbHost:$dbPort ($dbType) as $dbUser", MM_STOP );
		}
		$this->dbconn->debug = $sqlDebug;
		if (! $this->dbconn->PConnect ( $dbHost, $dbUser, $dbPasswd, $dbName )) {
			$this->logMsg ( "Unable to connect to database $dbName on $dbHost:$dbPort ($dbType) as $dbUser", MM_STOP );
		}
		
		// Load and parse the SQL actions file
		if (! isset ( $GLOBALS ['sqlActions'] )) {
			$this->logMsg ( "Loading SQL Actions", MM_ALL );
			$this->sqlActions = $this->loadActions ( $dbType );
			if (! $this->sqlActions) {
				$this->logMsg ( "Unable to load SQL Actions for $dbType", MM_STOP );
			}
			$GLOBALS ['sqlActions'] = $this->sqlActions;
		} else {
			$this->logMsg ( "Using cached SQL Actions", MM_INFO );
			$this->sqlActions = $GLOBALS ['sqlActions'];
		}
		
		// Load fundamentals table into property.
		if (! isset ( $GLOBALS ['mmparam'] )) {
			$this->logMsg ( "Loading fundamentals", MM_INFO );
			$this->mmparam = $this->loadFundamentals ();
			if (! $this->mmparam) {
				$this->logMsg ( "Unable to load fundamental parameters", MM_STOP );
			}
			$GLOBALS ['mmparam'] = $this->mmparam;
		} else {
			$this->logMsg ( "Using cached fundamentals", MM_INFO );
			$this->mmparam = $GLOBALS ['mmparam'];
		}
		
		// Load the plugin manager and initialize the plugins
		if (! isset ( $GLOBALS ['pluginMgr'] )) {
			$this->logMsg ( "Calling plugins manager", MM_INFO );
			$this->pluginMgr = new pluginManager ( $this->mmconfig );
			if (! $this->pluginMgr) {
				$this->logMsg ( "Unable to load plugin manager", MM_ERROR );
			}
			$GLOBALS ['pluginMgr'] = $this->pluginMgr;
		} else {
			$this->logMsg ( "Using cached plugins", MM_INFO );
			$this->pluginMgr = $GLOBALS ['pluginMgr'];
		}
		return $this->dbconn;
	}
	
	/**
	 * Class destructor
	 *
	 * Closes open database connections
	 */
	function close() {
		$this->dbconn->Close ();
	}
	 
	
   // insert   Log Messages  :   
	function logMsg($message, $level = 1, $type = 999, $destination = "") { 
		if (LOG == 1){
		       $log  =  mysql_escape_string( $message) ;
			   $this->dbconn->Execute("INSERT INTO Log SET log='{$log}' , date='".date("Y-m-d")."'  "); 
		 }
	}
	
	/**
	 * Fetch the SQL query list into memory
	 *
	 * Reads the sqlActions file.
	 *
	 * @param string $dbVendor
	 *        	(must exist in rdbms directory)
	 * @param
	 *        	array		Query array keyed by name
	 */
	function loadActions($dbVendor = '') {
		$this->logMsg ( ">>> loadActions", MM_ALL );
		
		// Build filename using config
		if (! $dbVendor) { // Use the default
			$actionsFile = $this->mmconfig ['home'] . "/rdbms/SQL_actions.xml";
		} else { // Use the RDBMS-specific file
			$actionsPath = $this->mmconfig ['home'] . "rdbms";
			$dbVendor ? ($actionsVendor = $dbVendor) : ($actionsVendor = $this->mmconfig ['dbType']);
			$actionsFile = "$actionsPath/$actionsVendor.xml";
		}
		$this->logMsg ( sprintf ( "Loading actions path '%s'", $actionsPath ), MM_INFO );
		$this->logMsg ( sprintf ( "Loading config file '%s'", $actionsFile ), MM_INFO );
		
		// ToDo: Validate file exists, otherwise fall back to default.
		
		// Create the SQL Actions object that will parse the file and return the array
		$actions = new sqlActions ( $this->dbconn );
		
		$this->sqlActions = $actions->parseToArray ( $actionsFile );
		
		$actions->Destroy ();
		
		$this->logMsg ( sprintf ( "<<< loadActions" ), MM_ALL );
		return $this->sqlActions;
	}
	
	/**
	 * Get a requested SQL query from the query list
	 *
	 * Fetches the requested SQL action object ( or XML file, if necessary),
	 * substitutes any values provided in the parameters array, and executes
	 * the resulting SQL statement(s). Caching is not currently implemented.
	 *
	 * @param string $sqlAction
	 *        	action name
	 * @param array $parameters
	 *        	array
	 * @param integer $limit
	 *        	returned
	 * @param boolean $cache
	 *        	results if TRUE
	 * @return array result set(s)
	 */
	function getAction($sqlAction, $parameters = '', $limit = '', $cache = TRUE) {
		$this->logMsg ( sprintf ( ">>> getAction (%s)", $sqlAction ), MM_ALL );
		
		$dbconn = &$this->dbconn;
		$actionArray = $this->sqlActions [$sqlAction];
		if (isset ( $limit ) and $limit != '')
			$maxResults = $this->readParam ( 'max_results_returned' );
		
		if (! $actionArray) {
			$this->logMsg ( sprintf ( "Action '%s' returned no SQL", $sqlAction ), MM_WARN );
			return FALSE;
		}
		
		$this->logMsg ( sprintf ( "Action '%s'", $sqlAction ), MM_INFO );
		
		// Loop through the queries in the queryset
		if (count ( $actionArray ) > 1) {
			$result = array ();
		}
		foreach ( $actionArray as $sql ) {
			$this->logMsg ( sprintf ( "Raw action is '%s'", $sql ), MM_INFO );
			if ($parameters) {
				// Loop through the parameters making substitutions
				while ( list ( $key, $value ) = each ( $parameters ) ) {
					;
					$this->logMsg ( sprintf ( "Replaced '%s' with '%s'", $key, $value ), MM_INFO );
					$sql = str_replace ( $key, $value, $sql );
					$sql = str_replace ( "&lt;", "<", $sql );
					$sql = str_replace ( "&gt;", ">", $sql );
					$sql = str_replace ( "< =", "<=", $sql );
					$sql = str_replace ( "> =", ">=", $sql );
				}
				reset ( $parameters );
			}
			
			if (! $sql) {
				$this->logMsg ( "NULL SQL: continuing", MM_NOTICE );
				continue;
			}
			$this->logMsg ( sprintf ( "Executing '%s'", $sql ), MM_INFO );
			
			// Build the Execute variable function
			if (1 == 0) {
				$cacheDir = $this->mmconfig ['sql_cache_dir'];
			}
			
			// Execute the SQL
			if (count ( $actionArray ) == 1) {
				$this->logMsg ( "Executing for single return", MM_INFO );
				if ($limit) { // Limit the number of returned rows
					$this->logMsg ( sprintf ( "Select limit is %s", $maxResults ), MM_INFO );
					$result = $dbconn->SelectLimit ( $sql, $maxResults );
				} else {
					$result = $dbconn->Execute ( $sql );
				}
			} else {
				$this->logMsg ( "Executing for multiple returns", MM_INFO );
				if ($limit) { // Limit the number of returned rows
					$this->logMsg ( sprintf ( "Select limit is %s", $maxResults ), MM_INFO );
					$result = $dbconn->SelectLimit ( $sql, $maxResults );
				} else {
					$result [] = $dbconn->Execute ( $sql );
				}
			}
		}
		
	 
		return $result;
	}
	
	 
	function loadFundamentals() {
		$this->logMsg ( ">>> loadFundamentals", MM_ALL );
		
		$dbconn = $this->dbconn;
		$result = $dbconn->Execute ( "SELECT fname, fvalue, fstring FROM Fundamentals" );
		$resultArray = $result->GetAssoc ();
	     $param = [] ; 
	     
		foreach ( $resultArray as $key => $value )  { 
		  $param [$key] = $value [1];
	  		
		}
		$this->mmparam = $param;
  		return $param;
	}
	
	 
	function readParam($name) {
		$this->logMsg ( sprintf ( "readParam (%s is %s)", $name, $this->mmparam [$name] ), MM_INFO );
		return $this->mmparam [$name];
	}

/**
 * Log an event to the eventhistory
 *
 * @param string $event
 *        	Name
 * @param string $data
 *        	pertinent to event
 * @param string $descr
 *        	of event
 * @param string $user
 *        	ID, if available
 */
}

/**
 * Provides tools and utilities for fundamental mindmeld objects
 *
 * The fundamental object from which thoughts, neurons,
 * and all other brain components are derived
 */
class _brainObject extends mindmeld {
	
	/**
	 *
	 * @var integer Unique numeric ID for this _brainObject.
	 */
	var $_referenceId;
	
	/**
	 *
	 * @var boolean Flag specifying whether this object is enabled (TRUE|FALSE)
	 */
	var $_enabled;
	
	/**
	 *
	 * @var string Date when this object was created or was last updated
	 */
	var $_interacted;
	
	/**
	 *
	 * @var string Status of object
	 */
	var $_status;
	
	/**
	 * Class constructor
	 */
	function _brainObject($args = NULL) {
		return $this->mindmeld ( $args );
	}
	
	/**
	 * Set the ID of an object
	 *
	 * Sets a unique ID for this object.
	 *
	 * @param integer $referenceId        	
	 * @return integer ID
	 */
	function _setReferenceId($referenceId) {
		$this->_referenceId = $referenceId;
		return $this->_referenceId;
	}
	
	/**
	 * Get the ID of an object
	 *
	 * Returns the ID assigned to this object.
	 *
	 * @return integer of requested object
	 */
	function _getreferenceId() {
		return $this->_referenceId;
	}
	
	/**
	 * Set the enabled state of an object
	 *
	 * Sets the enabled flag for this object. The flag may be set to enabled
	 * (TRUE) or disabled (FALSE).
	 *
	 * @param boolean $enabled        	
	 * @return boolean state
	 */
	function setEnabled($enabled) {
		$this->_enabled = $enabled;
		return $this->_enabled;
	}
	
	/**
	 * Get the enabled state of an object
	 *
	 * Returns the current state of this object: enabled (TRUE) or disabled
	 * (FALSE).
	 *
	 * @return boolean state of object
	 */
	function getEnabled() {
		return $this->_enabled;
	}
	
	/**
	 * Set the timestamp of an object
	 *
	 * Sets the datestamp on this object to the specified date and time. If
	 * no argument is specified, the datestamp is set to the current date
	 * and time.
	 *
	 * @param string $datestamp        	
	 * @return string if successful, else NULL.
	 */
	function setInteracted($datestamp = "") {
		$this->logMsg ( sprintf ( ">>> setInteracted (%s)", $datestamp ), MM_ALL );
		
		if ($datestamp == "") {
			// No date was specified, use the current datetime.
			$assignedDate = time ();
		} else {
			// Use specified date
			$assignedDate = $datestamp;
		}
		$this->_interacted = $assignedDate;
		$this->logMsg ( sprintf ( "<<< setInteracted (%s)", $this->_interacted ), MM_ALL );
		return $this->_interacted;
	}
	
	/**
	 * Get the timestamp of an object
	 *
	 * Returns the datestamp from this object. The datestamp specifies the
	 * creation time of the object, or the last time it was updated.
	 *
	 * @return string of object
	 */
	function getInteracted() {
		return $this->_interacted;
	}
	
	/**
	 * Set the status of an object
	 *
	 * Sets the status code
	 *
	 * @param string $status        	
	 * @return string status
	 */
	function setStatus($status) {
		$this->_status = trim ( $status );
		return $this->_status;
	}
	
	/**
	 * Get the status of an object
	 *
	 * Returns the status code for this object
	 *
	 * @return string status
	 */
	function getStatus() {
		return $this->_status;
	}
	
	/**
	 * Parse a string into symbols
	 *
	 * Parses a string into symbols after stripping HTML tags, control characters,
	 * and other undesirables. It would be good to return frequency of occurance, as
	 * well.
	 *
	 * @param string $subject
	 *        	be parsed
	 * @param string $delimiter        	
	 * @param boolean $stripTags
	 *        	tags from the string
	 * @param boolean $rtnFreq
	 *        	"frequency of occurrance" column to the
	 *        	returned array.
	 * @return array containing a list of symbols or an associative array of
	 *         symbols and frequencies.
	 */
	function parseSymbols($subject, $delimiter = ' ', $stripTags = MM_NO_HTML_SYMBOLS, $rtnFreq = MM_NO_FREQ_WEIGHTING) {
		$allowedRegex = "[^\p{L}0-9\']";
		
		/*
		 * PLUGIN HOOK: Filter to manipulate a raw symbols string Provides direct access to a symbols string before it has been parsed by the symbol parser. Note: The hook does not provide information about how or why the symbol parser was called. All symbol-parsing requests, whether due to searches, learning, or any other source, will be processed by plugins that use this hook! @param string Raw test string @param string Requested symbol delimiter. @return string Filtered text string
		 */
		$subject = $this->pluginMgr->executePlugins ( 'parseSymbols_prefilter', $subject, $delimiter );
		
		// Make sure there are breaks between HTML tags
		$repl = ">$delimiter";
		$subject = str_replace ( '>', $repl, $subject );
		
		if ($stripTags) {
			// Strip out HTML tags and unsafe stuff
			$subject = preg_replace ( "/<head *>.*<\/head *>/", "", $subject );
			$subject = preg_replace ( "/<script .*>.*<\/script *>/", "", $subject );
			$subject = preg_replace ( "/<\?.*\?>/", "", $subject );
			$subject = preg_replace ( "<!--.*-->", "", $subject );
			$subject = preg_replace ( "/<[[:space:]]*([^>]*)[[:space:]]*>/", "<\\1>", $subject );
			$subject = preg_replace ( "/<a([^>]*)href=\"?([^\"]*)\"?([^>]*)>/", "<a href=\"\\2\">", $subject );
			$subject = preg_replace ( '/&\s;/', '', $subject ); // Strip html special chars
			$subject = strip_tags ( $subject );
		}
		
		// Strip any single quotes that aren't surrounded by letters
		$subject = preg_replace ( '/\'(\W)/', "$delimiter\1", $subject );
		$subject = preg_replace ( '/(\W)\'/', "\1$delimiter", $subject );
		
		$subject = preg_replace ( '/\'/', '', $subject ); // Strip out single quotes
		$subject = str_replace ( '\\', $delimiter, $subject ); // Strip out all slashes
		$subject = strtoupper ( trim ( $subject ) ); // Trim subject
		                                           
		// Tokenize
	 	$symbolList = preg_split ( "/" . $allowedRegex . "/u", trim ( preg_replace ( "$delimiter{2,}", $delimiter, preg_replace ( $allowedRegex, $delimiter, $subject ) ) ) );
		
	   if ($rtnFreq) {
			// Calculate frequencies
			$this->logMsg ( "Returning frequency data", MM_INFO );
			$symbolList = array_count_values ( $symbolList );
			$listType = 'FREQUENCY';
		} else {
			// De-dupe symbol list and remove empty entries
			$this->logMsg ( "Returning linear data", MM_INFO );
			$symbolList = array_values ( array_unique ( $symbolList ) );
			$listType = 'LINEAR';
		}
		
		/*
		 * PLUGIN HOOK: Filter to manipulate a parsed symbols list Provides access to a symbols list that results from a parsed symbols string. If he list is of type FREQUENCY, it is an associative array, the key being the symbol and the value being the number of times that symbol occurred in the raw symbols string. If the list is of type LINEAR, it is a numeric array with the value being the symbol. Note: The hook does not provide information about how or why the symbol parser was called. All symbol-parsing requests, whether due to searches, learning, or any other source, will be processed by plugins that use this hook! @param array Parsed symbols list @param string List type: FREQUENCY or LINEAR @return array Filtered symbols list
		 */
		$symbolList = $this->pluginMgr->executePlugins ( 'parseSymbols_postfilter', $symbolList, $listType );
		$words =  [] ;
		
		 /*
		 * Create Words Object from  Strings :  
		 * 2014   
		 * pashkovdenis@gmail.com  
		 *  
		 */
		 foreach($symbolList  as $id => $word){
			if (!$rtnFreq){
			 $word_object = new Word($word);
			 $words [$id] = $word_object->id ;
			}else{
				// post  
				$word_object = new Word($id);
				$words  [$word_object->id] = 1 ; 
			}
		 
		 }
		 $symbolList = $words ; 
		 return $symbolList;
	}
}

/**
 * Loads and parses an XML file, creating an array of "ready-to-run" SQL statements
 *
 * This class loads an RDBMS-specific SQL Actions XML file and creates an
 * array of queries based upon the name of each query set.
 */
class sqlActions {
	
	/**
	 *
	 * @var array containing SQL query sets
	 * @internal
	 *
	 */
	var $sqlArray;
	
	/**
	 *
	 * @var object Parser object
	 * @internal
	 *
	 */
	var $xmlParser;
	
	/**
	 *
	 * @var object connection object
	 */
	var $dbconn;
	
	/**
	 *
	 * @var object dbQuerySet object
	 * @internal
	 *
	 */
	var $querySet;
	
	/**
	 *
	 * @var string Current XML element
	 *      @private
	 */
	var $currentElement;
	
	/**
	 *
	 * @var string RDBMS Platform string
	 */
	var $dbType;
	
	/**
	 *
	 * @var long Magic Quotes Runtime value
	 */
	var $mgq;
	
	/**
	 * Constructor.
	 * Initialize the object
	 *
	 * @param object $dbconn
	 *        	object
	 */
	function sqlActions($dbconn) {
		
		// Initialize the environment
		// $this->mgq = get_magic_quotes_runtime();
		// set_magic_quotes_runtime(0);
		$this->dbconn = &$dbconn;
		$this->sqlArray = array ();
	}
	
	/**
	 * Loads and parses an XML file
	 *
	 * This method accepts a path to an xmlschema-compliant XML file,
	 * loads it, parses it, and uses it to create the SQL to generate the objects
	 * described by the XML file.
	 *
	 * @param string $file
	 *        	XML file
	 *        	
	 * @return array of SQL queries, ready to execute
	 */
	function ParseToArray($file) {
		
		// Create the parser
		$this->xmlParser = &$xmlParser;
		$xmlParser = xml_parser_create ();
		xml_set_object ( $xmlParser, $this );
		
		// Initialize the XML callback functions
		xml_set_element_handler ( $xmlParser, "_xmlcb_startElement", "_xmlcb_endElement" );
		xml_set_character_data_handler ( $xmlParser, "_xmlcb_cData" );
		
		// Open the file
		if (! ($fp = fopen ( $file, "r" ))) {
			die ( "Unable to open file" );
		}
		
		// Process the file
		while ( $data = fgets ( $fp, 4096 ) ) {
			if (! xml_parse ( $xmlParser, $data, feof ( $fp ) )) {
				die ( sprintf ( "XML error: %s at line %d", xml_error_string ( xml_get_error_code ( $xmlParser ) ), xml_get_current_line_number ( $xmlParser ) ) );
			}
		}
		
		// Return the array of queries
		return $this->sqlArray;
	}
	
	/**
	 * XML Callback to process start elements
	 *
	 * @access private
	 */
	function _xmlcb_startElement($parser, $name, $attrs) {
		$dbType = $this->dbType;
		if (isset ( $this->table ))
			$table = &$this->table;
		if (isset ( $this->index ))
			$index = &$this->index;
		if (isset ( $this->querySet ))
			$querySet = &$this->querySet;
		$this->currentElement = $name;
		
		// Process the element. Ignore unimportant elements.
		if (in_array ( trim ( $name ), array (
				"VENDOR",
				"COMMENT" 
		) )) {
			return FALSE;
		}
		
		switch ($name) {
			
			case "ACTION" : // Freeform SQL queryset
			               // Start a new query set.
				$actionId = $attrs ['ID'];
				// print "<P>Action <B>$actionId</B><UL>";
				$this->querySet = new _dbQuerySet ( $actionId );
				break;
			
			case "SQL" : // Queryset SQL query
			            // Begin adding a query to the current query set.
			            // print "<LI>SQL<OL>";
				if (isset ( $this->querySet )) {
					$this->querySet->buildQuery ();
				}
				break;
			
			default :
				print "OPENING ELEMENT '$name'<BR/>\n";
		}
	}
	
	/**
	 * XML Callback to process cDATA elements
	 *
	 * @access private
	 */
	function _xmlcb_cData($parser, $data) {
		$element = &$this->currentElement;
		
		if (trim ( $data ) == "")
			return;
			
			// Process the data depending on the element
		switch ($element) {
			
			case "SQL" : // Query SQL data
			            // print "<LI>$data</LI>";
				if (isset ( $this->querySet ) and isset ( $this->querySet->query ))
					$this->querySet->buildQuery ( $data );
				break;
			
			default :
			// print "<UL><LI>CDATA ($element) $data</UL>\n";
		}
	}
	
	/**
	 * XML Callback to process end elements
	 *
	 * @access private
	 */
	function _xmlcb_endElement($parser, $name) {
		
		// Process the element. Ignore unimportant elements.
		if (in_array ( trim ( $name ), array (
				"VENDOR",
				"COMMENT" 
		) )) {
			return FALSE;
		}
		
		switch (trim ( $name )) {
			
			case "SQL" : // Queryset element
			            // print "</OL>";
			            // Add finished query to the query set
				if (isset ( $this->querySet ) and isset ( $this->querySet->query ))
					$this->querySet->addQuery ();
				break;
			
			case "ACTION" : // Query SQL element
			               // print "</UL></P>";
			               // Add finished query set to the local array.
				if (isset ( $this->querySet )) {
					$querySQL = $this->querySet->create ();
					$this->sqlArray = array_merge ( $this->sqlArray, $querySQL );
					;
					$this->querySet->destroy ();
				}
				break;
			
			default :
			// print "<LI>CLOSING $name</UL>\n";
		}
	}
	
	/**
	 * Destructor
	 */
	function Destroy() {
		xml_parser_free ( $this->xmlParser );
		// set_magic_quotes_runtime( $this->mgq );
		unset ( $this );
	}
}

/**
 * Creates the SQL to execute a list of provided SQL queries
 *
 * This class compiles a list of SQL queries specified in the external file. This is
 * a private class used by the SQLActions class.
 *
 * @access private
 *        
 */
class _dbQuerySet {
	
	/**
	 *
	 * @var string of query set
	 */
	var $name;
	
	/**
	 *
	 * @var array containing a list of SQL queries
	 */
	var $querySet;
	
	/**
	 *
	 * @var string used to build of a query line by line
	 */
	var $query;
	
	/**
	 * Constructor.
	 * Initializes the queries array
	 */
	function _dbQuerySet($name) {
		$this->name = $name;
		$this->querySet = array ();
		$this->query = '';
	}
	
	/**
	 * Appends a line to a query that is being built line by line
	 *
	 * $param string	$data	Line of SQL data or NULL to initialize a new query
	 */
	function buildQuery($data = NULL) {
		if (isset ( $data )) {
			$this->query .= ' ' . trim ( $data );
		} else {
			$this->query = '';
		}
	}
	
	/**
	 * Adds a completed query to the query list
	 *
	 * @return string of added query
	 */
	function addQuery() {
		
		// Push the query onto the query set array
		$finishedQuery = $this->query;
		array_push ( $this->querySet, $finishedQuery );
		
		// Return the query set array
		return $finishedQuery;
	}
	
	/**
	 * Creates and returns the current query set
	 *
	 * @return array set
	 */
	function create() {
		$querySetArray [$this->name] = $this->querySet;
		return $querySetArray;
	}
	
	/**
	 * Destructor
	 */
	function destroy() {
		unset ( $this );
	}
}

/**
 * Manages the event log
 *
 * Provides methods to read and write events in the event log.
 * Events are logged or fetched by instantiating specific event types that
 * extend this class. Events are read and managed by calling this
 * class directly. Events contain 'userId', 'event', 'data', 'eventDate', and
 * 'description' fields.
 */
class mmEvent {
	
	/**
	 * '
	 * 
	 * @var string User ID, if available.
	 */
	var $userId;
	
	/**
	 * '
	 * 
	 * @var string Succinct event code. E.g. CREATE, DELETE, etc.
	 */
	var $event;
	
	/**
	 * '
	 * 
	 * @var string Key data specific to this instance.
	 */
	var $data;
	
	/**
	 * '
	 * 
	 * @var string Timestamp of occurrance.
	 */
	var $eventDate;
	
	/**
	 * '
	 * 
	 * @var string Additional useful information. I.e., Error information, etc.
	 */
	var $descr;
	function message($event, $data, $descr = '', $userId = NULL) {
		logMsg ( 'Logging generic event', MM_ALL );
		$this->event = $event;
		$this->data = $data;
		$this->descr = $descr;
		if (isset ( $userId ))
			$this->userId = $userId;
		
		$this->add ();
	}
	
	/**
	 * Adds the current log object to the database
	 */
	function add() {
		
		// Verify properties have been properly initialized
		if (! isset ( $this->userId )) {
			$this->userId = 'SYSTEM';
		}
		
		if (! isset ( $this->event )) {
			logMsg ( 'Requested to log event without event', MM_NOTICE );
			return NULL;
		}
		
		if (! isset ( $this->data )) {
			logMsg ( 'Requested to log event without data', MM_NOTICE );
			return NULL;
		}
		
		logMsg ( sprintf ( "Adding '%s' event (user is '%s')", $this->event, $this->userId ), MM_ALL );
		
		$mm = new mindmeld ();
		$dbconn = $mm->dbconn;
		
		// Prepare data for load
		if (! isset ( $this->descr )) {
			$this->descr = '';
		}
		
		// $this->eventDate = $dbconn->DBDate( time() );
		$this->eventDate = time ();
		
		// Use insertSQL to create the new event
		
		// Fetch an empty record
		$empty = $dbconn->Execute ( "SELECT * FROM EventHistory WHERE event = '[:_NULLEVENT-:]'" );
		
		// Initialize the replacement record
		$record = array ();
		
		$record ['userId'] = $this->userId;
		$record ['event'] = strtoupper ( $this->event );
		$record ['data'] = $this->data;
		$record ['descr'] = $this->descr;
		$record ['eventDate'] = $this->eventDate;
		
		$insertSQL = $dbconn->GetInsertSQL ( $empty, $record );
		if (! $insertSQL) {
			logMsg ( 'Error generating SQL to insert event', MM_NOTICE );
			return NULL;
		}
		
		$dbconn->Execute ( $insertSQL );
		
		logMsg ( 'Event added successfully', MM_ALL );
	}
	function addThought($descr = '', $userId = NULL) {
		logMsg ( 'Logging event: createThought', MM_ALL );
		$this->event = MM_EVENT_ADD;
		$this->data = 'Thought';
		$this->descr = $descr;
		if (isset ( $userId ))
			$this->userId = $userId;
		
		$this->add ();
	}
	function changeThought($descr = '', $userId = NULL) {
		logMsg ( 'Logging event: createThought', MM_ALL );
		$this->event = MM_EVENT_CHANGE;
		$this->data = 'Thought';
		$this->descr = $descr;
		if (isset ( $userId ))
			$this->userId = $userId;
		
		$this->add ();
	}
	function removeThought($descr = '', $userId = NULL) {
		logMsg ( 'Logging event: createThought', MM_ALL );
		$this->event = MM_EVENT_DELETE;
		$this->data = 'Thought';
		$this->descr = $descr;
		if (isset ( $userId ))
			$this->userId = $userId;
		
		$this->add ();
	}
	function dream($module, $descr = '', $userId = NULL) {
		logMsg ( 'Logging event: Dream Execution', MM_ALL );
		$this->event = MM_EVENT_EXECUTE;
		$this->data = $module;
		$this->descr = $descr;
		if (isset ( $userId ))
			$this->userId = $userId;
		
		$this->add ();
	}
}

// Only extend the Smarty class if smarty has been loaded.
if (class_exists ( 'Smarty' )) {
	
	// Include the themer
	include ($MM_GLOBALS ['home'] . "include/themer.inc.php");
	
	/**
	 * class mmHtmlTemplate
	 *
	 * This object extends the Smarty Template base object to include fundamental
	 * paths and variables.
	 *
	 * @package interfaces
	 * @todo Move this to the interfaces file.
	 */
	class mmHtmlTemplate extends Smarty {
		
		/**
		 * Class constructor
		 *
		 * Predefine smarty global variables to values specified in
		 * the Mindmeld configuration file.
		 */
		function mmHtmlTemplate($themeName = NULL) {
			global $MM_GLOBALS;
			
			$this->template_dir = $MM_GLOBALS ['home'] . 'interfaces/smarty/templates';
			$this->compile_dir = $MM_GLOBALS ['cache'] . 'templates_c';
			$this->config_dir = $MM_GLOBALS ['home'] . 'interfaces/smarty/configs';
			$this->cache_dir = $MM_GLOBALS ['cache'] . 'html_cache';
			
			$this->assign_by_ref ( "site", $MM_GLOBALS ['site'] );
			$this->assign_by_ref ( "urlAsk", $MM_GLOBALS ['url_ask'] );
			$this->assign_by_ref ( "urlLearn", $MM_GLOBALS ['url_learn'] );
			$this->assign_by_ref ( "urlManage", $MM_GLOBALS ['url_manage'] );
			$this->assign_by_ref ( "urlSelf", $MM_GLOBALS ['urlSelf'] );
			
			// $this->caching = TRUE;
			
			// Load the theme
			$this->assign_by_ref ( "theme", loadTheme ( $themeName ) );
			
			$this->assign ( 'app_name', 'MindMeld' );
		}
	}
}

/**
 * Bypass gettext for systems to do not support it
 *
 * Check for gettext and provide a dummy gettext function if it isn't available.
 *
 * @param string $passthru        	
 * @return string text
 */
if (! function_exists ( '_' )) {
	function _($passthru = NULL) {
		return $passthru;
	}
}

/**
 * Provide security for user input
 *
 * Provide security for user input from all sources. Format for
 * the input value is: "passed value, test sequence" where the
 * sequence is a pipe-delimited list of keywords and optional
 * arguments.
 *
 * The checklist sequences are:
 * type=INT|FLOAT|STRING
 * min={INTEGER|FLOAT}
 * max={INTEGER|FLOAT}
 * minlength={INTEGER}
 * maxlength={INTEGER}
 *
 * Function operates on the original string and returns
 * NULL if the string is acceptable or an error message if
 * if wasn't.
 *
 * @param mixed $value
 *        	check (by reference)
 * @param string $test
 *        	be performed
 * @return string message or NULL
 */
function checkInput(&$value, $test_sequence) {
	// Store the original value so we know how it changed.
	$rawValue = $value;
	$value = NULL;
	
	$errMsg = array (
			'INT_FAIL' => "Not an integer value",
			'FLOAT_FAIL' => "Not a float value",
			'BOOLEAN_FAIL' => "Not a boolean value",
			'MIN_CRITERIA' => "Bad criterion for minimum value",
			'MIN_FAIL' => "Value less than minimum",
			'MAX_CRITERIA' => "Bad criterion for maximum value",
			'MAX_FAIL' => "Value greater than maximum",
			'MINLEN_CRITERIA' => "Bad criterion from minimum length",
			'MINLEN_FAIL' => "Value less than minimum length",
			'MAXLEN_CRITERIA' => "Bad criterion from maximum length",
			'MAXLEN_FAIL' => "Value exceeds than maximum length",
			'REGEX_FAIL' => "Value fails regex match" 
	);
	
	// Parse the test sequence into individual tests
	$testList = explode ( "|", $test_sequence );
	
	// Apply each test
	foreach ( $testList as $test ) {
		
		// Break test into key and sequence
		list ( $key, $criteria ) = explode ( "=", $test, 2 );
		$key = strtoupper ( $key );
		
		// Handle any test aliases
		if ($key == "MINLEN")
			$key = "MINLENGTH";
		if ($key == "MAXLEN")
			$key = "MAXLENGTH";
			
			// Select the test
		switch ($key) {
			case "TYPE" : // Data type
				$criteria = strtoupper ( $criteria );
				
				if ($criteria == "INT") { // INT (integer) types
				                           // Regex match begin line followed by optional - or +
				                           // followed by one or more digits followed by end line.
					$goodMatch = preg_match ( "/^[-+]?[0-9]+$/", $rawValue );
					if (! $goodMatch) {
						$value = NULL;
						return $errMsg ['INT_FAIL']; // Fail out
					}
				}
				
				if ($criteria == "FLOAT") { // FLOAT (float) types
				                             // Regex match begin line followed by optional - or +
				                             // followed by one or more digits followed by optional
				                             // decimal point followed by one or more digits followed by
				                             // optional E followed by one or more digits followed by end line.
					$goodMatch = preg_match ( "/^[-+]?[0-9]+(\.[0-9]+)?([-+]?[eE][0-9]+)?$/", $rawValue );
					if (! $goodMatch) {
						$value = NULL;
						return $errMsg ['FLOAT_FAIL']; // Fail out
					}
				}
				
				if ($criteria == "BOOLEAN") { // Boolean types
				                               // Regex match case-insensitive begin line followed
				                               // by TRUE or FALSE followed by end line.
					$goodMatch = preg_match ( "/(?i)^true|false$/", $rawValue );
					if (! $goodMatch) {
						$value = NULL;
						return $errMsg ['BOOLEAN_FAIL']; // Fail out
					}
				}
				break;
			
			case "MIN" : // Numeric minimum value
			            // Criteria is the minimum allowed value
			            
				// Regex match begin line followed by optional - or +
			            // followed by one or more digits followed by optional
			            // decimal point followed by one or more digits followed by
			            // optional E followed by one or more digits followed by end line.
				if (! preg_match ( "/^[-+]?[0-9]+(\.[0-9]+)?([eE][0-9]+)?$/", $criteria )) { // bad criterion
					return $errMsg ['MIN_CRITERION'];
				} else {
					if ($rawValue < $criteria) {
						$value = NULL;
						return $errMsg ['MIN_FAIL']; // Failed test
					}
				}
				break;
			
			case "MAX" : // Numeric maximum value
			            // Criteria is the maximum allowed value
			            
				// Regex match begin line followed by optional - or +
			            // followed by one or more digits followed by optional
			            // decimal point followed by one or more digits followed by
			            // optional E followed by one or more digits followed by end line.
				if (! preg_match ( "/^[-+]?[0-9]+(\.[0-9]+)?([eE][0-9]+)?$/", $criteria )) { // Bad criterion
					return $errMsg ['MAX_CRITERIA'];
				} else {
					if ($rawValue > $criteria) {
						$value = NULL;
						return $errMsg ['MAX_FAIL']; // Failed test
					}
				}
				break;
			
			case "MINLENGTH" : // String minimum length
			                  // Criteria is the minimum number of allowed characters
				if (! preg_match ( "/^[0-9]+$/", $criteria )) { // bad criterion
					return $errMsg ['MINLEN_CRITERIA'];
				} else { // Good criterion
					if (strlen ( $rawValue ) < $criteria) {
						$value = NULL;
						return $errMsg ['MINLEN_FAIL']; // Too short
					}
				}
				break;
			
			case "MAXLENGTH" : // String maximum length
			                  // Criteria is the minimum number of allowed characters
				if (! preg_match ( "/^[0-9]+$/", $criteria )) { // Bad criterion
					return $errMsg ['MAXLEN_CRITERIA'];
				} else { // Good criterion
					if (strlen ( $rawValue ) > $criteria) {
						$value = NULL;
						return $errMsg ['MAXLEN_FAIL']; // Too long
					}
				}
				break;
			
			default :
		}
	}
	
	$value = $rawValue;
	return NULL;
}

/**
 * remove_magic_quotes from variable
 *
 * Checks the setting of magic_quotes_gpc and acts accordingly, stripping
 * quotes and escapes from globals arrays as necessary.
 *
 * Thanks to gordon at kanazawa dot ac dot jp for this chunk of code.
 */
function remove_magic_quotes(&$x) {
	if (is_array ( $x )) {
		while ( list ( $key, $value ) = each ( $x ) ) {
			if ($value)
				remove_magic_quotes ( $x [$key] );
		}
	} elseif (ini_get ( 'magic_quotes_sybase' )) {
		$x = preg_replace ( "/''/", "'", $x );
	} elseif (get_magic_quotes_runtime ()) {
		$x = preg_replace ( "/\\\"/", '"', $x );
	} elseif (get_magic_quotes_gpc ()) {
		$x = stripslashes ( $x );
	}
}

/**
 * Fetch a global variable from the Superglobals array
 *
 * @param mixed $varName
 *        	variable to fetch
 * @param mixed $default
 *        	return for nonexistant variable
 * @return mixed of global variable
 */
function fetchGlobal($varName, $default = NULL) {
	if (array_key_exists ( $varName, $_SERVER )) {
		$varValue = $_SERVER [$varName];
	} elseif (array_key_exists ( $varName, $_COOKIE )) {
		$varValue = $_COOKIE [$varName];
	} elseif (array_key_exists ( $varName, $_POST )) {
		$varValue = $_POST [$varName];
	} elseif (array_key_exists ( $varName, $_GET )) {
		$varValue = $_GET [$varName];
	} elseif (array_key_exists ( $varName, $_ENV )) {
		$varValue = $_ENV [$varName];
	} else {
		$varValue = $default;
	}
	
	return $varValue;
}

/**
 * auto-link URLs and email addresses in a string
 *
 * Scripted by JoungKyun Kim <admin at oops.org>
 * auto_link ver 5 by 31-JAN-2002
 * This function is part of JSBoard at http://jsboard.kldp.org
 * that is PHP Web Board.
 *
 * @param string $str
 *        	auto-link
 * @return string string
 * @todo Deprecate in favor of plugin filters
 */
function auto_link($str) {
	
	// don't use target if tail is follow
	$regex ['file'] = "gz|tgz|tar|gzip|zip|rar|mpeg|mpg|exe|rpm|dep|rm|ram|asf|ace|viv|avi|mid|gif|jpg|png|bmp|eps|mov";
	$regex ['file'] = "(\.($regex[file])\") TARGET=\"_blank\"";
	
	// define URL ( include korean character set )
	$regex ['http'] = "(http|https|ftp|telnet|news|mms):\/\/(([\xA1-\xFEa-z0-9:_\-]+\.[\xA1-\xFEa-z0-9:;&#=_~%\[\]\?\/\.\,\+\-]+)([\.]*[\/a-z0-9\[\]]|=[\xA1-\xFE]+))";
	
	// define E-mail address ( include korean character set )
	$regex ['mail'] = "([\xA1-\xFEa-z0-9_\.\-]+)@([\xA1-\xFEa-z0-9_\-]+\.[\xA1-\xFEa-z0-9\-\._\-]+[\.]*[a-z0-9]\??[\xA1-\xFEa-z0-9=]*)";
	
	// If use "wrap=hard" option in TEXTAREA tag,
	// connected link tag that devided sevral lines
	$src [] = "/<([^<>\n]*)\n([^<>\n]+)\n([^<>\n]*)>/i";
	$tar [] = "<\\1\\2\\3>";
	$src [] = "/<([^<>\n]*)\n([^\n<>]*)>/i";
	$tar [] = "<\\1\\2>";
	$src [] = "/<(A|IMG)[^>]*(HREF|SRC)[^=]*=[ '\"\n]*($regex[http]|mailto:$regex[mail])[^>]*>/i";
	$tar [] = "<\\1 \\2=\"\\3\">";
	
	// replaceed @ charactor include email form in URL
	$src [] = "/(http|https|ftp|telnet|news|mms):\/\/([^ \n@]+)@/i";
	$tar [] = "\\1://\\2_HTTPAT_\\3";
	
	// replaced special char and delete target
	// and protected link when use html link code
	$src [] = "/&(quot|gt|lt)/i";
	$tar [] = "!\\1";
	$src [] = "/<a([^>]*)href=[\"' ]*($regex[http])[\"']*[^>]*>/i";
	$tar [] = "<A\\1HREF=\"\\3_orig://\\4\" TARGET=\"_blank\">";
	$src [] = "/href=[\"' ]*mailto:($regex[mail])[\"']*>/i";
	$tar [] = "HREF=\"mailto:\\2#-#\\3\">";
	$src [] = "/<([^>]*)(background|codebase|src)[ \n]*=[\n\"' ]*($regex[http])[\"']*/i";
	$tar [] = "<\\1\\2=\"\\4_orig://\\5\"";
	
	// auto linked url and email address that unlinked
	$src [] = "/((SRC|HREF|BASE|GROUND)[ ]*=[ ]*|[^=]|^)($regex[http])/i";
	$tar [] = "\\1<A HREF=\"\\3\" TARGET=\"_blank\">\\3</a>";
	$src [] = "/($regex[mail])/i";
	$tar [] = "<A HREF=\"mailto:\\1\">\\1</a>";
	$src [] = "/<A HREF=[^>]+>(<A HREF=[^>]+>)/i";
	$tar [] = "\\1";
	$src [] = "/<\/A><\/A>/i";
	$tar [] = "</A>";
	
	// restored code that replaced for protection
	$src [] = "/!(quot|gt|lt)/i";
	$tar [] = "&\\1";
	$src [] = "/(http|https|ftp|telnet|news|mms)_orig/i";
	$tar [] = "\\1";
	$src [] = "'#-#'";
	$tar [] = "@";
	$src [] = "/$regex[file]/i";
	$tar [] = "\\1";
	
	// restored @ charactor include Email form in URL
	$src [] = "/_HTTPAT_/";
	$tar [] = "@";
	
	// put border value 0 in IMG tag
	$src [] = "/<(IMG SRC=\"[^\"]+\")>/i";
	$tar [] = "<\\1 BORDER=0>";
	
	$str = preg_replace ( $src, $tar, $str );
	return $str;
}

/**
 * Remove dangerous and undesirable content from HTML
 *
 * @param string $str
 *        	make safe
 * @return string string
 * @todo Deprecate in favor of plugin filters
 */
function safeHTML($str) {
	$approvedtags = array (
			"p" => 2, // 2 means accept all qualifiers: <foo bar>
			"b" => 1, // 1 means accept the tag only: <foo>
			"i" => 1,
			"a" => 2,
			"em" => 1,
			"sub" => 1,
			"sup" => 1,
			"br" => 1,
			"strong" => 1,
			"blockquote" => 1,
			"tt" => 1,
			"hr" => 1,
			"li" => 1,
			"ol" => 1,
			"ul" => 1,
			"img" => 2,
			"span" => 2 
	);
	
	if (! function_exists ( 'style2html' )) {
		function style2html($haystack, $style, $html, $tag = "span") {
			$tagStartOpen = "<$tag style=\"(\w*-?\w*:\s+.*;)*\s*";
			$tagEndOpen = "\s*(\s?\w*-?\w*:\s+.*;\s*)*\s*\">";
			$tagClose = "<\/$tag>";
			
			$haystack = preg_replace ( "/(?iU){$tagStartOpen}{$style}{$tagEndOpen}(.*){$tagClose}/", "<$tag style=\"\\1\\2\"><$html>\\3</$html></$tag>", $haystack );
			
			return $haystack;
		}
	}
	
	$keys = array_keys ( $approvedtags );
	
	// Remove head, script, and comment tags.
	// $str = preg_replace( "/(?im)<head .*>.*<\/head *>/", "", $str );
	$str = eregi_replace ( "<head *>.*</head *>", "", $str );
	$str = eregi_replace ( "<script .*>.*</script *>", "", $str );
	$str = eregi_replace ( "<\?.*\?>", "", $str );
	$str = eregi_replace ( "<!--.*-->", "", $str );
	$str = eregi_replace ( "<[[:space:]]*([^>]*)[[:space:]]*>", "<\\1>", $str );
	$str = eregi_replace ( "<a([^>]*)href=\"?([^\"]*)\"?([^>]*)>", "<a href=\"\\2\">", $str );
	$str = preg_replace ( '/(?Uis)\<a *href="javascript:.*\<\/a>/', '', $str );
	
	$tmp = "";
	
	while ( eregi ( "<([^> ]*)([^>]*)>", $str, $reg ) ) {
		$i = strpos ( $str, $reg [0] );
		$l = strlen ( $reg [0] );
		if ($reg [1] [0] == "/")
			$tag = strtolower ( substr ( $reg [1], 1 ) );
		else
			$tag = strtolower ( $reg [1] );
		
		if (in_array ( $tag, $keys ) && $a = $approvedtags [$tag]) {
			if ($reg [1] [0] == "/")
				$tag = "</$tag>";
			elseif ($a == 1)
				$tag = "<$tag>";
			else
				$tag = "<$tag " . $reg [2] . ">";
		} else {
			$tag = "";
		}
		$tmp .= substr ( $str, 0, $i ) . $tag;
		$str = substr ( $str, $i + $l );
	}
	$str = $tmp . $str;
	
	return $str;
}

/**
 * Prepare a string for display as HTML
 *
 * Prepares a string for display as HTML. Currently, this does the following:
 * - Strip slashes (escapes) embedded by prefilter or abstraction.
 * - Add linebreaks if the string isn't HTML-formatted, else make it safe for
 * HTML display.
 * - Convert mailtos and URLs to hyperlinks.
 *
 * Allowed filters:
 * NONE: Don't format (Default)
 * HTML: Format as HTML.
 * TEXT: Format as text.
 * PRE: Format as preformatted text.
 * AUTO: Automatically determine format.
 *
 * @param string $html
 *        	be filtered
 * @param boolean $autolink
 *        	convert URLs and mailtos to links.
 * @param string $format
 *        	to format for display
 * @return string string ready for display as HTML.
 * @todo Deprecate in favor of plugin filters
 */
function postfilterHtml($html, $autolink = FALSE, $format = "NONE") {
	$cleanHtml = $html;
	
	// Display appropriately
	switch ($format) {
		
		case "NONE" :
			// Don't reformat.
			break;
		
		case "HTML" :
			// Embedded HTML. Make it safe.
			$cleanHtml = safeHTML ( $cleanHtml );
			break;
		
		case "TEXT" :
			// Make it safe.
			$cleanHtml = safeHTML ( $cleanHtml );
			
			// No embedded HTML, so add linebreaks
			$cleanHtml = nl2br ( $cleanHtml );
			break;
		
		case "PRE" :
			// Make it safe
			$cleanHtml = safeHTML ( $cleanHtml );
			
			// Preformatted text, so add PRE tags
			$cleanHtml = '<PRE>' . $cleanHtml . '</PRE>';
			break;
		
		default : // AUTO
		         // Make it safe
			$cleanHtml = safeHTML ( $cleanHtml );
			
			// Add linebreaks if no formatting is detected.
			if (! eregi ( "<(P|BR).*>", $cleanHtml )) {
				$cleanHtml = nl2br ( $cleanHtml );
			}
	}
	
	if ($autolink) {
		// Convert mailtos and https to hyperlinks.
		$cleanHtml = auto_link ( $cleanHtml );
	}
	
	return $cleanHtml;
}

/**
 * Parse a detail for display as HTML
 *
 * Prepares a string for display as HTML. It extracts the HTML header,
 * header meta information, script information, style information, and the title,
 * and an object that contains all of the above, including the raw header,
 * header contents, and body contents.
 *
 * @param string $html
 *        	to be parsed
 * @return object object
 */
function parseHtml($html) {
	logMsg ( "Preparing HTML for display", MM_INFO );
	
	$rtnArray = array (); // Array to hold return data
	
	/**
	 * HTML display object.
	 *
	 * Contains interesting parts of an HTML document, ready-parsed
	 */
	if (! class_exists ( 'htmlDisplay' )) {
		class htmlDisplay {
			
			/**
			 *
			 * @var string HTML source document
			 */
			var $html;
			
			/**
			 *
			 * @var string Virgin HTML header
			 */
			var $header;
			
			/**
			 *
			 * @var string Content of HTML header without "header" tags
			 */
			var $headerContent;
			
			/**
			 *
			 * @var array Array containing meta tags and meta content
			 */
			var $headerMetaTags;
			
			/**
			 *
			 * @var array Array containing script tags and script content
			 */
			var $headerScriptTags;
			
			/**
			 *
			 * @var array Array containing style tags and style content
			 */
			var $headerStyleTags;
			
			/**
			 *
			 * @var string String containing header title
			 */
			var $headerTitleTag;
			
			/**
			 *
			 * @var string String containing all header content not listed above
			 */
			var $headerMiscTags;
			
			/**
			 *
			 * @var string String containing body content without body tags
			 */
			var $body;
		}
	}
	
	$disp = new htmlDisplay ();
	$disp->html = $html;
	
	// Extract headers and content
	if (eregi ( '<head *>.*</head *>', $disp->html, $result )) {
		$disp->header = $result [0];
	} else {
		logMsg ( "Found no header tags", MM_INFO );
		return $disp->html;
	}
	
	// Strip <head> and </head> tags from headers
	$disp->headerContent = preg_replace ( '/(?i)\<\/?head *\>/', '', $disp->header );
	$disp->headerMiscTags = $disp->headerContent;
	
	// Extract title tags from headers
	if (preg_match_all ( '/(?i)\<title .*\/?\>/', $disp->header, $result ) > 0) {
		$disp->headerTitleTag = $result [0];
		$disp->headerMiscTags = preg_replace ( '/(?i)\<title .*\/?\>/', '', $disp->headerMiscTags );
	}
	
	// Extract meta tags from headers (return array of meta tags)
	if (preg_match_all ( '/(?i)\<meta .*\/?\>/', $disp->header, $result ) > 0) {
		$disp->headerMetaTags = $result [0];
		$disp->headerMiscTags = preg_replace ( '/(?i)\<meta .*\/?\>/', '', $disp->headerMiscTags );
	}
	
	// Extract script tags from headers (return array of script tags)
	if (preg_match_all ( '/(?Uis)\<script.*>.*\<\/script.*>/', $disp->header, $result ) > 0) {
		$disp->headerScriptTags = $result [0];
		$disp->headerMiscTags = preg_replace ( '/(?i)\<script .*>.*\<\/script .*\>/', '', $disp->headerMiscTags );
	}
	
	// Extract style tags from headers (return array of style tags)
	if (preg_match_all ( '/(?i)\<link .*\/?\>/', $disp->header, $result ) > 0) {
		$disp->headerStyleTags = $result [0];
		$disp->headerMiscTags = preg_replace ( '/(?i)\<link .*\/?\>/', '', $disp->headerMiscTags );
	}
	
	// Extract body content from body
	$bodyOnly = eregi_replace ( '^.*<body[^>]*>', '', $disp->html );
	$disp->body = eregi_replace ( '<\/body[^>]*>.*$', '', $bodyOnly );
	
	return $disp;
}

/**
 * Build and display message for user who is denied access
 *
 * Deny authorization to users who aren't permitted access (for instance,
 * authoring and editing)..
 *
 * @param string $message
 *        	message
 */
function denyAuthorization($message) {
	 
}

/**
 * Check a user's access to a resource
 *
 * Check user's authorization against the agent list in the database. The user
 * must have appropriate permissions to perform the specified activity.
 *
 * Basically, the user gets a password challange and their login, if valid, is
 * returns their permitted roles. If any of the permitted roles matches the
 * required role, the user is allowed to continue. The "admin" role has full
 * permissions.
 *
 * @param string $requiredRole
 *        	to perform the activity.
 * @return boolean if successful.
 */
function checkAuthorization($requiredRole) {
	global $PHP_AUTH_USER;
	global $PHP_AUTH_PW;
	
	$auth = false;
	
	if (isset ( $PHP_AUTH_USER ) && isset ( $PHP_AUTH_PW )) {
		
		// Connect to the database and execute the query
		$brain = new mindmeld ();
		$dbConn = $brain->dbconn;
		
		// Substitute the login values into the query. We MD5-encrypt
		// the password before sending it, since we're storing encrypted
		// passwords in the database.
		$attrib = array (
				'%USERID%' => $PHP_AUTH_USER,
				'%PASSWORD%' => md5 ( $PHP_AUTH_PW ),
				'%ROLE%' => $requiredRole 
		);
		$sql = $brain->getAction ( 'check agent authorization', $attrib );
		
		// We only need the first row.
		$recordSet = &$dbConn->Execute ( $sql );
		// Administrators have full permissions (like unix root)
		if (! $recordSet->EOF) {
			$auth = true;
		}
		
		$brain->close;
	}
	
	 
	return $auth;
}

/**
 * Shows a debug message (for development and debugging)
 *
 * @param mixed $var
 *        	be displayed
 * @param string $msg
 *        	to be display
 * @param string $style
 *        	style for output
 */
function showDebug($var, $msg = NULL, $style = NULL) {
	if (! $style)
		$style = "color: orange; font-size: smaller";
	print "<P STYLE=\"$style\">";
	
	if ($msg)
		print "<STRONG>$msg:</STRONG> ";
	
	if (is_array ( $var ) or is_object ( $var )) {
		print "<PRE>";
		print_r ( $var );
		print "</PRE>";
	} else {
		print "'$var'";
	}
	print "</P>";
}

/**
 * logMsg - wrapper for mindmeld::logMsg
 *
 * Writes a log message in the specified location.
 *
 * Messages can specify the type, as defined in PHP's "error_log" function:
 *
 * 0: Syslog
 * 1: Send to email
 * 3: Log to file
 *
 * Messages can specify the log level (All levels lower then
 * the selected level are displayed):
 *
 * 0: Error (halts execution of code)
 * 1: Warning messages (system level)
 * 2: Light Debug (possible concerns, user-level warnings)
 * 3: Medium Debug (important method returns)
 * 4: Full Debug (general methods and information)
 * 5: Everything!
 *
 * - Move debug_level to config item.
 * - Move destination to config item
 * - Error levels 0-1 should default to type=0,
 * levels 2-5 should default to type=3.
 *
 * @param string $message
 *        	message
 * @param integer $level
 *        	(0-4)
 * @param integer $type
 *        	(0,1,3)
 * @param string $destination
 *        	location or email address
 *        	
 */
function logMsg($message, $level = 1, $type = 999, $destination = "") {
	// mindmeld::logMsg( $message, $level, $type, $destination );
}
?>
