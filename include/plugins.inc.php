<?PHP
 
 class pluginManager {
 	
 	/**
	 * @var array List of loaded plugins
	 */
	 var $loadedPlugins;
	 
 	function pluginManager( $mmconfig ) {
 		$this->loadPlugins( $mmconfig );
 	}
 	
 	/**
	 * Loads core plugins and user plugins.
	 *
	 * @param array $mmconfig Mindmeld configuration array
	 * @return array List of loaded plugins
	 */
 	function loadPlugins( $mmconfig ) {
		
		// Get count of classes existing before plugins are loaded
		$numClasses = count( get_declared_classes() );
		
		$this->loadPluginFiles( $mmconfig['home'] . 'plugins/core' );	// Load core plugins
		$this->loadPluginFiles( $mmconfig['home'] . 'plugins/proprietary' );	// Load proprietary plugins, deprecated
		$this->loadPluginFiles( $mmconfig['home'] . 'plugins' );	// Load user plugins
 
 		// Only initialize plugin classes that extend the generic plugin class. Then
		// sort the plugins by priority, so lower numbers get executed first.
 		$pluginArr = array_slice( get_declared_classes(), $numClasses );
 		foreach( $pluginArr as $pluginClass ) {
 			if( get_parent_class( $pluginClass ) === 'plugin' )	$this->loadedPlugins[] = $pluginClass;
 		}
		sort( $this->loadedPlugins  );
		
 		return $this->loadedPlugins;
 	}
 	
 	/**
	 * Load all files containing plugins
	 *
	 * Loads all plugin files from the specified directory. Plugin
	 * files must be named as "plugin_pluginname.inc," e.g.,
	 * plugin_autoLink.inc.
	 *
	 * @param string $path Path to plugins directory
	 */
	function loadPluginFiles( $path ) {
		// Get a directory listing from plugins/core and include each plugin.
		if( !is_dir( $path ) ) return NULL;
		logMsg( sprintf( "Using plugin path '%s'", $path ), MM_INFO );
		if( $handle = opendir( $path ) ) {
			while( false !== ( $file = readdir( $handle ) ) ) {
				if( preg_match( '/^ *plugin_.*.inc */', $file ) ) {
					logMsg( sprintf( "Loading plugin file '%s'", $file ), MM_INFO );
					include_once( "$path/$file" );
				}
			}
			closedir( $handle );
		}
	}
 		
 	/**
	 * Executes all plugins linked to the provided hook
	 * 
	 * @param string $hook Name of hook
 	 * @param mixed Parameters to pass to the hook function
	 * @return array List of plugins executed
	 */
 	function executePlugins( $hook ) {
 		$numArgs = func_num_args();
				
 		if( $numArgs <1 ) {
 			return NULL;
 		}
 		
 		$args = func_get_args();
 		$hook = array_shift( $args );
 		
 		// Extract the target variable. This is the variable that will be
		// modified and fed into each consecutive filter.
 		if( isset( $args ) ) {
 			$target = array_shift( $args );
 		}	
 		// The remaining args are static and are made available to
		// each filter.
 		
 		// Detect all plugins that have a method of "hook"
		foreach( $this->loadedPlugins as $plugin ) {
			$methods = get_class_methods( $plugin );
			if( in_array( strtolower( $hook ), get_class_methods( $plugin ) ) ) {
				logMsg( sprintf( "Found hook %s in plugin %s", $hook, $plugin ), MM_INFO );
				$pluginObj = new $plugin;
				$result = $pluginObj->$hook( $target, $args );
				// Only update the target argument if it was passed through
				// by the previous plugin.
				if( $result !== NULL ) $target = $result;
			}
		}	
		return $target;
 	}
 }
 
 /**
 * Base plugin class
 *
 * All plugins must extend this class or they will not get loaded.
 */
 class plugin {
 }
?>