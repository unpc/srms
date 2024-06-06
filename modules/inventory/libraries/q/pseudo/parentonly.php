<?php

class Q_Pseudo_ParentOnly implements Q_Pseudo {
	
	private $_query;
	static $guid=0;

    function __construct($query) {
        $this->_query = $query;
    }   


    function process($selector) {
    	var_dump('expression');
		// :daymax(time|group_by)
		$query = $this->_query;
		$db = $query->db;
		$subSQL = 'SELECT * FROM '.$db->make_ident($query->name).' GROUP BY '.$selector;

		$t = 'dm'.(self::$guid++);

		$query->join_tables[] = $subSQL.' '.$t;
    }   

}
