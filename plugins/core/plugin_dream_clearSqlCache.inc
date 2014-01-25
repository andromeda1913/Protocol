<?PHP
 
class p50_dream_clearSqlCache extends plugin {
	
    /**
    * Dream-time utility to clear the SQL cache.
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		global $ADODB_CACHE_DIR;
		
		if ( $ADODB_CACHE_DIR ) {
			if ( ! $retirementAge ) {
				$retirementAge = $dream->readParam( 'stale_query_age' );
			}
			
			$dirHandle = dir( $ADODB_CACHE_DIR );
			
			$dream->logMsg( sprintf ( "Cache dir %s, retirement age is %s minutes",
				$dirHandle->path, $retirementAge ), 4 );
			
			$clearedFiles = 0;
			while ( ( $cacheFile = $dirHandle->read() ) !== false ) {
				$pathToFile = $dirHandle->path."/".$cacheFile;
				$fileAge = ( time() - filectime( $pathToFile ) ) / 60;
				if ( $fileAge > $retirementAge && eregi( "^adodb", $cacheFile ) ) {
					unlink( $pathToFile ) ?
						$clearedFiles++ :
						$dream->logMsg( sprintf ( "Unable to delete %s", $pathToFile ), MM_WARN );
				}
			}
			
			$msg = "Cleared $clearedFiles cache files";
			$event = new mmEvent;
			$event->dream( get_class( $this ), $msg  );
			$dream->logMsg( $msg, MM_ALL );
		}
		return NULL;
	}
}
?>