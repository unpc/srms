<?php

class Index_Controller extends Base_Controller
{

    // 列出所有仪器
    public function index($tab = 'normal', $sort = 'control')
    {
        $me = L('ME');

        $type = strtolower(Input::form('type'));

        $export_types = ['print', 'csv'];

        $form_token = Input::form('form_token');

        if (in_array($type, $export_types)) {
            $old_form = (array)$_SESSION[$form_token];

            if (!count($old_form)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
                URI::redirect('!equipments/index');
            }

            $new_form = (array)Input::form();

            if ($new_form['submit']) {
                unset($old_form['columns']);
            }

            $form = $_SESSION[$form_token] = $new_form + $old_form;

            $equipments = Q($form['selector']);

            call_user_func([$this, '_export_' . $type], $equipments, $form);
        } else {
            if (!$me->is_allowed_to('列表', 'equipment')) {
                URI::url('error/404');
            }

            $form_token = Session::temp_token('equipment_list_', 300); //生成唯一一个SESSION的key

            //多栏搜索
            $form = Lab::form(function (&$old_form, &$form) {
                if (!isset($old_form['group_id']) && !isset($form['group_id']) && !Session::get_url_specific('default_search_set')) {
                    if ($_SESSION['eqlist_default_group_id']) {
                        $group = O('tag_group', $_SESSION['eqlist_default_group_id']);
                    }
                    // 使用默认的机构筛选
                    $me = L('ME');
                    $form['group_id'] = $me->default_group('equipment');
                    Session::set_url_specific('default_search_set', true);
                }

            });
            $form = new ArrayIterator($form);
            Event::trigger('extra_form_value', $form);

            //GROUP搜索
            $pre_selectors = new ArrayIterator;
            $group = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');

            if ($group->id && $group->root->id == $group_root->id) {
                $pre_selectors['group'] = "$group";
            } else {
                $group = null;
            }

            $location = O('tag_location', $form['location_id']);
            $location_root = Tag_Model::root('location');

            if ($location->id && $location->root->id == $location_root->id) {
                $pre_selectors['location'] = "{$location}";
            } else {
                $location = null;
            }

            $tag_equipment = O('tag_equipment', $form['tag_equipment_id']);
            $tag_equipment_root = Tag_Model::root('equipment');

            if ($tag_equipment->id && $tag_equipment->root->id == $tag_equipment_root->id) {
                $pre_selectors['tag_equipment'] = "{$tag_equipment}";
            } else {
                $tag_equipment = null;
            }


            $tag_equipment_technical = O('tag_equipment_technical', $form['tag_equipment_technical_id']);
            $tag_equipment_technical_root = Tag_Model::root('equipment_technical');

            if ($tag_equipment_technical->id && $tag_equipment_technical->root->id == $tag_equipment_technical_root->id) {
                $pre_selectors['tag_equipment_technical'] = "{$tag_equipment_technical}";
            } else {
                $tag_equipment_technical = null;
            }

            $tag_equipment_education = O('tag_equipment_education', $form['tag_equipment_education_id']);
            $tag_equipment_education_root = Tag_Model::root('equipment_education');

            if ($tag_equipment_education->id && $tag_equipment_education->root->id == $tag_equipment_education_root->id) {
                $pre_selectors['tag_equipment_education'] = "{$tag_equipment_education}";
            } else {
                $tag_equipment_education = null;
            }

            $selector = '';
            if ($form['contact']) {
                $contact = Q::quote(trim($form['contact']));
                $pre_selectors['contact'] = "user<contact[name*=$contact|name_abbr*=$contact]";
            }

            if ($form['current_user']) {
                $now = time();
                $current_user = Q::quote($form['current_user']);
                $pre_selectors['current_user'] = "user[name*=$current_user|name_abbr*=$current_user] eq_record[dtend=0][dtstart<=$now]";
            }

            $selector .= 'equipment';

            if (!$me->access('管理所有内容')) {
                $selector .= "[!hidden]";
            }

            if ($form['name']) {
                if (Config::get('equipment.enable_name_fuzzy_search')) {
                    $selector = (string)Event::trigger('equipment.name_fuzzy_search.selector', $form, $selector) ?: $selector;
                } else {
                    $name = Q::quote(trim($form['name']));
                    $selector .= "[name*=$name|name_abbr*=$name]";
                }
            }


            if ($form['ref_no']) {
                $selector .= '[ref_no*=' . Q::quote(trim($form['ref_no'])) . ']';
            }

            if ($form['location']) {
                $location = Q::quote($form['location']);
                //获取value
                $locations = Equipment_Model::locations();
                $selector .= "[location={$locations[$location]}]";
            }
            if ($form['control_mode']) {
                if ($form['control_mode'] == 'nocontrol') {
                    $selector .= '[!control_mode]';
                } else {
                    $selector .= '[control_mode=' . Q::quote($form['control_mode']) . ']';
                }
            }

            if ($form['control_mode'] != 'nocontrol' && $form['control_status']) {
                if ($form['control_status'] == 'available') {
                    $selector .= '[!is_using]';
                } else {
                    $selector .= '[is_using]';
                }
            }


            /*if ($form['domain']) {
            $domain = join(', ', json_decode($form['domain'], true));
            $selector .= "[domain*=$domain]";
            }*/

            if ($form['share_method']) {
                switch ($form['share_method']) {
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


            if ($form['atime_s']) {
                $atime_s = Q::quote($form['atime_s']);
                $selector .= "[atime>=$atime_s]";
            }

            if ($form['atime_e']) {
                $atime_e = Q::quote(Date::get_day_end($form['atime_e']));
                $selector .= "[atime<=$atime_e]";
            }

            if (Config::get('equipment.enable_share') &&
                (
                    (
                        //设置了默认只显示共享的仪器，但是用户具有管理所有内容
                        Config::get('equipment.enable_show_list_share') &&
                        $me->access('管理所有内容')
                    )
                    ||
                    (
                    !Config::get('equipment.enable_show_list_share')
                    )
                )
            ) {
                if (isset($form['share']) && $form['share'] >= 0) {
                    $share = (int)$form['share'];
                    if (Config::get('equipment.enable_show_list_share')) {
                        $selector .= "[cers_share={$share}]";
                    } else {
                        $selector .= "[share={$share}]";
                    }
                }
            }

            //仪器价格
            $form['price_s'] = floatval($form['price_s']);
            $form['price_e'] = floatval($form['price_e']);
            if ($form['price_s']) {
                $price_s = Q::quote($form['price_s']);
                $selector .= "[price>=$price_s]";
            }
            if ($form['price_e']) {
                $price_e = Q::quote($form['price_e']);
                $selector .= "[price<=$price_e]";
            }

            $cache = Cache::factory();

            $this->layout->body->primary_tabs = Widget::factory('tabs');
            if (Config::get('equipment.total_count') && $cache->get('equipment_count')) {
                $this->layout->body->primary_tabs
                    ->add_tab('normal', [
                        'url' => URI::url("!equipments/index.normal"),
                        'title' => I18N::T('equipments', '正常设备') . ' [' . $cache->get('equipment_count')[EQ_Status_Model::IN_SERVICE] . ']',
                    ])
                    ->add_tab('broken', [
                        'url' => URI::url("!equipments/index.broken"),
                        'title' => I18N::T('equipments', '故障设备') . ' [' . $cache->get('equipment_count')[EQ_Status_Model::OUT_OF_SERVICE] . ']',
                    ])
                    ->add_tab('scrapped', [
                        'url' => URI::url("!equipments/index.scrapped"),
                        'title' => I18N::T('equipments', '废弃设备') . ' [' . $cache->get('equipment_count')[EQ_Status_Model::NO_LONGER_IN_SERVICE] . ']',
                    ]);
            } else {
                $this->layout->body->primary_tabs
                    ->add_tab('normal', [
                        'url' => URI::url("!equipments/index.normal"),
                        'title' => I18N::T('equipments', '正常设备'),
                    ])
                    ->add_tab('broken', [
                        'url' => URI::url("!equipments/index.broken"),
                        'title' => I18N::T('equipments', '故障设备'),
                    ])
                    ->add_tab('scrapped', [
                        'url' => URI::url("!equipments/index.scrapped"),
                        'title' => I18N::T('equipments', '废弃设备'),
                    ]);
            }

            switch ($tab) {
                case 'broken':
                    $arg = 'broken';
                    $this->layout->body->primary_tabs->select($tab);
                    $selector .= '[status=' . EQ_Status_Model::OUT_OF_SERVICE . ']';
                    break;
                case 'scrapped':
                    $arg = 'scrapped';
                    $this->layout->body->primary_tabs->select($tab);
                    $selector .= '[status=' . EQ_Status_Model::NO_LONGER_IN_SERVICE . ']';
                    break;
                default:
                    $arg = false;
                    $this->layout->body->primary_tabs->select($tab);
                    $selector .= '[status=' . EQ_Status_Model::IN_SERVICE . ']';
                    break;
            }

            // 创建一个Tag选择视图
            $tag_root = Tag_Model::root('equipment');

            if ($form['tag_id']) {
                $tag = O('tag_equipment', $form['tag_id']);
                if ($tag->id && $tag->root->id == $tag_root->id) {
                    $pre_selectors['tag'] = "$tag";
                } else {
                    $tag = null;
                }
            }

            $location_root = Tag_Model::root('location');

            if ($form['location_tag']) {
                $location_tag = O('tag_location', $form['location_tag']);
                if ($location_tag->id && $location_tag->root->id == $location_root->id) {
                    $pre_selectors['location_tag'] = "$location_tag";
                } else {
                    $location_tag = null;
                }
            }

            $pre_selectors = (string)Event::trigger('equipment.extra.pre_selector', $form, $pre_selectors) ?: $pre_selectors;

            //具有共享功能后
            $selector = (string)Event::trigger('equipment.extra.selector', $form, $selector, $pre_selectors) ?: $selector;
            if (count($pre_selectors)) {
                $selector = '(' . implode(', ', (array)$pre_selectors) . ') ' . $selector;
            }
            $sort_by = $form['sort'];
            $sort_asc = $form['sort_asc'];
            $selector = (string)Event::trigger('equipment.sort.selector', $form, $selector);

            $equipments = Q($selector);

            if (Config::get('equipment.total_count') && $cache->get('equipment_count')) {
                $all_count = $cache->get('equipment_count')['total'];
            }
            $total_count = $equipments->total_count();

            if ($this->layout->body->primary_tabs->selected == 'normal') {
                $using_count = $equipments->find('[is_using=1]')->total_count();
            }

            $form['form_token'] = $form_token;
            $form['selector'] = $selector;
            $_SESSION[$form_token] = $form;

            $start = (int)$form['st'];
            $per_page = 30;
            $start = $start - ($start % $per_page);

            $pagination = Lab::pagination($equipments, $start, $per_page);

            $panel_buttons = new ArrayIterator;

            if ($me->is_allowed_to('添加', 'equipment')) {
                $panel_buttons[] = [
                    // 'url' => URI::url('!equipments/add'),
                    'tip' => I18N::T('equipments', '添加仪器'),
                    'text' => I18N::T('equipments', '添加'),
                    'extra' => 'q-object="add" q-event="click" q-src="' . URI::url('!equipments/index') .
                        '" class="button button_add "',
                    // 'url' => Event::trigger('db_sync.transfer_to_master_url', '!equipments/add') ?: URI::url('!equipments/add'),
                ];
                $panel_buttons[] = Event::trigger('equipment.add.import.button');
            }

            if ($me->is_allowed_to('导出', 'equipment')) {
                $panel_buttons[] = [
                    //'url' => URI::url(),
                    'text' => I18N::T('equipments', '导出'),
                    'tip' => I18N::T('equipments', '导出Excel'),
                    'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!equipments/index') .
                        '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                        '" class="button button_save "',
                ];
                $panel_buttons[] = [
                    //'url' => URI::url(),
                    'text' => I18N::T('equipments', '打印'),
                    'tip' => I18N::T('equipments', '打印'),
                    'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!equipments/index') .
                        '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                        '" class="button button_print "',
                ];

            }

            if (Config::get('equipment.sort_reserv')) {
                if ($sort_by == 'reserv') {
                    $panel_buttons[] = [
                        'url' => URI::url('!equipments/index.' . $tab . '?sort=default'),
                        'text' => I18N::T('equipments', '按控制方式排序'),
                        'extra' => 'class="button button_refresh"',
                    ];
                } else {
                    $panel_buttons[] = [
                        'url' => URI::url('!equipments/index.' . $tab . '?sort=reserv'),
                        'text' => I18N::T('equipments', '按开放预约排序'),
                        'extra' => 'class="button button_refresh"',
                    ];
                }
            }

            //添加定制的panel_buttons
            $panel_buttons[] = Event::trigger('equipments.index.panel_buttons', $form_token);

            $this->add_css('preview');
            $this->add_js('preview');
            $primary_tabs = $this->layout->body->primary_tabs->select($tab);
            $primary_tabs->content =
                V('index', [
                    'tab' => $tab,
                    //'secondary_tabs' => $secondary_tabs,
                    'pagination' => $pagination,
                    'sort_by' => $sort_by,
                    'sort_asc' => $sort_asc,
                    'tag' => $tag,
                    'tag_root' => $tag_root,
                    'location_tag' => $location_tag,
                    'view_mode' => $form['view_mode'],
                    'form' => $form,
                    'st' => $start,
                    'eq_count' => [
                        'all_count' => $all_count,
                        'total_count' => $total_count,
                        'using_count' => $using_count,
                    ],
                    'group' => $group,
                    'group_root' => $group_root,
                    'location' => $location,
                    'location_root' => $location_root,
                    'equipments' => $equipments,
                    'panel_buttons' => $panel_buttons,
                    'tag_equipment' => $tag_equipment,
                    'tag_equipment_root' => $tag_equipment_root,
                    'tag_equipment_technical' => $tag_equipment_technical,
                    'tag_equipment_technical_root' => $tag_equipment_technical_root,
                    'tag_equipment_education' => $tag_equipment_education,
                    'tag_equipment_education_root' => $tag_equipment_education_root,
                ]);
        }
    }

// public function add()
// {
//     $me = L('ME');

//     if (!$me->is_allowed_to('添加', 'equipment')) { //管理仪器设备 => 添加仪器设备
//         URI::redirect('error/401');
//     }

//     if ($me->is_allowed_to('添加', 'equipment')) {
//         $this->layout->body->primary_tabs
//             ->add_tab('add', [
//                     'url'=>URI::url('!equipments/add'),
//                     'title'=>I18N::T('equipments', '添加仪器'),
//             ])
//             ->select('add');
//     }

//     $group_root = Tag_Model::root('group');
//     $equipment = O('equipment');

//     if (Input::form('submit')) {
//         $form = Form::filter(Input::form());
//         $form->validate('price', 'compare(>=0)', I18N::T('equipments', '仪器价格不能设置为负数!'));

//         $requires = Config::get('form.equipment_add')['requires'];

//         array_walk($requires, function ($v, $k) use ($form, $user, $group_root) {
//             switch ($k) {
//                 case 'name':
//                     $form->validate('name', 'not_empty', I18N::T('equipments', '请输入仪器名称!'));
//                     break;
//                 case 'price':
//                     $form->validate('price', 'compare(>0)', I18N::T('equipments', '请输入仪器价格!'));
//                     break;
//                 case 'ref_no':
//                     $form->validate('ref_no', 'not_empty', I18N::T('equipments', '请输入仪器编号!'));
//                     break;
//                 case 'incharges':
//                     $incharges = (array)@json_decode($form['incharges'], true);
//                     if (count($incharges) == 0) {
//                         $form->set_error('incharges', I18N::T('equipments', '请指定至少一名仪器负责人!'));
//                     }
//                     break;
//                 case 'contacts':
//                     $contacts = (array)@json_decode($form['contacts'], true);
//                     if (count($contacts) == 0) {
//                         $form->set_error('contacts', I18N::T('equipments', '请指定至少一名仪器联系人!'));
//                     }
//                     break;
//                 default:
//                     break;
//             }
//         });

//         Event::trigger('equipment[add].post_submit_validate', $form);

//         if ($form['email']) {
//             $form->validate('email', 'is_email', I18N::T('equipments', '联系邮箱填写有误!'));
//         }

//         $ref_no = trim($form['ref_no']);
//         if ($ref_no) {
//             $exist_equipment = O('equipment', ['ref_no' => $ref_no]);
//             if ($exist_equipment->id) {
//                 $form->set_error('ref_no', I18N::T('equipments', '您输入的仪器编号在系统中已存在!'));
//             }
//         }

//         /*
//         guoping.zhang@2011.01.17
//         仪器负责人人数上限
//         */
//         if (Config::get('equipment.max_incharges')) {
//             $max_incharges = Config::get('equipment.max_incharges');
//             //现在改仪器负责人数目
//             $incharges_count = count($incharges);
//             if ($incharges_count > $max_incharges) {
//                 $form->set_error('incharges', I18N::T('equipments', '仪器最多能指定%count个负责人!', ['%count'=>$max_incharges]));
//             }
//         }

//         if ($form->no_error) {
//             // NO.BUG#218(xiaopei.li@2010.12.14)
//             // 修正添加仪器对所属单位的处理
//             // organization属性已由group替代
//             // $equipment->organization = $form['organization'];
//             if (isset($form['ref_no']) && $ref_no) {
//                 $equipment->ref_no = $ref_no;
//             }
//             $equipment->cat_no = $form['cat_no'];
//             $equipment->name = $form['name'];
//             $equipment->en_name = $form['en_name'];
//             $equipment->model_no = $form['model_no'];
//             $equipment->manu_at = $form['manu_at'];
//             $equipment->manufacturer = $form['manufacturer'];
//             $equipment->manu_date = $form['manu_date'];
//             $equipment->purchased_date = $form['purchased_date'];
//             $equipment->location = $form['location'];
//             $equipment->tech_specs = $form['tech_specs'];
//             $equipment->features = $form['features'];
//             $equipment->configs = $form['configs'];
//             $domain = Config::get('equipment.domain');
//             $applicationarea = '';

//             if (count($form['domain'])) {
//                 foreach ($form['domain'] as $key => $value) {
//                     $applicationarea .= $domain[$key].',';
//                     $applicationcode .= $key;
//                 }
//                 $applicationarea = rtrim($applicationarea, ',');
//             }

//             $equipment->domain = $applicationarea;
//             $equipment->price = (double)$form['price'];
//             $equipment->specification = $form['specification'];
//             $equipment->email = $form['email'];
//             $equipment->phone = $form['phone'];
//             $equipment->atime = $form['atime']; //入网时间
//             if (Config::get('equipment.enable_share')) {
//                 $equipment->share = $form['share'];
//             }
//             if ($me->is_allowed_to('进驻仪器控', 'equipment')) {
//                 $equipment->yiqikong_share = (int)$form['yiqikong_share'];
//             }

//             Event::trigger('equipment[add].post_submit', $form, $equipment);

//             $group = O('tag', $form['group_id']);
//             $equipment->group = null;

//             if ($group->root->id == $group_root->id) {
//                 $group_root->disconnect($equipment);
//                 $group->connect($equipment);
//                 $equipment->group = $group;
//             }

//             //初始化默认 仪器控制方式
//             //$equipment->control_mode = 'nocontrol';
//             $equipment->save();

//             if ($equipment->id) {
//                 Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id]), 'journal');
//                 $group->connect($equipment);

//                 //仪器多个负责人
//                 foreach (json_decode($form['incharges']) as $id=>$name) {
//                     $user = O('user', $id);
//                     if (!$user->id) {
//                         continue;
//                     }
//                     $equipment->connect($user, 'incharge');
//                     $user->follow($equipment);
//                 }

//                 //仪器多个联系人
//                 foreach (json_decode($form['contacts']) as $id=>$name) {
//                     $user = O('user', $id);
//                     if (!$user->id) {
//                         continue;
//                     }
//                     $equipment->connect($user, 'contact');
//                     $equipment->connect($user, 'incharge');
//                     $user->follow($equipment);
//                 }

//                 $tags = @json_decode($form['tags'], true);
//                 if (count($tags)) {
//                     //有tag，关联tag
//                     Tag_Model::replace_tags($equipment, $tags, 'equipment');
//                 } else {
//                     //关联root
//                     $equipment->connect(Tag_Model::root('equipment'));
//                 }

//                 Event::trigger('equipment[add].post_submit_saved', $form, $equipment);

//                 Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设备添加成功!'));

//                 if (Config::get('equipment.total_count')) {
//                     $cache = Cache::factory();
//                     $equipment_count = $cache->get('equipment_count');
//                     $equipment_count[EQ_Status_Model::IN_SERVICE] ++;
//                     $equipment_count['total'] ++;
//                     $cache->set('equipment_count', $equipment_count, 3600);
//                 }

//                 URI::redirect($equipment->url(null, null, null, 'view'));
//             } else {
//                 Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设备添加失败! 请与系统管理员联系。'));
//             }
//         }
//     }

//     $group_root = Tag_Model::root('group');

//     $other_view = Event::trigger('equipment[edit].view', $form, $equipment);

//     $this->layout->form = $form;
//     $this->layout->body->primary_tabs
//         ->select('add')
//         ->set('content', V('add', [
//             'group_root' => $group_root,
//             'other_view' => $other_view
//         ]))
//         ->set('equipment', $equipment);
// }

//选择完打印列，点击“确定”，执行该打印事件
    public function _export_print($equipments, $form)
    {
        $valid_columns   = Config::get('equipments.export_columns.equipment');
        $visible_columns = Input::form('columns');

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $this->layout = V('equipments_print', [
            'equipments'    => $equipments,
            'valid_columns' => $valid_columns,

        ]);

        //记录日志
        $me = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]打印了仪器列表', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
    }

