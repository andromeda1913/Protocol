<?php
  
/*
 * Rommie Abstract Model
 */
abstract class _Abstract { 
	
	protected $table;
	private $last_id;
	private $count;
	private $data;
	private $connection;
	private $sql;
	private $ordering;
	private $limit;
	private $where = array ();
	
	// connect to database ;
	public function __construct() {
		$this->data = array ();
		$this->table = false;
		$this->last_id = 0;
		$this->count = 0;
		$this->ordering = "";
		$this->limit = "";
		$this->connection = mysql_connect ( HOST, USER, PASS );
		if ($this->connection)
			mysql_select_db ( DATABASE );
		else
			throw new \Exception ( "Cannot connect  database " );
	}
	
	// set Limiter
	public function setLimiter($l = '') {
		$this->limit = $l;
		return $this;
	}
	public function setTable($table) {
		$this->table = $table;
		return $this;
	}
	
	public function delete($id){
		$this->setSql("DELETE FROM ##  WHERE id='{$id}' ")->exec(); 
		if($this->selectBy("id", $id)) 
				return false; 		
		return true; 
	}
	// 
	public function selectAll(){
		return $this->setSql("SELECT * FROM ##")->loadList() ; 
	}
	// set Ordering
	public function setOrdering($r = "") {
		$this->ordering = $r;
		return $this;
	}
	// set Raw Sql ;
	public function setSql($sql) {
		if (is_string ( $sql ))
			$this->sql = $sql;
		return $this;
	}
	public function getSql() {
		return $this->sql;
	}
	// Set Where Extensions
	public function where($field, $value) {
		if (! strstr ( $field, "LIKE" ))
			$field .= "=";
		$this->where [] = $field . "'" . $value . "'";
		return $this;
	}
	
	// this metho will update By post
	public function updateBypost($post = []) {
		$str = "";
		$c = 0;
		 
			
		
		foreach ( $post as $k => $v ) {
			if ($c > 0)
				$str .= ", ";
			if ($k == "start" || $k == "end")
				$v = strtotime ( $v ); 
			else{ 
			
			if (strstr($k , "start") || strstr($k , "_end")  ||  strstr($k , "end_"))
				$v = strtotime ( $v ); 
			} 
	 
				
				if($k=="enable"){
					if($v!='1') 
							$v=0 ; 
				}
			
			$str .= " `{$k}`='{$v}' ";
			
			$c ++;
		}
		
		$this->setSql ( "UPDATE ## SET " . $str . " WHERE id='{$post["id"]}' " )->exec ();
	}
	
	// insert Value
	public function generalInsert($data = []) {
		$str = " INSERT INTO ## SET  ";
		$c = 0;
		foreach ( $data as $key => $v ) {
			if ($c > 0)
				$str .= ", ";
			
			
			if ($key == "start" || $key == "end")
				$v = strtotime ( $v );
			if (strstr($key , "start") || strstr($key , "_end") ||  strstr($key , "end_"))
				$v = strtotime ( $v );
			
			
			
			$str .= " `$key` = '" . mysql_real_escape_string ( $v ) . "' ";
			$c ++;
		}
		$this->setSql ( $str )->exec (); 
		return mysql_insert_id(); 
	}
	
	// Exec function
	public function exec() {
		if ($this->sql == "")
			throw new \Exception ( "Empty Request " );
		
		if (count ( $this->where ))
			$this->sql .= " WHERE " . join ( " AND ", $this->where );
		$this->sql = str_replace ( "##", $this->table, $this->sql );
		$this->sql .= " " . $this->ordering . " " . $this->limit;
		$res = mysql_query ( $this->sql );
		
		if (! $res)
			throw new \Exception ( "Mysql Error " . mysql_error () . $this->sql );
		
		$this->sql = "";
		$this->where = array ();
		$this->limit = "";
		$this->ordering = '';
		
		return $res;
	}
	
	// Select Field by Id
	public function selectBy($field, $v, $select=[]) {
		if(count($select)==0)
		return $this->setSql ( "SELECT * FROM ## WHERE `" . $field . "`='" . $v . "' " )->load ();
		else 
		return $this->setSql ( "SELECT ".join(",",$select)." FROM ## WHERE " . $field . "='" . $v . "' " )->load ();
		
	}
	public function selectByAll($field, $v) {
		return $this->setSql ( "SELECT * FROM ## WHERE " . $field . "='" . $v . "' " )->loadList ();
	}
	// Load Single Row
	public function load() {
		$result = mysql_fetch_object ( $this->exec () );
		return $result;
	}
	
	// Load Object List Row
	public function loadList() {
		$result = array ();
	 
		 
			$res = $this->exec ();
			while ( $r = mysql_fetch_object ( $res ) ) {
				$result [] = $r;
			}
		 
		
		return $result;
	}
}

 