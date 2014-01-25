<?PHP
 
define( 'MMDEFAULT_DBTYPE', 'mysql' );
define( 'MMDEFAULT_DBNAME', 'mindmeld' );
define( 'MMDEFAULT_DBHOST', 'localhost' );
define( 'MMDEFAULT_DBPORT', '' );
define( 'MMDEFAULT_DBUSER', 'mindmeld' );
define( 'MMDEFAULT_DBPASS', '' );
define( 'MMDEFAULT_PREFIX', 'mm' );
define( 'MMDEFAULT_THEME', 'arsgreen' );
define( 'MMDEFAULT_INTERFACE', 'smarty' );
define( 'MMDEFAULT_CACHE', 'cache' );
define( 'MMDEFAULT_ADODB', 'opensource/adodb' );
define( 'MMDEFAULT_ADODB_CACHE', 'sql_cache' );
define( 'MMDEFAULT_SMARTY_HOME', 'opensource/smarty/' );
/**#@-*/

/**
* Manage configuration files
*
* Provides methods for loading, parsing, merging, and managing
* Mindmeld configuration files.smar
*/
class mmConfiguration {

	/** Attributes **/
	
	/**
	* @var array Configuration array
	*/
	var $configuration;
	
	/**
	* @var array Loaded configuration files
	*/
	var $loadedConfigs;
	
	/**
	* @var array Configuration items that are file paths
	*/
	var $pathItems;
	
	
	/** Methods **/
	
	/**
	* Constructor. Initializes the configuration
	*
	* Resets object properties. If quickLoad is TRUE, the function will attempt
	* to load a configuration from the current directory. It will use the local
	* configuration to bootstrap a configuration from the mindmeld home directory.
	* Finally, it will load default values for any remaining configuration items and set
	* the the global MM_GLOBALS array from the resulting configuration array.
	*
	* @param boolean $quickLoad	Load local, home, then defaults. Initialize MM_GLOBALS
	*/
	function mmConfiguration( $quickLoad = FALSE ) {
		// Reset properties
		$this->configuration = array();
		$this->loadedConfigs = array();
		$this->pathItems = array( 'home', 'webhome', 'cache', 'includes', 'site',
			'smarty_home', 'postnuke_home', 'docroot', 'adodb' );
		
		if( $quickLoad ) {
			// Load local config
			$this->loadConfiguration();
			
			if( $this->configuration['home'] ) {
				// Load main mindmeld.config (if it exists), but don't overwrite existing keys.
				$this->loadConfiguration( $this->configuration['home'] . 'mindmeld.config' , FALSE );
			}
			
			// Load defaults, but don't overwrite existing keys.
			$this->loadDefaultConfiguration( FALSE );
			
			$this->setGlobals();
		}
	}
	
	/**
	* Loads and parses the requested INI-formatted file. 
	*
	* If no file is specified, the function first attempts to load a standard
	* file from the local directory. 
	*
	* Returns the parsed configuration array. if no file is found the function 
	* returns NULL. 
	*
	* @param string $file Path and file
	* @return array config array
	*/
	function loadIni( $file = 'mindmeld.config' ) {
		
		// Locally-scoped config array
		$configArray = array();
		
		// Look for config file in cwd if no file was specified.
		$file = realpath( $file );
		
		// Fail out if we can't find the file
		if( !is_readable( $file ) ) {
			return NULL;
		}
	
		// Set up regex for the load
		$skipLineRegex = '/([#;<].*|^\s$)/i';
		$cleanStartRegex = "/^[\'\"]/";
		$cleanEndRegex = "/[\'\"]$/";
		
		// Load the file
		$fptr = fopen( realpath( $file ), 'r' );
		if( !$fptr ) {
			return NULL;
		}
				
		// Process the file
		while( $line = fgets( $fptr, 1024 ) ) {
			if( preg_match( $skipLineRegex, $line ) ) continue;
			list( $key, $value ) = explode( "=", $line );
			$key = trim( $key );
			$value = trim( $value );
			$value = preg_replace( $cleanStartRegex, "", $value );
			$value = preg_replace( $cleanEndRegex, "", $value );
			
			// Set the key in the local config array
			$configArray[$key] = $value;
		}
		@fclose( $fptr );

		return $configArray;
	}
	
	/***
	* Loads a configuration file into the existing configuration array.
	*
	* Merges the contents of the configuration file specified in the file
	* argument into the current configuration.
	*
	* If ignoreDuplicates is set to TRUE, keys that already exist in the 
	* current configuration will NOT be overwritten. If ignoreDuplicates is
	* FALSE, keys in the file will overwrite existing keys.
	*
	* @param string $file	Configuration file
	* @param boolean $overwrite	If true, overwrite existing keys
	* @return array	Updated configuration array
	*/
	function loadConfiguration( $file = 'mindmeld.config', $overwrite = FALSE ) {
		
		// Attempt to load the requested INI file.
		$configArray = $this->loadIni( $file );
		
		if( !$configArray ) { // Failed to load: set config to defaults
			print "Failed to load file: " . realpath( $file );
			$configArray = $this->loadDefaultConfiguration();
		} else {
			$this->loadedConfigs[] = $file;
		}
		
		// If ignoreDuplicates is TRUE, throw away any keys that already
		// exists in the configuration property. Otherwise, overwrite keys
		$configArray = $this->mergeConfig( $configArray, $overwrite );
		
		// Update the object's configuration property.
		if( $configArray ) {
			$this->configuration = $configArray;
		} else {
			$configArray = NULL;
		}
		
		return $configArray;
	}
	
