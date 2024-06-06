<?php

class API_Equipment {
    
    function get_tag_view($root_name, $name, $t_id, $url=NULL) {
        if (!$url) $url = URI::url('tags');
        $root = Tag_Model::root($root_name);
        $tag = O($root->name(), $t_id);
        $view = '<div class="gray_tag_container middle">'.(string)Widget::factory('application:tag_selector', [
                    'tag' => $tag,
                    'root' => $root,
                    'name' => $name,
                    'ajax' => TRUE,
                    'ajax_url' => $url
                ]).'</div>';
        return $view; 
    }
	
	public static $errors = [
        401 => 'Access Denied',
        500 => 'Internal Error'
    ];

    public static $sortables = [
        'name_abbr',
        'model_no',
        'id',
        'is_upload_pictures', // 复旦大学一定要求，仪器上传了图片的仪器排在前面展示
        'price',
    ];

    private function _ready() {
        // TODO config-able whitelist
        $whitelist = Config::get('api.security_ip');
        $whitelist[] = '127.0.0.1';
        $whitelist[] = '172.17.42.1';
        $whitelist[] = $_SERVER["SERVER_ADDR"];

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist) && false) {
            throw new API_Exception(self::$errors[401], 401);
		}
		return;
    }

	private function _token_check($token) {
        $cache = Cache::factory('redis');

        if(!$cache->get($token)) {
            throw new API_Exception(self::$errors[500], 500);
        }
	}

    public function searchEquipments($criteria = []) {
        $this->_ready();
        $return_data = [];
        $cache = Cache::factory('redis');

        //生成token
        $token = uniqid();
        $return_data['token'] = $token;

        $selector ="equipment";
        $pre_selectors =[];

        if ($criteria['cat']) {
			$cat = O('tag_equipment', $criteria['cat']);
			if($cat->root->id) {
				$pre_selectors['cat'] = "{$cat}";
			}
        }

        // cats=1,2,3
        if ($criteria['cats']!=''){
            $pre_selectors['cat'] = "tag_equipment[id=".trim($criteria['cats'])."]";
        }

        if ($criteria['group']) {
            $group = O('tag_group', $criteria['group']);
            if ($group->root->id) {
                $pre_selectors['group'] = "{$group}";
            }
        }

        // groups=1,2,3
        if ($criteria['groups']) {
            $pre_selectors['group'] = "tag_group[id=".trim($criteria['groups'])."]";
        }

        if ($criteria['years']) {
            $year = explode(',', $criteria['years']);
            $ar_year = [];
            foreach($year as $v) {
                if ($v) {
                    $ar_year[] = "atime=".strtotime($v.'-01-01')."~".strtotime($v.'-12-31 23:59:59');
                }
            }
            if ($ar_year) {
                $selector .= "[".implode('|',$ar_year)."]";
            }
        }
        if($criteria['contact']) {
            $contact = Q::quote($criteria['contact']);
            $user = Q("user[name*=$contact]");
            if ($user->total_count()) {
                $pre_selectors['contact'] = "user[name*=$contact]<contact";
            }else {
                $selector .= "[id=-1]";
            }
        }
        if($criteria['incharge']) {
            $incharge = Q::quote($criteria['incharge']);
            $user = Q("user[name*=$incharge]");
            if ($user->total_count()) {
                $pre_selectors['incharge'] = "user[name*=$incharge]<incharge";
            }
        }
        if($criteria['phone']) {
            $selector .= "[phone*=".Q::quote($criteria['phone'])."]";
        }

        if ($criteria['platform']) {
            $platform = O('platform', (int)$criteria['platform']);
            if ($platform->id) {
                $pre_selectors['platform'] = "{$platform}";
            }
        }
        
        if ($criteria['ids']!=''){
            $ids = Q::quote($criteria['ids']);
            $selector .= "[id={$ids}]";
        }

        if ($criteria['hide_eq_ids']!=''){
            $hide_eq_ids = is_array($criteria['hide_eq_ids']) ? $criteria['hide_eq_ids'] : explode(',', $criteria['hide_eq_ids']);
            foreach($hide_eq_ids as $hide_eq_id) {
                $selector .= "[id!={$hide_eq_id}]";
            }
        }
        
        if ($criteria['yiqikong_ids'] != '') {
            $yiqikong_ids = Q::quote($criteria['yiqikong_ids']);
            $selector .= "[yiqikong_id={$yiqikong_ids}]";
        }

        if ($criteria['searchtext']!=''){
            $name = Q::quote($criteria['searchtext']);
            $selector .="[name*=$name]";
        }
        if($criteria['model_no']!=''){
            $model_no = Q::quote($criteria['model_no']);
            $selector .="[model_no*=$model_no]";
        }
        if($criteria['ref_no']!=''){
            $ref_no = Q::quote($criteria['ref_no']);
            $selector .="[ref_no*=$ref_no]";
        }

		if ($criteria['price'] != '') {
			$prices = explode('-', $criteria['price']);
			$price_start = floatval($prices[0]);
			$selector .= "[price>=$price_start]";
			if($prices[1]) {
				$price_end = floatval($prices[1]);
			    $selector .= "[price<=$price_end]";
			}
		}

        if ($criteria['status']) {
            $status = join(',', $criteria['status']);
            $selector .= "[status={$status}]";
        }

        if($criteria['is_using']){
            $is_using = Q::quote($criteria['is_using']);
            $selector .="[is_using={$is_using}]";
        }

        if ($criteria['share_method']) {
            switch ($criteria['share_method']) {
                case 'reserv':
                    $selector .= "[accept_reserv]";
                    break;
                case 'sample':
                    $selector .= "[accept_sample]";
                    break;
                case 'reserv|sample':
                    $selector .= "[accept_reserv|accept_sample]";
                    break;
                case 'reserv&sample':
                    $selector .= "[accept_reserv][accept_sample]";
                    break;
                case 'no':
                    $selector .= "[!accept_reserv][!accept_sample]";
                    break;
            }
        }

        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', $pre_selectors).') ' . $selector;
        }
        if ($criteria['sort'] && in_array($criteria['sort']['by'], self::$sortables)) {
            $sort_by = trim(Q::quote($criteria['sort']['by']), '"');
            $sort_sc = trim(Q::quote($criteria['sort']['sc']), '"');
            $selector .= ":sort($sort_by $sort_sc)";
        } elseif (Module::is_installed('eq_sample') && !Module::is_installed('eq_reserv')) {
            $selector .= ':sort(accept_sample D)';
        } elseif (!Module::is_installed('eq_sample') && Module::is_installed('eq_reserv')) {
            $selector .= ':sort(accept_reserv D)';
        } elseif (Module::is_installed('eq_sample') && Module::is_installed('eq_reserv')) {
            $selector .= ':sort(accept_reserv D, accept_sample D)';
        }
        $cache->set($token, $selector, 3600);
        $return_data['total'] = Q($selector)->total_count();
        return $return_data;
    }

    public function getEquipments($token, $start=0, $num = 30) {
        //security checking
		$this->_token_check($token);
        $cache = Cache::factory('redis');	
        $selector = $cache->get($token);
		if(!$start) $start = 0;
        $equipments = Q("$selector")->limit($start,$num);
        return $this->get_equipments_model($equipments);
    }

	public function getEquipment($token) {
		$this->_token_check($token);
        $cache = Cache::factory('redis');
        $selector = $cache->get($token);
        $equipments = $this->get_equipments_model(Q("$selector"));
		if(is_array($equipments) && sizeof($equipments) > 0) {
			return $equipments[0];	
		}
	}

    private function get_equipments_model($equipments) {
        $equipments_data = [];
        foreach ($equipments as $equipment) {
            $tag = $equipment->group;
            $group = $tag->id ? [$tag->id => $tag->name] : [] ;
            while($tag->parent->id && $tag->parent->root->id){
                $group += [$tag->parent->id => $tag->parent->name];
                $tag = $tag->parent;
            }
            $root = Tag_Model::root('equipment');
            $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
            $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');
            
            $location1 = '';
            if (Config::get('equipment.location_type_select')){
                $ls = Q("{$equipment} tag_location")->to_assoc('id', 'name');
                if (count($ls)) {
                    $location1 .= join(' ', $ls);
                }
            }else{
                $location1 = $equipment->location;
            }
            
            $data = [
                'id'    => $equipment->id,
                'icon_url' => $equipment->icon_url('32'),//默认图标，向后兼容
                'icon16_url' => $equipment->icon_url('16'),
                'icon32_url' => $equipment->icon_url('32'),
                'icon48_url' => $equipment->icon_url('48'),
                'icon64_url' => $equipment->icon_url('64'),
                'icon128_url' => $equipment->icon_url('128'),
                'iconreal_url' => $equipment->icon_file('real') ?
                    Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime :
                    $equipment->icon_url('128'),
                'url' => $equipment->url(),
                'name' => $equipment->name,
                'name_abbr' =>$equipment->name_abbr,
                'phone' => $equipment->phone,
                'contact' => join(', ', $users),
                'email' => $equipment->email,
                'location' => $location1,
                'accept_sample' => $equipment->accept_sample,
                'accept_reserv' => $equipment->accept_reserv,
                'reserv_url' => $equipment->url('reserv'),
                'sample_url' => $equipment->url('sample'),
                'price' => $equipment->price,
                'status' => $equipment->status,

                'ref_no' => $equipment->ref_no,
                'cat_no' =>$equipment->cat_no,
                'model_no' => $equipment->model_no,
                'control_mode' => $equipment->control_mode,
                'is_using' => $equipment->is_using,
                'connect' => $equipment->connect,
                'is_monitoring' => $equipment->is_monitoring,
                'is_monitoring_mtime' => $equipment->is_monitoring_mtime,
                'current_user' => $equipment->current_user()->name,
                'accept_limit_time' =>  $equipment->accept_limit_time,
                'organization' =>$equipment->organization,
                'specification' =>$equipment->specification,
                'tech_specs' => $equipment->tech_specs,
                'features' => $equipment->features,
                'configs' => $equipment->configs,
                'open_reserv' => $equipment->open_reserv,
                'charge_info' => $equipment->charge_info,

                'manu_at' => $equipment->manu_at,
                'manufacturer' => $equipment->manufacturer,
                'manu_date' => $equipment->manu_date,
                'purchased_date' => $equipment->purchased_date,
                'control_address' => $equipment->control_address,
                'require_training' => $equipment->require_training,
                
                'ctime' => $equipment->ctime,
                'atime' => $equipment->atime,
                'mtime' => $equipment->mtime,
                'access_code' => $equipment->access_code,
                'group' => $group,
                'group_id' => $equipment->group->id,
                'group_name' => $equipment->group->name,
                'tag_root_id' => $equipment->tag_root_id,
                'billing_dept_id' =>  $equipment->billing_dept_id,
                'incharges' =>join(', ', $incharges),
                'tags' => join(', ', $tags),
                'incharges_info' => $incharges,
                'contacts_info' => $users,
                'tagsInfo' => $tags,
                'en_name' =>$equipment->en_name,
                'yiqikong_share' =>$equipment->yiqikong_share,
                'bluetooth_serial_address' =>$equipment->control_address
            ];

            if (Module::is_installed('nrii')) {
                $nrii = O('nrii_equipment', ['eq_id' => $equipment->id]);
                if ($nrii->id) {
                    //参考收费标准
                    $data['charge_rule'] = $nrii->fee;
                    //对外开放共享规定
                    $data['requirement'] = $nrii->requirement;
                    //服务内容
                    $data['service_content'] = $nrii->service_content;
                    //服务典型成果
                    $data['achievement'] = $nrii->achievement;
                }
            }
            $data = Event::trigger('equipment.api.extra', $equipment, $data) ? : $data;

            $equipments_data[] = $data;
        }
        return $equipments_data;
    }

	function getEquipmentGroups($criteria = []) {
        $this->_ready();
		$group_name = H($criteria['group_name']);
        $data = [];
        $group_root = Tag_Model::root('group');
        if ($group_name) {
            $group = O('tag_group', ['root' => $group_root, 'name' => $group_name]);
        }
        $group = $group->id ? $group : $group_root;

        $SQL = "tag_group[parent={$group}]";

        if (isset($criteria['order_by'])) {
            $order_by = H($criteria['order_by']);
            $desc = H($criteria['order_by_desc'] ?: 'A');
            $SQL .= ":sort({$order_by} {$desc})";
        }

        $groups = Q($SQL);

        foreach ($groups as $child) {
            $next_child = self::get_next_child($child);
            $data[$child->id] = [
                'name' => H($child->name),
                'children' => $next_child,
                'eq_count' => Q("{$child} equipment")->total_count()
            ];
        }
        return $data;
	}

    function getEquipmentTags($criteria = []) {
        $this->_ready();
        $tag_name = $criteria['tag_name'];
        $parent_id = $criteria['parent_id'];
        $cat_root = Tag_Model::root('equipment');
        if ($tag_name) {
            $tag = O('tag_equipment', ['root' => $cat_root, 'name' => $tag_name]);
        }
        $tag = $tag->id ? $tag : $cat_root;

        $SQL = "tag_equipment[parent={$tag}]";

        if (isset($criteria['order_by'])) {
            $order_by = H($criteria['order_by']);
            $desc = H($criteria['order_by_desc'] ?: 'A');
            $SQL .= ":sort({$order_by} {$desc})";
        }

        $tags = Q($SQL);

        if ($tags->total_count()) {
            foreach ($tags as $child) {
                $next_child = self::get_next_child($child);
                $data[$child->id] = [
                    'name'=>H($child->name),
                    'children'=>$next_child,
                    'eq_count' => Q("{$child} equipment")->total_count()
                ];
            }
        } else {
            $next_child = self::get_next_child($child);
            $data[$tag->id] = [
                'name'=>H($tag->name),
                'children'=>$next_child,
                'eq_count' => Q("{$tag} equipment")->total_count()
            ];
        }

        return $data;
    }

    private function get_next_child($tag) {
        if (!$tag->root->id) return;
        $all = [];
        $children = $tag->children();
        foreach ($children as $child) {
            $all[$child->id]['name'] = H($child->name);
            $all[$child->id]['eq_count'] = Q("{$child} equipment")->total_count();
        }

        return $all;
    }

    static function getSummaryInfo($criteria = []) {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
        $dimension = $criteria['dimension'];

        $out_status = EQ_Status_Model::OUT_OF_SERVICE;
        $no_status = EQ_Status_Model::NO_LONGER_IN_SERVICE;
        $pre = $tag->id ? "{$tag}" : '';
        $summary = [];
        $summary['totalCount'] = Q("{$pre} equipment")->total_count();
        $summary['controlCount'] = Q("{$pre} equipment[control_mode]")->total_count();
        $summary['outServiceCount'] = Q("{$pre} equipment[status={$out_status}]")->total_count();
        $summary['usingCount'] = Q("{$pre} equipment[control_mode][is_using=1]")->total_count();
        $summary['unUsingCount'] = Q("{$pre} equipment[control_mode][is_using=0]")->total_count();
        $summary['noLongInCount'] = Q("{$pre} equipment[status={$no_status}]")->total_count();
        $summary['totalPrice'] = Q("{$pre} equipment")->SUM('price');

        $pre = $tag->id ? (
            $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user"
        ) : '';
        $now = Date::time();
        $time = Q("{$pre} eq_record[dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtend') 
            - Q("{$pre} equipment eq_record[dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtstart');
        
        $time = (int) ($time / (60 * 60));
        $summary['totalTime'] = $time;
        $summary['totalUse'] = Q("{$pre} eq_record")->total_count();
        return $summary;
    }

    static function getNewUsers ($start = 0, $num = 10, $vars=[]) {
        $time = (!$vars['time'] || !is_numeric($vars['time'])) ? Date::time() : $vars['time'];

        $num > 100 and $num = 100;

        $pre_selectors = [];

        if ($vars['group']) {
            $group = O('tag_group', (int)$vars['group']);
            if ($group->id) {
                $pre_selectors['group'] = "$group equipment";
            } 
        }

        $selector = "eq_record[dtstart<={$time}]:sort(dtstart DESC)";

        if (count($pre_selectors)) {
            $selector = '(' . join(', ', $pre_selectors) . ') ' . $selector;
        }

        $records = Q($selector)->limit($start, $num);

        foreach ($records as $record) {
            if ($GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$record project")->lab;
            }
            else {
                $lab = Q("{$record->user} lab")->current();
            }
            $result[] = [
                'name' => H($record->user->name ?: T('匿名用户')),
                'lab' => H($lab->name),
                'group' => H($record->user->group->name),
                'equipment_id' => H($record->equipment->id),
                'equipment' => H($record->equipment->name),
                'status' => $equipment->status,
                'is_using' => $equipment->is_using,
                'dtstart' => $record->dtstart,
                'dtend' => $record->dtend
            ];
        }
        return $result;
    }

    // 使用机时排行
    static function time_rank ($num = 10, $start = 0, $end = 0) {
        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(IF ( dtend > %end OR dtend = 0, %end, dtend ) - IF ( dtstart < %start, %start, dtstart ) ) as `sum`" .
        " FROM `equipment` as `e`" .
        " JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`" .
        " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end" .
        " GROUP BY `e`.`id`" .
        " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'time' => sprintf('%.2f', (float)($row->sum / 3600))
            ];
        }

        return $equipments;
    }
    static function getTopUsers($num=10, $start=0, $end = 0)
    {
        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `u`.`id`, `u`.`name`, SUM(LEAST(`re`.`dtend`, %dtend) - GREATEST(`re`.`dtstart`, %dtstart)) as `sum` " .
            " FROM `user` as `u`" .
            " JOIN `eq_record` as `re` ON `u`.`id` = `re`.`user_id` " .
            " WHERE `re`.`dtend` > 0 AND `re`.`dtstart` >= %dtstart " .
            " AND `re`.`dtend` <= %dtend GROUP BY `re`.`user_id` " .
            " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%dtstart' => $start,
            '%dtend' => $end,
            '%num' => (int)$num
        ]))->rows();

        $users = [];

        foreach ($rows as $row) {
            $user = O('user', $row->id);
            $users[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'group' => $user->group->name,
                'time' => (int)($row->sum / 3600)
            ];
        }

        return $users;
    }

    // 送样服务机时排行
    static function sample_rank ($num = 10, $start = 0, $end = 0) {
        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(IF ( dtend > %end OR dtend = 0, %end, dtend ) - IF ( dtstart < %start, %start, dtstart ) ) as `sum`" .
            " FROM `equipment` as `e`" .
            " JOIN `eq_sample` as `r` ON `e`.`id` = `r`.`equipment_id`" .
            " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end" .
            " GROUP BY `e`.`id`" .
            " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $equipment = O('equipment', $row->id);

            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'group' => $equipment->group->name,
                'time' => sprintf('%.2f', (float)($row->sum / 3600))
            ];
        }

        return $equipments;
    }

    // 使用次数排行
    static function count_rank ($num = 10, $start = 0, $end = 0) {
        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `e`.`id`, `e`.`name`, COUNT(`r`.`user_id`) as `cnt`" . 
        " FROM `equipment` as `e`" .
        " JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`" .
        " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end" .
        " GROUP BY `e`.`id`" .
        " ORDER BY `cnt` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'use' => (int)$row->cnt
            ];
        }

        return $equipments;
    }

    // 服务人数排行
    static function use_rank ($num = 10, $start = 0, $end = 0, $criteria = []) {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
		$dimension = $criteria['dimension'];
        $pre = $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user";

        $num > 100 and $num = 100;

        $db = Database::factory();

        $join = $tag->id ? (
            $dimension == 'equipment' 
            ? " JOIN `_r_tag_group_equipment` as `c` ON `e`.`id` = `c`.`id2` AND `c`.`id1` = {$tag->id} " 
            : " JOIN `_r_user_tag_group` as `c` ON `r`.`user_id` = `c`.`id1` AND `c`.`id2` = {$tag->id} " 
        ) : '';

        $SQL = "SELECT `id`, `name`, COUNT(`user_id`) AS `cnt` FROM (
            SELECT DISTINCT `e`.`id`, `e`.`name`, `r`.`user_id`
            FROM `equipment` as `e`
            JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`"
            . $join .
            "WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end
        ) AS `T1`
        GROUP BY `id`
        ORDER BY `cnt` DESC LIMIT 0, %num";
        
        $result = $db->query(strtr($SQL,  [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]));
        $rows = $result ? $result->rows() : [];

        $equipments = [];

        foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'use' => (int)$row->cnt,
            ];
        }

        return $equipments;
    }
    
    //考虑模块依赖？？？
    static function achieve_rank ($num = 10, $start = 0, $end = 0) {
        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `id`, `name`, SUM(`count`) AS `count` FROM (
        SELECT * FROM
        (SELECT `e`.`id`, `e`.`name`, COUNT(`r`.`id1`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_equipment_award` AS `r` ON `e`.`id` = `r`.`id1`
        JOIN `award` AS `a` ON `a`.`id` = `r`.`id2`
        WHERE `a`.`date` BETWEEN %start AND %end
        GROUP BY `e`.`id`
        ORDER BY `count` DESC LIMIT 0, 10) AS T1
        UNION
        SELECT * FROM (SELECT `e`.`id`, `e`.`name`, COUNT(`r`.`id2`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_patent_equipment` AS `r` ON `e`.`id` = `r`.`id2`
        JOIN `patent` AS `p` ON `p`.`id` = `r`.`id1`
        WHERE `p`.`date` BETWEEN %start AND %end
        GROUP BY `e`.`id`
        ORDER BY `count` DESC LIMIT 0, 10) AS T2
        UNION
        SELECT * FROM (SELECT `e`.`id`, `e`.`name`, COUNT(`r`.`id2`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_publication_equipment` AS `r` ON `e`.`id` = `r`.`id2`
        JOIN `publication` AS `p` ON `p`.`id` = `r`.`id1`
        WHERE `p`.`date` BETWEEN %start AND %end
        GROUP BY `e`.`id`
        ORDER BY `count` DESC LIMIT 0, 10) AS T3
        ) AS T GROUP BY `id`";

        $result = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]));
        $rows = $result ? $result->rows() : [];

        $equipments = [];

        if ($rows) foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'count' => (int)$row->count
            ];
        }

        return $equipments;
    }
    
    static function rank_total ($start = 0, $end = 0) {
        $db = Database::factory();
        
        $SQL = "SELECT SUM(`r`.`dtend` - `r`.`dtstart`) 
        FROM `equipment` as `e`
        JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`
        WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end";
        
        $equipment['time'] = sprintf('%.2f', (float)($db->value(strtr($SQL, ['%start' => (int)$start, '%end' => (int)$end])) / 3600));
        
        $SQL = "SELECT COUNT(`r`.`user_id`) 
        FROM `equipment` as `e`
        JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`
        WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end";
        
        $equipment['use'] = $db->value(strtr($SQL, ['%start' => (int)$start, '%end' => (int)$end]));
        
        $SQL = "SELECT SUM(`count`) AS `count` FROM (
        SELECT * FROM (SELECT COUNT(`r`.`id1`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_equipment_award` AS `r` ON `e`.`id` = `r`.`id1`
        JOIN `award` AS `a` ON `a`.`id` = `r`.`id2`
        WHERE `a`.`date` BETWEEN %start AND %end) AS T1
        UNION
        SELECT * FROM (SELECT COUNT(`r`.`id2`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_patent_equipment` AS `r` ON `e`.`id` = `r`.`id2`
        JOIN `patent` AS `p` ON `p`.`id` = `r`.`id1`
        WHERE `p`.`date` BETWEEN %start AND %end) AS T2
        UNION
        SELECT * FROM (SELECT COUNT(`r`.`id2`) AS `count`
        FROM `equipment` AS `e`
        JOIN `_r_publication_equipment` AS `r` ON `e`.`id` = `r`.`id2`
        JOIN `publication` AS `p` ON `p`.`id` = `r`.`id1`
        WHERE `p`.`date` BETWEEN %start AND %end) AS T3
        ) AS T";

        $equipment['achieve'] = (int)$db->value(strtr($SQL, ['%start' => (int)$start, '%end' => (int)$end]));
        
        return $equipment;
    }

    static function getStat ($id = 0) {
        $eq = O("equipment", $id);
        if (!$eq->id) return;

        $records = Q("{$eq} eq_record[dtend>0]");
        $ret = [
            'users' => Q("{$eq} eq_record[dtend>0] user")->total_count(),
            'nums' => $records->total_count(),
            'time' => $records->SUM('dtend') - $records->SUM('dtstart'),
        ];
        return $ret;
    }

    static function getStats ($ids = 0){
        $eqs = Q("equipment[id={$ids}]");
        $users=0;
        $nums=0;
        $time=0;
        foreach ($eqs as $eq){
            $records = Q("{$eq} eq_record[dtend>0]");
            $users += Q("{$eq} eq_record[dtend>0] user")->total_count();
            $nums += $records->total_count();
            $time += $records->SUM('dtend') - $records->SUM('dtstart');
        }

        $ret = [
            'users' => $users,
            'nums' => $nums,
            'time' => $time,
        ];

        return $ret;
    }

    // 二级组织机构的仪器数量(前十条)
    public static function getGroupEquipmentsCount($group_name = '', $number = 10)
    {
        $root = Tag_Model::root('group');
        $school = O('tag_group', ['parent' => $root, 'root' => $root, 'name' => H($group_name)]);
        $tags = Q("tag_group[parent={$school}][root={$root}]");
        
        $groups = [];
        $num = [];
        foreach ($tags as $tag) {
            $total_count = Q("{$tag} equipment")->total_count(); 
            if (!$total_count) continue;
            $group = [];
            $group['code'] =  $tag->code;
            $group['name'] =  $tag->name;
            $num[] = $group['equipmentsCount'] = $total_count;
            $groups[] = $group;
        }

        array_multisort($num, SORT_DESC, SORT_NUMERIC, $groups);
        $groups = array_slice($groups, 0, $number);

        return $groups;
    }

    function getEquipmentTagGroups($criteria = []) {
        $this->_ready();
        $data = [];
        $group_root = Tag_Model::root('group');
		$parent_group_name = H($criteria['parent_group_name']);

        if ($parent_group_name) {
            $parent_group = O('tag_group', ['root' => $group_root, 'name' => $parent_group_name]);
        }

        $parent_group = $parent_group->id ? $parent_group : $group_root;

        $SQL = "tag_group[parent={$parent_group}]";

        if (isset($criteria['order_by'])) {
            $order_by = H($criteria['order_by']);
            $desc = H($criteria['order_by_desc'] ?: 'A');
            $SQL .= ":sort({$order_by} {$desc})";
        }

        $groups = Q($SQL);

        foreach ($groups as $child) {
            $next_child = self::get_next_child($child, true);
            $data[$child->id] = [
                'name' => H($child->name),
                'children' => $next_child,
                'eq_count' => Q("{$child} equipment")->total_count()
            ];
        }
        return $data;
	}


    /**
     * 获取仪器入网总数、注册人员总数、注册课题组总数、累计服务机时、累计服务人次
     */
    static function getSummary()
    {
        // $this->_ready();
        $db = Database::factory();

        // 人员总数
        $user = Q('user')->total_count();

        // 入网仪器总数
        $equipment = Q('equipment')->total_count();

        // 注册课题组总数
        $lab = Q('lab')->total_count();
        
        // 累计服务机时
        $sql = 'select sum(dtend - dtstart)/3600 as dur from eq_record where dtend > dtstart and flag != 2';
        $service_dur = $db->value($sql);

        // 服务人次
        $service_user = Q('eq_reserv')->total_count() + Q('eq_sample')->total_count();

        // 培训人次
        $status = UE_Training_Model::STATUS_APPROVED;
        $training_user = Q("ue_training[status={$status}]")->total_count();

        return [
            'user' => $user,
            'equipment' => $equipment,
            'lab' => $lab,
            'service_dur' => round($service_dur, 2),
            'service_user' => $service_user,
            'training_user' => $training_user,
        ];
    }

    // 使用机时排行，默认上一自然年
    // 返回仪器名称及仪器所属组织机构
    static function getUsageRank ($num = 10, $start = 0, $end = 0)
    {
        // $this->_ready();
        $num > 100 and $num = 100;
        $start = $start ?: strtotime(date("Y-01-01 00:00:00",strtotime("-1 year")));
        $end = $end ?: strtotime(date("Y-12-31 23:59:59",strtotime("-1 year")));

        $db = Database::factory();

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(IF ( dtend > %end OR dtend = 0, %end, dtend ) - IF ( dtstart < %start, %start, dtstart ) ) as `sum`" .
        " FROM `equipment` as `e`" .
        " JOIN `eq_record` as `r` ON `e`.`id` = `r`.`equipment_id`" .
        " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `r`.`flag` != 2" .
        " GROUP BY `e`.`id`" .
        " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $equipment = O('equipment', $row->id);

            $equipments[] = [
                'name' => H($row->name),
                'group' => $equipment->group->name,
                'usage' => round($row->sum / 3600, 2),
            ];
        }

        return $equipments;
    }

    // 人员使用机时排行，默认上一自然年
    static function getUserRank($num=10, $start=0, $end = 0)
    {
        // $this->_ready();
        $num > 100 and $num = 100;
        $start = $start ?: strtotime(date("Y-01-01 00:00:00",strtotime("-1 year")));
        $end = $end ?: strtotime(date("Y-12-31 23:59:59",strtotime("-1 year")));

        $db = Database::factory();

        $SQL = "SELECT `u`.`id`, `u`.`name`, SUM(re.dtend - re.dtstart) as `sum` " .
        " FROM `user` as `u`" .
        " JOIN `eq_record` as `re` ON `u`.`id` = `re`.`user_id` " .
        " WHERE `re`.`dtend` > 0 AND `re`.`dtstart` >= %dtstart " .
        " AND `re`.`dtend` <= %dtend AND `re`.`flag` != 2 " .
        " AND `u`.`id` NOT IN (SELECT DISTINCT id1 FROM _r_user_equipment WHERE `type` = 'incharge') " .
        " GROUP BY `re`.`user_id` " .
        " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
                '%dtstart' => $start,
                '%dtend' => $end,
                '%num' => (int)$num
            ]))->rows();

        $users = [];

        foreach ($rows as $row) {
            $user = O('user', $row->id);
            $lab = Q("{$user} lab")->current();
            $users[] = [
                'name' => H($row->name),
                'lab' => $lab->id ? $lab->name : '',
                'group' => $user->group->name,
                'usage' => round($row->sum / 3600, 2),
            ];
        }

        return $users;
    }

    // 送样服务机时排行，默认上一自然年
    static function getSampleRank ($num = 10, $start = 0, $end = 0)
    {
        // $this->_ready();
        $num > 100 and $num = 100;
        $start = $start ?: strtotime(date("Y-01-01 00:00:00",strtotime("-1 year")));
        $end = $end ?: strtotime(date("Y-12-31 23:59:59",strtotime("-1 year")));

        $db = Database::factory();

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(IF ( dtend > %end OR dtend = 0, %end, dtend ) - IF ( dtstart < %start, %start, dtstart ) ) as `sum`" .
        " FROM `equipment` as `e`" .
        " JOIN `eq_sample` as `r` ON `e`.`id` = `r`.`equipment_id`" .
        " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end" .
        " GROUP BY `e`.`id`" .
        " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int)$num,
            '%start' => (int)$start,
            '%end' => (int)$end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $equipment = O('equipment', $row->id);

            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'group' => $equipment->group->name,
                'sample' => round($row->sum / 3600, 2),
                'time' => sprintf('%.2f', (float)($row->sum / 3600))
            ];
        }

        return $equipments;
    }

    // 预约排行前十院级单位仪器使用情况，默认上一自然年
    static function getTopGroupReserv($num = 10, $start = 0, $end = 0)
    {
        // $this->_ready();
        $rtn = [];
        $num > 100 and $num = 100;
        $start = $start ?: strtotime(date("Y-01-01 00:00:00",strtotime("-1 year")));
        $end = $end ?: strtotime(date("Y-12-31 23:59:59",strtotime("-1 year")));

        $db = Database::factory();

        $sql = "SELECT tag_group.name, equipment.group_id, sum(eq_reserv.dtend - eq_reserv.dtstart) AS dur
                FROM eq_reserv
                        LEFT JOIN equipment ON eq_reserv.equipment_id = equipment.id
                        LEFT JOIN tag_group ON tag_group.id = equipment.group_id
                WHERE eq_reserv.dtend >= {$start}
                        AND eq_reserv.dtend <= {$end}
                        AND eq_reserv.dtend > eq_reserv.dtstart
                        AND equipment.group_id != 0
                        AND tag_group.name != ''
                GROUP BY equipment.group_id
                ORDER BY dur DESC
                LIMIT {$num}";

        $res = $db->query($sql);
        if ($res) {
            $rows = $res->rows();
            foreach($rows as $row) {
                $sql = "SELECT sum(eq_record.dtend - eq_record.dtstart) AS dur
                        FROM eq_record
                        WHERE eq_record.dtend >= {$start}
                                AND eq_record.dtend <= {$end}
                                AND eq_record.flag != 2
                                AND equipment_id IN (SELECT id FROM equipment WHERE group_id = {$row->group_id})";
                $usage_dur = $db->value($sql);

                $rtn[] = [
                    'group' => $row->name,
                    'reserv' => round($row->dur / 3600, 2),
                    'usage' => round($usage_dur / 3600, 2)
                ];
            }
        }

        return $rtn;
    }

    // 使用排行前十院级单位仪器使用情况，默认上一自然年
    static function getTopGroupUsage($num = 10, $start = 0, $end = 0)
    {
        // $this->_ready();
        $rtn = [];
        $num > 100 and $num = 100;
        $start = $start ?: strtotime(date("Y-01-01 00:00:00",strtotime("-1 year")));
        $end = $end ?: strtotime(date("Y-12-31 23:59:59",strtotime("-1 year")));

        $db = Database::factory();

        $sql = "SELECT tag_group.name, equipment.group_id, sum(eq_record.dtend - eq_record.dtstart) AS dur
                FROM eq_record
                        LEFT JOIN equipment ON eq_record.equipment_id = equipment.id
                        LEFT JOIN tag_group ON tag_group.id = equipment.group_id
                WHERE eq_record.dtend >= {$start}
                        AND eq_record.dtend <= {$end}
                        AND eq_record.dtend > eq_record.dtstart
                        AND eq_record.flag != 2
                        AND equipment.group_id != 0
                        AND tag_group.name != ''
                GROUP BY equipment.group_id
                ORDER BY dur DESC
                LIMIT {$num}";

        $res = $db->query($sql);
        if ($res) {
            $rows = $res->rows();

            foreach($rows as $row) {
                $rtn[] = [
                    'group' => $row->name,
                    'usage' => round($row->dur / 3600, 2),
                ];
            }
        }

        return $rtn;
    }

}

