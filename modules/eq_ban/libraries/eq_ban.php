<?php
class EQ_Ban
{
    static function banned_cannot_use($e, $equipment, $params)
    {
        $me = $params[0];
        // 多课题组模式下，eq_banned的lab字段为空时为全局封禁
        // 单课题组不考虑
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab = "[!lab]";
        }
        if (Q("$me eq_banned[!object]$lab")->total_count()
            || Q("$me eq_banned[object]$lab tag_group.object $equipment")->total_count()) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_ban', '您已被加入黑名单，不可使用仪器。'));
            $e->return_value = true;
            return false;
        }
        //判断仪器黑名单设置
        $allowLabs = self::get_eq_unbanned_lab($me, $equipment);
        if (empty($allowLabs)) {
            //机主允许使用
            if(Switchrole::user_select_role() != '仪器负责人' || !Equipments::user_is_eq_incharge($me, $equipment)){
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_ban', '您已被加入黑名单，不可使用仪器。'));
                $e->return_value = TRUE;
                return FALSE;
            }
        }
    }

    static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $me = L('ME');
        $equipment = $view->calendar->parent;

        if ((($me->access('为所有仪器添加预约'))
            || ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
            || ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment)))
        ) {
            $result = true;
        } else {
            $e = Event::factory('permission_check');
            call_user_func_array('EQ_Ban::banned_cannot_use', [$e, $equipment, [$me]]);
            if ((bool)$e->return_value) {
                $result = false;
                // 就为了显示description
                $atime = false;
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $lab = "[!lab]";
                }
                foreach (Q("$me eq_banned[!object]$lab") as $eq_banned) {
                    if ($atime !== 0 && $atime < $eq_banned->atime) {
                        $atime = $eq_banned->atime;
                    }
                }
                foreach (Q("$equipment tag_group<object eq_banned[user=$me][object]$lab") as $eq_banned) {
                    if ($atime !== 0 && $atime < $eq_banned->atime) {
                        $atime = $eq_banned->atime;
                    }
                }
                foreach (Q("$equipment<object eq_banned[user=$me][object]") as $eq_banned) {
                    if ($atime !== 0 && $atime < $eq_banned->atime) {
                        $atime = $eq_banned->atime;
                    }
                }

                $date = $atime ? date('Y.m.d', $atime) : I18N::T('eq_ban', '永久');
                $description = I18N::T('eq_ban', '处于封禁期，到期时间: %date', [
                    '%date' => $date
                ]);
                // 就为了显示description end
            } else {
                $result = true;
            }
        }

        $check_list = $view->check_list;
        $check_list[] = [
            'title' => I18N::T('eq_ban', '黑名单'),
            'result' => $result,
            'description' => $description
        ];
        $view->check_list = $check_list;
    }

    static function is_user_banned($user, $equipment = NULL)
    {
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab = "[!lab]";
        }
        if ($equipment === NULL) {
            return (bool)Q("eq_banned[user={$user}][!object]$lab")->total_count();
        } elseif (is_object($equipment) && $equipment->name() === 'equipment') {
            if(Q("$user eq_banned[!object]$lab")->total_count()
                || Q("$user eq_banned[object]$lab tag_group.object $equipment")->total_count()){
                return true;
            }
        }
        $allowLabs = self::get_eq_unbanned_lab($user, $equipment);
        if (empty($allowLabs)) {
            //机主允许使用
            if(!Equipments::user_is_eq_incharge($user, $equipment)){
                return TRUE;
            }
        }
        return false;
    }

    static function user_get_labs_selector($e, $selector, $pre_selector, $user, $equipment)
    {
        $me = L('ME');
        if ($me->access('管理所有内容') || Equipments::user_is_eq_incharge($me, $equipment)) {
            return true;
        }
        // 全局封禁的课题组
        $selector .= ":not(eq_banned[!object][user=$user] lab)";
        // 全局封禁的人员的所有课题组
        // $selector .= ":not(eq_banned[!object][!lab_id] $user lab)";
        if ($equipment->id) {
            // 仪器封禁的人员
            // $selector .= ":not($equipment<object eq_banned[!lab_id] $user lab)";
            // 仪器所在平台封禁的课题组
            $selector .= ":not($equipment tag<object eq_banned[user=$user] lab)";
            // 仪器所在平台封禁的人员所在的课题组
            // $selector .= ":not($equipment tag<object eq_banned[!lab_id] $user lab)";
        }
        $e->return_value = $selector;
        return true;
    }

    static function update_abbr($e, $object, $new_data)
    {
        if (!class_exists('PinYin')) return TRUE;

        $abbr = '';
        if ($object->name() == 'eq_banned' && $new_data['object']) {

            $obj = $new_data['object'];
            if ($obj->name() != 'equipment' && $obj->name() != 'tag') {
                return true;
            }

            $abbr = PinYin::code($obj->name);
            $object->obj_abbr = $abbr;
        }

        return true;
    }

    static function user_deleted($e, $user)
    {
        if (!$user->id) {
            return true;
        }
        $user_v = O('user_violation', ['user' => $user]);
        if ($user_v->id) {
            $user_v->delete();
        }
        return true;
    }

    static function record_save($e, $object)
    {
        $me = L('ME');
        $record = O('eq_banned_record');
        $object->object ? $record->object = $object->object : '';
        $record->obj_abbr = $object->obj_abbr;
        $record->lab = $object->lab;
        $record->reason = $object->reason;
        $record->user = $object->user;
        $record->ctime = $object->ctime;
        $record->atime = $object->atime;
        $me ? $record->unsealing_user = $me : '';
        $record->unsealing_ctime = time();
        $record->save();
    }

    /*
     * @Date:2019-07-25 15:15:33
     * @Author: LiuHongbo
     * @Email: hongbo.liu@geneegroup.com
     * @Description:深圳大学土木学院【定制】迟到/ 爽约用户被拉入黑名单后，
     * 系统自动删除该用户封禁时间之后的所有仪器预约记录。预约记录删除后，其他用户可在该时段预约。
     */
    public static function delete_reserv($e, $object)
    {
        $ban_time = $object->ctime ?: time();
        $user = $object->user;
        //由于预约记录删除时都绑定了trigger，做了后置操作，所以只能通过这种方式一条条删除，否则可能会有所遗漏
        $reservs = Q("eq_reserv[user=$user][dtstart>={$ban_time}]");
        foreach ($reservs as $reserv) {
            $reserv->delete();
        }
    }
   
    /**
     * 获取当前用户没有被禁的课题组信息
     * @param $user
     */
    public static function get_eq_unbanned_lab($user, $equipment,$params = [])
    {
        $userLabs = [];
        $labs = $params['labs'] ?? Q("{$user} lab");
        if ($labs->total_count()) {
            foreach ($labs as $lab)
                $userLabs[$lab->id] = $lab->name;
        }

        //如果是仪器黑名单，只要存在非黑名单的课题组即可
        $banneds = Q("eq_banned[object][user={$user}][object={$equipment}]");
        $bannedLabs = [];
        if ($banneds->total_count()) {
            foreach ($banneds as $banned) {
                if (!$banned->lab->id) {
                    //lab_id为0则代表全局仪器封禁，不区分课题组
                    return [];
                }
                $bannedLabs[$banned->lab->id] = $banned->lab->name;
            }
            return @array_diff($userLabs, $bannedLabs);
        }
        return $userLabs;
    }

    static function feedback_no_project_view($e) {
        $e->return_value .= V('eq_ban:equipment/feedback_no_project');
    }
}
