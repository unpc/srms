<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function user($lab_id=0) {
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
			$selector = "user[!hidden][atime][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		}
		else {
			$selector = "user[!hidden][atime]:limit({$start},{$n})";
		}
		$lab = O('lab', $lab_id);
		if ($lab->id) {
            $pre_selector = "{$lab} ";
        }
        $selector = $pre_selector.$selector;
		$users = Q($selector);
		$users_count = $users->total_count();

		if ($start == 0 && !$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/user', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
				];
			}

			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
	
	function object_tags($oname='', $oid=0) {
		if (!$oname) return;
		$object = O($oname, $oid);
		if (!$object->id || !($object->tag_root instanceof Tag_Model)) return;

		$object_root = $object->tag_root;

		$temp_root_name = Config::get('tag.equipment_user_tags', '仪器用户标签');
        $equipment_user_tags_root = Tag_Model::root('equipment_user_tags');
		$temp_root = Q("tag[parent_id=0][name={$temp_root_name}]")->current();

		if ( !$object_root->id  && !$temp_root->id) return;
		
		$s = trim(Input::form('s'));
		/*
		加入滚动加载功能，当加载数量达到100是提示重新输入搜索项
		*/
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

		$db = Database::factory();

        $sql = "select * from (
                select concat('tag%',id) as id,name,_extra from `tag` where `root_id`={$object_root->id}
                union
                select concat('tag_equipment_user_tags%',id) as id,name,_extra from `tag_equipment_user_tags` where `root_id`={$equipment_user_tags_root->id}
                ) as t";

		if ($s) {
			$s = Q::quote($s);
            $sql .= " where name like '%{$s}%'";
		}

        $sql .=" limit {$start},{$n}";

        $results = $db->query($sql);
        $tags = $results->rows();

		$tags_count = $results->count();

		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag){
				$tag->path = json_decode($tag->_extra,1)['path'];
				
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/tag', ['tag'=>$tag, 'tag_root'=>$tag->root]),
					'alt' => $tag->id,
					'text' => $tag->name,
				];
			}
			//$rest = $tags->total_count() - $tags_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function groups() {
		$root = Tag_Model::root('group');
		
		$s = trim(Input::form('s'));
		/*
		加入滚动加载功能，当加载数量达到100是提示重新输入搜索项
		*/
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
		if ($s) {
			$s = Q::quote($s);
			$tags = Q("tag_group[root={$root}][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
		}
		else {
			$tags = Q("tag_group[root={$root}]:limit({$start},{$n})");
		}

		$tags_count = $tags->length();

		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag){
				if (!$tag->parent->id) continue;
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/tag', ['tag'=>$tag, 'tag_root'=>$root]),
					'alt' => $tag->id,
					'text' => $tag->name,
				];
			}
			//$rest = $tags->total_count() - $tags_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}	
	}

	/**
	 * xiaopei.li@2011.02.23
	 * multi-tag-selector的通用型auto-complete
	 *
	 * @param root
	 *
	 * @return
	 */
	function tags($root_id) {
		$s = trim(Input::form('s'));
		/*
		加入滚动加载功能，当加载数量达到100是提示重新输入搜索项
		*/
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
		$root = O('tag', $root_id);
		if (!$root->id) {
			return FALSE;
		}

		if ($s) {
			$s = Q::quote($s);
			$all_tags = Q("tag[root={$root}][name*={$s}|name_abbr*={$s}]");
			$tags = $all_tags->limit($n);

			$all_tags_count = $all_tags->total_count();
			$tags_count = $tags->length();

			$all_tags = (array) $all_tags->to_assoc('name', 'id');
			$tags = (array) $tags->to_assoc('name', 'id');
		}
		else {
			$all_tags = Q("tag[root={$root}]");
			$tags = $all_tags->limit($n);

			$all_tags_count = $all_tags->total_count();
			$tags_count = $tags->length();

			$all_tags = (array) $all_tags->to_assoc('name', 'id');
			$tags = (array) $tags->to_assoc('name', 'id');
		}
		
		//$rest = $all_tags_count - $tags_count;
		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag=>$reserved){
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/small_tag', ['tag'=>$tag]),
					'alt' => $tag,
					'text' => $tag,
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

	function equipment() {
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
			$equipments = Q("equipment[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
		}
		else {
			$equipments = Q("equipment:limit({$start},{$n})");
		}
		$equipments_count = $equipments->total_count();

		if ($start == 0 && !$equipments_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($equipments as $equipment) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/equipment', ['equipment'=>$equipment]),
					'alt' => $equipment->id,
					'text' => $equipment->name,
					'data' => json_encode($equipment->contacts()->to_assoc('id', 'name'))
				];
			}

			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}


	function location() {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $root = Tag_Model::root('location');
        /*
        NO.TASK#312(guoping.zhang@2011.01.07)
        查询限制数量：10
        */
        if ($s) {
            $s = Q::quote($s);
            $tags = Q("tag_location[root={$root}][name*={$s}]:limit({$start},{$n})");
        }
        else {
            $tags = Q("tag_location[root={$root}]:limit({$start},{$n})");
        }

        $tags_count = $tags->total_count();
        if ($start == 0 && !$tags_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach($tags as $tag){
                $tag_name = $tag->name;
                $tag_names = [];
                    $parent = $tag;
                    while ($parent->id && $parent->parent->id) {
                        array_unshift($tag_names, $parent->name);
                        $parent = $parent->parent;
                    }
                    $tag_name = implode(' > ', $tag_names);

                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/tag', ['tag'=>$tag_name]),
                    'alt' => $tag->id,
                    'text' => $tag->name,
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
