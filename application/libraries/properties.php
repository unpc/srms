<?php

class Properties extends _Properties {

	private static function _append_tags_and_orders(&$tids, $ts) {
		$tids += $ts->to_assoc('id', 'id');
	}

	function get($name, $tag=NULL, $key="@TAG") {
		static $tags, $last_user;
		
		if ($tag == NULL || $tag == '@') return parent::get($name);
		if ($tag == '*') $tag = NULL;

		switch ($name) {
		case '@TAG':
		case 'tag_root':
			break;
		default:
			$tagged = (array) parent::get($key);

			if ($tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
			}

			$user = L('ME');
			if ($last_user->id != $user->id || $tags === NULL) {
				$root = $this->object()->tag_root;
				if ($root->id) {

					$last_user = $user;

					$tids = [];
					self::_append_tags_and_orders($tids, Q("$user tag[root=$root]"));

					if (Q("$user lab")->total_count()) {
						self::_append_tags_and_orders($tids, Q("$user lab tag[root=$root]"));
					}

					$group = $lab->group;
					if ($group->id) {
						$groot = $group->root;
						if (!$groot->id) $groot = Tag_Model::root('group');
						foreach(Q("tag[root=$root] tag[root=$groot]") as $g) {
							if (!$g->is_itself_or_ancestor_of($group)) continue;
							self::_append_tags_and_orders($tids, Q("$g tag[root=$root]"));
						}
					}

					$group = $user->group;
					if ($group->id) {
						$groot = $group->root;
						if (!$groot->id) $groot = Tag_Model::root('group');
						foreach(Q("tag[root=$root] tag[root=$groot]") as $g) {
							if (!$g->is_itself_or_ancestor_of($group)) continue;
							self::_append_tags_and_orders($tids, Q("$g tag[root=$root]"));
						}
					}

					$tids = implode(',', $tids);
					if ($tids) $tags = Q("tag[id=$tids]:sort(weight A)")->to_assoc('id', 'name');
					else $tags = [];
				
				}

			}
			
			foreach ((array) $tags as $tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
			}
			
		}

		return parent::get($name);

	}
	
	function set($name, $val=NULL, $tag=NULL, $key = "@TAG") {
		if ($tag) {
			$tagged = (array) parent::get($key);
			
			if ($tag == '*') {
				foreach($tagged as & $t) {
					if($val === NULL) {
						unset($t[$name]);
						if (!$t){ unset($t); }
					}
					else {
						$t[$name]=$val;
					}
				}
			}
			else {
				if($val === NULL){
					unset($tagged[$tag][$name]);
					if(!$tagged[$tag]){
						unset($tagged[$tag]);
					}
				}
				else{
					$tagged[$tag][$name] = $val;
				}
			}
			
			return parent::set($key, $tagged);
		}
		else {
		
			return parent::set($name, $val);
		}
	
	}

}
