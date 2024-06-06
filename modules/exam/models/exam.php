<?php

class Exam_Model extends Presentable_Model {
	public function getRemoteUrl(){
		if (!$this->id || !$this->remote_id || !$this->remote_app) {
			return false;
		}
		$confs = (array)Config::get('exam.remote_exam');
		if ($conf = $confs[$this->remote_app]) {
			return strtr($conf['domain'].$conf['paths']['do'], ['%id'=> $this->remote_id]);
		}
		return false;
	}
}
