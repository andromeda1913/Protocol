<?PHP
 
require_once ($MM_GLOBALS ['home'] . "interfaces/{$MM_GLOBALS['interface']}/include/" . "interface_{$MM_GLOBALS['interface']}_learn.inc");

// Process incoming data
remove_magic_quotes ( $_POST );
set_magic_quotes_runtime ( 0 );

// Translate submit buttons. The key is what we'll accept from a form,
// the value is the state we send on to the switch.
$allowedSubmits = array (
		'submit' => 'VALUE',
		'submit_preview' => 'PREVIEW_ANSWER',
		'submit_add' => 'ADD_ANSWER' 
);

$state = setState ( $allowedSubmits );

// Create the session
session_name ( 'learn' );
session_start ();

// If submit is undefined, we must be starting a new session, so
// clean up any old session data and start fresh.
if (! isset ( $state ) and isset ( $_SESSION )) {
	$_SESSION = array ();
	session_destroy ();
	session_name ( 'learn' );
}

// Create the initial mmLearn object and initialize the
// session variable. This doesn't allow for multiple
// concurrent learning!
if (! isset ( $_SESSION ['newAnswer'] )) {
	$newAnswer = new mmLearn ();
	$_SESSION ['newAnswer'] = &$newAnswer;
} else {
	$newAnswer = &$_SESSION ['newAnswer'];
}

// Fetch any defined form variables from superglobals
if (fetchGlobal ( 'thoughtId' ) != NULL)
	$newAnswer->thoughtId = fetchGlobal ( 'thoughtId' );
if (fetchGlobal ( 'thoughtSummary' ) != NULL)
	$newAnswer->thoughtSummary = fetchGlobal ( 'thoughtSummary' );
if (fetchGlobal ( 'thoughtDetail' ) != NULL)
	$newAnswer->thoughtDetail = fetchGlobal ( 'thoughtDetail' );
	
	// Validate ALL outside input
	// TODO: Move these into the actual classes
if (isset ( $newAnswer->thoughtId ))
	$quarentine ['thoughtId'] = checkInput ( $newAnswer->thoughtId, VALID_THOUGHT_ID );
if (isset ( $newAnswer->thoughtSummary ))
	$quarentine ['thoughtSummary'] = checkInput ( $newAnswer->thoughtSummary, VALID_THOUGHT_SUMMARY );
if (isset ( $newAnswer->thoughtDetail ))
	$quarentine ['thoughtDetail'] = checkInput ( $newAnswer->thoughtDetail, VALID_THOUGHT_DETAIL );

if (isset ( $quarentine )) {
	foreach ( $quarentine as $key => $violation ) {
		if ($violation) {
			print "<P STYLE='color: #FF0000'>You submitted invalid information. ($violation in $key).<BR/>Please try again.</P>";
			$mm = new mindmeld ();
			$mm->logMsg ( "(learn) Security violation '$violation' in '$key'", MM_ERROR );
			// TODO: This should really fall back to the previous state.
			$state = NULL;
		}
	}
}

if (! isset ( $state ))
	$state = '';
	
	// Open the display
pageHeader ();
debugSession ();

// Switch to call form/Page handlers
switch ($state) {
	
	case "PREVIEW_ANSWER" :
		
		// Show a preview of the actual content.
		// Then give the user an opportunity to make
		// changes. Check that all required fields are filled in.
		$newAnswer->previewResults ();
		$newAnswer->getContent ( TRUE );
		
		break;
	
	case "ADD_ANSWER" :
		
		// Notify the user if any required fields are missing, so they can re-enter the data.
		if (! $newAnswer->thoughtSummary or ! $newAnswer->thoughtDetail) {
			$newAnswer->previewResults ();
			$newAnswer->getContent ( TRUE );
		} else { // All fields provided: add the new thought.
			$newAnswer->addThought ();
			$newAnswer->displayThought ();
		}
		break;
	
	case "Load" : // Load detail from a file
	       
		
		$maxsize = "40960";
		
		if ($userfile and $userfile != "none") {
			if ($userfile_size > $maxsize) {
				 
			} else {
				
				if (is_readable ( $userfile ) && is_file ( $userfile )) {
					
					$fileHandle = fopen ( $userfile, "r" );
					$rawText = fread ( $fileHandle, filesize ( $userfile ) );
					
					if (stristr ( $userfile_name, '.txt' )) {
						// It's a text file
						$detailFormat = "Text";
						$newAnswer->thoughtDetail = htmlentities ( $rawText );
					} elseif (stristr ( $userfile_name, '.htm' ) || stristr ( $userfile_name, '.html' )) {
						// It's an html file
						$detailFormat = "HTML";
						$newAnswer->thoughtDetail = $rawText;
					} else {
						$newAnswer->thoughtDetail = $rawText;
					}
				}
			}
		}
		
		// Get the new content from the user
		
		$newAnswer->getContent ();
		
		break;
	
	default : // Enter the new answer
	         
		// Clear exsiting values
	         // NOTE: These are probably redundant now.
	         // Then get the new content from the user
		
		$newAnswer->thoughtId = NULL;
		$newAnswer->thoughtSummary = NULL;
		$newAnswer->thoughtDetail = NULL;
		
		$newAnswer->getContent ();
		break;
}

// Close the display
pageFooter ();
debugSession ();
?>
