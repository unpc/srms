<?php

class Index_Controller extends Base_Controller {
	
	function index() {
        $me = L('ME');
        if (!$me->is_allowed_to('列表', 'door')) {
            URI::redirect('error/401');
        }
        $pre_selectors = [];
        $selector = 'door';
        if (!$me->access('管理所有门禁')) {
            $selector = "{$me}<incharge ". $selector;
        }

		//多栏搜索
		$form = Lab::form();		
		

		if($form['name']) {
			$name = Q::quote(trim($form['name']));
			$selector .= "[name*=$name]";
		}
		
		if($form['location1']) {
			$location1 = Q::quote($form['location1']);
			$selector .= "[location1*=$location1]";
		}
		
		if($form['location2']) {
			$location2 = Q::quote(trim($form['location2']));
			$selector .= "[location2*=$location2]";
		}

        if($form['type']) {
			$selector .= "[type*=" . $form['type'] . "]";
		}

        if ($form['incharge']) {
            $incharge = Q::quote(trim($form['incharge']));
            $pre_selectors['incharge'] = "user<incharge[name*=$incharge|name_abbr*=$incharge]";
        }

        $location_root = Tag_Model::root('location');
        if ($form['location_id'] && $form['location_id'] != $location_root->id) {
            $location = o("tag_location", $form['location_id']);
            $pre_selectors['location'] = "$location";
        }
		
		//排序
		$sort_by = $form['sort'] ?: 'name';
		$sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'D':'A';

        //$selector = Event::trigger('entrance.door.extra_selector', $selector, $form) ? : $selector;
		
        if (count($pre_selectors)) {
            $selector = '(' . implode(', ', (array)$pre_selectors) . ') ' . $selector;
        }

		$selector .= ":sort({$sort_by} {$sort_flag} ,id D)";
		$doors = Q($selector);
		
        $per_page = Config::get('per_page.door', 25);
		$pagination = Lab::pagination($doors, (int)$form['st'], $per_page);

        $panel_buttons = new ArrayIterator;

        if ( $me->is_allowed_to('添加', 'door') ) {
            if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('door')) {
                $panel_buttons[] = [
                    'url' => "{$master['host']}!entrance/add?oauth-sso=db_sync.".LAB_ID,
                    'text' => I18N::T('entrance', '添加门禁'),
                    'tip' => I18N::T('entrance', '添加门禁'),
                    'extra' => 'class="button button_add" q-object="add" q-event="click" q-src="'.URI::url('!entrance/index').'" '
                ];
            } else {
                $panel_buttons[] = [
                    'url' => URI::url('!entrance/index/add'),
                    'text' => I18N::T('entrance', '添加门禁'),
                    'tip' => I18N::T('entrance', '添加门禁'),
                    'extra' => 'class="button button_add blue"'
                ];
            }
        }

        $columns = self::get_door_filed($form);
        $search_box = V('application:search_box', ['panel_buttons' => $panel_buttons,'top_input_arr'=>['name'], 'columns' => $columns]);
		
