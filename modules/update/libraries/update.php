<?php

class Update {

	static function home_url($e, $home) {
		$me = L('ME');
		if ($me->id && $home == 'update' && Update::count_updates($me) > 0) {
			$e->return_value = '!update';
			return FALSE;
		}
	}

	static function on_orm_updating($e, $object, $old_data, $new_data) {
	
		//by Jia Huang @ 2010.10.23
		//禁止没有当前用户的情况下进行更新处理
		if (!L('ME')->id) return TRUE;
	
		$update_names = Config::get('lab.update_module_name');
		if (!in_array($object->name(), $update_names)) {
			return TRUE;
		}
		
		$datas = Event::trigger($object->name().'_model.updating  model.updating', $object, $old_data, $new_data);
		if (!$datas) return TRUE;

		foreach ((array) $datas as $data) {
			if (!is_array($data)) continue;
			self::add_update($data['subject'], $data['action'], $data['object'], $data['old_data'], $data['new_data']);
		}
		 
	}

	static function add_update($subject, $action, $object, $old_data=NULL, $new_data=NULL) {
		if ($subject->id) {
			$update = O('update');
			$update->object = $object;
			$update->subject = $subject;
			$update->action = $action;
			$update->new_data = json_encode($new_data);
			$update->old_data = json_encode($old_data);
			$update->save();
		}	
	}

	static function prepare_read_table() {
		$db = ORM_Model::db('update');

		$db->prepare_table('_r_user_update',
			[ 
				//fields
			'fields' => [
					'id1'=>['type'=>'bigint', 'null'=>FALSE],
					'id2'=>['type'=>'bigint', 'null'=>FALSE],
					'type'=>['type'=>'varchar(20)', 'null'=>FALSE],
					'approved'=>['type'=>'tinyint unsigned', 'null'=>FALSE, 'default'=>0],
				], 
				//indexes
			'indexes' => [ 
					'PRIMARY'=>['type'=>'primary', 'fields'=>['id1', 'id2', 'type']],
					'id1'=>['fields'=>['id1', 'type']],
					'id2'=>['fields'=>['id2', 'type']],
					'approved'=>['fields'=>['approved']],
				]
			]
		);

	}
	
	static function fetch($start, $per_page, &$next_start, $oname='all') {
		$updates = new ArrayIterator;
		$user = L('ME');
		$left = $per_page;

		self::prepare_read_table();

		$db = ORM_Model::db('update');

		if ($oname == 'all') {
			$SQL = strtr(Config::get('update.query.fetch_all'), ['%uid'=>$user->id]);
		}
		else {
			$SQL = strtr(Config::get('update.query.fetch_partial'), ['%uid'=>$user->id, '%oname'=> $oname]);
		}

		while ($left > 0) {
			$candidates = new ORM_Iterator('update', "$SQL LIMIT $start,$left");
			$ccount = count($candidates);
			if ($ccount == 0) break;	
			$start += $ccount;

			foreach ($candidates as $id => $candidate) {
				$subject = $candidate->subject;
				$object = $candidate->object;
				if (!$object->id || !$subject->id) {
					unset($candidates[$id]);
					continue;
				}
				$action = $candidate->action;
				$key = "{$subject->name()}_{$subject->id}_{$action}_{$object->name()}_{$object->id}";
				$update = $updates[$key];
				if ($update == NULL) {
					$updates[$key] = $update = O('update');
				}
				if ($update && (($update->ctime - $candidate->ctime) > 86400)) {
					$updates[$key."_".$candidate->id] = $candidate;
				}
				else {
					$update->merge($candidate);				 
				}
			}
			$left = $per_page - count($updates);
		}
		$next_start = $start;
		return $updates;
	}
	
	static function count_updates($user, $oname='*', $only_new=FALSE) {
		self::prepare_read_table();
		$SQL = strtr(Config::get('update.query.count_updates'), ['%uid'=>$user->id]);
		$db = ORM_Model::db('update');
		return $db->value($SQL);
	}


	/* TASK #1303::Update更新需要能够清除全部(kai.wu@2011.08.10) */
	static function fetch_all_updates($oname) {
		$user = L('ME');
		if ($oname == 'all') {
			$SQL = strtr(Config::get('update.query.fetch_all'), ['%uid'=>$user->id]);
		}
		else {
			$SQL = strtr(Config::get('update.query.fetch_partial'), ['%uid'=>$user->id, '%oname'=> $oname]);
		}
		$updates = new ORM_Iterator('update', "$SQL");
		
		return $updates;
	}
	
	/* TASK #1303::Update更新需要能够清除全部(kai.wu@2011.08.15) */
	static function delete_all_updates($oname) {
		$user = L('ME');
		$db = ORM_Model::db('_r_user_update');
		if ($oname == 'all') {
			$SQL = strtr(Config::get('update.query.delete_all'), ['%uid'=>$user->id]);
		}
		else {
			$SQL = strtr(Config::get('update.query.delete_partial'), ['%uid'=>$user->id, '%oname'=> $oname]);
		}
		
		return $db->query($SQL);
	}
}
