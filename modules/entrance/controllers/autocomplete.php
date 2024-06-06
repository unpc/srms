<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function users() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		$n = 5;
		if($start == 0) $n = 10;
        if ($s) {
            $s = Q::quote($s);
            
            $selector = "user[name*={$s}|name_abbr*={$s}][atime]:limit({$start},{$n})";
		}
		else{
			$selector = "user[!hidden][atime]:limit({$start},{$n})";

		}
			$users = Q($selector);
			$users_count = $users->total_count();

			if ($start == 0 && !$users_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach($users as $user) {
					Output::$AJAX[] = [
						'html' => (string)V('entrance:autocomplete/user',['user'=>$user]),
						'alt' => $user->id,
						'text' => $user->friendly_name(),
					];
				}
				// $rest = $users->total_count() - $users_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
		}
	}
	
	function labs() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		if ($s) 
		{
			$s = Q::quote($s);
			$selector = "lab[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		}
		else{
			$selector ="lab:limit({$start},{$n})";
		}

			$labs = Q($selector);
			$labs_count = $labs->total_count();

			if ($start == 0 && !$labs_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach($labs as $lab) {
					Output::$AJAX[] = [
						'html'=>(string) V('entrance:autocomplete/lab',['lab'=>$lab]),
						'alt'=>$lab->id,
						'tip'=>I18N::T('entrance','%lab',['%lab'=>$lab->name]),
					];
				}
				// $rest = $labs->total_count() - $labs_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		
	}
	
	function groups() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
		$root = Tag_Model::root('group');
		if ($s) {
			$s = Q::quote($s);
			$groups = Q("tag_group[root={$root}][name*={$s}|name_abbr*={$s}]:sort(weight):limit({$start},{$n})");
			$groups_count = $groups->total_count();
			
			if ($start == 0 && !$groups_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach($groups as $group){
					$tag = (string)V('application:tag/path', ['tag'=>$group]);
					Output::$AJAX[] = [
						'html' => (string) V('entrance:autocomplete/group', ['tag'=>$tag]),
						'alt' => $group->id,
						'text' => $group->name,
					];
				}
				// $rest = $groups->total_count() - $groups_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
        }
		
	}
	
	function doors() {
        $me = L('ME');
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;	
		if ($s) {
			$s = Q::quote($s);
            $selector = "door[name*={$s}]:limit({$start},{$n})";
            if (!$me->access('管理所有门禁')) {
                $selector = "{$me}<incharge ". $selector;
            }

			$doors = Q($selector);
			$doors_count = $doors->total_count();
		
			if ($start == 0 && !$doors_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($doors as $door) {
					Output::$AJAX[] = [
						'html'=>(string) V('entrance:autocomplete/door', ['door'=>$door]),
						'alt'=>$door->id,
						'tip'=>I18N::T('entrance', '%door', ['%door'=>$door->name]),
					];
				}
				// $rest = $doors->total_count() - $doors_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		}
	}

	function devices() {
        $me = L('ME');
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
			$s = trim($s);
			$iot_door = new Iot_door();
			$remote_devices = $iot_door::getDevicesList([
				'st' => $start, 
				'pp' => $n, 
				'keywords' => $s,
				'type' => Input::form()['type']]);

			$devices = [];
			if ($remote_devices['items']) foreach ($remote_devices['items'] as $item) {
				$device = o('door_device', ['uuid' => $item['id']]);
				$device->uuid = $item['id'];
				$device->name = $item['name'];
				$device->save();
				$devices[] = ['id' => $device->id, 'name' => $device->name];
			}

			$devices_count = count($devices);
			if ($start == 0 && !$devices_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($devices as $device) {
					Output::$AJAX[] = [
						'html' => $device['name'],
						'text' => $device['name'],
						'alt'=> $device['id'],
						'id'=>$device['id'],
						'flag'=>'device'
					];		
				}

				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
	}
}
