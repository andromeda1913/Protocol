<?php

/*
 * Mean command pashkovdenis@gmail.com 2014 Imaplements : change meaning of the word add synonims Protocol Engine :
 */
class Mean implements commandInterface {
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
		if (strstr ( $string, "mean" )) {
			$string = explode ( " ", $string );
			if (count ( $string ) == 3) {
				$word = new Word ( $string [0] );
				if ($word->id) {
					$syn = trim ( mb_strtolower ( $string [2] ) );
					if (! $word->is ( $syn )) {
						$word->addSynonim ( $syn );
						$this->raw = "Synonim was Added";
					} else
						$this->raw = $word->word . "  already has that meaning";
				}
				$this->out = false;
			}
		}
		return $this;
	}
	public function setOutput($thoudId = true) {
		$this->out = $thoudId;
		return $this;
	}
	public function execute($queryID = null) {
		return $this->out;
	}
}