<?PHP
 
class p85_htmlAutoFormat extends plugin {
	
    /**
     * autoformat for HTML display
     * 
     * Automatically changes newlines in the thought detail to HTML 
     * break tags if no HTML paragraph or break tags are found.
     *
	 * @param object $thoughtObj Thought object
	 * @return object Processed thought object
	 */
	function displayAnswerPrefilter( $thoughtObj ) {

		// The filter
		$detail = $thoughtObj->getDetail();

		if ( !eregi( "<(P|BR).*>", $detail ) ) {
			$detail = nl2br( $detail );
		}
		
		// Update the return object
		$thoughtObj->setDetail( $detail );
		
		return $thoughtObj;
	}
}
?>