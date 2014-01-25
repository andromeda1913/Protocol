<?php

/*
 * Forget Last Answer please Protocol 2014 pashkovdenis@gmail.com
 */
class Forget implements commandInterface {
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
		if ($this->input == "forget") {
			$query = $this->dbo->query ( "SELECT queryId FROM Query ORDER BY queryId DESC LIMIT 1  " );
			$last_query_id = $query->fields ["queryId"] - 1;
			$mind = new mind ();
			$resl = array_shift ( $mind->answers ( $last_query_id, "ACTIVE", TRUE, FALSE, FALSE ) );
			$this->dbo->query ( "UPDATE Thought SET status='DELETED' WHERE thoughtId='{$resl[0]}'  " );
			
			$this->out = false;
			$this->raw = "Ok";
		}
		return $this->out;
	}
}


