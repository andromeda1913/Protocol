<?PHP
 
require_once ($MM_GLOBALS ['home'] . "interfaces/{$MM_GLOBALS['interface']}/include/" . "interface_{$MM_GLOBALS['interface']}_ask.inc");

// Process incoming data
remove_magic_quotes ( $_POST );
set_magic_quotes_runtime ( 0 );

// Translate submit buttons from forms
$allowedSubmits = array (
		'submit' => 'VALUE',
		'submit_search' => 'SHOW_RESULTSLIST',
		'submit_selected' => 'SHOW_ANSWER',
		'submit_fb_yes' => 'FEEDBACK_POSITIVE',
		'submit_fb_maybe' => 'FEEDBACK_NEUTRAL',
		'submit_fb_no' => 'FEEDBACK_NEGATIVE',
		'submit_newQuery' => 'UNSET' 
);

$state = setState ( $allowedSubmits );

// Start and manage the session
$id = time () . rand ( 0, 32767 );
session_name ( 'search' );
session_start ();

if (! isset ( $_SESSION ['currentQuery'] ) or ! $state) { // Start a new session
                                                       
	// Clear old session leftovers and start the new session
	session_destroy ();
	session_id ( $id );
	session_start ();
	$state = '';
	$_SESSION = array ();
	
	// Start a new search
	$currentQuery = new mmSearch ();
	$_SESSION ['currentQuery'] = &$currentQuery;
} else { // Reload the query from the session
	$currentQuery = &$_SESSION ['currentQuery'];
}

// Fetch any defined form variables from superglobals
if (fetchGlobal ( 'queryId' ) != NULL)
	$currentQuery->queryId = fetchGlobal ( 'queryId' );
if (fetchGlobal ( 'theQuestion' ) != NULL)
	$changedQuestion = fetchGlobal ( 'theQuestion' );
if (fetchGlobal ( 'thoughtId' ) != NULL)
	$currentQuery->thoughtId = fetchGlobal ( 'thoughtId' );
if (fetchGlobal ( 'edit' ) != NULL) {
	$currentQuery->edit = fetchGlobal ( 'edit', FALSE );
} elseif (! isset ( $currentQuery->edit )) {
	$currentQuery->edit = FALSE;
}
if (fetchGlobal ( 'haveFeedback' ) != NULL) {
	$currentQuery->haveFeedback = fetchGlobal ( 'haveFeedback', FALSE );
} elseif (! isset ( $currentQuery->haveFeedback )) {
	$currentQuery->haveFeedback = FALSE;
}

// Validate ALL outside input
// TODO: Move these into the actual classes
if (isset ( $currentQuery->queryId ))
	$quarentine ['queryId'] = checkInput ( $currentQuery->queryId, VALID_QUERY_ID );
if (isset ( $currentQuery->theQuestion ))
	$quarentine ['theQuestion'] = checkInput ( $changedQuestion, VALID_THE_QUESTION );
if (isset ( $currentQuery->thoughtId ))
	$quarentine ['thoughtId'] = checkInput ( $currentQuery->thoughtId, VALID_THOUGHT_ID );
if (isset ( $currentQuery->haveFeedback ))
	$quarentine ['haveFeedback'] = checkInput ( $currentQuery->haveFeedback, VALID_HAVE_FEEDBACK );
if (isset ( $currentQuery->edit ))
	$quarentine ['edit'] = checkInput ( $currentQuery->edit, VALID_EDIT );

