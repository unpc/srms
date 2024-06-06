<?php

class Index_Controller extends Base_Controller {
	function index(){
		URI::redirect('!nrii/nrii');
	}

	function nrii($tab='device')
	{

		Event::bind('nrii.index.content', [$this, '_index_device_content'], 0, 'device');
		Event::bind('nrii.index.content', [$this, '_index_center_content'], 0, 'center');
		Event::bind('nrii.index.content', [$this, '_index_unit_content'], 0, 'unit');
		Event::bind('nrii.index.content', [$this, '_index_equipment_content'], 0, 'equipment');
		Event::bind('nrii.index.content', [$this, '_index_service_content'], 0, 'service');
		Event::bind('nrii.index.content', [$this, '_index_record_content'], 0, 'record');

		$this->layout->body->primary_tabs
            ->tab_event('nrii.index.tab')
			->content_event('nrii.index.content')
			->select($tab);
	}

	function download($name = NULL){
		if (in_array($name, ['device', 'center', 'unit', 'equipment', 'intensityFile', 'specializationFile'])) {
		    if(!$fullpath = Event::trigger('nrii.equipment.getfile',$name.'.xls')){
                $fullpath = Nrii_Import::getFile($name.'.xls');
            }
			Downloader::download($fullpath);
			exit();
		}

	}

