<?php

class API_Calendar {

    function authorize($clientId, $clientSecret)
    {
        $calendar = Config::get('rpc.servers')['calendar'];
        if ($calendar['client_id'] == $clientId && 
            $calendar['client_secret'] == $clientSecret) {
            $_SESSION['calendar.client_id'] = $clientId;
            return session_id();
        }
        
        return false;
    }
    
    private function _checkAuth()
    {
        $calendar = Config::get('rpc.servers')['calendar'];
        if (!isset($_SESSION['calendar.client_id']) || 
            $calendar['client_id'] != $_SESSION['calendar.client_id']) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    public function get($id, $name, $type) {
        $this->_checkAuth();

        $object = O($name, $id);
        
        if (!$object->id) return FALSE;
        
        $calendar = O('calendar' , ['parent'=>$object, 'type'=> $type]);

        if (!$calendar->id) return FALSE;

        return $calendar->id;
    }

    static function postCommitComponent($form) {
        $form = json_decode($form, true);
        $uuid = $form['uuid'];
        $me = O('user', $form['agent_id']);
        Cache::L('ME', $me);
        if ($form['equipment_id']) {
            $equipment = O('equipment', (int) $form['equipment_id']);
            $calendar = O('calendar', ['parent' => $equipment, 'type' => 'eq_reserv']);
            if (!$calendar->id) {
                $calendar = O('calendar');
                $calendar->parent = $equipment;
                $calendar->type   = 'eq_reserv';
                $calendar->name   = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
                $calendar->save();
            }
        }
        if ($form['id'] && intval($form['id']) == $form['id']) {
            $reserv = O('eq_reserv', (int) $form['id']);
            $component = $reserv->component;
        }
        if ($form['calendar_id']) {
            $calendar = O('calendar', $form['calendar_id']);
        }

        if (!$component->id && !$calendar->id) return;
        $component = $component->id ? $component : O('cal_component', $form['component_id']);
        if (!$component->calendar->id) {
            $component->calendar = $calendar;
        }
        $component->calendar = !$component->calendar->parent->id ? $calendar : $component->calendar;
        $component->organizer = $form['user_id'] ? O('user', $form['user_id']) : $component->organizer;
        $component->organizer = $component->organizer->id ? $component->organizer : $me;
        $component->name = $form['name'] ?: '仪器使用预约';
        $component->description = $form['description'];
        $component->type = isset($form['type']) ? $form['type'] : ($component->type ?: Cal_Component_Model::TYPE_VEVENT);

        $oldId = $component->id;
        
        //调整位置 
        Event::trigger('calendar.component_form.submit', Form::filter($form), $component, ['calendar'=>$calendar]);

        $dtstart = $form['dtstart'];
        $dtend = $form['dtend'];
        if ($dtstart > $dtend) {
            list($dtstart, $dtend) = [$dtend, $dtstart];
        }
        $component->dtstart = $dtstart;
        $component->dtend = $dtend;

        //Log处理机制    
        $msg = Event::trigger('calendar.component_form.attempt_submit.log', $form, $component, $calendar);
        if ( !$msg ) {
            if (!$component->id) {
                $msg = sprintf('[calendars] %s[%d] 于 %s 尝试创建新的预约!', $me->name, $me->id, Date::format(Date::time()));
            }
            else {
                $msg = sprintf('[calendars] %s[%d] 于 %s 尝试修改预约[%d]!', $me->name, $me->id, Date::format(Date::time()), $component->id);
            }
        }
        Log::add($msg, 'journal');

        /*
        *  临时为了配合排队的机制，采用堵塞文件锁来限制单台套仪器的更新
        *  cheng.liu@geneegroup.com
        *  2016.11.29
        *  不采用Config::get('system.tmp_dir')机制，采用/tmp/
        */
        $mutex_file = '/tmp/'.Misc::key('calendar', $calendar->id);
        $fp = fopen($mutex_file, 'w+');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                Cache::L('COMPONENT_FORM', $form);
                $can_save = $component->id ? $me->is_allowed_to('修改', $component) : $me->is_allowed_to('添加', $component);
                if ( $can_save ) {

                    define('CLI_MODE', 1);
                    Config::load(LAB_PATH, 'system');
                    $base_url = Config::get('system.base_url');
                    define('CLI_MODE', 0);
                    Config::load(LAB_PATH, 'system');
                    if ( $base_url ) {
                        Config::set('system.base_url', $base_url);
                        Config::set('system.script_url', $base_url);
                    }
                    
                    $ret = $component->save();
                    /* 获取到merge的视图值 */
                    $merge_yet = L('MERGE_COMPONENT_ID');
                    if ( $ret ) {
                        Cache::L('YiQiKongReservAction', true); // bug: 21835 用户预约后发了两条提醒消息
                        if (isset($form['count'])) $form['extra_fields']['count'] = $form['count'];
                        Event::trigger('calendar.component_form.post_submit', $component, $form);
                        if (!$oldId) {
                            Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功创建新的预约[%component_id]!', array(
                                '%user_name' => $me->name,
                                '%user_id' => $me->id,
                                '%date' => Date::format(Date::time()),
                                '%component_id' => $component->id,
                                )), 'journal');

                        }
                        else {
                            Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功修改预约[%component_id]!', array(
                                '%user_name' => $me->name,
                                '%user_id' => $me->id,
                                '%date' => Date::format(Date::time()),
                                '%component_id' => $component->id,
                                )), 'journal');
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        @unlink($mutex_file);
                        return [
                            'uuid' => $uuid, 
                            'success' => 1,
                            'component_id' => $component->id
                        ];
                    }

                    if ( $merge_yet ) {
                        $component = O('cal_component', $merge_yet);
                        Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 合并预约[%component_id], 时间为[%dtstart ~ %dtend]', array(
                                '%user_name' => $me->name,
                                '%user_id' => $me->id,
                                '%date' => Date::format(Date::time()),
                                '%component_id' => $merge_yet,
                                '%dtstart' => Date::format($component->dtstart),
                                '%dtend' => Date::format($component->dtend)
                                )), 'journal');

                        $ids = join(',', (array)L('REMOVE_COMPONENT_IDS'));

                        Cache::L('MERGE_COMPONENT_ID', NULL);
                        Cache::L('REMOVE_COMPONENT_IDS', NULL);
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        @unlink($mutex_file);
                        return [
                            'uuid' => $uuid, 
                            'success' => 1,
                            'component_id' => $merge_yet,
                            'merge_component_id' => $ids
                        ];
                    }
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    @unlink($mutex_file);
                    return [
                        'uuid' => $uuid, 
                        'error_msg' => I18N::T('calendar', '添加预约失败!')
                    ];
                    
                }
                else {
                    $messages = Lab::messages(Lab::MESSAGE_ERROR);
                    if (in_array(I18N::T('billing', '实验室余额不足, 目前无法使用该设备。'), $messages) && in_array(I18N::T('eq_charge', '实验室余额不足, 您目前无法预约该设备。'), $messages)) {
                        unset($messages[
                            array_search(I18N::T('billing', '实验室余额不足, 目前无法使用该设备。'), $messages, true)
                        ]);
                    }
                    if (count($messages) > 0) {
                        $errorMsg = implode(', ', $messages);
                    }
                }
                Cache::L('COMPONENT_FORM', NULL);
                flock($fp, LOCK_UN);
                fclose($fp);
                @unlink($mutex_file);
                return ['uuid' => $uuid, 'error_msg' => $errorMsg];
            }

            $errorMsg = T('系统繁忙，请稍后重试!');
        }

        return ['uuid' => $uuid, 'error_msg' => $errorMsg];
    }

}
