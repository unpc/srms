<?php

class People
{
    public static function setup()
    {
        Event::bind('update.index.tab', 'People::_index_update_tab');
    }

    public static function setup_profile_page()
    {
        // 暂时禁用
        /*
    Event::bind('profile.view.tab', array(__CLASS__, '_index_attachments_tab'), 100, 'attachments');
    Event::bind('profile.view.content', array(__CLASS__, '_index_attachments_content'), 100, 'attachments');
     */
    }

    public static function _index_update_tab($e, $tabs)
    {
        $tabs->add_tab('user', [
            'url'   => URI::url('!update/index.user'),
            'title' => I18N::T('people', '成员更新'),
        ]);
    }

    public static function get_update_parameter($e, $object, array $old_data = [], array $new_data = [])
    {
        if ($object->name() != 'user' || !$old_data) {
            return;
        }
        $difference = array_diff_assoc($new_data, $old_data);

        $old_difference = array_diff_assoc($old_data, $new_data);
        $arr            = array_keys($difference);
        $info_keys      = array_keys(People::$user_info);
        $photo_keys     = array_keys(People::$user_photo);
        $data           = $e->return_value;
        if (!count($difference)) {
            return;
        }

        $delta = [];
        //判断动作
        if (count(array_intersect($info_keys, $arr))) {
            $delta['action'] = 'edit_info';
            if (in_array('lab', $arr)) {
                $lab = $difference['lab'];
                if ($lab->id) {
                    $difference['lab'] = Markup::encode_Q($lab);
                }

            }
            if (in_array('gender', $arr)) {
                $difference['gender'] = User_Model::$genders[$difference['gender']];
            }
            if (in_array('member_type', $arr)) {
                $members = User_Model::get_members();
                $type    = $difference['member_type'];
                if ($type < 10) {
                    $members = $members['学生'];
                } elseif ($type >= 10 && $type < 20) {
                    $members = $members['教师'];
                } else {
                    $members = $members['其他'];
                }
                $difference['member_type'] = I18N::T('people', $members[$type]);
            }
            /*
            NO. BUG#283 (Cheng.liu@2010.12.21)
            update私密信息的隐藏
             */
            /*
        if (in_array('dfrom', $arr) || in_array('dto', $arr)) {
        $from = $difference['dfrom'] ? date('Y/m/d', $difference['dfrom']) : '之前';
        $to = $difference['dto'] ? date('Y/m/d', $difference['dto']) : '至今';
        $difference['date'] = $from.' - '.$to;
        unset($difference['dfrom']);
        unset($difference['dto']);
        }
        if (in_array('atime', $arr)) {
        $difference['activate'] = $difference['atime'] ? I18N::T('people', '是') : I18N::T('people', '否');
        unset($difference['atime']);
        }
        if (in_array('undeletable', $arr)) {
        $difference['undeletable'] = $difference['undeletable'] ? I18N::T('people', '是') : I18N::T('people', '否');
        }
         */
        } elseif (count(array_intersect($photo_keys, $arr))) {
            $delta['action'] = 'edit_photo';
        } else {
            return;
        }
        $subject           = L('ME');
        $delta['subject']  = $subject;
        $delta['object']   = $object;
        $delta['new_data'] = $difference;
        $delta['old_data'] = $old_difference;

        $key        = Misc::key((string) $subject, $delta['action'], (string) $object);
        $data[$key] = (array) $data[$key];

        Misc::array_merge_deep($data[$key], $delta);

        $e->return_value = $data;
    }

    static $user_info = [
        'name'         => '姓名',
        'member_type'  => '人员类型',
        'organization' => '单位名称',
        'gender'       => '性别',
        'lab'          => '实验室',
        /*
        NO. BUG#283 (Cheng.liu@2010.12.21)
        update私密信息的隐藏
         */
        /*
    'major'            =>        '专业',
    'ref_no'    =>        '学号/工号',
    'lab'            =>        '实验室',
    'department'    =>        '所属系所',
    'dfrom'            =>        '开始时间',
    'dto'            =>        '结束时间',
    'date'            =>        '所在时间',
    'email'            =>        '电子邮箱',
    'phone'            =>        '联系电话',
    'address'        =>        '地址',
    'mobile'        =>        '电话',
    'card_no'        =>        'IC卡卡号',
    'activate'        =>        '是否激活',
    'undeletable'    =>        '不可删除',
    'atime'            =>        '激活'
     */
    ];

