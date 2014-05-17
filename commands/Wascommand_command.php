<?php
//commander   

class Wascommand implements commandInterface {
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
			if (strstr ( $this->input, "was command" )) {
				$query = $this->dbo->query ( "UPDATE Thought SET commander =1 ORDER BY ThoughtId  DESC LIMIT 1  " );
				 
			}
		}
		
		return $this->out;
	}
}