	/***
	* Loads a standard set up default keys into the configuration.
	*
	* Merges the default configuration into the current configuration.
	*
	* @return array	Updated configuration array
	*/
	function loadDefaultConfiguration() {
		
		$configArray = array();
		
		// Set some system defaults
		$configArray['dbType'] = MMDEFAULT_DBTYPE;
		$configArray['dbName'] = MMDEFAULT_DBNAME;
		$configArray['dbHost'] = MMDEFAULT_DBHOST;
		$configArray['dbUser'] = MMDEFAULT_DBUSER;
		$configArray['dbPass'] = MMDEFAULT_DBPASS;
		$configArray['theme'] = MMDEFAULT_THEME;
		$configArray['interface'] = MMDEFAULT_INTERFACE;
		$configArray['prefix'] = MMDEFAULT_PREFIX;
		
		// Set the compound defaults
		if( isset( $this->configuration['webhome'] ) ) {
			$configArray['webhome'] = $this->configuration['webhome'];
		} else {
			$configArray['webhome'] =  $this->formatPath( dirname( __FILE__ ) ) . "acweb";
		}
		
		if( isset( $this->configuration['home'] ) ) {
			$configArray['home'] = $this->configuration['home'];
		} else {
			$configArray['home'] =  $this->formatPath( dirname( __FILE__ ) );
		}
		
		if( isset( $this->configuration['site'] ) ) {
			$configArray['site'] = $this->configuration['site'];
		} else {
			$configArray['site'] = $this->formatPath( preg_replace( "/\/[^\/]*$/", "/", $_SERVER['SCRIPT_NAME'] ) );
		}
	
		$configArray['includes'] = $this->formatPath( $configArray['home'] . '/include/' );
		$configArray['cache'] = $this->formatPath( $configArray['home'] . MMDEFAULT_CACHE );
		$configArray['adodb'] = $this->formatPath( $configArray['home'] . MMDEFAULT_ADODB );
		$configArray['sql_cache_dir'] = $this->formatPath( $configArray['cache'] . MMDEFAULT_ADODB_CACHE, NULL, FALSE );
		$configArray['smarty_home'] = $this->formatPath( $configArray['home'] . MMDEFAULT_SMARTY_HOME );
		$configArray['docroot'] = $_SERVER['DOCUMENT_ROOT'] . $configArray['site'];
		
		// If ignoreDuplicates is TRUE, throw away any keys that already
		// exists in the configuration property. Otherwise, overwrite keys
		$configArray = $this->mergeConfig( $configArray, FALSE );
		
		// Update the object's configuration property.
		if( $configArray ) {
			$this->configuration = $configArray;
			$this->loadedConfigs[] = 'defaults';
		} else {
			$configArray = NULL;
		}
		return $configArray;
	}
	
	/**
	* Merges a configuration array into the object's configuration.
	*
	* @param array $configArray	Array to merge
	* @param boolean $overwrite	If true, overwrite existing keys
	* @return array	Merged configuration array
	*/
	function mergeConfig( $configArray, $overwrite = FALSE ) {
		
		$merged = $this->configuration;
		
		foreach( $configArray as $key => $value ) {
			
			// If key doesn't exist, create it. If it exists but overwrite is enabled,
			// overwrite the existing key.
			if( !array_key_exists( $key, $merged ) or $overwrite ) {
				$merged[$key] = $value;
				
				// Clean up the path
				if( in_array( $key, $this->pathItems ) ) {
					$merged[$key] = $this->formatPath( $value );
				}
			}
		}
		
		return $merged;
	}
	
	/**
	* Ensures a specific filesystem path ends with a single slash.
	*
	* Adds the slash if necessary.
	*
	* @param string $path	File path
	* @param string $file	File
	* @param boolean $endWithSlash	Force path to end with a slash character
	* @return string Formatted path
	*/
	function formatPath( $path = '', $file = '', $endWithSlash = TRUE ) {	
		// Use cwd if path isn't supplied.
		if( !$path ) $path = getcwd();
		
		if( $endWithSlash ) {
			// Make sure the path ends with a single slash
			$fmtpath = str_replace( '\\', '/', $path );
			$fmtpath = preg_replace( '/^(.)+[^\/]$/', '\0/', $fmtpath );
			
			// Append the file, if provided
			if( $file ) $fmtpath .= $file;
			
		} else {
			// Make sure path does not end with a single slash
			$fmtpath = str_replace( '\\', '/', $path );
			$fmtpath = preg_replace( '/\/$/', '', $fmtpath );
		}

		return $fmtpath;
	}
	
	/**
	* Copies the configuration array into the MM_GLOBALS array.
	*/
	function setGlobals() {
		global $MM_GLOBALS;
		if( !isset( $MM_GLOBALS ) or !is_array( $MM_GLOBALS ) ) {
			$MM_GLOBALS = array();
		}
		if( !isset( $this->configuration ) or !is_array( $this->configuration ) ) {
			$this->configuration = array();
		}
		$MM_GLOBALS = array_merge( $MM_GLOBALS, $this->configuration );
	}
	
	/**
	* Returns the current configuration array
	*
	* @return array Configuration array
	*/
	function getConfiguration() {
		return $this->configuration;
	}
}
?>