    static $user_photo = [
        'mtime' => '图像',
    ];

    public static function get_update_message($e, $update)
    {
        if ($update->object->name() !== 'user') {
            return;
        }

        switch ($update->action) {
            case 'edit_info':
                $config = 'people.info.msg.model';
                break;
            case 'edit_photo':
                $config = 'people.photo.msg.model';
                break;
            default:
                return;

        }
        $me       = L('ME');
        $subject  = $update->subject->name;
        $old_data = json_decode($update->old_data, true);
        $object   = $old_data['name'] ? $old_data['name'] : $update->object->name;

        /*
        if ($me->id == $update->subject->id && $me->id == $update->object->id) {
        $subject = I18N::T('people', '我');
        $object = I18N::T('people', '自己');
        }
        elseif ($me->id == $update->subject->id) {
        $subject = I18N::T('people', '我');
        }
        elseif ($me->id == $update->object->id) {
        $object = I18N::T('people', '我');
        }
        elseif ($update->object->id == $update->subject->id) {
        $subject = $object;
        }
         */

        $opt = Lab::get($config, Config::get($config));
        $msg = I18N::T('people', $opt['body'], [
            '%subject' => URI::anchor($update->subject->url(), H($subject), 'class="blue label"'),
            '%date'    => '<strong>' . Date::fuzzy($update->ctime, 'TRUE') . '</strong>',
            '%user'    => URI::anchor($update->object->url(), H($object), 'class="blue label"'),
        ]);
        $e->return_value = $msg;
        return false;
    }

    static $actions = ['edit_info', 'edit_photo'];

    public static function get_update_message_view($e, $update)
    {
        $actions = People::$actions;
        if (in_array($update->action, $actions)) {
            if ($update->action == 'edit_info') {
                $properties = People::$user_info;
            } elseif ($update->action == 'edit_photo') {
                return;
            }
            $e->return_value = V('people:update/show_msg', ['update' => $update, 'properties' => $properties]);
            return false;
        }
    }