	//用于提示批量导入错误信息
	function importMessage($mode){
		if ($_SESSION[$mode . '_import']){
		    $result = $_SESSION[$mode . '_import'];
		    unset($_SESSION[$mode . '_import']);

		    switch ($mode) {
		    	case 'device':
		    		$name = '大型科学装置';
		    		break;
		    	case 'center':
		    		$name = '科学仪器中心';
		    		break;
		    	case 'unit':
		    		$name = '科学仪器服务单元';
		    		break;
		    	case 'equipment':
		    		$name = '科学仪器';
		    		break;
		    	default:
		    		return;
		    		break;
		    }
		    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii',
		        strtr($name . '已导入[%eq_new]个，失败[%eq_failed]个（共%eq_total个）',
		                ['%eq_total'=> $result[0], '%eq_new'=> $result[1], '%eq_failed' => $result[2]]
		            )
		        ));
		    foreach ($result[3] as $value) {
		        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii',
		        strtr('[%eq_name]成功',
		                ['%eq_name'=> $value]
		            )
		        ));
		    }
		    foreach ($result[4] as $value) {
		        Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii',
		        strtr('[%eq_name]失败，原因: %reason',
		                ['%eq_name' => $value['name'], '%reason' => $value['reason']]
		            )
		        ));
		    }
		}
	}

	//用于提示批量修改信息
	function batchMessage(){
		$result = $_SESSION['nrii_record_massage'];
		unset($_SESSION['nrii_record_massage']);

		if (!empty($result['error'])){
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '批量修改失败!'));
		}elseif(count($result) == 2){
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', "批量修改成功{$result[0]}个，失败{$result[1]}个"));
		}
	}

	function _index_device_content($e, $tabs) {
		$this->importMessage('device');

		$form = Lab::form();

		$panel_buttons = [
			'add' => [
				'text'  => I18N::T('nrii', '新增装置'),
				'extra' => 'class="button button_add" ',
				'url' => URI::url('!nrii/device/add')
			],
			'import' => [
				'text'  => I18N::T('nrii', '批量导入'),
				'extra' => 'class="button button_import" q-object="import" q-event="click" q-src="' . H(URI::url('!nrii/device')) . '"',
				'url' => URI::url('!nrii/device/import')
			],
			'download' => [
				'text'  => I18N::T('nrii', '下载模板'),
				'extra' => 'class="button button_export"',
				'url' => URI::url('!nrii/download.device')
			],
			'sync' => [
				'text'  => I18N::T('nrii', '上传至国家科技部'),
				'extra' => 'class="button button_refresh" ',
				'url' => URI::url('!nrii/device/sync')
			],
			'export' => [
				'text'  => I18N::T('nrii', '导出'),
				'extra' => 'class="button button_save" q-object="device_export" q-event="click" q-src="' . H(URI::url('!nrii/device')) . '"'
			],
		];

        $extraButtons = Event::trigger('nrii.device.extra.buttons', $form, $panel_buttons);
        if($extraButtons) {
            $panel_buttons = $extraButtons;
        }

		$selector  = 'nrii_device';

		if ($form['cname']){
			$selector .= '[cname*='.trim($form['cname']).']';
		}
		if ($form['innerId']) {
			$selector .= '[inner_id*='.trim($form['innerId']).']';
		}
		if ($form['ename']){
			$selector .= '[ename*='.trim($form['ename']).']';
		}
		if ($form['worthmin'] && $form['worthmax']){
			$selector .= '[worth='.trim($form['worthmin']).'~'.trim($form['worthmax']).']';
		}
		if ($form['nation']){
			$selector .= '[nation*='.trim($form['nation']).']';
		}
		if ($form['contact']){
			$selector .= '[contact*='.trim($form['contact']).']';
		}

        $newSelector = Event::trigger('nrii.device.extra.selector', $form, $selector);
        if ($newSelector) {
            $selector = $newSelector;
        }

		$devices = Q($selector);
		$_SESSION['nrii_device'] = $selector;
		$pagination = Lab::pagination($devices, (int)$form['st'], 15);

		$tabs->content = V('nrii:device/list', [
				'form' => $form,
				'devices' => $devices,
				'pagination' => $pagination,
				'panel_buttons' => $panel_buttons
			]);

	}

	function _index_center_content($e, $tabs) {

		$this->importMessage('center');

		$form = Lab::form();

		$panel_buttons = [
			'add' => [
				'text'  => I18N::T('nrii', '新增仪器中心'),
				'extra' => 'class="button button_add" ',
				'url' => URI::url('!nrii/center/add')
			],
			'import' => [
				'text'  => I18N::T('nrii', '批量导入'),
				'extra' => 'class="button button_import" q-object="import" q-event="click" q-src="' . H(URI::url('!nrii/center')) . '"',
				'url' => URI::url('!nrii/center/import')
			],
			'download' => [
				'text'  => I18N::T('nrii', '下载模板'),
				'extra' => 'class="button button_export"',
				'url' => URI::url('!nrii/download.center')
			],
			'sync' => [
				'text'  => I18N::T('nrii', '上传至国家科技部'),
				'extra' => 'class="button button_refresh" ',
				'url' => URI::url('!nrii/center/sync')
			],
			'export' => [
				'text'  => I18N::T('nrii', '导出'),
				'extra' => 'class="button button_save" q-object="center_export" q-event="click" q-src="' . H(URI::url('!nrii/center')) . '"'
			],
		];

        $extraButtons = Event::trigger('nrii.center.extra.buttons', $form, $panel_buttons);
        if($extraButtons) {
            $panel_buttons = $extraButtons;
        }

		$selector  = 'nrii_center';

		if ($form['centname']){
			$selector .= '[centname*='.trim($form['centname']).']';
		}
		if ($form['innerId']){
			$selector .= '[inner_id*='.trim($form['innerId']).']';
		}
		if ($form['level']){
			$selector .= '[level='.trim($form['level']).']';
		}
		if ($form['contact']){
			$selector .= '[contact*='.trim($form['contact']).']';
		}

		$newSelector = Event::trigger('nrii.center.extra.selector', $form, $selector);
		if ($newSelector) {
		    $selector = $newSelector;
        }

		$centers = Q($selector);
		$_SESSION['nrii_center'] = $selector;

		$pagination = Lab::pagination($centers, (int)$form['st'], 15);
		$tabs->content = V('nrii:center/list', [
				'form' => $form,
				'centers' => $centers,
				'pagination' => $pagination,
				'panel_buttons' => $panel_buttons
			]);

	}

	function _index_unit_content($e, $tabs) {

		$this->importMessage('unit');

		$form = Lab::form();

		$panel_buttons = [
			'add' => [
				'text'  => I18N::T('nrii', '新增服务单元'),
				'extra' => 'class="button button_add" ',
				'url' => URI::url('!nrii/unit/add')
			],
			'import' => [
				'text'  => I18N::T('nrii', '批量导入'),
				'extra' => 'class="button button_import" q-object="import" q-event="click" q-src="' . H(URI::url('!nrii/unit')) . '"',
				'url' => URI::url('!nrii/unit/import')
			],
			'download' => [
				'text'  => I18N::T('nrii', '下载模板'),
				'extra' => 'class="button button_export"',
				'url' => URI::url('!nrii/download.unit')
			],
			'sync' => [
				'text'  => I18N::T('nrii', '上传至国家科技部'),
				'extra' => 'class="button button_refresh" ',
				'url' => URI::url('!nrii/unit/sync')
			],
		];

		$selector  = 'nrii_unit';

		if ($form['unitname']){
			$selector .= '[unitname*='.trim($form['unitname']).']';
		}
		if ($form['category']){
			$selector .= '[category='.trim($form['category']).']';
		}

		//按时间搜索
		if($form['dateOn']){
			if($form['beginDate1']){
				$form['beginDate1'] = strtotime(date("Y-m-d", $form['beginDate1']));
				$beginDate1 = Q::quote($form['beginDate1']);
				$selector .= "[begin_date>=$beginDate1]";
			}

			if($form['beginDate2']){
				$form['beginDate2'] = strtotime(date("Y-m-d", $form['beginDate2'])) + 86399;
				$beginDate2 = Q::quote($form['beginDate2']);
				$selector .= "[begin_date<=$beginDate2]";
			}
		}

		if ($form['contact1']){
			$selector .= '[contact*='.trim($form['contact1']).']';
		}
		$units = Q($selector);

		$pagination = Lab::pagination($units, (int)$form['st'], 15);
		$tabs->content = V('nrii:unit/list', [
				'form' => $form,
				'units' => $units,
				'pagination' => $pagination,
				'panel_buttons' => $panel_buttons
			]);

	}

	function _index_equipment_content($e, $tabs) {

		$this->importMessage('equipment');

		$form = Lab::form();

		if (L('ME')->is_allowed_to('管理', 'nrii')) {
			$panel_buttons = [
				'add' => [
					'text'  => I18N::T('nrii', '新增科学仪器'),
					'extra' => 'class="button button_add" ',
					'url' => URI::url('!nrii/equipment/add')
				],
				'import' => [
					'text'  => I18N::T('nrii', '批量导入'),
					'extra' => 'class="button button_import" q-object="import" q-event="click" q-src="' . H(URI::url('!nrii/equipment')) . '"',
					'url' => URI::url('!nrii/equipment/import')
				],
				'download' => [
					'text'  => I18N::T('nrii', '下载模板'),
					'extra' => 'class="button button_export"',
					'url' => URI::url('!nrii/download.equipment')
				],
				'sync' => [
					'text'  => I18N::T('nrii', '上传至国家科技部'),
					'extra' => 'class="button button_refresh" ',
					'url' => URI::url('!nrii/equipment/sync')
				],
				'export' => [
					'text'  => I18N::T('nrii', '导出'),
					'extra' => 'class="button button_save" q-object="equipment_export" q-event="click" q-src="' . H(URI::url('!nrii/equipment')) . '"'
				],
			];
		}


		$selector  = 'nrii_equipment';

		if ($form['eq_name']){
			$selector .= '[eq_name*='.trim($form['eq_name']).']';
		}
		if ($form['innerId']){
			$selector .= '[inner_id*='.trim($form['innerId']).']';
		}
		if ($form['class']){
			$class = substr(trim($form['class']), 0, 2);
			$selector .= '[class^='.$class.']';
		}
		if ($form['nation']){
			$selector .= '[nation*='.trim($form['nation']).']';
		}
		if ($form['worthmin'] && $form['worthmax']){
			$selector .= '[worth='.trim($form['worthmin']).'~'.trim($form['worthmax']).']';
		}elseif ($form['worthmin']) {
			$selector .= '[worth>=' . trim($form['worthmin']).']';
		}elseif ($form['worthmax']){
			$selector .= '[worth<=' . trim($form['worthmax']).']';
		}
		if ($form['affiliate']){
			$selector .= '[affiliate='.trim($form['affiliate']).']';
		}
		if ($form['affiliate_name']){
			$selector .= '[affiliate_name*='.trim($form['affiliate_name']).']';
		}
        if (isset($form['nrii_status']) && $form['nrii_status'] != -1){
            $selector .= '[nrii_status='.trim($form['nrii_status']).']';
        }
        if (isset($form['shen_status']) && $form['shen_status'] != -1){
            $selector .= '[shen_status='.trim($form['shen_status']).']';
        }
        $selector .= Event::trigger('nrii_equipment.extra.filter', $form);
		$equipments = Q($selector);
		$_SESSION['nrii_equipment'] = $selector;
		$total_count = $equipments->total_count();

		$pagination = Lab::pagination($equipments, (int)$form['st'], 15);
		$tabs->content = V('nrii:equipment/list', [
				'form' => $form,
				'equipments' => $equipments,
				'total_count' => $total_count,
				'pagination' => $pagination,
				'panel_buttons' => (array)$panel_buttons
			]);

	}

	function _index_service_content($e, $tabs) {
		$form = Lab::form();

		$keys = Nrii::$serivice_keys;
        foreach ($keys as $key) {
            $service[$key] = Lab::get('nrii.service.' . $key, $form[$key]);
        }
		$panel_buttons = [
			'edit' => [
				'text'  => I18N::T('nrii', '编辑服务成效'),
				'extra' => 'class="button button_edit" ',
				'url' => URI::url('!nrii/service/edit')
			],
			'sync' => [
				'text'  => I18N::T('nrii', '上传至国家科技部'),
				'extra' => 'class="button button_refresh" ',
				'url' => URI::url('!nrii/service/sync')
			],
		];

		$tabs->content = V('nrii:service/view', [
				'service' => $service,
				'panel_buttons' => $panel_buttons
			]);
	}

	function _index_record_content($e, $tabs) {
		$me = L('ME');

		$this->batchMessage();

		$form = Lab::form();

		$panel_buttons = [
			'batch' => [
				'text'  => I18N::T('nrii', '批量修改'),
				'extra' => 'class="button button_edit" id="batch_edit" q-object="batch_edit" q-event="click" q-src="' . H(URI::url('!nrii/record')) . '"',
				'url' => URI::url('!nrii/record/batch')
			],
			'sync' => [
				'text'  => I18N::T('nrii', '上传至国家科技部'),
				'extra' => 'class="button button_refresh" ',
				'url' => URI::url('!nrii/record/sync')
			],
			'export' => [
				'text' => I18N::T('nrii', '导出Excel'),
				'extra' => 'class="button button_save" id="export" q-object="export" q-event="click" q-src="' . H(URI::url('!nrii/record')) . '"',
				'url' => URI::url('!nrii/record/export')
			],
		];

		if ($me->access('管理所有内容') || $me->access('科技部对接管理')) {
			$panel_buttons['refresh'] = [
				'text' => I18N::T('nrii', '数据刷新'),
				'extra' => 'class="button button_save" id="refresh" q-object="refresh" q-event="click" q-src="' . H(URI::url('!nrii/record')) . '"',
				'url' => URI::url('!nrii/record/refresh')
			];
		}

		$selector  = 'nrii_record';

		if ($form['eq_name']){
			$selector .= '[eq_name*='.trim($form['eq_name']).']';
		}
		if ($form['innerId']){
			$selector .= '[inner_id*='.trim($form['innerId']).']';
		}

		if ($form['worthmin'] && $form['worthmax']){
			$selector .= '[amounts='.trim($form['worthmin']).'~'.trim($form['worthmax']).']';
		}elseif ($form['worthmin']) {
			$selector .= '[amounts>=' . trim($form['worthmin']).']';
		}elseif ($form['worthmax']){
			$selector .= '[amounts<=' . trim($form['worthmax']).']';
		}
		if ($form['subject']){
			$selector .= '[subject_name*=' . $form['subject'] . ']';
		}
		if ($form['applicant']){
			$selector .= '[applicant*='.trim($form['applicant']).']';
		}
		if (isset($form['nrii_status']) && $form['nrii_status'] != -1){
			$selector .= '[nrii_status='.trim($form['nrii_status']).']';
		}

        $selector .= Event::trigger('nrii_record.extra.filter', $form);
		$_SESSION['nrii_record'] = $selector;

		$records = Q($selector);

		$pagination = Lab::pagination($records, (int)$form['st'], 15, NULL,'nrii_record', URI::url('!nrii/record'));
		$tabs->content = V('nrii:record/list', [
				'form' => $form,
				'records' => $records,
				'pagination' => $pagination,
				'panel_buttons' => $panel_buttons
			]);
	}
}
