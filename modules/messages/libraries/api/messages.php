<?php

/**
应用级别错误代码:
1000: 请求来源非法!
1001: 收件人不存在!
1002: 发件人不存在!
1003: 信息保存失败!
 **/
class API_Messages
{

    public function _auth($signed_token)
    {
        $msg_server = Config::get('messages.server');
        $rpc_token = Config::get('messages.rpc_token');

        return $signed_token === $rpc_token;
    }

    public function extract_users($signed_token, $receiver, $range)
    {
        $now = Date::time();

        if (!self::_auth($signed_token)) {
            throw new API_Exception('请求来源非法!', 1000);
        }

        list($start, $per_page) = $range;

        $ret = [];

        $id = $receiver['id'];
        switch ($receiver['type']) {
            case 'user':
                if (is_array($id)) {
                    if (is_array($id)) $id = implode(',', $id);
                    $ret = array_values(Q("user[id={$id}]")->limit($start, $per_page)->to_assoc('id', 'id'));
                }else {
                    $user = O('user', $id);
                    if ($user->id) {
                        $ret = [$user->id];
                    }
                }
                break;
            case 'all':
                $ret = array_values(Q('user')->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
            case 'group':
                $ret = array_values(Q('(tag_group#' . $id . ') user')->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
            case 'role':
                $role = O('role', (int) $id);
                if ($id > 0 && $role->weight > 0) {
                    $role = O('role', $id);
                    if ($role->weight < 0) {
                        $now = time();
                        switch ($role->name) {
                            case '目前成员':
                                $selector = "user[dto!=0][dto<{$now}]";
                                break;
                            case '过期成员':
                                $selector = "user[dto!=0][dto<{$now}]";
                                break;
                            default:
                                $mt = User_Model::get_members()[$role->name];
                                if ($mt) {
                                    reset($mt);
                                    $mt_min = key($mt);
                                    end($mt);
                                    $mt_max = key($mt);
                                    $selector = "user[member_type>=$mt_min][member_type<=$mt_max]";
                                }
                                break;
                        }
                        $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                    } else {
                        $ret = array_values(Q("role[id={$id}] user")->limit($start, $per_page)->to_assoc('id', 'id'));
                    }
                } else {
                    $default_roles = Config::get('roles.default_roles');

                    $default_roles_key_name = []; //存储默认role的key和name

                    foreach ($default_roles as $default_role) {
                        $default_roles_key_name[($default_role['key'])] = $default_role['name'];
                    }

                    $key = array_search($role->name, $default_roles_key_name);

                    $role_mts = []; // 角色与member_type的对应数组
                    $weight = 0;
                    foreach (L('ROLES') as $role) {
                        if ($role->weight < 0) {
                            $r = $default_roles[$role->weight];
                            if ($r) {
                                if (in_array($r['key'], (array) Config::get('people.disable_member_type'))) {
                                    continue;
                                }
                                if (in_array($r['key'], ['current', 'past']) && !$GLOBALS['preload']['people.enable_member_date']) {
                                    continue;
                                }
                                $mt_key = $r['member_type_key'] ?: $r['name'];
                                $role_mts[$r['key']] = User_Model::get_members()[$mt_key];
                            }
                        }
                        $weight++;
                    }

                    switch ($key) {
                        case 'current':
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                $selector = "user[dto=0,{$now}~]";
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'past':
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                $selector = "user[dto!=0][dto<{$now}]";
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'lab_pi':
                            $selector = "lab<pi user[atime][dto=0|dto<{$now}]";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'equipment_charge':
                            $selector = "equipment<incharge user[atime][dto=0|dto<{$now}]";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'need_help':
                            $selector = "role[id={$id}] user";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        default:
                            $mt = $role_mts[$key];

                            if ($mt) {
                                reset($mt);
                                $mt_min = key($mt);
                                end($mt);
                                $mt_max = key($mt);
                                $selector = "user[member_type>=$mt_min][member_type<=$mt_max]";
                            } else {
                                $selector = 'user';
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                    }
                }
                break;
            case 'lab':
                $lab = O('lab', $id);
                $ret = array_values(Q("$lab user")->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
        }

        return $ret;
    }

    public function send($signed_token, $receiver_id, $message_form)
    {
        if (!self::_auth($signed_token)) {
            throw new API_Exception('请求来源非法!', 1000);
        }

        $receiver = O('user', $receiver_id);
        if (!$receiver->id) {
            throw new API_Exception('收件人不存在!', 1001);
        }

        $sender = O('user', $message_form['sender']);

        if (!$sender->id) {
            $message_form['body'] = $message_form['body'] . "\n\n" . I18N::T('messages', "Labscout LIMS Team\n\n[系统消息, 请勿回复]");
        }

        $message = O('message');
        $message->title = (string) (new Markup($message_form['title']));
        $message->body = $message_form['body'];
        $message->sender = $sender;
        $message->receiver = $receiver;

        if ($message->save()) {

            Log::add(strtr('[messages] %user_name[%user_id] 添加了新消息 %message_title[%message_id]', [
                '%user_name' => $sender->name,
                '%user_id' => $sender->id,
                '%message_title' => $message->title,
                '%message_title' => $message->id,
            ]), 'journal');

            //messages还需要再发一次Log
            Log::add(strtr('[messages] %sender_name[%sender_id]发送消息给%receiver_name[%receiver_id], 主题[%subject]', [
                '%sender_name' => $sender->name,
                '%sender_id' => $sender->id,
                '%receiver_name' => $receiver->name,
                '%receiver_id' => $receiver->id,
                '%subject' => $message->title,
            ]), 'messages');

            if ($message_form['email'] || $message_form['sms']) {
                Event::trigger('message.send.way.submit', $message, $message_form);
            }

            return $message->id;
        }

        throw new API_Exception('信息保存失败!', 1003);
    }

    public static function getList($e, $params, $data, $query)
    {
        if (!$query['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $query['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }
        $start = (int) $query['st'] ?: 1;
        if ($start < 0) {
            throw new Exception('page error', 400);
        }
        $per_page = (int)$query['pp'] ?: 15;
        $start = $start - ($start % $per_page);
        $query = $query['query'];

        if($query) {
            $query = Q::quote($query);
            $selector = "message[title*={$query}][sender]";
        }
        else {
            $selector = 'message[sender]';
        }
        if(isset($query['is_read']) && $query['is_read']){
            $is_read = $query['is_read'] == 1 ? 0 : 1;
            $selector .= "[is_read={$is_read}]";
        }
        $selector .= "[receiver={$me}]:sort(is_read A, ctime D)";
        $messages = Q($selector);

        $messages = $messages->limit($start, $per_page);
        $not_reads = Q("message[receiver={$me}][is_read=0]")->total_count();


        $res = ['start' => $start?:0, 'per_page' => $per_page,'total' => $messages->total_count(),'not_read_nums' => $not_reads,  'items' => []];
        foreach ($messages as $message) {
            $data = self::formatMessage($message);
            $res['items'][] = $data;
        }
        $e->return_value = new ArrayIterator($res);
        return false;
    }

    public static function message_patch($e, $params, $data, $query)
    {
        if (!$data['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $data['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }
        if (!is_array($data['messages'])) {
            $data['messages'] = [$data['messages']];
        }

        $messages = [];
        foreach ($data['messages'] as $mId) {
            $message = O('message', $mId);
            if (!$message->id) {
                throw new Exception('message not found', 404);
            }
            if ($message->receiver->id != $me->id) {
                throw new Exception('forbbiden', 403);
            }
            $messages[] = $message;
        }

        $res = [];
        foreach ($messages as $message) {
            if (!$message->is_read) {
                $message->is_read = true;
                $message->save();
            }
            $res[] = self::formatMessage($message);
        }
        $e->return_value = new ArrayIterator($res);
        return false;
    }

    public static function getData($e, $params, $data, $query)
    {
        if (!$query['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $query['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }

        $message = O('message', $query['message']);
        if ($message->receiver->id != $me->id) {
            throw new Exception('forbbiden', 403);
        }
        $res = self::formatMessage($message);
        $e->return_value = new ArrayIterator($res);
        return false;
    }


    public static function deleteMessage($e, $params, $data, $query)
    {
        if (!$query['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $query['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }
        if (!is_array($query['messages'])) {
            $query['messages'] = [$query['messages']];
        }

        $messages = [];
        foreach ($query['messages'] as $mId) {
            $message = O('message', $mId);
            if (!$message->id) {
                throw new Exception('message not found', 404);
            }
            if ($message->receiver->id != $me->id) {
                throw new Exception('forbbiden', 403);
            }
            $messages[] = $message;
        }

        foreach ($messages as $message) {
            $message->delete();
            Log::add(strtr('[messages] %user_name[%user_id] 删除了消息 %message_title[%message_id]', [
                '%user_name'     => $me->name,
                '%user_id'       => $me->id,
                '%message_title' => $message->title,
                '%message_id'    => $message->id,
            ]), 'journal');
        }
        $e->return_value = new ArrayIterator(['status' => 'success']);
        return false;
    }

    public static function replyMessage($e, $params, $data, $query)
    {
        if (!$data['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $data['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }
        if (!$data['message']) {
            throw new Exception('message not found', 400);
        }
        $message = O('message', $data['message']);
        if (!$message->id) {
            throw new Exception('message not found', 400);
        }
        if (!$message->sender->id) {
            throw new Exception('system message can not reply', 400);
        }
        if (!$data['receiver']) {
            throw new Exception('receiver not found', 400);
        }

        $receiver = O('user', $data['receiver']);

        if (!$receiver->id) {
            throw new Exception('receiver not found', 400);
        }
        if (!$data['title'] || !$data['body']) {
            throw new Exception('must fill title and body', 400);
        }

        $message           = O('message');
        $message->title    = $data['title'];
        $message->body     = $data['body'];
        $message->receiver = $receiver;
        $message->sender   = $me;
        $message->save();

        Log::add(strtr('[messages] %user_name[%user_id] 回复了 %receiver_name 的消息', [
            '%user_name'     => $me->name,
            '%user_id'       => $me->id,
            '%receiver_name' => $message->receiver->name,
        ]), 'journal');

        $e->return_value = new ArrayIterator(['status' => 'success']);
        return false;
    }

    public static function sendMessage($e, $params, $data, $query)
    {

        // title body email sms
        // Cache::L('ME', $me)
        if (!$data['user']) {
            throw new Exception('user not found', 400);
        }
        $me = O('user', $data['user']);
        if (!$me->id) {
            throw new Exception('user not found', 400);
        }
        Cache::L('ME', $me);
        if (!$data['title'] || !$data['body']) {
            throw new Exception('must fill title and body', 400);
        }
        $form = [
            'title' => $data['title'],
            'body' => $data['body'],
            'email' => $data['email'],
            'sms' => $data['sms'],
        ];
        $receivers_type = $data['receivers_type'];
        if ($receivers_type == 'all') {
            Message::send($form, 'all');
        } elseif ($receivers_type == 'user') {
            $receiver_users = (array) $data['receiver_ids'];
            if (!count($receiver_users)) {
                throw new Exception('收件人不能为空!', 400);
            }

            if (count($receiver_users) == 1) {
                foreach ($receiver_users as $key) {
                    Message::send($form, 'user', $key);
                }
            } else {
                $batch_id = Message::start_batch();
                foreach ($receiver_users as $key ) {
                    Message::send($form, 'user', $key, $batch_id);
                }
                Message::finish_batch($batch_id);
            }
        } elseif ($receivers_type == 'group') {
            $receiver_groups = (array) $data['receiver_ids'];
            if (!count($receiver_groups)) {
                throw new Exception('收件组织机构不能为空!', 400);
            }
            if (count($receiver_groups) == 1) {
                foreach ($receiver_groups as $key) {
                    Message::send($form, 'group', $key);
                }
            } else {
                $batch_id = Message::start_batch();
                foreach ($receiver_groups as $key) {
                    Message::send($form, 'group', $key, $batch_id);
                }
                Message::finish_batch($batch_id);
            }
        } elseif ($receivers_type == 'role') {
            $roles = (array) $data['receiver_ids'];
            if (!count($roles)) {
                throw new Exception('收件角色不能为空!', 400);
            }
            if (count($roles) == 1) {
                foreach ($roles as $key) {
                    Message::send($form, 'role', $key);
                }
            } else {
                $batch_id = Message::start_batch();
                foreach ($roles as $key) {
                    Message::send($form, 'role', $key, $batch_id);
                }
                Message::finish_batch($batch_id);
            }

        } elseif ($receivers_type == 'lab' && Module::is_installed('labs')) {
            $labs = (array) $data['receiver_ids'];

            if (!count($labs)) {
                throw new Exception('收件实验室不能为空!', 400);
            }
            if (count($labs) == 1) {
                foreach ($labs as $key) {
                    Message::send($form, 'lab', $key);
                }
            } else {
                $batch_id = Message::start_batch();
                foreach ($labs as $key) {
                    Message::send($form, 'lab', $key, $batch_id);
                }
                Message::finish_batch($batch_id);
            }
        }

        $e->return_value = new ArrayIterator(['status' => 'success']);
        return false;
    }



    private static function formatMessage($message) {
        return [
            //'url' => $message->url(),
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->name
            ],
            'title' => $message->title,
            'id' => $message->id,
            'body' => $message->body,
            'is_read' => $message->is_read,
            'ctime' => $message->ctime,
            'mtime' => $message->mtime,
        ];
    }

}
