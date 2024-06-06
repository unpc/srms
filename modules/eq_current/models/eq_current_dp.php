<?php

class EQ_Current_DP_Model extends ORM_Model {

	function save($overwrite=FALSE) {
		$ret = parent::save($overwrite);
		if ($ret) {
			$ctime = $this->ctime;
			$db = ORM_Model::db('eq_current_dp');
			$id = $this->id;
			$update_dp = Q("eq_current_dp[ctime<$ctime]:sort(ctime D, id D):limit(1)")->current();
			if ($update_dp) {
				$update_dp->_update_followings();
			}
		}		
		return $ret;
	}

	function _update_followings() {
		$db = ORM_Model::db('eq_current_dp');
		$id = $this->id;
		$ctime = $this->ctime;
		$db->query('SET @prev_dp=(%d)', $id);
		$db->query(
			'UPDATE eq_current_dp SET prev_id=(@prev_dp:=id) WHERE ctime>%d ORDER BY ctime ASC, id ASC',
			$id, $ctime);
	}
}
