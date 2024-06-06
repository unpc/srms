<?php
class User_API
{
    public static function users_get($e, $params, $data, $query)
    {
        if ($query['identity']) {
            $identity = $query['identity'];
            $user = Event::trigger('get_user_by_username', $identity);
            $users = [$user->id => self::user_format($user)];
        } else {
            $selector = "user";
            if ($query['labId']) {
                $labId = $query['labId'];
                $lab = O("lab", $labId);
                if ($lab->id) {
                    $selector = "{$lab} {$selector}";
                } else {
                    $selector .= '[id=0]';
                }
            }
            if ($query['name']) {
                $name = Q::quote($query['name']);
                $selector .= "[name*={$name}]";
            }
            if ($query['ref_no']) {
                $ref_no = Q::quote($query['ref_no']);
                $selector .= "[ref_no*={$ref_no}]";
            }
            if ($query['group_id']) {;
                $group = O('tag_group', intval($query['group_id']));
                if ($group->id) {
                    $pre_selectors['group'] = "{$group}";
                }
            }
            if (count($pre_selectors) > 0) {
                $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
            }
            $total = $pp = Q("$selector")->total_count();
            $start = (int) $query['st'] ?: 0;
            $per_page = (int) $query['pp'] ?: 30;
            $start = $start - ($start % $per_page);
            $selector .= ":limit({$start},{$per_page})";

            foreach (Q($selector) as $user) {
                $users[$user->id] = self::user_format($user);
            }
        }
        $ret = [
            'items' => array_values($users),
        ];
        $ret['total'] = $total ?: count($ret['items']);
        $e->return_value = $ret;
        return;
    }

    public static function user_get($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        if (!$user->id) {
            throw new Exception('user not found', 404);
        }
        $e->return_value = self::user_format($user);
        return;
    }
    public static function get_user_by_id($e, $id)
    {
        $user = O('user', $id);
        if ($user->id) {
            $e->return_value = $user;
            return false;
        }
    }

    public static function get_user_by_username($e, $userName)
    {
        // $userName = 'ldap/ctxtest01@bgic.com';
        if (preg_match_all('/^(?<source>.*?)\/(?<identity>.*?)$/i', $userName, $matches)) {
            if ($matches['source'] && $matches['identity']) {
                $server = Config::get('gateway.server');
                $request_url = $server['url'] . "user";
                $token = Gateway::getToken();
                $options = [
                    'headers' => [
                        'X-Gapper-OAuth-Token' => $token,
                    ],
                    'query' => [
                        'source' => $matches['source'],
                        'identity' => $matches['identity'],
                    ],
                ];
                $result = Gateway::exec($request_url, 'GET', $options);
                $e->return_value = Q("user[gapper_id={$result['id']}][atime]")->current();
                return;
            }
        }
        if (preg_match_all('/^(?<gapper_id>\d+)$/i', $userName, $matches)) {
            if ($matches['gapper_id']['0']) {
                $gapper_id = $matches['gapper_id']['0'];
                $e->return_value = Q("user[gapper_id={$gapper_id}]")->current();
            }
        }
    }

    public static function user_format($user)
    {
        $labs = [];
        foreach (Q("{$user} lab") as $lab) {
            $labs[$lab->id] = [
                'id' => $lab->id,
                'name' => $lab->name,
            ];
        }

        $groups = [];
        if ($user->group->id) {
            $groups[$user->group->id] = [
                "id" => $user->group->id,
                "name" => $user->group->name,
            ];
        }

        $icon_file = $user->icon_file(128);
        if ($icon_file) {
            $avatar = Config::get('system.base_url') . Cache::cache_file($icon_file, true) . '?_=' . $user->mtime;
        }

        //获取人员角色，前端要求
        $privileges = [];
        if($user->access('管理所有内容')) $privileges[] = 'admin';
        return [
            'id' => (int) $user->id,
            'user' => (int) $user->id,
            'name' => (string) $user->name,
            'ref_no' => (string) $user->ref_no,
            'phone' => (string) $user->phone,
            'email' => (string) $user->email,
            'card' => $user->card_no,
            'avatar' => $avatar,
            'identities' => [
                'ldap' => '',
            ],
            'privileges' => $privileges,
            'labs' => $labs,
            'groups' => $groups
        ];
    }

    public static function user_info_get($e, $params, $data, $query)
    {
        if (isset($query['id'])) {
            $user = O('user', $query['id']);
            if (!$user->id) {
                $user = O('user', ['gapper_id' =>  $query['id']]);
            }
        } else if (isset($query['card'])) {
            $user = O('user', ['card_no' => $query['card']]);
        }
        if (!$user->id) {
            throw new Exception('user not found', 404);
        }
        $e->return_value = self::user_format($user);
        return;
    }

    public static function user_card_get($e, $params, $data, $query)
    {
        $user = O('user', $query['id']);
        if (!$user->id) {
            throw new Exception('user not found', 404);
        }
        $e->return_value = [
            $user->id => (string) $user->card_no
        ];
        return;
    }
}
