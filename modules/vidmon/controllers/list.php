<?php
class List_Controller extends Base_Controller {
	function index() {

		$form = Lab::form(function(& $old_form, & $form) {});		

		$selector = 'vidcam';

		if($form['name']){
			$name = Q::quote(trim($form['name']));
			$selector .= "[name_abbr*=$name|name*=$name]";
		}
		if ($form['location']) {
			$location = Q::quote(trim($form['location']));
			$selector .= "[location*=$location|location2*=$location]";
		}
		$selector = Event::trigger('vidmon.vidcam.extra_selector', $selector, $form) ? : $selector;
        $selector .= ':sort(name_abbr ASC, id ASC)';

        $user = L('ME');
        $pre_selector = [];

		// 此功能设计极其不合适
		// 产品既要求不改变原有设计(我强烈怀疑产品是因为不知道原有设计)，又要加新功能
		if (!in_array($user->token, Config::get('lab.admin'))
			&& !$user->access('管理所有内容')) {
                if (!$user->access('查看视频监控模块')) {
                    if (Module::is_installed('eq_vidcam')) {
                        $pre_selector[] = "{$user}<incharge|{$user}<incharge equipment<camera";
                    }else{
                        $pre_selector[] = "{$user}<incharge";
                    }
                }
        }

        if ($form['incharge']) {
            $incharge = Q::quote(trim($form['incharge']));
            $pre_selector[] = "user[name*={$incharge}]<incharge";
        }

        if (count($pre_selector)) {
            $selector = '('. join(', ', $pre_selector) .  ") {$selector}";
        }
		$vidcams = Q($selector);

        $start = (int) $form['st'];
        $per_page = Config::get('per_page.vidmon', 25);
        $pagination = Lab::pagination($vidcams, $start, $per_page);

        $panel_buttons = new ArrayIterator;
        if ($user->is_allowed_to('添加', 'vidcam')) {
            if (Event::trigger('db_sync.need_to_hidden', 'vidcam')) {
                $panel_buttons[] = [
                    'text'=>I18N::T('vidmon','添加摄像头'),
                    'tip'=>I18N::T('vidmon','添加摄像头'),
                    'extra'=>'q-object="vidcam_add" q-event="click" q-src="' . Event::trigger('db_sync.transfer_to_master_url', '!vidmon/vidcam', '', true) .
                        '" class="button button_add "',
                ];
            } else {
                $panel_buttons[] = [
                    'text'=>I18N::T('vidmon','添加摄像头'),
                    'tip'=>I18N::T('vidmon','添加摄像头'),
                    'extra'=>'q-object="vidcam_add" q-event="click" q-src="' . URI::url('!vidmon/vidcam') .
                        '" class="button button_add "',
                ];
            }
        }

        if (Config::get('stream')['use_stream']){
            $panel_buttons[] = [
                'text'=>I18N::T('vidmon','刷新'),
                'tip'=>I18N::T('vidmon','刷新列表'),
                'extra'=>'q-object="refresh_list" q-event="click" q-src="' . URI::url('!vidmon/stream') .
                    '" class="button icon-refresh "',
            ];
        }

        $columns = self::get_vidmon_field($form);
        $search_box = V('application:search_box', ['panel_buttons' => $panel_buttons,'top_input_arr'=>['name', 'location', 'incharge'], 'columns' => $columns]);

		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->content = V('list', [
			'vidcams' => $vidcams,
			'form'=>$form,
            'pagination'=> $pagination,
            'columns'=>$columns,
            'search_box'=>$search_box,
		]);

		$primary_tabs->select('list');
	}

	function get_vidmon_field($form){
        $columns = [
            'name' => [
                'title' => I18N::T('vidmon', '名称'),
                'filter' => [
                    'form' => V('vidmon:vidcam_table/filter/name', ['name' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : NULL
                ],
                'nowrap' => TRUE
            ],
            'status'=>[
                'title' => I18N::T('vidmon', '状态'),
                'nowrap' =>TRUE
            ],
            'location' => [
                'title' => I18N::T('vidmon', '地址'),
                'filter' => [
                    'form' => V('vidmon:vidcam_table/filter/location', ['location' => $form['location']]),
                    'value' => $form['location'] ? H($form['location']) : NULL
                ],
                'nowrap'=>TRUE,

            ],
            'incharge'=> [
                'title'=> I18N::T('vidmon', '负责人'),
                'nowrap'=> TRUE,
            ],
            'rest' => [
                'title'=>'操作',
                'align' => 'right',
                'nowrap' => TRUE,
            ]
        ];
        if (in_array(L('ME')->token, Config::get('lab.admin')) || L('ME')->access('管理所有内容')) {
            $columns['incharge']['filter'] = [
                'form'=> V('vidmon:vidcam_table/filter/incharge', ['incharge'=> $form['incharge']]),
                'value'=> $form['incharge'] ? H($form['incharge']) : null,
            ];
        }
        $columns = new ArrayIterator($columns);
        Event::trigger('extra.vidcam.column', $columns, $vidcam, $form);
        return (array) $columns;
	}
}