		$content = V('index',[
					   'doors'=>$doors,
					   'pagination'=>$pagination,
					   'form'=>$form,
					   'sort_by'=>$sort_by,
					   'sort_asc'=>$sort_asc,
                       'search_box'=>$search_box,
                       'columns'=>$columns
					]);
		$this->layout->body->primary_tabs
							->select('index')
							->set('content',$content);
		$this->add_css('entrance:common');
	}

    public function add($tab = 'info')
    {
        $me = L('ME');

        if (!L('ME')->is_allowed_to('添加', 'door')) {
            URI::redirect('error/401');
        }

        Event::bind('door.add.content', [$this, '_add_info'], '0', 'info');

        $this->layout->body->primary_tabs = Widget::factory('tabs')
            ->add_tab('info', [
                'url'   => URI::url('!entrance/index/add'),
                'title' => I18N::T('entrance', '添加门禁'),
            ])
            ->tab_event('door.add.tab')
            ->content_event('door.add.content')
            ->select($tab);

        $this->add_css('entrance:calendar');

        $this->layout->title = H($door->name);
        $breadcrumbs = [
            [
                'url' => '!entrance/index',
                'title' => I18N::T('entrance', '门禁列表'),
            ],
            [
                'title' => '添加门禁',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

    }

    public function _add_info($e, $tabs)
    {
        $me   = L('ME');

        if (Input::form('submit')) {
            $door = O('door');

            $location_root = Tag_Model::root('location');

            $form = Form::filter(Input::form());

            $form
                ->validate('name', 'not_empty', I18N::T('entrance', '名称不能为空'));
            $requires = Config::get('form.entrance')['requires'];
            if (in_array('location', $requires)) {
                $form
                ->validate('location', 'not_empty', I18N::T('entrance', '地理位置不能为空'))
                ->validate('location', $form['location'] != '{}', I18N::T('entrance', '地理位置不能为空'));
            }
            switch ($form['type']) {
                case Door_Model::type('genee'):
                    $form
                        ->validate('in_addr', 'not_empty', I18N::T('entrance', '进门地址不能为空'))
                        ->validate('lock_id', 'not_empty', I18N::T('entrance', '门锁ID不能为空'))
                        ->validate('detector_id', 'not_empty', I18N::T('entrance', '门磁ID不能为空'));
                    break;
                case Door_Model::type('mp'):
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '关联门禁不可为空'));
                    $device = O('door_device', ['uuid' => $form['remote_device']]);
                    if ($device->id && Q("{$device}<remote_device door")->total_count()) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    break;
                case Door_Model::type('mpv2'):
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '请从门禁列表中选择关联门禁'));
                    if (Q("door[remote_device_id=" . $form['remote_device'] . "]")->total_count()
                        || Q("door[remote_device2_id=" . $form['remote_device'] . "]")->total_count()
                    ) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    if ($form['remote_device2']) {
                        if (Q("door[remote_device_id=" . $form['remote_device2'] . "]")->total_count()
                            || Q("door[remote_device2_id=" . $form['remote_device2'] . "]")->total_count()
                        ) {
                            $form->set_error('remote_device2', I18N::T('entrance', '关联门禁设备2不可重复'));
                        }
                    }
                    break;
                default:
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '请从门禁列表中选择关联门禁'));
                    if (Q("door[remote_device_id=".$form['remote_device']."]")->total_count()) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    break;
            }

            if ($form->no_error) {
                $door->name      = $form['name'];
                if($form['type'] == Door_Model::type('genee')) $door->in_addr = $form['in_addr'];
				else $door->in_addr = '';

                $is_single = true;
                if (!$is_single) {
                    $door->out_addr = $form['out_addr'];
                } else {
                    $door->out_addr = '';
                }
                $door->is_single_direction = $is_single;
                $door->lock_id             = $form['lock_id'];
                $door->detector_id         = $form['detector_id'];
                $door->type                = $form['type'];
                switch ($form['type']) {
                    case Door_Model::type('mp'):
                        $device = O('door_device', ['uuid' => $form['remote_device']]);
                        if (!$device->id) {
                            $device->uuid = $form['remote_device'];
                            $device->save();
                        }
                        $door->remote_device = $device;
                        break;
                    case Door_Model::type('mpv2'):
                        $door->remote_device = $form['remote_device']?o('door_device', $form['remote_device']):0;
                        $door->remote_device2 = $form['remote_device2']?o('door_device', $form['remote_device2']):0;
                        break;
                    default:
                        $door->remote_device = $form['remote_device']?o('door_device', $form['remote_device']):0;
                        break;
                }
                if ($door->save()) {

                    foreach ((array) @json_decode($form['incharges'], true) as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }
                        $door->connect($user, 'incharge');
                    }
                    $tags = @json_decode($form['location'], true);
                    if (count($tags)) {
                        Tag_Model::replace_tags($door, $tags, 'location');
                    } else {
                        $location_root = Tag_Model::root('location');
                        $tags = Q("$door tag_location[root=$location_root]");
                        foreach ($tags as $t) {
                            $t->disconnect($door);
                        }
                    }

                    $type = explode(':', $door->device['uuid'])[0];
                    if ($type == 'cacs' || $type == 'icco') {
                        // 由于 icco-server 式的门禁 is_monitoring 可能管理得不及时,
                        // 而 Device_Agent 中是会对是否 connect 做判断的, 比较严谨.
                        // 所以在此去除 is_monitoring 的判断
                        $agent = new Device_Agent($door, false, 'out');
                        $agent->call('sync');
                    }
                    /* 记录日志 */
                    Log::add(strtr('[entrance] %user_name[%user_id] 修改 门禁%door_name[%door_id]的基本设置', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%door_name' => $door->name,
                        '%door_id'   => $door->id,
                    ]), 'journal');
                    if (!$me->is_allowed_to('修改', $door)) {
                        URI::redirect($door->url());
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁信息更新成功!'));
                    URI::redirect($door->url('rule', null, null, 'edit'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁信息更新失败!'));
                }
            }
        }

        $tabs->content = V('door/add.info', ['form' => $form]);

    }


	function get_door_filed($form){
        $location_root = Tag_Model::root('location');
        if ($form['location_id']) {
            $location = o("tag_location", $form['location_id']);
        }
        $columns = [
            'name'=>[
                'title'=>I18N::T('entrance', '门禁名称'),
                'align'=>'left',
                'sortable'=>TRUE,
                'filter' => [
                    'form' => V('entrance:doors_table/filters/name', ['name'=>$form['name']]),
                    'value' => $form['name'] ? H($form['name']) : NULL
                ],
                'nowrap'=>TRUE
            ],
            'location'=>[
                'title'=>I18N::T('entrance', '地理位置'),
                'filter' => [
                    'form' => V('entrance:doors_table/filters/location', [
                        'name' => 'location_id',
                        'tag' => $location,
                        'root' => $location_root,
                        'field_title' => I18N::T('people', '请选择地理位置'),
                    ]),
                    'value' => $location->id ? H($location->name): NULL,
                    'field' => 'location_id',
                ],
                'nowrap'=>TRUE
            ],
            'type'=>[
                'title'=>I18N::T('entrance', '门禁类型'),
                'align'=>'left',
                'filter' => [
                    'form'  => V('entrance:doors_table/filters/type', ['form' => $form]),
                    'value' => I18N::T('entrance', $form['type']),
                ],
                'nowrap'=>TRUE
            ],
            'incharge'=>[
                'title'=>I18N::T('entrance', '负责人'),
                'align'=>'left',
                'filter' => [
                    'form' => V('entrance:doors_table/filters/incharge', ['name'=>'incharge', 'value'=>$form['incharge']]),
                    'value' => $form['incharge'] ? H($form['incharge']) : NULL
                ],
                'nowrap'=>TRUE
            ],
            'rest'=>[
                'title'=>'操作',
                'align'=>'left',
                'nowrap'=>TRUE,
            ],
        ];
        return $columns;
    }
	
}

