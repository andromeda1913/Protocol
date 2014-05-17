<?php

/*
 * Extract Selector For the cReated Map pashkovdenis@gmail.com 2014 : Amy Project :
 */
class Extract implements commandInterface {
	private $dbo;
	private $input;
	private $out = true;
	public $raw = " ";
	public function setDBo($dbo) {
		$this->dbo = $dbo;
		return $this;
	}
	public function setInput($string) {
		$this->input = $string;
		
		return $this;
	}
	public function setOutput($thoudId = true) {
		$this->out = $thoudId;
		return $this;
	}
	public function execute($queryID = null) {
		if ($this->input) {
			if (strstr ( $this->input, "extract:" )) {
				$ex = explode ( "extract:", $this->input );
				$map = new Map ();
				$query = $this->dbo->query ( "SELECT queryId FROM Query ORDER BY queryId DESC LIMIT 1  " );
				$last_query_id = $query->fields ["queryId"] - 1;
				$mind = new mind ();
				$resl = array_shift ( $mind->answers ( $last_query_id, "ACTIVE", TRUE, FALSE, FALSE ) );
				if ($resl ["map_id"]) {
					$map->setSelectionForMap ( $resl ["map_id"], $ex [1] );
				} 
			 	$this->raw = "Extracted.";
				$this->out = false;
			}
		}
		
		return $this->out;
	}
}



/*
 * End Class File    
 * pashkovdenis@gmail.com  
 * 2014 :    
 * 
 */

