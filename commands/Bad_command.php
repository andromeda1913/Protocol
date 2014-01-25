<?php
/*
 * Bad Answer Protocol 2014 Mark As Frong Answer pashkovdenis@gmail.com
 */
class Bad implements commandInterface {
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
			if (strstr ( $this->input, "bad answer" )) {
				$query = $this->dbo->query ( "SELECT queryId FROM Query ORDER BY queryId DESC LIMIT 1  " );
				$last_query_id = $query->fields ["queryId"] - 1;
				$mind = new mind ();
				$resl = array_shift ( $mind->answers ( $last_query_id, "ACTIVE", TRUE, FALSE, FALSE ) );
				$mind->reinforceMemory ( $resl [0], $last_query_id, "NEGATIVE" );
				$this->raw = "ok";
				$this->out = false;
			}
		}
		
		return $this->out;
	}
}


