<?php
class Sync_User extends Sync_Handler
{
    public static $publish_keys = [
        'token',
        'email',
        'name',
        'card_no',
        'card_no_s',
        'dfrom',
        'dto',
        'weight',
        'atime',
        'ctime',
        'mtime',
        'hidden',
        'name_abbr',
        'phone',
        'address',
        'group',
        'member_type',
        'creator',
        'auditor',
        'ref_no',
        'binding_email',
        'gapper_id',
        'outside',
        'nl_cat_vis',
        'address_abbr',
        // 如果creator或者auditor是自己同时, 又改了名字..会死循环
        // 同时abbr会saved自动更新的
        // 'creator_abbr',
        // 'auditor_abbr',
        'organization',
        'gender',
        'major',
        'undeletable'
    ];

    public function uuid()
    {
        $user = $this->object;
        if ($user->token == 'genee|database') {
            return 'genee|database';
        }
        if ($user->token && !$user->uuid) {
            return uniqid(LAB_ID, true);
        }
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data)
    {
        // 临时用户不同步
        if (!$this->object->token) {
            return false;
        }

        foreach (self::$publish_keys as $key) {
            if (isset($new_data[$key])) {
                if (is_object($new_data[$key]) && $new_data[$key]->id != $old_data[$key]->id) {
                    return true;
                } elseif (is_scalar($new_data[$key]) && $new_data[$key] != $old_data[$key]) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function format()
    {
        $user = $this->object;
        $params = [
            'token' => $user->token,
            'email' => $user->email ,
            'name' => $user->name,
            'card_no' => $user->card_no,
            'card_no_s' => $user->card_no_s,
            'dfrom' => $user->dfrom,
            'dto' => $user->dto,
            'weight' => $user->weight,
            'atime' => $user->atime,
            'ctime' => $user->ctime,
            'mtime' => $user->mtime,
            'hidden' => $user->hidden,
            'name_abbr' => $user->name_abbr,
            'phone' => $user->phone,
            'address' => $user->address,
            'group_id' => $user->group->uuid,
            'member_type' => $user->member_type,
            'creator_id' => $user->creator->uuid,
            'auditor_id' => $user->auditor->uuid,
            'ref_no' => $user->ref_no,
            'binding_email' => $user->binding_email,
            'gapper_id' => $user->gapper_id,
            'outside' => $user->outside,
            'nl_cat_vis' => $user->nl_cat_vis,
            'address_abbr' => $user->address_abbr,
            'creator_abbr' => $user->creator_abbr,
            'auditor_abbr' => $user->auditor_abbr,
            'organization' => $user->organization,
            'gender' => $user->gender,
            'major' => $user->major,
            'undeletable' => $user->undeletable,
        ];

        // 本地用户，账号密码也要同步
        list($token, $backend) = explode('|', $user->token);
        if ($backend == 'database') {
            $db = Database::factory();
            $SQL = "SELECT `password` FROM `_auth` WHERE `token` = '%s'";
            $params['auth'] = $db->value($SQL, $token);
        }

        return $params;
    }

    public function handle($params)
    {
        $user = $this->object;
        $user->token = $params['token'];
        $user->email = $params['email'];
        $user->name = $params['name'] ? : '';
        $user->card_no = $params['card_no'];
        $user->card_no_s = $params['card_no_s'];
        $user->dfrom = $params['dfrom'] ? : 0;
        $user->dto = $params['dto'] ? : 0;
        $user->weight = $params['weight'] ? : 0;
        $user->atime = $params['atime'] ? : 0;
        $user->ctime = $params['ctime'] ? : 0;
        $user->mtime = $params['mtime'] ? : 0;
        $user->hidden = $params['hidden'] ? : 0;
        $user->name_abbr = $params['name_abbr'] ? : '';
        $user->phone = $params['phone'] ? : '';
        $user->address = $params['address'] ? : '';
        $group = O('tag', ['uuid' => $params['group_id']]);
        if ($group->id) {
            $user->group = $group;
        } else {
            $receive_topics = Config::get('sync.receive_topics');
            
            /**
             * 组织机构同步 更新$user->group
             * 组织机构不同步 不更新$user->group
             */
            if (in_array('tag.save', $receive_topics)) {
                $user->group = Tag_Model::root('group');
            }
        }
        $user->member_type = $params['member_type'];
        $creator = O('user', ['uuid' => $params['creator_id']]);
        $auditor = O('user', ['uuid' => $params['auditor_id']]);
        $user->creator = $creator;
        $user->auditor = $auditor;
        $user->ref_no = $params['ref_no'];
        $user->binding_email = $params['binding_email'] ? : '';
        $user->gapper_id = $params['gapper_id'];
        $user->outside = $params['outside'] ? : 0;
        $user->nl_cat_vis = $params['nl_cat_vis'] ? : '';
        $user->address_abbr = $params['address_abbr'] ? : '';
        $user->creator_abbr = $params['creator_abbr'] ? : '';
        $user->auditor_abbr = $params['auditor_abbr'] ? : '';
        $user->organization = $params['organization'];
        $user->gender = $params['gender'];
        $user->major = $params['major'];
        $user->undeletable = $params['undeletable'];

        if ($user->save()) {
            if(!$params['auth']){
                return true;//防止密码重复被设置为空
            }
            // 本地用户，账号密码也要同步
            list($token, $backend) = explode('|', $params['token']);
            if ($backend == 'database') {
                $db = Database::factory();

                $auth_schema = [
                    'fields' => [
                        'token'=>['type'=>'varchar(80)', 'null'=>false, 'default'=>''],
                        'password'=>['type'=>'varchar(100)', 'null'=>false, 'default'=>''],
                    ],
                    'indexes' => [
                        'primary'=>['type'=>'primary', 'fields'=>['token']],
                    ]
                ];

                class_exists('Auth');
                $db->prepare_table('_auth', $auth_schema);

                $opt = Config::get('auth.backends')['database'];
                $table = $opt['database.table'] ?: '_auth';
                $SQL = "SELECT `password` FROM `%s` WHERE `token` = '%s'";
                $auth = $db->value($SQL, $table, $token);
                if ($auth) {
                    $SQL = "REPLACE INTO `%s` (`token`, `password`) VALUES('%s', '%s')";
                    $db->query($SQL, $table, $token, $params['auth']);
                } else {
                    $SQL = "INSERT INTO `%s` VALUES ('%s', '%s')";
                    $db->query($SQL, $table, $token, $params['auth']);
                }
            }

            if ($group->id) {
                $group->connect($user);
            }

            //如果上了app，那么需要同步
            if (Module::is_installed('yiqikong')){
                Event::trigger('user.after_role_change', $user, [], []);
            }
        }
    }
}
