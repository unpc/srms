<?php

class Vidcam_Controller extends Base_Controller
{

    public function index($id = 0, $tab = '')
    {

        $me     = L('ME');
        $vidcam = O('vidcam', $id);

        if (!$vidcam->id) {
            URI::redirect('error/404');
        }

        if (!$me->is_allowed_to('查看', $vidcam)) {
            URI::redirect('error/401');
        }

        $breadcrumbs = [
            [
                'url' => '!vidmon/list',
                'title' => I18N::T('vidmon', '视频列表'),
            ],
            [
                'title' =>  $vidcam->name,
            ]
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('vidcam/header_content', ['vidcam' => $vidcam]);
        $this->layout->title = I18N::T('vidmon', H(""));

        $content = V('vidcam/index', ['vidcam' => $vidcam]);

        $this->layout->body->primary_tabs = Widget::factory('tabs');

        Event::bind('vidcam.view.content', [$this, '_index_realtime_content'], 0, 'realtime');

        if ($me->is_allowed_to('查看历史记录', $vidcam)) {
            Event::bind('vidcam.view.content', [$this, '_index_record_content'], 0, 'vid_record');
            $this->layout->body->primary_tabs
                ->add_tab('vid_record', [
                    'title'  => I18N::T('vidmon', '历史记录'),
                    'url'    => $vidcam->url('vid_record', null, null, 'view'),
                    'weight' => 100,
                ]);
        }

        $this->layout->body->primary_tabs
            ->add_tab('realtime', [
                'url'    => $vidcam->url('realtime'),
                'title'  => I18N::T('vidmon', '实时监控'),
                'weight' => 99,
            ])
            ->set('vidcam', $vidcam)
            ->tab_event('vidcam.view.tab')
            ->content_event('vidcam.view.content')
            ->select($tab);

        $this->add_css('common');
    }

    public function _index_realtime_content($e, $tabs)
    {
        $vidcam = $tabs->vidcam;

        if (Config::get('stream')['use_stream']) {
            $this->add_js('vidmon:hls');

            if ($vidcam->type == Vidcam_Model::TYPE_GENEE) {
                $content = V('vidcam/realtime', ['vidcam'=>$vidcam]);
            } else if ($vidcam->type == Vidcam_Model::TYPE_STREAM) {
                $content = V('vidcam/stream', ['vidcam'=>$vidcam]);
            }
        } else {
            $content = V('vidcam/realtime', ['vidcam'=>$vidcam]);
        }

        $tabs->content = $content;
    }

    public function _index_record_content($e, $tabs)
    {
        $vidcam = $tabs->vidcam;
        $now    = Date::time();
        $this->add_css('vidmon:timeline');
        $this->add_js('vidmon:timeline');
        $tabs->content = V('vidmon:vid_record', ['vidcam' => $vidcam]);
    }
}

class Vidcam_AJAX_Controller extends AJAX_Controller
{

    public function index_vidcam_add_click()
    {
        JS::dialog(V('vidcam/add'), ['width' => 400, 'title' => I18N::T('vidmon', '添加摄像头')]);
    }