if (isset ( $quarentine )) {
	foreach ( $quarentine as $key => $violation ) {
		if ($violation) {
			print "<P STYLE='color: #FF0000'>You submitted invalid information. ($violation in $key).<BR/>Please try again.</P>";
			$mm = new mindmeld ();
			$mm->logMsg ( "(ask) Security violation '$violation' in '$key'", MM_ERROR );
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
	
	case "SHOW_RESULTSLIST" :
		
		// Provided a query has been submitted:
		// 1. Display the query box (so the user can modify the query)
		// 2. Display the list of matching results.
		// If no query was submitted, just show the query box.
		
		// If the query hasn't changed, show the results list again
		if ($changedQuestion == $currentQuery->theQuestion) {
			$currentQuery->showQuery ();
			$currentQuery->showResults ( $currentQuery->getResults ( $currentQuery->queryId ) );
			break;
		} else {
			// The query has changed.
			$currentQuery->theQuestion = $changedQuestion;
			$currentQuery->showQuery ();
			
			// Perform the query if it isn't empty
			if ($currentQuery->theQuestion != "") {
				$currentQuery->showResults ( $currentQuery->performQuery () );
			}
		}
		break;
	
	case "SHOW_ANSWER" :
		
		// Fetch the thought and build the HTML headers (if necessary);
		$thought = $currentQuery->fetchThought ();
		if ($thought->displayRaw)
			$currentQuery->showHeader ( $thought );
		
		if ($currentQuery->edit != TRUE) {
			$currentQuery->feedback ();
		}
		
		// (GUI) Display the answer.
		$currentQuery->displayThought ( $thought );
		$currentQuery->updateQueryStatus ();
		
		if ($currentQuery->edit != TRUE) {
			$currentQuery->feedback ();
		}
		break;
	
	case "FEEDBACK_POSITIVE" :
		
		// Fetch the thought and build the HTML headers (if necessary);
		$thought = $currentQuery->fetchThought ();
		if ($thought->displayRaw)
			$currentQuery->showHeader ( $thought );
		
		$currentQuery->haveFeedback = TRUE;
		$currentQuery->edit = FALSE;
		
		$currentQuery->feedback ( $currentQuery->haveFeedback );
		
		// Reinforce the memory
		$mind = new mind ();
		$mind->reinforceMemory ( $currentQuery->thoughtId, $currentQuery->queryId, "POSITIVE" );
		
		// Update the decision status
		$decisionInfo = new decision ();
		$decisionInfo->updateDecision ( $currentQuery->queryId, $currentQuery->thoughtId, 'ACCEPTED' );
		
		// (GUI) Display the answer again (in case she's not done with it).
		$currentQuery->displayThought ( $thought );
		$currentQuery->updateQueryStatus ();
		
		// (GUI) Display the query box.
		$currentQuery->showQuery ();
		
		// (GUI) Display the results list
		$currentQuery->showResults ( $currentQuery->getResults ( $currentQuery->queryId ) );
		break;
	
	case "FEEDBACK_NEGATIVE" : // Negative feedback submitted
		
		$currentQuery->haveFeedback = TRUE;
		$currentQuery->edit = FALSE;
		
		$currentQuery->feedback ( $currentQuery->haveFeedback );
		
		// Weaken the memory
		$mind = new mind ();
		$mind->reinforceMemory ( $currentQuery->thoughtId, $currentQuery->queryId, "NEGATIVE" );
		
		// Update the decision status
		$decisionInfo = new decision ();
		$decisionInfo->updateDecision ( $currentQuery->queryId, $currentQuery->thoughtId, 'REJECTED' );
		
		// (GUI) Display the query box.
		$currentQuery->showQuery ();
		
		// (GUI) Display the results list
		$currentQuery->showResults ( $currentQuery->getResults ( $currentQuery->queryId ) );
		break;
	
	case "FEEDBACK_NEUTRAL" : // Neutral feedback submitted
	                         
		// Fetch the thought and build the HTML headers (if necessary);
		$thought = $currentQuery->fetchThought ();
		if ($thought->displayRaw)
			$currentQuery->showHeader ( $thought );
		
		$currentQuery->haveFeedback = TRUE;
		$currentQuery->edit = FALSE;
		
		$currentQuery->feedback ( $currentQuery->haveFeedback );
		
		// Update decision status
		$decisionInfo = new decision ();
		$decisionInfo->updateDecision ( $currentQuery->queryId, $currentQuery->thoughtId, 'UNSURE' );
		
		// Show the user the answer again (in case she's not done with it).
		$currentQuery->displayThought ( $thought );
		$currentQuery->updateQueryStatus ();
		
		// (GUI) Display the query box.
		$currentQuery->showQuery ();
		
		// (GUI) Display the results list.
		$currentQuery->showResults ( $currentQuery->getResults ( $currentQuery->queryId ) );
		break;
	
	default : // A new query. Display the query box.
	         
		// Display the query box so the user can submit a query.
		$currentQuery->showQuery ();
		break;
}

// Close the display
pageFooter ();
debugSession ();
?>