class Index_AJAX_Controller extends AJAX_Controller{
    function index_add_click() {
        /*
        NO.TASK#274(guoping.zhang@2010.11.27)
        应用权限设置新规则
        */
        if (!L('ME')->is_allowed_to('添加', 'door')) {
            URI::redirect('error/401');
        }

        $door = O('door');

        $form = Input::form();

        $view = V('add', [
            'form'       => $form,
            'door' => $door,
        ]);


        JS::dialog($view, ['title' => I18N::T('entrance', '添加门禁')]);

    }

    function index_add_submit() {
        /*
        NO.TASK#274(guoping.zhang@2010.11.27)
        应用权限设置新规则
        */
        if (!L('ME')->is_allowed_to('添加', 'door')) {
            URI::redirect('error/401');
        }
        
        $door = O('door');
        if(Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('location1', 'not_empty', I18N::T('entrance', '地址不能为空'))
                ->validate('location2', 'not_empty', I18N::T('entrance', '房间号不能为空'))
                ->validate('name', 'not_empty', I18N::T('entrance', '名称不能为空'))
                ->validate('in_addr', 'not_empty', I18N::T('entrance', '进门地址不能为空'))
                ->validate('lock_id', 'not_empty', I18N::T('entrance', '门锁ID不能为空'))
                ->validate('detector_id', 'not_empty', I18N::T('entrance', '门磁ID不能为空'));
            Event::trigger('door[edit].post_submit_validate', $form);

            $is_single = TRUE;
            
            // 不知道这里为什么check_box不勾的时候传的值是string null，先暂时这样处理
            if ($form['single_direction'] && $form['single_direction'] != 'null' ) {
                $is_single = FALSE;
                $form->validate('out_addr', 'not_empty', I18N::T('entrance', '出门地址不能为空'));
            }

            if($form->no_error) {
                $door->location1 = $form['location1'];
                $door->location2 = $form['location2'];
                $door->name = $form['name'];
                $door->in_addr = $form['in_addr'];
                if (!$is_single) {
                    $door->out_addr = $form['out_addr'];
                }
                else {
                    $door->out_addr = '';
                }
                $door->is_single_direction = $is_single;
                $door->lock_id = $form['lock_id'];
                $door->detector_id = $form['detector_id'];
                if($door->save()) {
                    foreach ((array)@json_decode($form['incharges'], TRUE) as $id=>$name) {
                        $user = O('user', $id);
                        if (!$user->id) continue;
                        $door->connect($user, 'incharge');
                    }
                    /* 记录日志 */
                    Log::add(strtr('[entrance] %user_name[%user_id] 添加 门禁:%door_name[%door_id]', [
                        '%user_name' =>  L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%door_name' => $door->name,
                        '%door_id' => $door->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL,I18N::T('entrance','门禁信息更新成功!'));
                    Js::redirect('!entrance/door/edit.' . $door->id);
//                    URI::redirect('!entrance/door/edit.'.$door->id);
                }
            }
        }

        $view = V('add', [
            'form'       => $form,
            'door' => $door,
        ]);


        JS::dialog($view, ['title' => I18N::T('entrance', '添加门禁')]);
        
    }
}