    public function index_vidcam_add_submit()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'vidcam')) {
            return false;
        }
        $form   = Form::filter(Input::form());
        $vidcam = O('vidcam');
        if ($form['submit']) {

            $form->validate('name', 'not_empty', I18N::T('vidmon', '视频设备名称不能为空!'));

            if (Config::get('stream')['use_stream']) {
                $form->validate('uuid', 'not_empty', I18N::T('vidmon', '视频设备标识不能为空!'));
            }

            $admin_tokens = array_map('Auth::normalize', (array) Config::get('lab.admin'));

            if (in_array($me->token, $admin_tokens)) {
                $form->validate('control_address', 'not_empty', I18N::T('vidmon', '控制地址不能为空!'));
            }

            $incharges = (array) @json_decode($form['incharge'], true);
            if (!count($incharges)) {
                $form->set_error('incharge', I18N::T('envmon', '视频设备负责人不能为空!'));
            }
            Event::trigger('vidcam[edit].post_submit_validate', $form);
			
			if ($form->no_error) {	
		
                $vidcam->name = $form['name'];
                $vidcam->uuid = trim($form['uuid']);
                $vidcam->type = $form['type'] ? : Vidcam_Model::TYPE_GENEE;
				$vidcam->location = $form['location'];
				$vidcam->location2 = $form['location2'];
                if (in_array($me->token, $admin_tokens)) {
                    $vidcam->control_address = $form['control_address'];
                }
                Event::trigger('vidcam[edit].post_submit', $form, $vidcam);
				if ($vidcam->save()) {
                    foreach($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $vidcam->connect($user, 'incharge');
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vidmon', '视频设备添加成功!'));
                    Log::add(strtr('[vidmon] %user_name[%user_id]添加%vidcam_name[%vidcam_id]摄像头', [
                        '%user_name'   => $me->name,
                        '%user_id'     => $me->id,
                        '%vidcam_name' => $vidcam->name,
                        '%vidcam_id'   => $vidcam->id,
                    ]), 'journal');
				}
		    	JS::refresh();
            }
            else {
                JS::dialog(V('vidcam/add', ['form'=>$form]), ['width'=>400]);
            }

        }

    }

	function index_vidcam_edit_click() {
		$form = Input::form();
		$vidcam = O('vidcam', $form['vidcam_id']);
        if (!$vidcam->id) return FALSE;
        JS::dialog(V('vidcam/edit', ['vidcam' => $vidcam]), ['width' => 400, 'title' => I18N::T('vidmon', '编辑摄像头')]);
	}


    public function index_vidcam_edit_submit()
    {
        $me     = L('ME');
        $form   = Form::filter(Input::form());
        $vidcam = O('vidcam', $form['vidcam_id']);

        if (!$vidcam->id || !$me->is_allowed_to('修改', $vidcam)) {
            return false;
        }

        if ($form['submit']) {

            $form->validate('name', 'not_empty', I18N::T('vidmon', '视频设备名称不能为空!'));

            if (Config::get('stream')['use_stream']) {
                $form->validate('uuid', 'not_empty', I18N::T('vidmon', '视频设备标识不能为空!'));
            }

            $admin_tokens = array_map('Auth::normalize', (array) Config::get('lab.admin'));
            if (in_array($me->token, $admin_tokens)) {
                $form->validate('control_address', 'not_empty', I18N::T('vidmon', '控制地址不能为空!'));
            }

            $incharges = (array) @json_decode($form['incharge'], true);
            if (!count($incharges)) {
                $form->set_error('incharge', I18N::T('envmon', '视频设备负责人不能为空!'));
            }
            Event::trigger('vidcam[edit].post_submit_validate', $form);

            if ($form->no_error) {
                $vidcam->name = $form['name'];
                $vidcam->uuid = trim($form['uuid']);
                $vidcam->type = $form['type'] ? : Vidcam_Model::TYPE_GENEE;
                $vidcam->location = $form['location'];
				$vidcam->location2 = $form['location2'];
                if (in_array($me->token, $admin_tokens)) {
                    $vidcam->control_address = $form['control_address'];
                }
                Event::trigger('vidcam[edit].post_submit', $form, $vidcam);

                if ($vidcam->save()) {
                    //disconnect
                    foreach (Q("{$vidcam} user.incharge") as $user) {
                        $vidcam->disconnect($user, 'incharge');
                    }

                    //reconnect
                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $vidcam->connect($user, 'incharge');
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vidmon', '视频设备信息修改成功!'));
                    Log::add(strtr('[vidmon] %user_name[%user_id]修改%vidcam_name[%vidcam_id]摄像头', [
                        '%user_name'   => $me->name,
                        '%user_id'     => $me->id,
                        '%vidcam_name' => $vidcam->name,
                        '%vidcam_id'   => $vidcam->id,
                    ]), 'journal');
                    JS::redirect('!vidmon/list');
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('vidmon', '视频设备信息修改失败!'));
                }
            } else {
                JS::dialog(V('vidcam/edit', ['vidcam' => $vidcam, 'form' => $form]), ['width' => 400]);
            }
        }
    }

    public function index_delete_vidcam_click()
    {
        $form   = Input::form();
        $vidcam = O('vidcam', $form['vidcam_id']);
        $me     = L('ME');

        if (!$vidcam->id || !$me->is_allowed_to('删除', $vidcam)) {
            return false;
        }

        if (JS::confirm(I18N::T('vidmon', '确定要删除该视频设备吗?'))) {
            if ($vidcam->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vidmon', '视频设备删除成功!'));
                JS::redirect('!vidmon/list');
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('vidmon', '视频设备删除失败!'));
                JS::refresh();
            }
        }
    }

    public function index_snapshot_refresh()
    {
        $form = Input::form();
        Vidmon::snapshot_refresh($form['id']);
        $now                                                  = Date::time();
        $vidcam                                               = O('vidcam', $form['id']);
        $alarm_capture_start                                  = $now - Config::get('vidmon.capture_duration');
        $alarm                                                = Q("vidcam_alarm[vidcam={$vidcam}][ctime={$alarm_capture_start}~{$now}]")->total_count();
        Output::$AJAX['#' . $form['img_id'] . ' - div.alarm'] = [
            'data' => (string) V('vidcam/alarm', ['alarm' => $alarm]),
            'mode' => 'replace',
        ];
	}

	
	


    public function index_view_img_click()
    {
        $form   = Input::form();
        $img_id = $form['img_id'];
        $img    = O('vidcam_capture_data', $img_id);
        JS::dialog((string) $img->get_img(), [
            'drag' => true,
        ]);
    }

    public function index_data_fetch()
    {
        $form = Input::form();

        $min    = (int) $form['min'];
        $max    = (int) $form['max'];
        $vidcam = O('vidcam', (int) $form['vid']);

        $interval = Config::get('vidmon.point_interval', 50);
        $width    = (int) max($form['width'], 1);

        $time          = $max - $min;
        $time_interval = max(ceil($time * $interval / $width), 1);

        $datas = [];

        $current = $min;

        while ($current < $max) {
            $start = $current;
            $end   = $current += $time_interval;

            $db  = Database::factory();
            $SQL = "SELECT `id`, `is_alarm`, `ctime` FROM `vidcam_capture_data`" .
            " WHERE `vidcam_id`=%id" .
            " AND `ctime` BETWEEN %start AND %end" .
            " LIMIT 1;";
            $vdatas = $db->query(strtr($SQL, [
                '%id'    => (int) $vidcam->id,
                '%start' => (int) $start,
                '%end'   => (int) $end,
            ]))->rows();

            foreach ($vdatas as $key => $v) {
                $ctime           = $v->ctime;
                $object          = new stdClass();
                $object->y       = Date::format($ctime, 'Y');
                $object->m       = Date::format($ctime, 'm') - 1;
                $object->d       = Date::format($ctime, 'd');
                $object->h       = Date::format($ctime, 'H');
                $object->i       = Date::format($ctime, 'i');
                $object->s       = Date::format($ctime, 's');
                $object->content = (string) V('vidmon:vidcam/timeline', ['vidcam' => $vidcam, 'vdata' => $v]);
                if ($v->is_alarm) {
                    $object->className = 'alarm';
                }
                $datas[$ctime] = $object;
            }
        }

        Output::$AJAX['vs'] = $datas;
    }

    public function index_restart_click()
    {

        $form   = Input::form();
        $vidcam = O('vidcam', $form['id']);

        if (!$vidcam->id) {
            return false;
        }

        $return = $vidcam->restart();

        JS::alert(I18N::T('vidmon', Vidcam_Model::$restart_message[$return]));
    }

    function index_stream_address_click() {
        $me = L('ME');

        $form = Input::form();
        $vidcam = O('vidcam', $form['id']);

        if (!$vidcam->id) return FALSE;

        if (!$me->is_allowed_to('查看', $vidcam)) {
            return FALSE;
        }

        $config = Config::get('stream');

        $time = date('c');

        $token = hash('sha256', $time.$config['auth.code']);

        $client = new \GuzzleHttp\Client([
            'base_uri' => $config['rtsp.url'].'/',
            'http_errors' => FALSE,
            'timeout' => 5
        ]);

        $response = $client->post('auth', [
            'form_params' => [
                'time' => $time,
                'code' => $token
            ]
        ])->getBody()->getContents();

        $response = json_decode($response, TRUE);

        $stream_address = $vidcam->stream_address.'?'.http_build_query($response);
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
        $stream_address = str_replace(["http://", "https://"], "$scheme://", $stream_address);
        $stream_address = preg_replace('/^(http|https):\/\/([^\/]+)(.*)$/', "$1://".$_SERVER['HTTP_HOST']."$3", $stream_address);

        Output::$AJAX = [
            'token' => $response['token'],
            'stream_address' => $stream_address,
        ];
	}
}
