<?PHP
 

class p80_safeHtml extends plugin {
	
	/**
	 * Plugin hook to process an answer prior to displaying it
	 *
	 * @param object $thoughtObj Thought object
	 * @return object Processed thought object
	 */
	
	
	function displayAnswerPrefilter( $thoughtObj ) { 
		
		
		/**
         * Remove dangerous and undesirable content from HTML
         *
         * @param string	$str	String to make safe
         * @return string	Safe string
         */ 
		
		
        function _safeHTML( $str )	{
        	
        	$approvedtags = array(
        							"p"=> 2,   // 2 means accept all qualifiers: <foo bar>
        							"b"=> 1,   // 1 means accept the tag only: <foo>
        							"i"=> 1,
        							"a"=> 2,
        							"em"=> 1,
        							"sub"=> 1, "sup" => 1,
        							"br"=> 1,
        							"strong"=> 1,
        							"blockquote"=> 1,
        							"tt"=> 1,
        							"hr"=> 1,
        							"li"=>1,
        							"ol"=> 1,
        							"ul"=> 1,
        							"img"=> 2,
        							"span"=> 2
        							);
        	
        	if( !function_exists( 'style2html' ) ) {		
        		function style2html( $haystack, $style, $html, $tag = "span" ) {
        			$tagStartOpen = "<$tag style=\"(\w*-?\w*:\s+.*;)*\s*";
        			$tagEndOpen = "\s*(\s?\w*-?\w*:\s+.*;\s*)*\s*\">";
        			$tagClose = "<\/$tag>";
        			
        			$haystack = preg_replace( "/(?iU){$tagStartOpen}{$style}{$tagEndOpen}(.*){$tagClose}/", 
        				"<$tag style=\"\\1\\2\"><$html>\\3</$html></$tag>", $haystack ); 
        	
        			return $haystack;
        		}
        	}
        	
        	$keys = array_keys( $approvedtags );
        	
        	// Remove head, script, and comment tags.
        	//$str = preg_replace( "/(?im)<head .*>.*<\/head *>/", "", $str );
        	$str = eregi_replace( "<head *>.*</head *>", "", $str );
        	$str = eregi_replace( "<script .*>.*</script *>", "", $str );
        	$str = eregi_replace( "<\?.*\?>", "", $str );
        	$str = eregi_replace( "<!--.*-->", "", $str );
        	$str = eregi_replace( "<[[:space:]]*([^>]*)[[:space:]]*>", "<\\1>", $str );
        	$str = eregi_replace( "<a([^>]*)href=\"?([^\"]*)\"?([^>]*)>", "<a href=\"\\2\">", $str );
        	$str = preg_replace( '/(?Uis)\<a *href="javascript:.*\<\/a>/', '', $str );
        
        	$tmp = "";
        
        	while ( eregi( "<([^> ]*)([^>]*)>", $str, $reg ) )	{
        		$i = strpos( $str, $reg[0] );
        		$l = strlen( $reg[0] );
        		if ( $reg[1][0] == "/" )
        			$tag = strtolower( substr( $reg[1], 1 ) );
        		else
        			$tag = strtolower( $reg[1] );
        		
        		if( in_array( $tag, $keys ) && $a = $approvedtags[$tag] )	{
        			if ( $reg[1][0] == "/" )
        				$tag = "</$tag>";
        			elseif ( $a == 1 )
        				$tag = "<$tag>";
        			else
        				$tag = "<$tag " . $reg[2] . ">";
        		} else {
        		  $tag = "";
        		}
        		$tmp .= substr( $str, 0, $i ) . $tag;
        		$str = substr( $str, $i+$l );
        	}
        	$str = $tmp . $str;
        
        	return $str;
        }
		
    	// Extract arguments
    	$thoughtObj->setDetail( _safeHtml( $thoughtObj->getDetail() ) );
    		
    	return $thoughtObj;
	}
}
?>