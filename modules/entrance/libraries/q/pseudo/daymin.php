<?php

class Q_Pseudo_DayMin implements Q_Pseudo {

    private $_query;
	static $guid=0;	
   
	function __construct($query) {
        $this->_query = $query;
    }   


    function process($selector) {
		//:daymin(time|group_by)
		list($field,$group_by) = explode('|', $selector);
		$query = $this->_query;
		$db = $query->db;

		$q_field = $db->make_ident($query->table, $field);

		$subSQL = '(SELECT MIN('.$field.') '.$field.' FROM '.$db->make_ident($query->name).' GROUP BY '.$group_by.',DATE(FROM_UNIXTIME('.$field.')))';

		$t = 'dm'.(self::$guid++);
		$t_field = $db->make_ident($t, $field);

		$query->join_tables[] = $subSQL.' '.$t;
		$query->join_criteria[] = $q_field . '=' . $t_field; 

    }   

}
