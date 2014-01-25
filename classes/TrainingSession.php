<?php

/*
 * Trainig Session Class 2014 : Protocol : Engine : pashkovdenis@gmail.com
 */
class TrainingSession {
	public $id;
	private $sets = [ ];
	public $is_open = 0;
	public $opened;
	public $ends;
	private $manager = null;
	public function __construct($id, FlowManager $manager, $title = "NoName") {
		if (! $id) {
			$manager->pdo->query ( "INSERT INTO training_sessions SET training_starts='" . time () . "'  ,  training_ends='' , is_open=1, title='{$title}'  " );
			$lastID = $manager->pdo->query ( "SELECT * FROM raining_sessions ORDER BY id DESC LIMIT 1" );
			$id = $lastID->fields [0] ["id"];
		}
		$this->id = $id;
		$this->manager = $manager;
		$set = $manager->pdo->query ( "SELECT * FROM training_sessions WHERE id='{$id}'  " );
		$this->is_open = $set->fields ["is_open"];
		$this->opened = $set->fields ["training_starts"];
		$this->ends = $set->fields ["training_ends"];
		$sets = $this->manager->pdo->query ( "SELECT * FROM training_set WHERE session_id='{$id}' AND is_variant =  0  ORDER BY id ASC  " );
		while ( ! $sets->EOF ) {
			$this->sets [] = new TrainingSet ( $sets->fields [0], $manager->pdo );
			$sets->moveNext ();
		}
	}
	public function learn() {
		foreach ( $this->sets as $s )
			$s->learn ();
		$this->manager->pdo->query ( "UPDATE training_sessions SET is_open=0 , training_ends='" . time () . "'  WHERE id='{$this->id}' " );
		return $this;
	}
	public function addSet(TrainingSet $set) {
		$this->sets [] = $set;
		return $this;
	}
	public function getSet($id) {
		foreach ( $this->sets as $s )
			if ($s->id == $id)
				return $s;
		
		return null;
	}
}


 