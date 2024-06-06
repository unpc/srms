<?php

class EQ_Power_Time_Model extends ORM_Model {

	function save($overwrite = FALSE) {
		$this->duration = max($this->dtend - $this->dtstart, 0);
		return parent::save($overwrite);
	}
}
