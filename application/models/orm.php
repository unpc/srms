<?php

class ORM_Model extends _ORM_Model {

	function followers() {
		return Q("follow[object=$this][dtend=0]");
	}
}