    //添加，修改，删除3个权限中进行新的添加/修改下属机构成员的信息权限的判断，$object为user
    public static function user_ACL($e, $user, $perm, $object, $options)
    {
        $ignores = $options['@ignore'];
        $pi      = Lab::get('lab.pi');
        if ($pi && $user->token == $pi) {
            $e->return_value = true;
            return false;
        }
        if (!is_array($ignores)) {
            $ignores = [$ignores];
        }

        $ignore = in_array('修改下属机构成员', $ignores) ? true : false;
        switch ($perm) {
            case '查看':
                if ($user->id == $object->id) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看成员列表')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '管理角色':
                if ($user->access('修改所有成员的角色')) {
                    $e->return_value = true;
                    return false;
                }
                if (!$ignore
                    && $user->access('修改下属机构成员的角色')
                    && $user->group->id && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看角色':
                if ($user->access('修改所有成员的角色') || $user->access('修改下属机构成员的角色')) {
                    $e->return_value = true;
                    return false;
                }

                $roles = L('ROLES');
                foreach ($roles as $r) {
                    //对系统添加的roles进行判断
                    if ($r->id > 0) {
                        if ($user->is_allowed_to('查看', $r)) {
                            $e->return_value = true;
                            return false;
                        }
                    }
                }
            case '添加':
                //检查是否超出限制
                if (isset($GLOBALS['preload']['lab.max_members']) && Q('user')->total_count() >= $GLOBALS['preload']['lab.max_members']) {
                    $e->return_value = false;
                    return false;
                }
                if ($user->access('添加/修改所有成员信息')) {
                    $e->return_value = true;
                    return false;
                }
                if (!$ignore
                    && $user->access('添加/修改下属机构成员的信息')
                    && $user->group->id
                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改':
                if ($user->access('添加/修改所有成员信息')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->id && $user->id == $object->id && !in_array('自己', $ignores)) {
                    $e->return_value = true;
                    return false;
                }
                if (!$ignore
                    && $user->access('添加/修改下属机构成员的信息')
                    && $user->group->id && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '删除':
                if ($object->undeletable || $user->id == $object->id) {
                    $e->return_value = false;
                    return false;
                }

                if ($user->access('添加/修改所有成员信息')) {
                    $e->return_value = true;
                    return false;
                }
                if (!$ignore
                    && $user->access('添加/修改下属机构成员的信息')
                    && $user->group->id && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改组织机构':
                if ($user->access('添加/修改所有成员信息')) {
                    $e->return_value = true;
                    return false;
                }
                /*
                因为修改组织机构可能存在于添加用户页面
                所以$object->group->id必然为空,所以为空时也能让其进入修改组织机构,
                 */
                if (!$ignore
                    && $user->access('添加/修改下属机构成员的信息')
                    && $user->group->id
                    && ($user->group->is_itself_or_ancestor_of($object->group) || !$object->group->id)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '导出':
                if ($user->access('添加/修改所有成员信息')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '隐藏':
                //只有lab_admin可以设置用户是否隐藏
                if (in_array($user->token, Config::get('lab.admin'))) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '激活':
                //对于已激活的用户，可以设定该用户未未激活
                $e->return_value = true;

                if (Q("($object, $user<pi) lab")->total_count()
                    && !$user->access('管理所有内容')
                    && Config::get('lab.cannot_active')) {
                    $e->return_value = false;
                    return false;
                }

                if ($object->atime) {
                    $e->return_value = true;
                    return false;
                }

                if (Q('user[atime>0]')->total_count() >= $GLOBALS['preload']['lab.max_active_members']) {
                    $e->return_value = false;
                    return false;
                }

                //因修改权限中，需对修改的对象进行判断，而在添加中不需要判断，故此处不能调用修改的权限来判断, 如果是添加新成员$object->id必为空。
                if (!$ignore && $user->access('添加/修改下属机构成员的信息') && !$object->id) {
                    $e->return_value = true;
                    return false;
                }

                if ($user->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }

                break;
            case '查看建立者':
                if ($user->access('查看用户建立者')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看审批者':
                if ($user->access('查看用户审批者')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看登录账号':
                if ($user->is_allowed_to('修改', $object)) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看所有成员的登录账号')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看下属机构成员的登录账号')
                    && $user->group->id && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看联系方式':
                //自己查看到自己的
                if ($object->id == $user->id) {
                    $e->return_value = true;
                    return false;
                }

                $user_info = O('user_info',['user'=>$object]);
                //所有人都可查看
                if ((int) $user_info->privacy == User_Model::PRIVACY_ALL) {
                    $e->return_value = true;
                    return false;
                }

                //相同lab可查看
                if ((int) $user_info->privacy == User_Model::Privacy_Lab
                    && Q("($object, $user) lab")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                //权限
                if ($user->access('查看所有成员的联系方式')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看下属机构成员的联系方式')
                    && $user->group->id && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }

                break;
        }
    }
    /*
    NO.TASK#274(guoping.zhang@2010.11.24)
    操作成员的附件权限设置
    $object为user对象
     */
    public static function operate_attachment_is_allowed($e, $user, $perm, $object, $options)
    {
        if ($options['type'] != 'attachments') {
            return;
        }

        if (!$object->id) {
            return;
        }

        if ($user->id == $object->id) {
            $e->return_value = true;
            return false;
        }
        switch ($perm) {
            case '下载文件':
                if ($user->access('下载所有成员的附件')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '上传文件':
            case '添加目录':
                if ($user->access('上传/创建所有成员的附件')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '列表文件':
                if ($user->access('查看所有成员的附件')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '删除文件':
            case '修改文件':
            case '修改目录':
            case '删除目录':
                if ($user->access('更改/删除所有成员的附件')) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }
    /*
    NO.TASK#274(guoping.zhang@2010.11.24)
    操作成员关注的成员信息权限设置
    $object为user对象
     */
    public static function operate_follow_is_allowed($e, $user, $perm, $object, $options)
    {
        if (!$object->id) {
            return;
        }

        /*
        NO.BUG#310(guoping.zhang@2010.12.28)
        添加数量判断
         */

        if ($perm == '关注' || $perm == '取消关注') {
            $e->return_value = true;
            return false;
        }

        if ($object->get_follows_count('user') == 0) {
            return;
        }

        switch ($perm) {
            case '列表关注':
            case '列表关注的用户':
                if ($user->id == $object->id) {
                    $e->return_value = true;
                    return false;
                }

                if ($user->access('查看其他用户关注的成员')) {
                    $e->return_value = true;
                    return false;
                }

                if ($user->access('查看下属机构用户关注的成员')
                    && $user->group->id
                    && $object->group->id
                    && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }

                break;
        }
    }

    // hook controller[*].ready, 检查是否当前用户属于激活用户能够访问
    public static function accessible_controller($e, $controller, $method, $params)
    {
        if ($controller instanceof Layout_Controller) {
            $me = L('ME');
            if ($me->id && $me->is_active() && $me->must_change_password()) {
                $path = (defined('MODULE_ID') ? '!' . MODULE_ID . '/' : '')
                . Config::get('system.controller_path')
                . '/'
                . Config::get('system.controller_method');

                if (Input::arg(0) !== 'logout' && $path !== '!people/index/password') {
                    URI::redirect('!people/index/password');
                }
            }
        }
    }

    public static function get_all_roles($e)
    {

        $me            = L('ME');
        $default_roles = (array) Config::get('roles.default_roles');
        $roles         = (array) $e->return_value;
        foreach (L('ROLES') as $role) {
            if ($role->id < 0) {
                $r = $default_roles[$role->id];
                if ($r) {
                    if (in_array($r['key'], (array) Config::get('people.disable_member_type'))) {
                        continue;
                    }
                    if (in_array($r['key'], ['current', 'past']) && !$GLOBALS['preload']['people.enable_member_date']) {
                        continue;
                    }
                    $roles[$r['key']] = I18N::T('people', $r['name']);
                }
            } else {
                if ($me->is_allowed_to('查看', $role)) {
                    $roles[$role->id] = $role->name;
                }
            }
        }

        $e->return_value = $roles;

    }

    public static function on_user_saved($e, $user, $old_data, $new_data)
    {
        if ($old_data['atime'] && !$new_data['atime']) {
            $roles = [];
            foreach ($user->roles() as $r) {
                if ($r <= 0) {
                    continue;
                }
                $roles[] = $r;
            }
            if (count($roles)) {
                $user->disconnect(['role', $roles]);
            }
        }

        if (!$old_data['atime'] && $new_data['atime']) {
            Event::trigger('people.auto_open_people', $user);
        }

        if ($user->replacement && Module::is_installed('nfs_share')) {
            NFS_Share::change_share(O('user', $user->replacement), $user);
        }

        if ($old_data['card_no'] != $new_data['card_no']) {
            Event::trigger('people.card_no_changed', $user);
        }
        //修改用户课题组时，判断用户是否是修改前课题组的负责人，若是，则将课题组负责人设置为空
        $old_lab = $old_data['lab'];
        $new_lab = $new_data['lab'];

        //如果用户的lab改变了，如果他是原来的课题组pi，则应该将原来lab的owner清空
        $db = Database::factory();
        if ($old_lab->id != $new_lab->id) {
            $db->query("UPDATE `lab` SET `owner_id`=0 WHERE `owner_id`={$user->id} AND `id`={$old_lab->id}");
        }

        if($new_data['group']){
            //新建用户的时候组织机构关联失败所以在这儿关联一次
            $user->group->connect($user);
        }
    }

    /* 存储用户的审查者(kai.wu@2011.10.17) */
    public static function user_before_save($e, $user, $new_data)
    {
        if ($new_data['atime']) {
            $me = L('ME');
			$user->auditor_abbr = PinYin::code($me->name);
            // 只有有atime更新，就要更新审批者为L('ME')
            // 但是user有可能是从sync模块而来，故L('ME')不存在的情况下不更新
            $user->auditor = $me->id ? $me : $user->auditor;

            // 仅仅creator不存在的情况下才会更新creator
            $creator = $user->creator;
            $user->creator = $creator->id ? $creator : $me;
        }
        if ($new_data['address']) {
            $user->address_abbr = PinYin::code($new_data['address']);
        }

        if (false && $new_data['name'] && $user->id) {
            $creator_users = Q("user[creator=$user]");
            if ($creator_users->total_count()) {
                foreach ($creator_users as $u) {
                    $u->creator_abbr = PinYin::code($new_data['name']);
                    $u->save();
                }
            }

            $auditor_users = Q("user[auditor=$user]");
            if ($auditor_users->total_count()) {
                foreach ($auditor_users as $u) {
                    $u->auditor_abbr = PinYin::code($new_data['name']);
                    $u->save();
                }
            }

        }
    }

    public static function update_name_abbr($e, $object, $new_data)
    {
        //update name_abbr
        if ($object->name() == 'user' && $new_data['name'] && class_exists('PinYin')) {

            $name_abbr            = PinYin::code($new_data['name']);
            $first_only_name_abbr = PinYin::code($new_data['name'], true);

            if ($name_abbr != $first_only_name_abbr) {
                $prefix    = str_replace(' ', '', $name_abbr);
                $name_abbr = join(' ', [$name_abbr, $first_only_name_abbr, $prefix]);
            }

            $object->name_abbr = $name_abbr;
            return;
        }
    }

    public static function eq_reserv_calendar_people_link($e, $user, $contents)
    {
        $contents['people'] = V('people:preview_container', ['content' => $contents['people'], 'user' => $user]);
        $e->return_value    = $contents;
    }

    public static function get_user_simple_info($e, $user, $show_user_phone = false, $show_user_email = false)
    {
        $e->return_value = (string) V('people:calendar/user_info', ['user' => $user, 'show_user_phone' => $show_user_phone, 'show_user_email' => $show_user_email]);
    }

    public static function message_send_way_view($e, $user, $form)
    {
        $sendways = (array) Config::get('notification.handlers');

        if (L('ME')->id == $user->id && $sendways['email']) {
            $e->return_value = (string) V('people:message/send.way', ['way' => $sendways['email'], 'form' => $form]);
        }
    }

	static function message_send_way_submit($e, $message, $form) {
		$sendways = (array)Config::get('notification.handlers');

		if ($sendways['email'] && $form['email'] == 'on') {

            $user    = $message->receiver;
            $sender  = $message->sender;
            $mails[] = $user->binding_email ? $user->binding_email : $user->email;
            $title   = $message->title;
            $body    = $message->body;

            if (!$sender->id) {
                $email = new Email();
            } else {
                $email = new Email($sender);
            }

            $email->to($mails);
            $email->subject($title);
            $email->body($body);
            $email->reply_to($sender->email, $sender->name);
            $email->send();

            Log::add(strtr('[people] %user_name[%user_id] 发送了新邮件 [%email_title]', ['%user_name' => $sender->name, '%user_id' => $sender->id, '%email_title' => $title]), 'journal');

        }
    }

    public static function print_token($token, $backends = '')
    {
        if (!$token) {
            return;
        }

        $backends  = $backends ?: Config::get('auth.backends');
        $token_arr = Auth::parse_token($token);
        $backend   = $backends[$token_arr[1]]['title'];
        $backend   = $backend ? '@' . I18N::T('people', $backend) : '';
        $token     = $token_arr[0] . $backend;
        return H($token);
    }

    public static function notif_classification_enable_callback($user)
    {
        return $user->atime;
    }

    public static function admin_notif_classification_enable_callback($user)
    {
        return $user->access('添加/修改所有成员信息')
            || $user->access('添加/修改下属机构成员的信息')
            || Q("$user<pi lab")->total_count();
    }

    public static function people_newsletter_content($e, $user)
    {

        $templates = Config::get('newsletter.template');
        $dtstart   = strtotime(date('Y-m-d')) - 86400;
        $dtend     = strtotime(date('Y-m-d'));

        $db       = Database::factory();
        $template = $templates['extra']['new_user'];
        $sql      = "SELECT id,name FROM user WHERE ctime>%d AND ctime<%d";
        $query    = $db->query($sql, $dtstart, $dtend);
        if ($query) {
            $peoples = $query->rows();
            if (count($peoples)) {
                $str .= V('people:newsletter/new_user', [
                    'peoples'  => $peoples,
                    'template' => $template,
                ]);
            }
        }
        if (strlen($str) > 0) {
            $e->return_value .= $str;
        }
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }

//        $perms['查看成员列表'] = 'on';
    }

    public static function user_is_active($e, $user)
    {
        $e->return_value = $user->atime > 0;
        return false;
    }

    public static function user_hide_sidebar($e, $user)
    {
        $e->return_value = $user->must_change_password();
        return false;
    }

    //获取邮件发送使用的email
    public static function get_binding_email($e, $user)
    {
        $e->return_value = $user->binding_email ?: $user->email;
        return false;
    }

    //获取手机通知发送使用的phone
    public static function get_binding_phone($e, $user)
    {
        $e->return_value = $user->binding_phone ?: $user->personal_phone;
    }

    public static function get_users_by_role($name = [])
    {

        if (!$name) {
            return false;
        }

        $now   = Date::time();
        $users = [];

        if (!is_array($name)) {
            $roles = [$name];
        }

        $default_roles = Config::get('roles.default_roles');
        $role_mts      = [];

        foreach (L('ROLES') as $role) {
            if ($role->id < 0) {
                $r = $default_roles[$role->id];

                if ($r) {
                    if (in_array($r['key'], (array) Config::get('people.disable_member_type'))) {
                        continue;
                    }
                    if (in_array($r['key'], ['current', 'past']) && !$GLOBALS['preload']['people.enable_member_date']) {
                        continue;
                    }
                    $mt_key              = $r['member_type_key'] ?: $r['name'];
                    $role_mts[$r['key']] = User_Model::get_members()[$mt_key];
                }
            }
        }

        foreach ($roles as $rid => $role_name) {
            $rid = $rid != 0 ? $rid : $role_name;

            //fix
            switch ($rid) {
                case ROLE_CURRENT_MEMBERS:
                    $rid = 'current';
                    break;
                case ROLE_PAST_MEMBERS:
                    $rid = 'past';
                    break;
                case ROLE_STUDENTS:
                    $rid = 'students';
                    break;
                case ROLE_TEACHERS:
                    $rid = 'teachers';
                    break;
            }

            $selector = 'user';

            switch ($rid) {
                case 'unactivated':
                    $selector .= '[atime=0]';
                    break;
                case 'current':
                    if ($GLOBALS['preload']['people.enable_member_date']) {
                        $selector .= "[dto=0,{$now}~]";
                    }
                    break;
                case 'past':
                    if ($GLOBALS['preload']['people.enable_member_date']) {
                        $selector .= "[dto!=0][dto<{$now}]";
                    }
                    break;
                case 'students':
                case 'teachers':
                    $mt = $role_mts[$rid];
                    if ($mt) {
                        reset($mt);
                        $mt_min = key($mt);
                        end($mt);
                        $mt_max = key($mt);
                        $selector .= "[member_type>=$mt_min][member_type<=$mt_max]";
                    } else {
                        $selector .= '[atime>0]';
                    }
                    break;
                default:
                    if (is_numeric($rid)) {
                        $selector = "role#{$rid} " . $selector;
                    }
                    break;
            }

            $users += Q($selector)->to_assoc('id', 'name');
        }

        return $users;
    }

	static function home_url($e, $user) {

		$home = $user->home ?: Config::get('lab.default_home');

		switch ($home) {
		case 'me':
			return '!people/profile/index.'.$user->id;
		case 'sbmenu':
			break;
		default:
			$url = Event::trigger('people.user.home_url', $user->home);
			if ($url) return $url;
			return '!people/profile/index.'.$user->id; // 默认返回个人页
		}
	}

	static function check_error_default_lab() {
		/* 检查默认实验室是否存在多的数据 */
		$name = Config::get('lab.name');
		$default_id = Lab::get('default_lab_id', NULL, NULL, TRUE);
		$labs = Q("lab[name={$name}|name=\"\"]:sort(id D)");
		if ($labs->total_count() > 1) {
			$has_user_labs = [];
			foreach ($labs as $lab) {
				if ( Q("{$lab} user")->total_count() ) {
					$has_user_labs[$lab->id] = $lab->id;
					if ($default_id != $lab->id) $default_id = $lab->id;
				}
			}
			$ids = join(',', $has_user_labs);
			foreach ( Q("lab[name={$name}]:not([id={$ids}])") as $l ) {
				$l->delete();
			}
			Lab::set('default_lab_id', $default_id);
		}
		/* 检查仪器实验室是否存在多的数据 */
		if ( Module::is_installed('labs') ) {
			$name = Config::get('equipment.temp_lab_name');
			$default_id = Lab::get('equipment.temp_lab_id', NULL, NULL, TRUE);
			$labs = Q("lab[name={$name}]:sort(id D)");
			if ($labs->total_count() > 1) {
				$has_user_labs = [];
				foreach ($labs as $lab) {
					if ( Q("{$lab} user")->total_count() ) {
						$has_user_labs[$lab->id] = $lab->id;
						if ($default_id != $lab->id) $default_id = $lab->id;
					}
				}
				$ids = join(',', $has_user_labs);
				foreach ( Q("lab[name={$name}]:not([id={$ids}])") as $l) {
					$l->delete();
				}
				Lab::set('equipment.temp_lab_id', $default_id);
			}
		}
	}

	static function access($e, $user, $params){
		$perm_name = $params[0];
		$skip = $params[1] ? : FALSE; // 增加可选是否能跳过管理所有内容的权限

		static $admin_tokens;

		if ($admin_tokens === NULL) {
			$admin_tokens = array_map("Auth::normalize", array_merge((array) Config::get('lab.admin', []), (array) Lab::get('lab.admin', [])));
		}

		if (in_array($user->token, $admin_tokens)) {
			$e->return_value = TRUE;
			return FALSE;
		}

		$now = time();

		switch($perm_name) {
		case '登录用户':
			$e->return_value = $user->id > 0;
			return FALSE;
		case '激活用户':
			$e->return_value = $user->is_active();
			return FALSE;
		case '不必修改密码':
			$e->return_value = !$user->must_change_password();
			return FALSE;
		case '过期用户':
			if ($GLOBALS['preload']['people.enable_member_date']) {
				$e->return_value = $user->id > 0 && $user->dto && $user->dto < $now;
				return FALSE;
			}
			$e->return_value = TRUE;
			return FALSE;

		case '当前用户':
			if ($GLOBALS['preload']['people.enable_member_date']) {
				$e->return_value = $user->id > 0 && (!$user->dto || $user->dto> $now);
			return FALSE;
			}
			$e->return_value = $user->id > 0;
			return FALSE;
		}

        if (self::perm_in_uno()) {

            /**
             * $config['添加/修改所有机构的仪器'] = [
                    'name' => '添加/修改仪器',
                    'object' => 'group',
               ];
             *
             * 调用方式不替换，仍然是user->access('添加/修改所有机构的仪器');
             * 这样如果是"添加/修改负责仪器"的时候，object应该是啥？
             *
             */
            $unoperms = Config::get('uno_perm');
            $unoperm = $unoperms[$perm_name] ?? ['name'=>$perm_name];
            $perm_name = $unoperm['name'];
            if ($unoperm['object'] == 'group')
                $object = $user->group;
            elseif ($unoperm['object'] == 'lab')
                $object = Q("{$user} lab")->current();
            else
                $object = O('tag_group',['type'=>'system']);

            if (!$object->id) {
                $object = Gateway::getRemoteGroupRootCached();
            }

            $perms = $user->uno_perms($object);
            //目前uno的情况是，如果用户A在c（组织机构为a-b-c）组织机构下，又在E'(组织机构为D'-E'-F')课题组下，那么A实际会在组织机构为a，b,c,D',E'下。这样A实际的组织机构应该为？
            //uno给A赋予a下的中心管理员权限，如果A最终同步到lims的groupid=c,那么在c下是中心管理员么（之前记得说不是，是配置错误的问题）？
            if ($perms['super_admin']) {
                $e->return_value = true;
                return false;
            }
            if ($skip) {
                $e->return_value = in_array($perm_name, $perms);
            } else {
                $e->return_value = in_array($perm_name, $perms) || in_array('管理所有内容', $perms);
            }
        } else {
            $perms = $user->perms();
            if ($skip) {
                $e->return_value = $perms[$perm_name];
            }
            else {
                $e->return_value = ($perms[$perm_name]) || ($perms['管理所有内容']);
            }
        }
        return FALSE;
	}

    public static function perm_in_uno()
    {
        return $GLOBALS['preload']['gateway.perm_in_uno'] && Module::is_installed('gateway');
    }

	public static function role_available($e, $role) {
		$default_roles = array_column(Config::get('roles.default_roles'), 'name');
		if (in_array($role->name, $default_roles)) {
			$e->return_value = FALSE;
			return FALSE;
		}
		else {
			$e->return_value = TRUE;
		}
	}

    public static function default_group($e, $user, $params=[]) {
		$e->return_value = $user->default_group_id;
	}

    public static function create_orm_tables()
    {
        if (!Lab::get('tag.member_type_id', 0)) {
            $root = Tag_Model::root('member_type');
            Q("tag[root={$root}]")->delete_all();

            //重新根据User_Model::$members生成tag
            foreach(User_Model::$members as $title => $sub) {
                $tag = O('tag');
                $tag->parent = $root;
                $tag->root = $root;
                $tag->name = $title;
                $tag->save();

                foreach($sub as $sub_title) {
                    $sub_tag = O('tag');
                    $sub_tag->root = $root;
                    $sub_tag->parent = $tag;
                    $sub_tag->name = $sub_title;
                    $sub_tag->save();
                }
            }
        }
    }
}
