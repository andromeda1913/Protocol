<?PHP
 
include_once( "cortex.inc.php" );

/**
* Count the number of tsneurons attached to each symbol
* and update the tsncount field of the symbol table.
* This is required by the noisefilter plugin class.
*/ 
class p20_dream_updateNeuralCounts extends plugin {
	
    /**
    * Update neural counts 
    * 
    * Dream-time utility to update tsncount with the number of neurons connected to 
 	* each symbol.
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
		
		// Call the update query (self-contained)
		$dbconn = $dream->dbconn;
		$result = $dream->getAction( 'update neural counts', '', NULL, FALSE );
		
		// Fetch the number of unique tsncount entires
		$sql = "SELECT COUNT(DISTINCT(tsncount)) FROM Symbol";
		$result = &$dbconn->GetRow( $sql );
		$tsnBins = $result[0];
		
		$msg = "Found $tsnBins bins";
		$event = new mmEvent;
		$event->dream( get_class( $this ), "Found $tsnBins bins"  );
		$dream->logMsg( "updateNeuralCounts $msg", MM_INFO );
		
		return NULL;
	}
}

/**
* Run the noisefilter.
* and update the tsncount field of the symbol table.
* This is required by the noisefilter plugin class.
*/ 
class p25_dream_noisefilter extends plugin {
	
    /**
    * Dream-time utility to filter noisewords from the mindmeld symbols table using 
 	* statistical analysis.
	*
	* The logic for the noise filter is non-trivial, so here's a brief
	* discourse on how it works:
	* 
	* There are three concepts that are important: symbols, tsneurons,
	* and tsncount--the number of neurons attached to a given symbol.
	* All the statistics are performed on tsncount on the symbol table.
	*
	* First, we check the statistical variance of tsncount to see if
	* there's enough data to make the noise filter valid. The minimum
	* acceptable variance is set in the min_symbol_variance value in
	* the fundamentals table. If tsncount > min_symbol_variance, the
	* filter can be run, otherwise quit now.
	*
	* Next, we calculate standard deviation of tsncount and multiply
	* it by the noiseword_threshold value from fundamentals. Call
	* this sum the threshold count. A symbol with a tsncount greater
	* then the threshold count is a noiseword.
	*
	* Since there may be many, many tsneurons in the system, we only
	* want to touch the ones that need to be changed. Initially, the
	* noiseword column in the symbol table will contain data from the
	* last time the filter was run:  0 if the symbol is a noiseword or
	* 1 if the symbol is not. At this run we will set every the
	* noiseword value of every symbol that is a noiseword but shouldn't 
	* be to 3, and set the noiseword value of every symbol that is a
	* noiseword but shouldn't be to 2.
	* 
	* Now we update the tsneurons: each symbol that has a noiseword
	* value or 2 or 3 sets the "enabled" flag of its tsneurons to 
	* noiseword minus 2--those with a value of 2 will set their
	* tsneurons to 0 (disabled), and those with a value of 3 will
	* set their tsneurons to 1 (enabled).
	*
	* Finally, we renormalize the symbols by subtracting 2 from
	* each noiseword entry. E.g., Symbols with a noiseword value
	* of 3 become 1, and those with a noiseword value of 2 become
	* 0. Clear as mud. 
    *
	* @param object $dream Dream object
	*/
	function dream( $dream ) {
				
		// Filter noise
		$filter = new symbol();
		
		$dbconn = $filter->dbconn;
		
		// Check the variance of tsncount
		
		// Necessary variance function not currently supported by MySQL. < 4.1	
		if( ( strtoupper( $dream->mmconfig['dbType'] ) == 'MYSQL' )  and !preg_match( "/^4.[1-9]/", mysql_get_server_info() ) ) {
			$dream->logMsg( "Noisefilter not supported by mysql", MM_NOTICE );
			return NULL;
		}

		$recordSet = $filter->getAction( 'calculate symbol variance' );
		
		if ( !isset( $recordSet )  ) {	// Error running query. Drop out.
			$filter->logMsg( "Error running variance query", MM_ERROR );
			return NULL;
		} else {	// Check variance against fundamental minimum variance
			$variance = $recordSet->fields[0];
			$minVariance = $filter->readParam( 'min_symbol_variance' );
			
			if ( $variance < $minVariance ) {
				$msg = "Variance $variance < threshold $minVariance. Aborting Noise Filter.";
				$filter->logMsg( $msg, MM_NOTICE );
					
				$event = new mmEvent;
				$event->dream( get_class( $this ), $msg );
				return NULL;
			} else {
				$filter->logMsg( sprintf ( "Variance %s > %s. Running Noise Filter.", $variance, $minVariance ), MM_INFO );
			}
		}
		
		// 1. Fetch the total number of active thoughts.
		$recordSet = $filter->getAction( 'calculate symbol deviation' );
		if ( !isset( $recordSet ) ) {
			$filter->logMsg( "Error running deviation query", MM_ERROR );
			return NULL;
		} else {
			$stddev = $recordSet->fields[0];
			$filter->logMsg( sprintf ( "Deviation %s", $stddev ), MM_INFO );
		}
		
		// 2. Fetch the noiseword threshold and calculate the threshold
		// count.
		
		$threshold = $filter->readParam( 'noiseword_threshold' );
		$thresholdCount = $threshold * $stddev;
		$filter->logMsg( sprintf ( "Threshold count %s", $thresholdCount ), MM_INFO );
		
		// 3. Update noiseword filter
		$filter->logMsg( "Filtering noise (symbols)", MM_INFO );
		$attrib = array( 	'%THRESHOLDCOUNT%' => $thresholdCount );
		$recordSet = $filter->getAction( 'filter noise', $attrib );

		// Fetch some statistics on what changed
		$filter->logMsg( "Querying noiseword groups", MM_INFO );
		$sql = "SELECT noiseword, count(*) FROM Symbol GROUP BY noiseword";
		$recordSet = &$dbconn->Execute( $sql );
		$symbolArray = $recordSet->GetArray();
		
		// 4. Enable/disable neurons from noise filter
		$filter->logMsg( "Filtering noise (tsneurons)", MM_INFO );
		$recordSet = $filter->getAction( 'filter neural noise' );
		
		// 5. Renormalize the noise filter so it's ready for
		//		the next run.
		$filter->logMsg( "Renormalizing filter", MM_INFO );
		$recordSet = $filter->getAction( 'renormalize noise filter' );
		
		$returnCode = $thresholdCount;
		
		$msg = "Threshold count is $thresholdCount";
		$event = new mmEvent;
		$event->dream( get_class( $this ), $msg );
		$filter->logMsg( sprintf ( "<<< filterNoise (%s)", $returnCode ), MM_ALL );
		
		return NULL;
	}
}
?>