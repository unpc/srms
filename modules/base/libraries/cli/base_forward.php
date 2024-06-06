<?php

class CLI_Base_Forward{
	static function forward(){
       
        $rpc = new RPC(Config::get('gini.url'));

		$base_point = Q('base_point[dtend>0]');
		foreach ($base_point as $value ) {
			$point = array();
			$point['user'] = $value->user;
			$point['gapper_id'] = self::_getUser($value->user)['gapper_id'];
			$point['member_type'] = self::_getUser($value->user)['member_type'];
			$point['sid'] = $value->sid;
			$point['source_id'] = LAB_ID;
			$point['source_name'] = Config::get('page.title_default');
            $point['uid'] = self::_getUser($value->user)['uid'];
			$point['address'] = $value->address;
			$point['province'] = self::_getProvince($value->address);
    		$point['city'] = self::_getCity($value->address);
			$point['dtstart'] = $value->dtstart;
			$point['dtend'] = $value->dtend;
			$point['keeptime'] = $value->dtend - $value->dtstart;
            $point['device'] = self::_getDevice($value->browser);
            $point['device_type'] = self::_getDeviceType($value->browser);
			$point['OS_type'] = self::_getOS($value->browser);
			$point['browser'] = self::_getBrowser($value->browser);
			$point['version'] = self::_getVersion($value->browser);
			$point['signout_way'] = $value->signout_way;
            $point['bid'] = $value->id;	
            $rpc->summary->point($point);
		}
		$base_action = Q('base_action');        
		foreach ($base_action as $key => $value) {
			$action = array();
			$action['action'] = $value->action;
			$action['module'] = $value->module;
			$action['ctime'] = $value->ctime;
            $action['uid'] = self::_getUID($value->base_point)['uid'];
            $action['gapper_id'] = self::_getUID($value->base_point)['gapper_id'];
            $action['source_id'] = LAB_ID;
            $action['bid'] = $value->base_point_id;
            $rpc->summary->action($action);  
		}
    }
   	
    private static function _getUser($user) {
		if($user->id){
			$gapper_id = $user->gapper_id;
			$user_id = $user->id;
			$member_type = $user->member_type;
			return [
				'gapper_id' => $gapper_id,
				'member_type' => $member_type,
                'uid' => $user_id,
			];
		}
	}
    private static function _getUID($base_point) {
        if($user = $base_point->user){
            $uid = $user->id;
            $gapper_id = $user->gapper_id;
            return [
                'gapper_id' => $gapper_id,
                'uid' => $uid,
            ];
        }
    }
    private static function _getProvince($address) {    
        $add = strtok($address, '.');
        if(in_array(strtok($add, '.'), array('10', '127', '168', '192'))){
        $reladdress = Config::get('habit.default_ip'); 
        }      
        $ipadd = file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $reladdress); 
        if(empty($ipadd)){ return false; } 
        if ($ipadd) {  
            $charset = iconv("gbk", "utf-8", $ipadd);
            $a = preg_replace(['/^var[^=]*= /', '/;/'], ['',''], $charset);
            $ipadds= json_decode($a, true);
            $province = $ipadds[province]; 
            return $province;   
        } else {  
            return "province is none";  
        }  
	}
	private static function _getCity($address) { 
        $add = strtok($address, '.');
        if(in_array(strtok($add, '.'), array('10', '127', '168', '192'))){
        $reladdress = Config::get('habit.default_ip'); 
        }      
        $ipadd = file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $reladdress); 
        if(empty($ipadd)){ return false; } 
        if ($ipadd) {  
            $charset = iconv("gbk", "utf-8", $ipadd);
            $a = preg_replace(['/^var[^=]*= /', '/;/'], ['',''], $charset);
            $ipadds= json_decode($a, true);
            $city = $ipadds[city]; 
            return $city;   
        } else {  
            return "city is none";  
        }  
		
	}
    private static function _getOS($browser) {  	
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) 
        require_once($autoload);
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($browser);
        $OS = $agent->platform();

        return $OS;     
    }  
    private static function _getBrowser($browser) {
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) 
        require_once($autoload);
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($browser);
        $br = $agent->browser();
        return $br;  
    }
    //判断设备种类
    private static function _getDevice($browser) {
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) 
        require_once($autoload);
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($browser);
        if($agent->isPhone()) {
            $device = "Mobile";
        }
        elseif($agent->isDesktop()) {
            $device = "Desktop";
        }
        elseif($agent->isRobot()) {
            $device = "Robot";
            // this currently only works for major robots like Google, Facebook, Twitter, Bing, Baidu 
        }
        else {
            $device = "Unrecognized";
        }
        return $device;  
    }
    private static function _getDeviceType($browser) {
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) 
        require_once($autoload);
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($browser);
        if($agent->isPhone()) {
            $type = $agent->device();
        }
        else {
            $type = "immovable device";
        }
        return $type;  
    }
    private static function _getVersion($browser) {
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) 
        require_once($autoload);
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($browser);
        $abrowser = $agent->browser();
        $version = $agent->version($abrowser);
        return $version;  
    }
}


