<?php

class Message
{

    private static $_batch;

    public static function send($form, $type, $oid = null, $batch_id = null)
    {
        // 目前邮件发送的机制已改为通过 node-lims2 发送, 流程详见:
        // http://dev.genee.cn/doku.php/software/node/lims2_message.md

        if (defined('DISABLE_MESSAGE')) {
            return;
        }

        $me = L('ME');

        $msg = [
            'title' => $form['title'],
            'body'  => $form['body'],
            'email' => $form['email'],
            'sms' => $form['sms'],
            'sender' => $me->id,
            'ctime'  => Date::time(),
        ];

        if (isset($form['send_type']) && ($form['send_type'] == Message_Model::TYPE_SYSTEM)) {
            unset($msg['sender']);
        }

        $receiver = [
            'type' => $type,
            'id'   => $oid,
        ];

        $msg_server = Config::get('messages.server');
        $send_to_uno = Module::is_installed('gateway') && Module::is_installed('uno');

        try {
            if ($send_to_uno) {
                $rest = new \GuzzleHttp\Client([
                    'base_uri' => Config::get('gateway.domain_url') . 'messages/postbox/',
                    'http_errors' => false,
                    'timeout' => 5
                ]);

                $params = [
                    'fromToken' => Gateway::getToken(),
                    'entry' => '',
                    'title' => $form['title'],
                    'body' => $form['body'],
                    'via' => ['local'],
                ];
                if ($receiver['type'] == 'user') {
                    $to = O('user', $receiver['id']);
                }
                if ($to->id && $to->gapper_id) {
                    $params['to'] = 'user/' . $to->gapper_id;
                }
                if ($me->id && $me->gapper_id) {
                    $params['from'] = 'user/' . $me->gapper_id;
                }

                $response = $rest->post(
                    'api/v1/message',
                    [
                        'json' => $params,
                    ]
                );

                Log::add(strtr('[messages] %sender_name[%sender_id]发送uno消息给%receiver_name[%receiver_id], 主题[%subject], response: [%code] %response', [
                    '%sender_name' => $me->id ? $me->name : '系统',
                    '%sender_id' => $me->id ?: '--',
                    '%receiver_name' => $to->name,
                    '%receiver_id' => $to->id,
                    '%subject' => $form['title'],
                    '%code' => $response->getStatusCode(),
                    '%response' => $response->getBody()->getContents()
                ]), 'messages');
                return true;
            }

            $client = new \GuzzleHttp\Client([
                'base_uri'    => $msg_server['addr'],
                'http_errors' => false,
                'timeout'     => $msg_server['timeout'],
            ]);

            if ($batch_id) {
                // 批量发送
                $client->post('batch_send', [
                    'form_params' => [
                        'batch_id' => $batch_id,
                        'receiver' => $receiver,
                        'msg'      => $msg,
                    ],
                ]);
            } else {
                // 单独发送 (更常用)
                $local_rpc_server = Config::get('system.local_api', URI::url('/api'));
                $rpc_token        = Config::get('messages.rpc_token');
                $options          = [
                    'extract_api'  => [
                        'url'    => $local_rpc_server,
                        'method' => 'messages/extract_users',
                        'token'  => $rpc_token,
                    ],
                    'dispatch_api' => [
                        'url'    => $local_rpc_server,
                        'method' => 'messages/send',
                        'token'  => $rpc_token,
                    ],
                ];

                $client->post('send', [
                    'form_params' => [
                        'receiver' => $receiver,
                        'msg'      => $msg,
                        'options'  => $options,
                    ],
                ]);
            }

            return true;
        } catch (RPC_Exception $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();

            Log::add("[rpc_exception] $message", 'rpc');
        }
    }

    public static function start_batch()
    {

        $local_rpc_server = Config::get('system.local_api', URI::url('/api'));
        $rpc_token        = Config::get('messages.rpc_token');
        $options          = [
            'extract_api'  => [
                'url'    => $local_rpc_server,
                'method' => 'messages/extract_users',
                'token'  => $rpc_token,
            ],
            'dispatch_api' => [
                'url'    => $local_rpc_server,
                'method' => 'messages/send',
                'token'  => $rpc_token,
            ],
        ];

        $msg_server = Config::get('messages.server');

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
        } catch (RPC_Exception $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();

            Log::add("[rpc_exception] $message", 'rpc');
        }

        return $return;
    }

    public static function finish_batch($batch_id)
    {

        $local_rpc_server = Config::get('system.local_api', URI::url('/api'));

        $msg_server = Config::get('messages.server');

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
        } catch (RPC_Exception $e) {
            //catch
            $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();

            Log::add("[rpc_exception] $message", 'rpc');
        }

        return $return;
    }

    static function notif_callback($item)
    {
        $me = L('ME');
        if (!$me->id) return 0;
        // 此处不使用total_count, total_count会进行排序，影响性能
        return Q("message[receiver={$me}][is_read=0]:limit(1)")->current()->id ? '&nbsp' : false;
    }

    public static function short_picture_of_people($e, $user)
    {
        $me    = L('ME');
        $items = (array) $e->return_value;

        if (!$user->id || $user->id == $me->id || !Config::get('messages.add_message.switch_on', true)) {
            $e->return_false = $items;
            return;
        }

        $items[] = (string) V('messages:message/short_info', ['user' => $user]);

        $e->return_value = (array) $items;
    }

    public static function is_accessible($e, $name)
    {
        $me = L('ME');

        /* 允许只要该用户在系统中存在， 无论是否激活， 都应显示message模块 */
        //23261 （3）17Kong/Sprint-264：3.2全面测试：在用户待激活页面，点击消息中心无响应，还是改了只有激活显示
        if ($me->id && $me->is_active()) {
            $e->return_value = true;
            return false;
        }
    }

    public static function sidebar_heartbeat($mode)
    {
        $menu = Config::get('layout.sidebar.menu');
        switch ($mode) {
            case 'list':
                $item                                = $menu['messages']['list'];
                $item['i18n']                        = $item['i18n'] ?: 'messages';
                Output::$AJAX['.list_item_messages'] = [
                    'data' => (string) V('application:sidebar/menu/list', ['id' => 'messages', 'item' => $item]),
                    'mode' => 'replace',
                ];
                break;
            case 'icon':
                $item                                = $menu['messages']['icon'];
                $item['i18n']                        = $item['i18n'] ?: 'messages';
                Output::$AJAX['.icon_item_messages'] = [
                    'data' => (string) V('application:sidebar/menu/icon', ['id' => 'messages', 'item' => $item]),
                    'mode' => 'replace',
                ];
                break;
        }
    }

    public static function message_newsletter_content($e, $user)
    {

        $templates = Config::get('newsletter.template');
        $db        = Database::factory();
        $template  = $templates['extra']['new'];
        $sql       = "SELECT COUNT(*) FROM message WHERE is_read=0 AND receiver_id=%d";
        $count     = $db->value($sql, $user->id);
        if ($count > 0) {
            $str .= V('messages:newsletter/new', [
                'count'    => $count,
                'template' => $template,
            ]);
        }

        if (strlen($str) > 0) {
            $e->return_value .= $str;
        }
    }
}
