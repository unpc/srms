<?php

interface Notification_Handler
{

    public static function send($sender, $receivers, $title, $body);
}

class Notification
{
    /*
     * $conf_key 消息模版名字
     * $receiver 接收者
     * $params   参数
     * $sender   发件者
     */

    static $handler = [];

    static function start_batch()
    {

        $local_rpc_server = Config::get('system.local_api', URI::url('/api'));
        $rpc_token        = Config::get('notification.rpc_token');
        $options          = [
            'extract_api'  => [
                'url'    => $local_rpc_server,
                'method' => 'notification/extract_users',
                'token'  => $rpc_token,
            ],
            'dispatch_api' => [
                'url'    => $local_rpc_server,
                'method' => 'notification/send',
                'token'  => $rpc_token,
            ],
        ];

        $msg_server = Config::get('notification.server');

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri'    => $msg_server['addr'],
                'http_errors' => false,
                'timeout'     => $msg_server['timeout'],
            ]);

            $return = $client->post('start_batch', [
                'form_params' => [
                    'options' => $options,
                ],
            ])->getBody()->getContents();
        } catch (Exception $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();

            Log::add("[exception] $message", 'rest');
        }

        return $return;
    }

    static function finish_batch($batch_id)
    {
        $local_rpc_server = Config::get('system.local_api', URI::url('/api'));

        $msg_server = Config::get('notification.server');

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri'    => $msg_server['addr'],
                'http_errors' => false,
                'timeout'     => $msg_server['timeout'],
            ]);

            $return = $client->post('finish_batch', [
                'form_params' => [
                    'batch_id' => $batch_id,
                ],
            ])->getBody()->getContents();
        } catch (Exception $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();
            Log::add("[exception] $message", 'rest');
        }

        return $return;
    }

    // Notification::send() 的 $sender 不推荐使用, 因为 Notification 应该是由系统发出的,
    // 目前程序中无使用此参数的地方, 此参数已考虑删除 (xiaopei.li@2012-10-12)
    static function send($conf_key, $receiver, $params = null, $sender = null, $batch_id = null)
    {
        if (defined('DISABLE_NOTIFICATION')) {
            return;
        }

        if (is_object($receiver)) {
            if (!$receiver->id) {
                return;
            }

            $rpc_receiver = [
                'type' => $receiver->name(), // user, tag, role
                'id'   => $receiver->id,
            ];
        } elseif (is_string($receiver)) {
            $rpc_receiver = [
                'type' => $receiver,
            ];
        } elseif (is_array($receiver) && isset($receiver['type'])) {
            $rpc_receiver = $receiver;
        } else {
            // 不支持的 receiver 类型
            return;
        }

        $arr = explode('|', $conf_key);
        if (!Config::get('notification.' . $conf_key) && !Config::get('notification.' . $arr[0])) {
            // 不存在的 notification
            if ($arr[0] != '#VIEW#') {
                return;
            }
            $locale                = Config::get('system.locale');
            list($category, $path) = explode(':', $arr[1], 2);
            $_path                 = Core::file_exists(VIEW_BASE . '@' . $locale . '/' . $path . VEXT, $category);
            if (!$_path) {
                $_path = Core::file_exists(VIEW_BASE . $path . VEXT, $category);
            }
            if (!$_path) {
                return;
            }
        }
        $notification = [
            'conf_key' => $conf_key,
            'params'   => $params,
            'sender'   => $sender->id,
            'locale'   => Config::get('system.locale', 'zh_CN'),
        ];

        $noti_server = Config::get('notification.server');
        $send_to_uno = Module::is_installed('gateway') && Module::is_installed('uno') && Config::get('notification.send_to_uno');//3.30先发使用uno的消息中心

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri'    => $noti_server['addr'],
                'http_errors' => false,
                'timeout'     => $noti_server['timeout'],
            ]);
            if ($send_to_uno) {
                $rest = new \GuzzleHttp\Client([
                    'base_uri' => Config::get('gateway.domain_url') . 'messages/postbox/',
                    'http_errors' => false,
                    'timeout' => 5
                ]);

                $template = Notification::get_template($conf_key);
                $i18n = $template['i18n_module'] ?: 'application';
                list($title, $body) = Notification::symbol_to_markup(
                    [
                        I18N::T($i18n, $template['title']),
                        I18N::T($i18n, $template['body'])
                    ],
                    $params,
                    $receiver
                );


                $via = ['local'];
                $handlers = Notification::get_handlers();
	            foreach ($handlers as $handler) {
	                if (!Notification::enable_send($conf_key, $handler)) continue;
	                if (!Notification::enable_receive($conf_key, $handler, $receiver)) continue;
	                $handler_info = Notification::get_handler_info($handler);
                    if ($handler_info['class'] == 'Notification_Email') {
                        $via['email'] = ['email_template' => strip_tags(new Markup(stripslashes($body)), TRUE)];
                    }
                }

                $params = [
                    'fromToken' => Gateway::getToken(),
                    'entry' => '',
                    'title' => strip_tags(new Markup(stripslashes($title)), TRUE),
                    'body' => strip_tags(new Markup(stripslashes($body)), TRUE),
                    'via' => $via,
                ];

                if ($receiver->id && $receiver->gapper_id) {
                    $params['to'] = 'user/' . $receiver->gapper_id;
                }
                if ($sender->id && $sender->gapper_id) {
                    $params['from'] = 'user/' . $sender->gapper_id;
                }

                $response = $rest->post(
                    'api/v1/message',
                    [
                        'form_params' => $params,
                    ]
                );
                Log::add(strtr('[messages] %sender_name[%sender_id]发送uno消息给%receiver_name[%receiver_id], 主题[%subject], response: [%code] %response', [
                    '%sender_name' => $sender->id ? $sender->name : '系统',
                    '%sender_id' => $sender->id ?: '--',
                    '%receiver_name' => $receiver->name,
                    '%receiver_id' => $receiver->id,
                    '%subject' => $title,
                    '%code' => $response->getStatusCode(),
                    '%response' => $response->getBody()->getContents()
                ]), 'messages');
                return true;
            }

            if ($batch_id) {
                // 批量发送
                $client->post('batch_send', [
                    'form_params' => [
                        'batch_id' => $batch_id,
                        'receiver' => $rpc_receiver,
                        'msg'      => $notification,
                    ],
                ]);
            } else {
                // 单独发送 (更常用)
                $local_rpc_server = Config::get('system.local_api', URI::url('/api'));
                $rpc_token        = Config::get('notification.rpc_token');
                $options          = [
                    'extract_api'  => [
                        'url'    => $local_rpc_server,
                        'method' => 'notification/extract_users',
                        'token'  => $rpc_token,
                    ],
                    'dispatch_api' => [
                        'url'    => $local_rpc_server,
                        'method' => 'notification/send',
                        'token'  => $rpc_token,
                    ],
                ];
                $client->post('send', [
                    'form_params' => [
                        'receiver' => $rpc_receiver,
                        'msg'      => $notification,
                        'options'  => $options,
                    ],
                ]);
            }
        } catch (EXCEPTION $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();
            Log::add("[exception] $message", 'rest');
        }

        return true;
    }

    static function symbol_to_markup($arr_str, $params)
    {
        if (is_array($params)) {
            foreach ($arr_str as $k => $str) {
                $arr_str[$k] = strtr($str, $params);
            }
        }
        foreach ($arr_str as $k => $str) {
            if (preg_match_all('/\%(\w+)/', $str, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $parts) {
                    if (is_callable(['Notification', '_token_' . $parts[1]])) {
                        $arr_str[$k] = preg_replace_callback('/(%' . preg_quote($parts[1]) . ')/', 'Notification::_token_' . $parts[1], $str);
                    }
                }
            }
        }
        return $arr_str;
    }

    // %current_user
    static function _token_current_user($matches)
    {
        return Markup::encode_Q(L('ME'));
    }

    /*
    $configs = array(
    'notification.equipment.eq_banned',
    'notification.xxx.xxx',
    x
    );
     */

    static function preference_views($conf, $vars = null, $module, $use_default = true)
    {
        if (!$use_default) {
            $prefix = $module . ':admin/';
        } else {
            $prefix = 'application:admin/';
        }

        //生成view
        $output = '';
        foreach ($conf as $c) {

            $opt = (array) Lab::get($c) + (array) Config::get($c);

            $send_by = [];

            foreach (Config::get('notification.handlers') as $handler => $info) {
                
                if($handler == 'modal' && !(isset($opt['send_by'][$handler]))) continue;

                if (isset($opt['send_by'][$handler]) && is_array($opt['send_by'][$handler])) {
                    $text         = $opt['send_by'][$handler][0];
                    $default_send = $opt['send_by'][$handler][1];
                } else {
                    $text         = $info['text'];
                    $default_send = $opt['send_by'][$handler];
                }

                $send_by[$handler] = [$text, self::enable_send($c, $handler)];
            }

            $opt['send_by']     = $send_by;
            $opt['type']        = $c;
            $opt['module_name'] = $opt['i18n_module'] ?: $module;
            $view_name          = $opt['#view'] ?: $prefix . 'notification';
            if (is_array($vars)) {
                $opt = array_merge($opt, $vars);
            }
            $output .= (string) V($view_name, $opt);
        }

        return $output;
    }

    // 获取notification的classification item name
    static function get_key($template_name, $type, $user)
    {
        $template_name = 'notification.' . str_replace('notification.', null, $template_name);
        if (Module::is_installed('uno')) {
            return join('.', [$template_name, $type, 'system_user']);
        } else {
            return join('.', [$template_name, $type, $user->id]);
        }
    }

    /*
     *用于获取系统中所有的handler
     *@return array 获取所有的handler
     */
    static function get_handlers()
    {
        return array_keys(Config::get('notification.handlers', []));
    }

    /*
     *用户获取单一handler的所有的配置
     *return array 获取该handler的详细配置
     */
    static function get_handler_info($handler)
    {
        //不在系统现有的handler中，false
        if (!in_array($handler, self::get_handlers())) {
            return false;
        }

        return Config::get('notification.handlers', [])[$handler];
    }

    /*
     *根据template返回模板名称
     *@params string template_name 模板名称
     *@return array|false 模板配置
     */
    static function get_template($template_name)
    {

        $conf_key = 'notification.' . str_replace('notification.', null, $template_name);

        $template = Lab::get($conf_key) ?: Config::get($conf_key);
        $template = Event::trigger('notification.get_template', $conf_key) ?: $template;

        return $template;
    }

    /*
     *用于获取该handler默认是否发送消息
     *@params string handler 发送方式名称
     *@return bool 默认是否发送消息
     */
    static function handler_default_send($handler)
    {

        $handler_info = self::get_handler_info($handler);

        return $handler_info['default_send'];
    }

    /*
     *用于获取该handler发送的消息是否默认接收
     *@params string handler 发送方式名称
     *@return bool 默认是否发送消息
     */
    static function handler_default_receive($handler)
    {

        $handler_info = self::get_handler_info($handler);
        return $handler_info['default_receive'];
    }

    /*
     *用于判断该handler下该template是否可发送
     *@params string template_name 发送模板名称
     *@params string handler 发送方式名称
     *@return bool 返回是否可进行该handler下的template发送
     */
    static function enable_send($template_name, $handler)
    {

        //判断template是否存在
        $template = self::get_template($template_name);

        //如果template不存在，或者handler不存在，不予发送
        if (!$template || !in_array($handler, self::get_handlers())) {
            return false;
        }

        if (array_key_exists($handler, (array) $template['send_by'])) {

            if (isset($template['send_by'][$handler]) && is_array($template['send_by'][$handler])) {
                $default_send = $template['send_by'][$handler][1];
            } else {
                $default_send = $template['send_by'][$handler];
            }

            return (bool) $default_send;
        } else {
            $return = self::handler_default_send($handler);
        }

        return (bool) $return;
    }

    /*
     *用户判断handler下template发送后user是否可进行接收
     *@params string template 发送模板名称
     *@params string handler 发送方式名称
     *@params User_Model user 接收用户
     *@return bool 返回进行该handler下的template发送后user是否可接收
     */
    static function enable_receive($template_name, $handler, $user)
    {
        //判断template是否存在
        $template      = self::get_template($template_name);
        $template_name = Event::trigger('notification.get_template_name', 'notification.' . $template_name) ?: $template_name;
        //如果template不存在，或者handler不存在或者user有问题，不予接收
        if (!$template || !in_array($handler, self::get_handlers()) || !($user instanceof User_Model) || !$user->id) {
            return false;
        }

        $user_set = Lab::get(self::get_key($template_name, $handler, $user));

        //如果用户设置过了, 直接按照用户设置来觉得是否可接收
        if ($user_set !== null) {
            return $user_set;
        } else {
            //如果用户没设置过, 则按照默认模板是否可接受、默认发送
            if ($template['receive_by'][$handler] === FALSE) {
                return false;
            } else {
                return $template['receive_by'][$handler] || self::handler_default_receive($handler);
            }
        }
    }

    //偏好设置里的消息提醒单独存放
    static function is_local_notification_field($field)
    {
        $classification = Config::get('notification.classification');
        $handlers = implode('|', self::get_handlers());
        foreach ((array)$classification as $items) {
            foreach ((array)$items as $key => $item) {
                if ($key[0] == '#') continue;
                foreach ($item as $v) {
                    $pattern = "/^notification.{$v}.({$handlers}).\d+/";
                    if (preg_match($pattern, $field)) return true;
                }
            }
        }
        return false;
    }
}
