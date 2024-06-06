<?php

class Staff_Model extends Presentable_model{

	static $educations = [1=>'本科生','研究生','博士生'];
	static $roles	   = [1=>'实习','试用','正式','离职'];

	function get_education(){
		$edu = $this->user->member_type;
		if( $edu ) {
			return User_Model::get_members()['学生'][ $edu ];
		} else {
			return null;
		}
	}

	function & links($mode = NULL) {
		$links = new ArrayIterator;

		if( L('ME')->is_allowed_to('修改', $this ) ){
			$links['editor'] = [
				'url' => "!people/profile/edit.{$this->user->id}.staff",
				'text'  => I18N::T('staff', '修改'),
				'extra' => 'class="blue"'
			];
		}
		return $links;
	}

	function time_diff($start=0, $end = 0){
		if(!$end){
			$end = time();
		}
		if(!$start){
			$start = time();
		}
		$s_y = idate('Y', $start);
		$s_m = idate('m', $start);
		$s_d = idate('d', $start);

		$e_y = idate('Y', $end);
		$e_m = idate('m', $end);
		$e_d = idate('d', $end);

		$time = ['y'=>0,'m'=>0,'d'=>0];
		if( $e_d < $s_d ){
			$time['d'] = $e_d - $s_d + 30;
			$e_m--;
		}else{
			$time['d'] = $e_d - $s_d;
		}
		if( $e_m < $s_m ){
			$time['m'] = $e_m - $s_m + 12;
			$e_y--;
		}else{
			$time['m'] = $e_m - $s_m;
		}

		$time['y'] = $e_y - $s_y;

		return $time;
	}
}
