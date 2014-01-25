<?PHP
 
require_once ( $MM_GLOBALS['home'] . "interfaces/{$MM_GLOBALS['interface']}/include/"
	. "interface_{$MM_GLOBALS['interface']}_manage.inc" );

// Process incoming data
remove_magic_quotes( $_POST );
set_magic_quotes_runtime( 0 );

// Create the session
session_name( 'manage' );
session_start();

// Translate submit buttons from forms
$allowedSubmits = array(	'submit' => 'VALUE',
							'submit_fetch' => 'SHOW_RESULTSLIST',
							'submit_preview' => 'PREVIEW_ANSWER',
							'submit_update' => 'UPDATE_ANSWER',
							'submit_search' => 'SHOW_RESULTSLIST',
							'submit_status' => 'UPDATE_ACTIONS',
							'submit_newQuery' => 'UNSET' );

// Clear extraneous data from other search types
if( isset( $_POST['submit_fetch'] ) ) {
	// Unset irrelevant search criteria
	if( isset( $_SESSION['manager']['theQuestion'] ) ) {
		unset( $_SESSION['manager']['theQuestion'] );
	}
} elseif (isset(  $_POST['submit_search'] ) ) {
	// Unset irrelevant search criteria
	if( isset( $_SESSION['manager']['thoughtId'] ) ) {
		unset( $_SESSION['manager']['thoughtId'] );
	}
}

$state = setState( $allowedSubmits );

// NOTE: Can this be obsoleted?
if( isset( $state ) ) {
	switch( $state ) {
		case 'Fetch':
			$state = 'SHOW_RESULTSLIST';
			break;
		case 'selection':
			$state = 'EDIT_ANSWER';
			break;
		case 'Update':
			$state = 'UPDATE_ACTIONS';
			break;
	}
}

// If state is undefined, we must be starting a new query, so
// clean up any old session data and start fresh.
if ( !isset( $state ) and isset( $_SESSION ) ) {
	$_SESSION = array();
	session_destroy();
	session_name( 'manage' );
}

// Create the initial mmManage object and initialize the
// session variable. This doesn't allow for multiple
// concurrent searches!
if ( !isset( $_SESSION['manager'] ) ) {
	$manager = new mmManage();
	$_SESSION['manager'] = &$manager;
} else {
	$manager = &$_SESSION['manager'];
}

// Fetch any defined form variables from superglobals
if( fetchGlobal( 'queryId' ) != NULL ) $manager->queryId = fetchGlobal('queryId');
if( fetchGlobal( 'thoughtId' ) != NULL ) $manager->thoughtId =  fetchGlobal( 'thoughtId' );
if( fetchGlobal( 'theQuestion' ) != NULL ) $manager->theQuestion = fetchGlobal( 'theQuestion' );
if( fetchGlobal( 'actionArray' ) != NULL ) $manager->actionArray = fetchGlobal( 'actionArray' );
if( fetchGlobal( 'thoughtSummary' ) != NULL ) $manager->thoughtSummary = fetchGlobal( 'thoughtSummary' );
if( fetchGlobal( 'thoughtDetail' ) != NULL ) $manager->thoughtDetail = fetchGlobal( 'thoughtDetail' );

// Set default query status for management
// TODO: Why is query status not getting set?
if ( $manager->queryId > 0 ) {
		$queryState = new query();
		$queryState->updateQuery( $manager->queryId, 'MANAGE' );
}

// Validate ALL outside input
// TODO: Move these into the actual classes
if( isset( $manager->queryId ) )
	$quarentine['queryId'] = checkInput( $manager->queryId, VALID_QUERY_ID );
if( isset( $manager->theQuestion ) )
	$quarentine['theQuestion'] = checkInput( $manager->theQuestion, VALID_THE_QUESTION );
if( isset( $manager->thoughtId ) )
	$quarentine['thoughtId'] = checkInput( $manager->thoughtId, VALID_THOUGHT_ID );
if( isset( $manager->thoughtSummary ) )
	$quarentine['thoughtSummary'] = checkInput( $manager->thoughtSummary, VALID_THOUGHT_SUMMARY );
if( isset( $manager->thoughtDetail ) )
	$quarentine['thoughtDetail'] = checkInput( $manager->thoughtDetail, VALID_THOUGHT_DETAIL );

if( isset( $quarentine ) ) {
	foreach( $quarentine as $key => $violation ) {
		if( $violation ) {
			print "<P STYLE='color: #FF0000'>You submitted invalid information. ($violation in $key).<BR/>Please try again.</P>";
			$mm = new mindmeld();
			$mm->logMsg( "(manage) Security violation '$violation' in '$key'", MM_ERROR );
			// TODO: This should really fall back to the previous state.
			$state = NULL;
		}
	}
}

if( !isset( $state ) ) $state = '';

// Open the display
pageHeader();
debugSession();

// Switch to call form/Page handlers
switch ( $state ) {
	
	case "SHOW_RESULTSLIST":
		
		$manager->showIndex();
		if ( $manager->theQuestion != "" || $manager->thoughtId != NULL ) {
			
			// Let user enter a new query.
			// Then show them the answers/actions list
			$manager->showResultsHeader();
			$manager->showResults( $manager->getResults() );
		}
		break;
	
	case "EDIT_ANSWER":
		$manager->fetchThought();
		$manager->updateContent();
		break;
		
	case "PREVIEW_ANSWER":
		$manager->previewUpdate();
		break;
		
	case "UPDATE_ANSWER":
		$manager->updateThought();
		$manager->updateAndDisplayThought();
		break;
		
	case "UPDATE_ACTIONS":
		
		// Commit the user's changes, then
		// Display the query editor and
		// redisplay the actions/answers list
		
		$manager->commitChanges();
		$manager->showIndex();	
		
		$manager->showResultsHeader();
		$manager->showResults( $manager->getResults() );
		break;
		
	case "Load":	// Load detail from a file
		
 
		$maxsize = "40960";
			
		if ( $userfile AND $userfile != "none" ) {
			if ( $userfile_size > $maxsize ) {
				 
			} else {
				
				if ( is_readable( $userfile ) && is_file( $userfile ) ) {
					
					$fileHandle = fopen( $userfile, "r" );
					$rawText = fread( $fileHandle, filesize( $userfile ) );
					
					if ( stristr( $userfile_name, '.txt' ) ) {
						// It's a text file
						$detailFormat = "Text";
						$manager->thoughtDetail = htmlentities( $rawText );
						
					} elseif ( stristr( $userfile_name, '.htm' ) || stristr( $userfile_name, '.html' ) ) {
						// It's an html file
						$detailFormat = "HTML";
						$manager->thoughtDetail = $rawText;
					} else {
						$manager->thoughtDetail = $rawText;
					}
				}
			}
		}
		
		// Get the new content from the user
		
		$manager->previewUpdate();
		
		break;
	
	default:		// Show the Answer Manager search page
		
		$manager->showQueryHeader();
		$manager->showIndex();
		
		break;
}

// Close the display
pageFooter();
debugSession();
?>
