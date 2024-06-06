<?php

class Sensor_Controller extends Base_Controller
{
    public function index()
    {
        URI::redirect('!envmon/index');
    }

}

class Sensor_AJAX_Controller extends AJAX_Controller
{
    public function index_add_sensor_click()
    {
        $me = L('ME');
        $form = Input::form();
        $node = O('env_node', $form['node_id']);

        if (!$node->id || !$me->is_allowed_to('添加传感器', $node)) {
            return false;
        }

        JS::dialog(V('envmon:sensor/add', ['node_id' => $node->id]), ['title' => I18N::T('envmon', '添加传感器')]);
    }

    public function index_add_sensor_submit()
    {
        $form = Form::filter(Input::form());
        $node = O('env_node', $form['node_id']);
        if (!$node->id) {
            return false;
        }

        if ($form['submit']) {
            $form
                ->validate('name', 'not_empty', I18N::T('envmon', '传感器名称不能为空!'))
                ->validate('interval_limit', 'is_numeric', I18N::T('envmon', '采样时间填写有误!'))
                ->validate('vfrom', 'is_numeric', I18N::T('envmon', '正常范围填写有误!'))
                ->validate('vto', 'is_numeric', I18N::T('envmon', '正常范围填写有误!'))
                ->validate('unit', 'not_empty', I18N::T('envmon', '传感器单位不能为空!'));

            if ($form['abnormal_check_status'] == 'on') {
                if ($form['alert_time_limit'] <= 0) {
                    $form->set_error('alert_time_limit', I18N::T('envmon', '数据异常检测范围必须大于等于零!'));
                }
                if ($form['check_abnormal_time'] <= 0) {
                    $form->set_error('check_abnormal_time', I18N::T('envmon', '数据异常检测间隔必须大于等于零!'));
                }
                if ($form['limit_abnormal_times'] <= 0) {
                    $form->set_error('limit_abnormal_times', I18N::T('envmon', '数据异常报警次数必须大于等于零!'));
                }
            }
            if ($form['nodata_check_status'] == 'on') {
                if ($form['nodata_alert_time'] <= 0) {
                    $form->set_error('nodata_alert_time', I18N::T('envmon', '无数据检测范围必须大于等于零!'));
                }
                if ($form['check_nodata_time'] <= 0) {
                    $form->set_error('check_nodata_time', I18N::T('envmon', '无据异常检测间隔必须大于等于零!'));
                }
                if ($form['limit_nodata_times'] <= 0) {
                    $form->set_error('limit_nodata_times', I18N::T('envmon', '无数据报警次数必须大于等于零!'));
                }
            }

            if ($form->no_error) {
                $sensor = O('env_sensor');
                $sensor->node = $node;
                $sensor->address = $form['address'] ?: null;
                $sensor->name = $form['name'];
                $sensor->vfrom = $form['vfrom'];
                $sensor->vto = $form['vto'];
                $sensor->unit = $form['unit'];
                $sensor->interval = Date::convert_interval($form['interval_limit'], $form['interval_format']);

                //进行数据异常报警
                $sensor->data_alarm = $form['data_alarm'] == 'on' ? 1 : 0;

                //数据异常设置
                if ($form['abnormal_check_status'] == 'on') {
                    $sensor->abnormal_check_status = 1;
                    $sensor->alert_time = $form['alert_time_limit'];
                    $sensor->check_abnormal_time = $form['check_abnormal_time'];
                    $sensor->limit_abnormal_times = $form['limit_abnormal_times'];
                } else {
                    $sensor->abnormal_check_status = 0;
                    $sensor->alert_time = Config::get('envmon.alert_time', 5);
                    $sensor->check_abnormal_time = Config::get('envmon.check_abnormal_time', 5);
                    $sensor->limit_abnormal_times = Config::get('envmon.limit_abnormal_times', 3);
                }

                //无数据设置
                if ($form['nodata_check_status'] == 'on') {
                    $sensor->nodata_check_status = 1;
                    $sensor->nodata_alert_time = $form['nodata_alert_time'];
                    $sensor->check_nodata_time = $form['check_nodata_time'];
                    $sensor->limit_nodata_times = $form['limit_nodata_times'];
                } else {
                    $sensor->nodata_check_status = 0;
                    $sensor->nodata_alert_time = Config::get('envmon.nodata_alert_time', 5);
                    $sensor->check_nodata_time = Config::get('envmon.check_nodata_time', 5);
                    $sensor->limit_nodata_times = Config::get('envmon.limit_nodata_times', 3);

                }

                $sensor->status = (int) (in_array($form['status'], Env_Sensor_Model::$STATUS_ARRAY) ? $form['status'] : Env_Sensor_Model::OUT_OF_SERVICE);

                if ($sensor->save()) {
                    Event::trigger('node.sensor.submit', $form, $sensor);
                    //log
                    $me = L('ME');
                    Log::add(strtr('[envmon] %user_name[%user_id] 为 %node_name[%node_id] 添加了传感器 %sensor_name[%sensor_id] ', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%node_name' => $node->name,
                        '%node_id' => $node->id,
                        '%sensor_name' => $sensor->name,
                        '%sensor_id' => $sensor->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '传感器添加成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '传感器添加失败!'));
                }
                JS::refresh();
            } else {
                JS::dialog(V('envmon:sensor/add', ['form' => $form, 'node_id' => $node->id]));
            }
        }
    }

    public function index_edit_sensor_click()
    {
        $form = Input::form();
        $sensor = O('env_sensor', $form['sensor_id']);
        $me = L('ME');

        //if (!$sensor->id || !$me->is_allowed_to('修改', $sensor)) return FALSE; TODO 补充权限后开启

        JS::dialog(V('envmon:sensor/edit', ['sensor' => $sensor]), ['title' => I18N::T('envmon', '设置传感器')]);
    }

    public function index_edit_sensor_submit()
    {
        $form = Form::filter(Input::form());
        $sensor = O('env_sensor', $form['sensor_id']);
        if (!$sensor->id) {
            return false;
        }

        if ($form['submit']) {
            $form
                ->validate('name', 'not_empty', I18N::T('envmon', '传感器名称不能为空!'))
                ->validate('interval_limit', 'is_numeric', I18N::T('envmon', '采样时间填写有误!'))
                ->validate('vfrom', 'is_numeric', I18N::T('envmon', '正常范围填写有误!'))
                ->validate('vto', 'is_numeric', I18N::T('envmon', '正常范围填写有误!'))
                ->validate('unit', 'not_empty', I18N::T('envmon', '传感器单位填写错误!'));

            if ($form['abnormal_check_status'] == 'on') {
                if ($form['alert_time_limit'] <= 0) {
                    $form->set_error('alert_time_limit', I18N::T('envmon', '数据异常检测范围必须大于等于零!'));
                }
                if ($form['check_abnormal_time'] <= 0) {
                    $form->set_error('check_abnormal_time', I18N::T('envmon', '数据异常检测间隔必须大于等于零!'));
                }
                if ($form['limit_abnormal_times'] <= 0) {
                    $form->set_error('limit_abnormal_times', I18N::T('envmon', '数据异常报警次数必须大于等于零!'));
                }
            }
            if ($form['nodata_check_status'] == 'on') {
                if ($form['nodata_alert_time'] <= 0) {
                    $form->set_error('nodata_alert_time', I18N::T('envmon', '无数据检测范围必须大于等于零!'));
                }
                if ($form['check_nodata_time'] <= 0) {
                    $form->set_error('check_nodata_time', I18N::T('envmon', '无据异常检测间隔必须大于等于零!'));
                }
                if ($form['limit_nodata_times'] <= 0) {
                    $form->set_error('limit_nodata_times', I18N::T('envmon', '无数据报警次数必须大于等于零!'));
                }
            }

            if ($form->no_error) {
                $sensor->interval = Date::convert_interval($form['interval_limit'], $form['interval_format']);
                //$sensor->alert_time = Date::convert_interval($form['alert_time_limit'], $form['alert_time_format']);
                $sensor->name = $form['name'];
                $sensor->address = $form['address'] ?: null;
                $sensor->vfrom = $form['vfrom'];
                $sensor->vto = $form['vto'];
                $sensor->unit = $form['unit'];
                $sensor_old_status = $sensor->status;

                //进行数据异常报警
                $sensor->data_alarm = $form['data_alarm'] == 'on' ? 1 : 0;

                //数据异常设置
                if ($form['abnormal_check_status'] == 'on') {
                    $sensor->abnormal_check_status = 1;
                    $sensor->alert_time = $form['alert_time_limit'];
                    $sensor->check_abnormal_time = $form['check_abnormal_time'];
                    $sensor->limit_abnormal_times = $form['limit_abnormal_times'];
                } else {
                    $sensor->abnormal_check_status = 0;
                    $sensor->alert_time = Config::get('envmon.alert_time', 5);
                    $sensor->check_abnormal_time = Config::get('envmon.check_abnormal_time', 5);
                    $sensor->limit_abnormal_times = Config::get('envmon.limit_abnormal_times', 3);
                }

                //无数据设置
                if ($form['nodata_check_status'] == 'on') {
                    $sensor->nodata_check_status = 1;
                    $sensor->nodata_alert_time = $form['nodata_alert_time'];
                    $sensor->check_nodata_time = $form['check_nodata_time'];
                    $sensor->limit_nodata_times = $form['limit_nodata_times'];
                } else {
                    $sensor->nodata_check_status = 0;
                    $sensor->nodata_alert_time = Config::get('envmon.nodata_alert_time', 5);
                    $sensor->check_nodata_time = Config::get('envmon.check_nodata_time', 5);
                    $sensor->limit_nodata_times = Config::get('envmon.limit_nodata_times', 3);

                }

                $sensor->status = (int) (in_array($form['status'], Env_Sensor_Model::$STATUS_ARRAY) ? $form['status'] : Env_Sensor_Model::OUT_OF_SERVICE);

                //设置更改时，清除虚属性
                $sensor->_alert_time_nodata = null;
                $sensor->_warning_time = null;
                $sensor->_alert_times_abnormal = null;
                $sensor->_first_warning_time = null;
                $sensor->_warning_nodata_time = null;

                if ($sensor->save()) {
                    Event::trigger('node.sensor.submit', $form, $sensor);
                    $env_sensor_alarm = Q("env_sensor_alarm[dtend=0][sensor={$sensor}]:sort(ctime D):limit(1)")->current();
                    if ($env_sensor_alarm->id) {
                        $env_sensor_alarm->dtend = Date::time();
                        $env_sensor_alarm->save();
                    }
                    //log
                    $me = L('ME');
                    Log::add(strtr('[envmon] %user_name[%user_id] 修改了 %node_name[%node_id] 的传感器 %sensor_name[%sensor_id] 的基本信息 ', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%node_name' => $sensor->node->name,
                        '%node_id' => $sensor->node->id,
                        '%sensor_name' => $sensor->name,
                        '%sensor_id' => $sensor->id,
                    ]), 'journal');

                    if ($sensor_old_status != $sensor->status) {
                        Log::add(strtr('[envmon] %user_name[%user_id] 修改了 %node_name[%node_id] 的传感器 %sensor_name[%sensor_id] 的监控状态为 %status ', [
                            '%user_name' => $me->name,
                            '%user_id' => $me->id,
                            '%node_name' => $sensor->node->name,
                            '%node_id' => $sensor->node->id,
                            '%sensor_name' => $sensor->name,
                            '%sensor_id' => $sensor->id,
                            '%status' => $sensor->status ? '不监控' : '监控',
                        ]), 'journal');
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '传感器修改成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '传感器修改失败!'));
                }

                JS::refresh();
            } else {
                JS::dialog(V('envmon:sensor/edit', ['sensor' => $sensor, 'form' => $form]));
            }
        }
    }

    public function index_delete_sensor_click()
    {

        if (JS::confirm(I18N::T('envmon', '你确定要删除吗？删除后不可恢复!'))) {
            $me = L('ME');
            $form = Input::form();

            $sensor = O('env_sensor', $form['sensor_id']);
            if (!$sensor->id) {
                return false;
            }

            if (!$me->is_allowed_to('删除', $sensor)) {
                return false;
            }

            $node = $sensor->node;
            $sensor_name = $sensor->name;
            $sensor_id = $sensor->id;
            if ($sensor->delete()) {
                Log::add(strtr('[envmon] %user_name[%user_id] 删除了 %node_name[%node_id] 的传感器 %sensor_name[%sensor_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%node_name' => $node->name,
                    '%node_id' => $node->id,
                    '%sensor_name' => $sensor_name,
                    '%sensor_id' => $sensor_id,
                ]), 'journal');
            }
            JS::refresh();
        }

    }

}