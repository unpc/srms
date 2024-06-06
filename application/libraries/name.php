<?php

class Name {
	public $last_name;
	public $middle_name;
	public $first_name;
	public $suffix;
	private $_reserved = FALSE;
	
	function __construct($name){
		$name = preg_replace("/\./", "", trim($name, " \n\t\r"));
		
		if(in_array($name,Config::get('reserved_name', []))){
			$this->_reserved = TRUE;
			$this->last_name = $name;
		} 
		elseif (0 < preg_match("/^([^,]+?)\s*(JR|SR|II|III|IV|MD|PHD)?\s*,\s*?([^,\s]+?)(?:\s+?([^,]+?))?$/i", $name, $matches)) {
			//Last [Suffix], First [Middle]
			$this->last_name = mb_convert_case($matches[1], MB_CASE_TITLE);
			if($matches[3]){
				$this->first_name = mb_convert_case($matches[3], MB_CASE_TITLE);
				$this->middle_name = mb_convert_case($matches[4], MB_CASE_TITLE);
			}
			elseif(0 < preg_match("/^([a-zA-Z]{1,2})$/", $name)){
				//Last FM
				$this->first_name = mb_convert_case(mb_substr($matches[3],0,1), MB_CASE_UPPER);
				$this->middle_name = mb_convert_case(mb_substr($matches[3],1,1), MB_CASE_UPPER);
			}
			else{
				//Last First
				$this->first_name = mb_convert_case(mb_substr($matches[3],0,1), MB_CASE_TITLE);
			}
			if($matches[2]){
				$this->suffix = $matches[2];
			}
		} 
		elseif (0 < preg_match("/^([^,]+?)\s+([A-Z][a-z])$/", $name, $matches)) {
			//First Ls
			$this->first_name = mb_convert_case($matches[1], MB_CASE_TITLE);
			$this->last_name = mb_convert_case($matches[2], MB_CASE_TITLE);
		} 
		elseif (0 < preg_match("/^([^,]+?)\s+([a-zA-Z]{1,2})$/", $name, $matches)) {
			//Last FM
			$this->last_name=mb_convert_case($matches[1], MB_CASE_TITLE);
			$this->first_name=mb_convert_case(mb_substr($matches[2],0,1), MB_CASE_UPPER);
			$this->middle_name=mb_convert_case(mb_substr($matches[2],1,1), MB_CASE_UPPER);
		} 
		elseif (0 < preg_match("/^(\S+?)\s+(.+\s)?(\S+?)$/", $name, $matches)) {
			//First [Middle] Last
			$this->last_name = mb_convert_case($matches[3], MB_CASE_TITLE);
			$this->first_name = mb_convert_case($matches[1], MB_CASE_TITLE);
			if($matches[2]){
				$tmp = strtolower(trim($matches[2]));
				if($tmp == 'van de' || $tmp == 'van den'){
					$this->last_name = $tmp.' '.$this->last_name;
				}
				elseif($tmp=='di'){
					$this->last_name = 'Di'.' '.$this->last_name;
				}
				else{
					$this->middle_name = mb_convert_case($tmp, MB_CASE_TITLE);
				}
			}
		} 
		else {
			$this->last_name = mb_convert_case($name, MB_CASE_TITLE);
		}
	}
	
	function valid(){
		return $this->_reserved || ($this->last_name && $this->first_name);
	}
	
	//return Yong FS for Fan Shi Yong
	function short_name(){
		return $this->last_name . ' ' . mb_substr($this->first_name, 0, 1) . mb_substr($this->middle_name, 0, 1);
	}
	
	function full_name($first_first=FALSE){
		$middle_name = $this->middle_name;
		$last_name = $this->last_name;
		$first_name = $this->first_name;
		
		if($first_first){
			if(empty($first_name)){
				return $last_name;
			}
			elseif(empty($middle_name)){
				return $first_name . ' ' . $last_name;
			}
			else{
				return $first_name . ' ' . $middle_name . ' ' . $last_name;
			}
		}
		else{
			if(empty($first_name)){
				return $last_name;
			}
			elseif(empty($middle_name)){
				return $last_name . ', ' . $first_name;
			}
			else{
				return $last_name . ', ' . $first_name . ' ' . $middle_name;
			}
		}
		
	}
	
}

?>
