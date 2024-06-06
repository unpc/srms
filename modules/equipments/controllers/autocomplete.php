<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function user($id = 0) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$equipment = O('equipment', $id);
		$now = Date::time();
		
		if ($s) {
            $s = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}|ref_no*={$s}][!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
		}
		else {
            $selector = "user[!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
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
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/user', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
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
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$no_inserver = EQ_Status_Model::NO_LONGER_IN_SERVICE;
		
		if ($s) {
			$s = Q::quote($s);
			$selector = "equipment[name*={$s}|name_abbr*={$s}|ref_no*={$s}][status!={$no_inserver}]:limit({$start},{$n})";
		}
		else {
			$selector = "equipment[status!={$no_inserver}]:limit({$start},{$n})";
		}

		$equipments = Q($selector);
		$equipments_count = $equipments->total_count();
		
		if ($start == 0 && !$equipments_count) {
			Output::$AJAX[] = array(
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			);
		}
		else {
			foreach ($equipments as $equipment) {
				$users = Q("{$equipment} user.incharge");
				$incharge_arr = array();
				foreach ($users as $user) {
					$incharge_arr[] = H($user->name);
				}
				$incharges = join(', ', $incharge_arr);

				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/equipment', array('equipment'=>$equipment)),
					'alt' => $equipment->id,
					'text' => H($equipment->name),
					'data' => array(
						'incharges' => $incharges,
					),
				);
			}


			if ($start == 95) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				);
			}
		}
	}
	
	function tags() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;	
		$n = 5;
		if($start == 0) $n = 10;
		$root = Tag_Model::root('equipment');
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		if ($s) {
			$s = Q::quote($s);
			$tags = Q("tag_equipment[root={$root}][name*={$s}]:limit({$start},{$n})");
		}
		else {
			$tags = Q("tag_equipment[root={$root}]:limit({$start},{$n})");
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
				if (Config::get('equipment.tag_equipment_display_parent')) {
					$tag_names = [];
					$parent = $tag;
					while ($parent->id && $parent->parent->id) {
						array_unshift($tag_names, $parent->name);
						$parent = $parent->parent;
					}
					$tag_name = implode(' > ', $tag_names);
				}

				Output::$AJAX[] = [
					'html' => (string) V('equipments:autocomplete/tag', ['tag'=>$tag_name]),
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
	
	function labs() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		if ($s) {
			$s = Q::quote($s);
			$labs = Q("lab[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
		}
		else {
			$labs = Q("lab:limit({$start},{$n})");
		}
		$labs_count = $labs->total_count();

		if ($start == 0 && !$labs_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($labs as $lab) {
				Output::$AJAX[] = [
					'html' => (string) V('equipments:autocomplete/lab', ['lab'=>$lab]),
					'alt' => $lab->id,
					'text' => $lab->name,
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
	
	function groups() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$root = Tag_Model::root('group');
		if ($s) {
			$s = Q::quote($s);
			$groups = Q("tag_group[root={$root}][name*={$s}|name_abbr*={$s}]:limit({$start}, {$n})");
		}
		else {
			$groups = Q("tag_group[root={$root}]:limit({$start}, {$n})");
		}
		$groups_count = $groups->total_count();
		
		if ($start == 0 && !$groups_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($groups as $group){
				Output::$AJAX[] = [
					'html' => (string) V('equipments:autocomplete/group', ['group'=>$group]),
					'alt' => $group->id,
					'text' => $group->name,
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

    function record_user($id = 0) {
        $s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
        $equipment = O('equipment', $id);

        if ($s) {
            $s = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}][!hidden]:limit({$start},{$n})";
        }
        else {
            $selector = "user[!hidden]:limit({$start},{$n})";
        }
        $users = Q($selector);
        $users_count = $users->total_count();
        $has_lab = Module::is_installed('labs');

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($users as $user) {
                if (Q("$user lab")->total_count() == 1 && $has_lab) {
                    $name = T('%user (%lab)', ['%user'=>H($user->name), '%lab'=>H(Q("$user lab")->current()->name)]);
                }
                else {
                    $name = H($user->name);
                }

                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/user', ['user'=>$user]),
                    'alt' => $user->id,
                    'text' => $name,
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

    function archive($id = 0) {
        $s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
        $equipment = O('equipment', $id);

        if ($s) {
            $s = Q::quote($s);
            $selector = "$equipment tag[name*={$s}|name_abbr*={$s}].archive:limit({$start},{$n})";
            $archives = Q($selector);
            $archives_count = $archives->total_count();

            if (!$archives_count) {
                Output::$AJAX[] = [
                        'html' => (string) V('autocomplete/special/empty'),
                        'special' => TRUE
                        ];
            }
            else {
                foreach ($archives as $archive) {

                    Output::$AJAX[] = [
                            'html' => (string) V('autocomplete/archive', ['archive'=>$archive]),
                            'alt' => $archive->id,
                            'text' => $archive->name,
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

	function user_tags($oid=0) {
		$object = O('equipment', $oid);
		if (!$object->id || !($object->tag_root instanceof Tag_Model)) return;
		$root = $object->get_root();

        $equipment_user_tags_root = Tag_Model::root('equipment_user_tags');
		
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/

        $db = Database::factory();

        $sql = "select * from (
                select concat('tag%',id) as id,name,_extra from `tag` where `root_id`={$root->id}
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
					'html' => (string) V('equipments:autocomplete/user_tag', ['tag'=>$tag]),
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

	function domain() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		$domains = array_slice(Config::get('equipment.domain'), $start, $n);

		$domain_count = count($domains);

		if ($start == 0 && !$domain_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($domains as $key => $domain){
				Output::$AJAX[] = [
					'html' => (string) V('equipments:autocomplete/domain', ['domain' => $domain]),
					'alt' => $key,
					'text' => $domain,
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


    public function equipment_incharge($id)
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($start >= 100) {
            return;
        }
        $n = 5;
        if ($start == 0) {
            $n = 10;
        }
        $now = Date::time();

        if ($s) {
            $s = Q::quote($s);
            $selector = "equipment#{$id}<incharge user[name*={$s}|name_abbr*={$s}][!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
        } else {
            $selector = "equipment#{$id}<incharge user[!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
        }

        $users = Q($selector);
        $users_count = $users->total_count();

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($users as $user) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/user', ['user'=>$user]),
                    'alt' => $user->id,
                    'text' => $user->friendly_name(),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }

    function tag_location() {
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
                if (Config::get('equipment.tag_equipment_display_parent')) {
                    $tag_names = [];
                    $parent = $tag;
                    while ($parent->id && $parent->parent->id) {
                        array_unshift($tag_names, $parent->name);
                        $parent = $parent->parent;
                    }
                    $tag_name = implode(' > ', $tag_names);
                }

                Output::$AJAX[] = [
                    'html' => (string) V('equipments:autocomplete/tag', ['tag'=>$tag_name]),
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
