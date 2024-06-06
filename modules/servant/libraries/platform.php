<?php
Class Platform {
    static function _check() {
        if (in_array(LAB_ID, Config::get('servant.enable_labs', []))) return;
        if ($_SERVER['REQUEST_URI'] == '/lims/api') return;

        return TRUE;
    }

    static function cache_platform ($e) {
        if (!Platform::_check()) return;

        $cache = Cache::factory('redis');
        $cached = $cache->get('platform.cached');
        if (!$cached) {
            $platforms = Q("platform");
            foreach ($platforms as $platform) {
                $p = [
                    'id' => $platform->id,
                    'code' => $platform->code,
                    'source_site' => $platform->source_site,
                    'source_lab' => $platform->source_lab,
                    'name' => $platform->name
                ];
                $cache->set('platform.'.$platform->code, $p, 3600);
            }
            $cache->set('platform.cached', TRUE, 3600);
        }
        return TRUE;
    }

    static function auth_login ($e) {
        if (!Platform::_check()) return;

        $cache = Cache::factory('redis');
        $platform = $cache->get('platform.'.LAB_ID);
        if (!$platform['id']) return TRUE;

        $source_session_name = 'session_lims2_'.$platform['source_site'].'_'.$platform['source_lab'];
        $source_session_id = $_COOKIE[$source_session_name];
        if (!$source_session_id) return TRUE;

        // get source site session id
        $session_id = session_id();

        // switch to source session and get auth token
        Session::shutdown();
        session_id($source_session_id);
        session_start();

        if (!in_array('logout', Input::args())) {
            $auth_token = $_SESSION['auth.token'];
        }
        else {
            unset($_SESSION['auth.token']);
        }
        Session::shutdown();

        session_id($session_id);
        session_start();
        if (!$auth_token) return TRUE;

        if (!Auth::logged_in()) {
            Auth::login($auth_token);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', "%platform_name从主站点同步登陆状态", [
                '%platform_name' => H(T($platform['name']))
            ]));
        }
        elseif (Auth::token() != $auth_token) {
            Auth::logout();
            Auth::login($auth_token);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', "%platform_name从主站点同步登陆状态", [
                '%platform_name' => H(T($platform['name']))
            ]));
        }
    }

    static function set_lab_admin ($e) {
        if (!Platform::_check()) return;

        $cache = Cache::factory('redis');
        $platform = $cache->get('platform.'.LAB_ID);
        if (!$platform['id']) return TRUE;

        $id = $platform['id'];
        $admins = Q("platform#{$id}<owner user")->to_assoc('id','token');
        $admins = array_merge($admins, Lab::get('lab.admin'));
        Config::set('lab.admin', $admins);
    }

    static function setup_view ($e, $tabs) {
        Event::bind('servant.secondary.tab', 'Platform::equipment_secondary_tab');
        Event::bind('servant.secondary.content', 'Platform::equipment_secondary_content', 0, 'equipment');

        Event::bind('servant.secondary.tab', 'Platform::lab_secondary_tab');
        Event::bind('servant.secondary.content', 'Platform::lab_secondary_content', 0, 'lab');

        Event::bind('servant.secondary.tab', 'Platform::user_secondary_tab');
        Event::bind('servant.secondary.content', 'Platform::user_secondary_content', 0, 'user');
    }

    static function equipment_secondary_tab ($e, $tabs) {
        $pf = $tabs->pf;
        $me = L('ME');

        if ($me->is_allowed_to('查看', $pf)) {
            $tabs->add_tab('equipment', [
                'url' => $pf->url('equipment'),
                'title' => I18N::T('servant', '下属仪器'),
                'weight' => 10
            ]);
        }
    }

    static function lab_secondary_tab ($e, $tabs) {
        $pf = $tabs->pf;
        $me = L('ME');

        if ($me->is_allowed_to('查看', $pf)) {
            $tabs->add_tab('lab', [
                'url' => $pf->url('lab'),
                'title' => I18N::T('servant', '下属课题组'),
                'weight' => 10
            ]);
        }
    }

    static function user_secondary_tab ($e, $tabs) {
        $pf = $tabs->pf;
        $me = L('ME');

        if ($me->is_allowed_to('查看', $pf)) {
            $tabs->add_tab('user', [
                'url' => $pf->url('user'),
                'title' => I18N::T('servant', '下属成员'),
                'weight' => 10
            ]);
        }
    }

    static function equipment_secondary_content ($e, $tabs) {
        $pf = $tabs->pf;
        $form = Lab::form();
        $me = L('ME');

        $selector = "{$pf} equipment";
        
        if($form['eq_name']) {
            $eq_name = Q::quote($form['eq_name']);
            $selector .= "[name*=$eq_name]";
        }

        if($form['location']){
            $location = Q::quote($form['location']);
            $selector .= "[location=$location]";
        }

        if($form['location2']){
            $location2 = Q::quote($form['location2']);
            $selector .= "[location2*=$location2]";
        }

        if($form['control_mode']){
            if($form['control_mode'] == 'nocontrol'){
                $selector .= '[!control_mode]';
            }
            else{
            
                $selector .= '[control_mode='.Q::quote($form['control_mode']).']';
            }		
        }

        if($form['control_mode']!='nocontrol' && $form['control_status']){
            if($form['control_status'] == 'available'){
                $selector .= '[!is_using]';
            }
            else{
            
                $selector .= '[is_using]';
            }		
        }

        $equipments = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($equipments, $start, $per_page);

        $buttons = new ArrayIterator;
        if ($me->is_allowed_to('修改', $pf)) {
            $buttons[] = [
                'url' => URI::url('!servant/platform/add'),
                'text' => I18N::T('servant', '增加仪器'),
                'extra' => 'class="button button_add" q-event="click" q-object="equ_add" q-static="' . H(['id' => $pf->id]) . '" q-src="' . $pf->url() . '"'
            ];
        }

        $tabs->content = V('servant:platform/equipments', [
            'buttons' => $buttons,
            'equipments' => $equipments,
            'pagination' => $pagination,
            'pf' => $pf,
            'form' => $form
        ]);
    }

    static function lab_secondary_content ($e, $tabs) {
        $pf = $tabs->pf;
        $form = Lab::form();
        $me = L('ME');
        $selector = "lab";

        if($form['lab_name']){
            $lab_name = Q::quote(trim($form['lab_name']));
            $selector .= "[name*=$lab_name|name_abbr^=$lab_name]";
        }

        $group = O('tag_group', $form['group_id']);
        $group_root = Tag_Model::root('group');	
        $pre_selector['platform'] = "{$pf}";	
        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selector['group'] = "$group";
        } else {
            $group = NULL;
        }

        $selector = '('.implode(',', $pre_selector).') '.$selector;
        $labs = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($labs, $start, $per_page);

        $buttons = new ArrayIterator;
        if ($me->is_allowed_to('修改', $pf)) {
            $buttons[] = [
                'url' => URI::url('!servant/platform/add'),
                'text' => I18N::T('servant', '增加课题组'),
                'extra' => 'class="button button_add" q-event="click" q-object="lab_add" q-static="' . H(['id' => $pf->id]) . '" q-src="' . $pf->url() . '"'
            ];
        }

        $tabs->content = V('servant:platform/labs', [
            'buttons' => $buttons,
            'labs' => $labs,
            'group_root' => $group_root,
            'group' => $group,
            'pagination' => $pagination,
            'form' => $form
        ]);
    }

    static function user_secondary_content ($e, $tabs) {
        $pf = $tabs->pf;
        $form = Lab::form();
        $me = L('ME');

        $selector = "{$pf} user";

        if($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
        }

        if($form['address']) {
			$address = Q::quote($form['address']);
			$selector .= "[address*=$address]";
        }

        $users = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($users, $start, $per_page);

        $buttons = new ArrayIterator;
        if ($me->is_allowed_to('修改', $pf)) {
            $buttons[] = [
                'url' => URI::url('!servant/platform/add'),
                'text' => I18N::T('servant', '增加成员'),
                'extra' => 'class="button button_add" q-event="click" q-object="user_add" q-static="' . H(['id' => $pf->id]) . '" q-src="' . $pf->url() . '"'
            ];
        }

        $tabs->content = V('servant:platform/users', [
            'buttons' => $buttons,
            'users' => $users,
            'pf' => $pf,
            'form' => $form,
            'pagination' => $pagination
        ]);
    }

    static function on_platform_saved($e, $platform, $old_data, $new_data)
    {
        // 如果站点信息为新建的情况下，需要更新想要的 Role 信息
        if ($new_data['id'] && !$old_data['id']) {
            $roles = Q('role[weight<0]');
            foreach ($roles as $role) {
                $platform->connect($role);
            }
        }
    }

    static function on_platform_before_delete($e, $platform)
    {
        // 删除掉已经关联过的role的信息
        foreach (Q("{$platform} role") as $role) {
            $platform->disconnect($role);
        }
    }

    static function set_roles($e)
    {
        $exist = Database::factory()->query('desc `_r_role_platform`');
        if($exist === false) return true;

        $platform = O('platform', ['code' => LAB_ID]);
        if (!$platform->id) {
            $roles = Q("role[weight>=0]:not(platform role):sort(weight A)");
            $default_roles = Q("role[weight<0]:sort(weight D)");
        }
        else {
            $roles = Q("{$platform} role[weight>=0]:sort(weight A)");
            $default_roles = Q("{$platform} role[weight<0]:sort(weight D)");
        }

        $role_num = $roles->length();
        $role_set = count($roles->to_assoc('weight', 'id'));
        if ($role_num != $role_set) {
            $first_role = $roles->current();
            $weight = $first_role->weight;
            foreach ($roles as $role) {
                if ($first_role->id != $role->id) {
                    $weight ++;
                    if ((int)$role->weight != $weight) {
                        $role->weight = $weight;
                        $role->save();
                    }
                }
            }
        }

        Cache::L('ROLES', $roles);

        foreach ($default_roles as $role_id => $role) {
            if ($role->weight == ROLE_PAST_MEMBERS && ! $GLOBALS['preload']['people.enable_member_date']) {
                continue;
            }
            $roles->prepend(['id' => $role->id, 'name' => $role->name, 'weight' => $role->weight]);
        }

        // 因为在使用多站点的地方, 必须保证 role 的获取是本站点下，所以需要屏蔽其他获取 Role 的函数访问
        return FALSE;
    }

    static function on_role_saved($e, $role, $old_data, $new_data)
    {
        // 如果站点信息为新建的情况下，需要更新想要的 Role 信息
        if ($new_data['id'] && !$old_data['id']) {
            $platform = O('platform', ['code' => LAB_ID]);
            if (!$platform->id) return;
            if (!$platform->connected_with($role)) {
                $platform->connect($role);
            }
        }
    }

    static function login_extra_validate ($e, $user) {
        $code = LAB_ID;
        $platform = O('platform', ['code' => $code]);
        if (!in_array($user->token, Config::get('lab.admin'))
        && !in_array($code, Config::get('servant.disable_code'))
        && !Q("$platform $user")->total_count()) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '帐号和密码不匹配! 请您重新输入.'));
            Log::add(strtr('[application] %user_name[%user_id]由于不属于此子平台[%code],登录失败', [
                '%user_name' => $user->name,
                '%user_id' => $user->id,
                '%code' => $code,
            ]), 'logon');
            throw new Error_Exception;
        }
    }

    static function get_equipments_model_extend ($e, $equipment, $data) {
        if (!$equipment->id) {
            $e->return_value = false;
            return TRUE;
        }

        $platform = Q("{$equipment} platform")->to_assoc('id', 'name');
        if ($platform) {
            $data['platform'] = $platform;
            $e->return_value = $data;
        }
        else {
            $e->return_value = false;
        }
        return TRUE;
    }

    static function on_object_saved ($e, $object) {
        if (!in_array($object->name(), ['equipment', 'user', 'lab'])) return;

        $platform = O('platform', ['code' => LAB_ID]);
        if (!$platform->id) return;

        $connect = Q("$object $platform");
        if (!$connect->total_count()) {
            $platform->connect($object);
        }
    }

    static function eq_stat_is_accessible ($e, $name) {
       $me = L('ME');
       $code = LAB_ID;
       $platform = O('platform', ['code' => $code]);
       if ($platform->id && Q("$me<owner $platform")->total_count()) {
           $e->return_value = TRUE;
           return FALSE;
       }
       return TRUE;
    }
}