//选择完导出的列，点击“确定”，执行该导出事件
    /*
    function _export_csv($equipments, $form) {

    $valid_columns = Config::get('equipments.export_columns.equipment');
    $visible_columns = (array)$form['columns'];

    foreach ($valid_columns as $p => $p_name ) {
    if (!isset($visible_columns[$p])) {
    unset($valid_columns[$p]);
    }
    }

    $csv =new CSV('php://output','w');
    $title = [];
    foreach ($valid_columns as $p => $p_name) {
    $title[] = I18N::T('equipments',$valid_columns[$p]);
    }
    $csv->write($title);

    if ($equipments->total_count()) {
    foreach ($equipments as $equipment) {
    $data = [];

    if (array_key_exists('name', $visible_columns)) {
    $data[] = T($equipment->name)?:'';
    }
    if (array_key_exists('ref_no', $visible_columns)) {
    $data[] = H($equipment->ref_no)?:'';
    }
    if (array_key_exists('eq_cf_id', $visible_columns)) {
    $data[] = H($equipment->id)?:'';
    }
    if (array_key_exists('cat', $visible_columns)) {
    $root = Tag_Model::root('equipment');
    $tags = Q("$equipment tag[root=$root]");
    $cats = [];
    foreach ($tags as $cat) {
    $cats[] = $cat->name;
    }
    $data[] = T(implode(', ',$cats))?:'';
    }
    if (array_key_exists('control_mode', $visible_columns)) {
    $control_mode = [
    'nocontrol' => I18N::T('equipments','不控制'),
    'power' => I18N::T('equipments','电源控制'),
    'computer' => I18N::T('equipments','电脑登录')
    ];
    $data[] = $control_mode[T($equipment->control_mode)]?:'';
    }
    if (array_key_exists('location', $visible_columns)) {
    $location = [
    H($equipment->location)
    ];
    $data[] = ($location[0]=='' && $location[1]=='')?'-':H(implode(', ',$location));
    }
    if (array_key_exists('contacts', $visible_columns)) {
    $users = Q("$equipment<contact user");
    $contacts = [];
    foreach ($users as $contact) {
    $contacts[] = $contact->name;
    }
    $data[] = T(implode(', ',$contacts))?:'';
    }
    if (array_key_exists('phone', $visible_columns)) {
    $data[] = T($equipment->phone)?:'-';
    }
    if (array_key_exists('group', $visible_columns)) {
    $anchors = [];
    if ( Config::get('tag.group_limit')>=0 && $equipment->group->id ) {

    $tag = $equipment->group;
    $tag_root = $equipment->group->root;

                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    if (Module::is_installed('app')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    URI::redirect($equipment->url(null, null, null, 'view'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设备添加失败! 请与系统管理员联系。'));
                }
            }
        }

    if (!isset($tag_root)) $tag_root = $tag->root;

    if ($tag->id == Tag_Model::root('group')->id) return;

    $found_root =  ($tag_root->id == $tag->root->id);
    foreach ((array) $tag->path as $unit) {
    list($tag_id, $tag_name) = $unit;
    if (!$found_root) {
    if ($tag_id != $tag_root->id) continue;
    $found_root = TRUE;
    }
    $anchors[] =   T($tag_name);
    }
    $data[] = implode(', ', $anchors);
    }
    if( !$anchors ) {
    $data[] = '';
    }
    }
    if (array_key_exists('specification', $visible_columns)) {
    $data[] = H($equipment->specification)?:'';
    }
    if (array_key_exists('brand', $visible_columns)) {
    $data[] = H($equipment->brand)?:'';
    }
    if (array_key_exists('model_no', $visible_columns)) {
    $data[] = H($equipment->model_no)?:'';
    }
    if (array_key_exists('manufacturer', $visible_columns)) {
    $data[] = H($equipment->manufacturer)?:'';
    }
    if (array_key_exists('manu_at', $visible_columns)) {
    $data[] = H($equipment->manu_at)?:'';
    }
    if (array_key_exists('purchased_date', $visible_columns)) {
    $data[] = T(date('Y/m/d',$equipment->purchased_date))?:'';
    }
    if (array_key_exists('manu_date', $visible_columns)) {
    $data[] = T(date('Y/m/d',$equipment->manu_date))?:'';
    }
    if (array_key_exists('cat_no', $visible_columns)) {
    $data[] = H($equipment->cat_no)?:'';
    }
    if (array_key_exists('gb_no', $visible_columns)) {
    $data[] = H($equipment->gb_no)?:'';
    }
    if (array_key_exists('tech_specs', $visible_columns)) {
    $data[] = H($equipment->tech_specs)?:'';
    }
    if (array_key_exists('features', $visible_columns)) {
    $data[] = H($equipment->features)?:'';
    }
    if (array_key_exists('configs', $visible_columns)) {
    $data[] = H($equipment->configs)?:'';
    }
    $csv->write($data);
    }

    }

    $csv->close();
    //记录日志
    $me = L('ME');
    Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了仪器列表', ['%user_name'=> $me->name,  '%user_id'=> $me->id]), 'journal');

    }
     */

    public function download_template()
    {
        Downloader::download(SITE_PATH . 'public/download/仪器信息导入模板.xlsx', false);
        exit;
    }

    public function before_import()
    {
        $file     = Input::file('Filedata');
        $fullpath = $file['tmp_name'];
        if (!file_exists('/tmp/eq_upload/')) {
            mkdir('/tmp/eq_upload/', 0755, true);
        }
        @copy($fullpath, '/tmp/eq_upload/' . $file['name']);
        if (!$file['error']) {
            $autoload = ROOT_PATH . 'vendor/os/php-excel/PHPExcel/PHPExcel.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
            $PHPReader = new \PHPExcel_Reader_Excel2007;
            if (!$PHPReader->canRead($fullpath)) {
                $PHPReader = new \PHPExcel_Reader_Excel5;
                if (!$PHPReader->canRead($fullpath)) {
                    echo "file error\n";
                    return;
                }
            }

            $PHPExcel     = $PHPReader->load($fullpath);
            $currentSheet = $PHPExcel->getSheet(0);
            $allColumn    = $currentSheet->getHighestColumn();
            $allRow       = $currentSheet->getHighestRow();

            $ColumnToKey = Config::get('equipments.import_columns.equipment');

            $judge = Config::get('equipments.import_columns.judge.equipment');

            foreach ($judge as $coloumn => $coloumn_value) {
                if ($currentSheet->getCellByColumnAndRow(ord($coloumn) - 65, 1)->getValue() != $coloumn_value) {
                    $res['status']  = false;
                    $res['content'] = 'Excel格式错误！请重试！';
                    echo json_encode($res);
                    exit;
                }
            }

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $ref_no    = $currentSheet->getCellByColumnAndRow(ord('C') - 65, $currentRow)->getValue();
                $equipment = O('equipment', ['ref_no' => $ref_no]);
                if ($equipment->id == '') {
                    $this->import('/tmp/eq_upload/' . $file['name']);
                } else {
                    $res['status']  = false;
                    $res['content'] = '/tmp/eq_upload/' . $file['name'];
                    echo json_encode($res);
                    exit;
                }
            }
        }
        exit;
    }

    public function import($file)
    {
        $fullpath = $file;
        $autoload = ROOT_PATH . 'vendor/os/php-excel/PHPExcel/PHPExcel.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        $PHPReader = new \PHPExcel_Reader_Excel2007;
        if (!$PHPReader->canRead($fullpath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5;
            if (!$PHPReader->canRead($fullpath)) {
                echo "file error\n";
                return;
            }
        }
        $PHPExcel     = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = Config::get('equipments.import_columns.equipment');

        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $ref_no = $currentSheet->getCellByColumnAndRow(ord('C') - 65, $currentRow)->getValue();
            $equipment = O('equipment', ['ref_no' => $ref_no]);
            if ($equipment->id == '') {
                $equipment = O('equipment');
            }
            foreach ($ColumnToKey as $k => $key) {
                $equipment->$key = $currentSheet->getCellByColumnAndRow(ord($k) - 65, $currentRow)->getValue();
                if ($k == 'E') {
                    $tag_name = $currentSheet->getCellByColumnAndRow(ord('E') - 65, $currentRow)->getValue();
                    $tag = O('tag_group', ['name' => $tag_name]);
                    if ($tag->id == '') {
                        $tag = O('tag_group');
                        $tag->name = $tag_name;
                        $name_abbr = PinYin::code($tag_name);
                        $tag->name_abbr = $name_abbr;
                        $root           = Tag_Model::root('group');
                        $tag->root      = $root;
                        $tag->save();
                    }
                    $equipment->group_id = $tag->id;
                }
            }
            $equipment->save();
        }

        exit;
    }

    function openfile(){
        $filename = __DIR__ . '/../' . PRIVATE_BASE . "files/入驻机构统一服务平台合作协议.pdf";
        header("Content-type: application/pdf");
        header("Content-Length: " . filesize($filename));
        readfile($filename);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    /*
    NO.TASK#313(guoping.zhang@2011.01.12)
    列表信息预览功能
     */
    public function index_preview_click()
    {
        $form = Input::form();
        $equipment = O('equipment', $form['id']);

        if (!$equipment->id) {
            return;
        }

        Output::$AJAX['preview'] = (string)V('equipments:equipment/preview', ['equipment' => $equipment]);
    }

    //导出、打印。点击导出、打印链接会触发该事件
    public function index_export_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.equipment');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请重试!'));
            // JS::refresh();
            return false;
        }

        $columns = Event::trigger('equipments.get.export.columns', $columns, $type) ?: $columns;

        $old_form = (array)$_SESSION[$form_token];

        if ($type == 'csv') {
            $title = I18N::T('equipments', '请选择要导出Excel的列');
        } elseif ($type == 'print') {
            $title = I18N::T('equipments', '请选择要打印的列');
        }

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
        ]), [
            'title' => I18N::T('equipments', $title),
        ]);
    }

    public function index_export_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];

        $old_form = (array)$_SESSION[$form_token];
        $new_form = (array)$form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0] . $file_name_arr[1];

        if ('csv' == $type) {
            $pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid' => $pid,
            ]), [
                'title' => I18N::T('equipments', '导出等待'),
            ]);
        }
    }

    public function _export_csv($selector, $form, $file_name)
    {
        $me = L('ME');
        $valid_columns = Config::get('equipments.export_columns.equipment');
        $valid_columns = Event::trigger('equipments.get.export.columns', $valid_columns, 'csv') ?: $valid_columns;
        $visible_columns = (array)$form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        unset($valid_columns['-1']);
        unset($valid_columns['-2']);

        if (isset($_SESSION[$me->id . '-export'])) {
            foreach ($_SESSION[$me->id . '-export'] as $old_pid => $old_form) {
                $new_valid_form = $form['form'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_equipment export ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "'>/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
    }

    public function index_print_submit()
    {
        exit("提交打印");
    }

    public function index_import_click()
    {
        JS::dialog((string)V('equipments:equipment/import'));
    }

    public function index_import_submit()
    {
        $fullpath = Input::form()['file'];
        $autoload = ROOT_PATH . 'vendor/os/php-excel/PHPExcel/PHPExcel.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        $PHPReader = new \PHPExcel_Reader_Excel2007;
        if (!$PHPReader->canRead($fullpath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5;
            if (!$PHPReader->canRead($fullpath)) {
                echo "file error\n";
                return;
            }
        }
        $PHPExcel = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = Config::get('equipments.import_columns.equipment');

        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $ref_no = $currentSheet->getCellByColumnAndRow(ord('C') - 65, $currentRow)->getValue();
            $equipment = O('equipment', ['ref_no' => $ref_no]);
            if ($equipment->id == '') {
                $equipment = O('equipment');
            }
            foreach ($ColumnToKey as $k => $key) {
                $equipment->$key = $currentSheet->getCellByColumnAndRow(ord($k) - 65, $currentRow)->getValue();
                if ($k == 'E') {
                    $tag_name = $currentSheet->getCellByColumnAndRow(ord('E') - 65, $currentRow)->getValue();
                    $tag = O('tag_group', ['name' => $tag_name]);
                    if ($tag->id == '') {
                        $tag = O('tag_group');
                        $tag->name = $tag_name;
                        $name_abbr = PinYin::code($tag_name);
                        $tag->name_abbr = $name_abbr;
                        $root = Tag_Model::root('group');
                        $tag->root = $root;
                        $tag->save();
                    }
                    $equipment->group_id = $tag->id;
                }
            }
            $equipment->save();
        }

        exit;
    }

    public function index_add_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'equipment')) { //管理仪器设备 => 添加仪器设备
            URI::redirect('error/401');
        }

        $group_root = Tag_Model::root('group');
        $equipment = O('equipment');

        $group_root = Tag_Model::root('group');

        $other_view = Event::trigger('equipment[edit].view', $form, $equipment);

        JS::dialog(V('add', ['group_root' => $group_root, 'form' => $form, 'other_view' => $other_view, 'equipment' => $equipment]), [
            'title' => I18N::T('labs', '添加仪器'),
        ]);
    }

    public function index_add_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'equipment')) { //管理仪器设备 => 添加仪器设备
            URI::redirect('error/401');
        }

        $group_root = Tag_Model::root('group');
        $equipment = O('equipment');

        if (Input::form('submit')) {
            $form = Form::filter(Input::form());
            $form->validate('price', 'compare(>=0)', I18N::T('equipments', '仪器价格不能设置为负数!'));

            $requires = Config::get('form.equipment_add')['requires'];

            if (!$me->is_allowed_to('修改组织机构', 'equipment') && $requires['group_id']) {
                unset($requires['group_id']);
            }
            if (!$me->is_allowed_to('修改组织机构', 'equipment') && !$form['group_id']) {
                $form['group_id'] = $me->group->id;
            }
            array_walk($requires, function ($v, $k) use ($form, $user, $group_root) {
                switch ($k) {
                    case 'name':
                        $form->validate('name', 'not_empty', I18N::T('equipments', '请输入仪器名称!'));
                        break;
                    case 'price':
                        $form->validate('price', 'compare(>0)', I18N::T('equipments', '请输入仪器价格!'));
                        break;
                    case 'ref_no':
                        $form->validate('ref_no', 'not_empty', I18N::T('equipments', '请输入仪器编号!'));
                        break;
                    case 'incharges':
                        $incharges = (array)@json_decode($form['incharges'], true);
                        if (count($incharges) == 0) {
                            $form->set_error('incharges', I18N::T('equipments', '请指定至少一名仪器负责人!'));
                        }
                        break;
                    case 'contacts':
                        $contacts = (array)@json_decode($form['contacts'], true);
                        if (count($contacts) == 0) {
                            $form->set_error('contacts', I18N::T('equipments', '请指定至少一名仪器联系人!'));
                        }
                        break;
                    case 'model_no':
                        $form->validate('model_no', 'not_empty', I18N::T('equipments', '请输入型号!'));
                        break;
                    case 'specification':
                        $form->validate('specification', 'not_empty', I18N::T('equipments', '请输入规格!'));
                        break;
                    case 'manufacturer':
                        $form->validate('manufacturer', 'not_empty', I18N::T('equipments', '请输入生产厂家!'));
                        break;
                    case 'purchased_date':
                        $form->validate('purchased_date', 'not_empty', I18N::T('equipments', '请输入购置日期!'));
                        break;
                    case 'group_id':
                        $form->validate('group_id', 'not_empty', I18N::T('equipments', '请选择所属单位!'));
                        $form->validate('group_id', 'compare(>1)', I18N::T('equipments', '请选择所属单位!'));
                        break;
                    case 'phone':
                        $form->validate('phone', 'not_empty', I18N::T('equipments', '请输入联系电话!'));
                        break;
                    default:
                        break;
                }
            });

            Event::trigger('equipment[add].post_submit_validate', $form);

            if ($form['email']) {
                $form->validate('email', 'is_email', I18N::T('equipments', '联系邮箱填写有误!'));
            }

            $ref_no = trim($form['ref_no']);
            if ($ref_no) {
                $exist_equipment = O('equipment', ['ref_no' => $ref_no]);
                if ($exist_equipment->id) {
                    $form->set_error('ref_no', I18N::T('equipments', '您输入的仪器编号在系统中已存在!'));
                }
            }

            /*
            guoping.zhang@2011.01.17
            仪器负责人人数上限
             */
            if (Config::get('equipment.max_incharges')) {
                $max_incharges = Config::get('equipment.max_incharges');
                //现在改仪器负责人数目
                $incharges_count = count($incharges);
                if ($incharges_count > $max_incharges) {
                    $form->set_error('incharges', I18N::T('equipments', '仪器最多能指定%count个负责人!', ['%count' => $max_incharges]));
                }
            }

            if ($form->no_error) {
                // NO.BUG#218(xiaopei.li@2010.12.14)
                // 修正添加仪器对所属单位的处理
                // organization属性已由group替代
                // $equipment->organization = $form['organization'];
                if (isset($form['ref_no']) && $ref_no) {
                    $equipment->ref_no = $ref_no;
                }

                if (isset($form['cat_no'])) $equipment->cat_no = $form['cat_no'];
                if (isset($form['name'])) $equipment->name = $form['name'];
                if (isset($form['en_name'])) $equipment->en_name = $form['en_name'];
                if (isset($form['model_no'])) $equipment->model_no = $form['model_no'];
                if (isset($form['manu_at'])) $equipment->manu_at = $form['manu_at'];
                if (isset($form['manufacturer'])) $equipment->manufacturer = $form['manufacturer'];
                if (isset($form['manu_date']) && $form['manu_date']) $equipment->manu_date = $form['manu_date'];
                if (isset($form['purchased_date']) && $form['purchased_date']) $equipment->purchased_date = $form['purchased_date'];
                if (isset($form['tech_specs'])) $equipment->tech_specs = $form['tech_specs'];
                if (isset($form['features'])) $equipment->features = $form['features'];
                if (isset($form['configs'])) $equipment->configs = $form['configs'];
                if (isset($form['open_reserv'])) $equipment->open_reserv = $form['open_reserv'];
                if (isset($form['charge_info'])) $equipment->charge_info = $form['charge_info'];
                $domain = Config::get('equipment.domain');
                $applicationarea = '';

                if (count($form['domain'])) {
                    foreach ($form['domain'] as $key => $value) {
                        $applicationarea .= $domain[$key] . ',';
                        $applicationcode .= $key;
                    }
                    $applicationarea = rtrim($applicationarea, ',');
                }

                $equipment->domain = $applicationarea;
                if (isset($form['price'])) $equipment->price = (double)$form['price'];
                if (isset($form['specification'])) $equipment->specification = $form['specification'];
                if (isset($form['phone'])) $equipment->phone = $form['phone'];
                if (isset($form['email'])) $equipment->email = $form['email'];
                if (isset($form['atime']) && $form['atime']) $equipment->atime = $form['atime'];
                if (isset($form['site'])) $equipment->site = $form['site'];

                if (Config::get('equipment.enable_share')) {
                    $equipment->share = $form['share'];
                }
                if ($me->is_allowed_to('进驻仪器控', 'equipment')) {
                    $equipment->yiqikong_share = (int)$form['yiqikong_share'];
                }
                if ($me->is_allowed_to('隐藏', 'equipment')) {
                    $equipment->hidden = (int)$form['hidden'];
                }
                Event::trigger('equipment[add].post_submit', $form, $equipment);

                $group = O('tag_group', $form['group_id']);
                $equipment->group = O('tag_group');

                if ($group->root->id == $group_root->id) {
                    $group_root->disconnect($equipment);
                    $group->connect($equipment);
                    $equipment->group = $group;
                }

                //初始化默认 仪器控制方式
                //$equipment->control_mode = 'nocontrol';
                $equipment->save();

                if ($equipment->id) {
                    Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');
                    $group->connect($equipment);

                    //仪器多个负责人
                    $role = O('role', ['name' => '仪器负责人']);
                    foreach (json_decode($form['incharges']) as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }
                        $equipment->connect($user, 'incharge');
                        $user->follow($equipment);

                        if (People::perm_in_uno()) {
                            $res = Gateway::postRemoteUserGroupRoles([
                                'user_id' => $user->gapper_id,
                                'role' => ['gid' => 1, 'rid' => $role->gapper_id],
                            ]);
                        }
                    }

                    //仪器多个联系人
                    foreach (json_decode($form['contacts']) as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }
                        $equipment->connect($user, 'contact');
                        $equipment->connect($user, 'incharge');
                        $user->follow($equipment);
                    }

                    $tags = @json_decode($form['tags'], true);
                    if (count($tags)) {
                        //有tag，关联tag
                        Tag_Model::replace_tags($equipment, $tags, 'equipment');
                    } else {
                        //关联root
                        $equipment->connect(Tag_Model::root('equipment'));
                    }

                    if ($form['tag_technical']) {
                        //有tag，关联tag
                        $tags = [$form['tag_technical']=>O('tag_equipment_technical',$form['tag_technical'])->name];
                        Tag_Model::replace_tags($equipment, $tags, 'equipment_technical');
                    } else {
                        //关联root
                        $equipment->connect(Tag_Model::root('equipment_technical'));
                    }

                    if ($form['tag_education']) {
                        //有tag，关联tag
                        $tags = [$form['tag_education']=>O('tag_equipment_education',$form['tag_education'])->name];
                        Tag_Model::replace_tags($equipment, $tags, 'equipment_education');
                    } else {
                        //关联root
                        $equipment->connect(Tag_Model::root('equipment_education'));
                    }
                    
                    if (Config::get('equipment.location_type_select')) {
                        /* $location_tags = @json_decode($form['location'], true);
                        if (count($location_tags)) {
                            // 有tag，关联tag
                            Tag_Model::replace_tags($equipment, $location_tags, 'location');
                        } else {
                            // 关联root
                            $equipment->connect(Tag_Model::root('location'));
                        } */

                        if (isset($form['location_id'])) {
                            $location = O('tag_location', $form['location_id']);
    
                            $location_root = Tag_Model::root('location');
                            if ($location->root->id == $location_root->id) {
                                $location_root->disconnect($equipment);
                                $location->connect($equipment);
                                $equipment->location = $location;
                            }
                        }
                    }

                    Event::trigger('equipment[add].post_submit_saved', $form, $equipment);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设备添加成功!'));

                    if (Config::get('equipment.total_count')) {
                        $cache = Cache::factory();
                        $equipment_count = $cache->get('equipment_count');
                        $equipment_count[EQ_Status_Model::IN_SERVICE]++;
                        $equipment_count['total']++;
                        $cache->set('equipment_count', $equipment_count, 3600);
                    }

                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    JS::redirect($equipment->url(null, null, null, 'view'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设备添加失败! 请与系统管理员联系。'));
                }
            }
        }

        $group_root = Tag_Model::root('group');

        $other_view = Event::trigger('equipment[edit].view', $form, $equipment);

        JS::dialog(V('add', ['group_root' => $group_root, 'form' => $form, 'other_view' => $other_view, 'equipment' => $equipment]), [
            'title' => I18N::T('labs', '添加仪器'),
        ]);

//        $this->layout->form = $form;
        //        $this->layout->body->primary_tabs
        //            ->select('add')
        //            ->set('content', V('add', [
        //                'group_root' => $group_root,
        //                'other_view' => $other_view
        //            ]))
        //            ->set('equipment', $equipment);
    }

    /**
     * 设置仪器置顶
     */
    public function index_placed_top_click()
    {
        $me = L('ME');

        if (!($me->access('管理所有内容') || Config::get('equipments.placed_at_the_top'))) {
            URI::redirect('error/401');
        }

        $form = Input::form();
        $type = $form['type'];
        $equipment = O('equipment', (int)$form['equipment_id']);
        if (!$equipment->id) JS::refresh();
        if ($type == 'top') {
            $equipment->is_top = true;
            $equipment->top_time = Date::time();
        } else {
            $equipment->is_top = false;
            $equipment->top_time = 0;
        }
        $equipment->save();
        JS::refresh();
    }
}