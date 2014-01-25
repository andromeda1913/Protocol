<?PHP
 
function loadTheme( $themeName = NULL ) {
	
	global $MM_GLOBALS;
	
	// Use the theme from the config file if no theme is specified.
	if ( !$themeName && $MM_GLOBALS['theme'] ) $themeName = $MM_GLOBALS['theme'];
	
	if ( !$themeName ) return NULL;
	
	$themePath = $MM_GLOBALS['webhome'] . "themes/$themeName/";
	$imagePath = $MM_GLOBALS['site'] . "themes/$themeName/images/";

	// Fetch the requested theme
	if ( include( $themePath."theme.php" ) ) {
		
		foreach( $theme as $module => $itemArray ) {
			foreach( $itemArray as $item => $typeArray ) {
				foreach( $typeArray as $type => $value ) {
					
					$themeEntry = $theme[$module][$item][$type];
					switch( strtoupper( $type ) ) {
						case "TEXT":
							break;
						case "STYLE":
							$themeEntry = "STYLE='$themeEntry'";
							break;
						case "IMAGE":
							$themeEntry = "$imagePath".$themeEntry;
							break;
					}
					$theme[$module][$item][$type] = $themeEntry;
				}
			}
		}
		return $theme;
	}
}
?>
