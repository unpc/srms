<?php

class Building_Controller extends Base_Controller {
	
	function index($id=0, $tab='2d') {
		/* NO.TASK#310 为gismon设置权限	*/
		/* (xiaopei.li@2010.12.20)		*/
		$building = O('gis_building',$id);

		if (!L('ME')->is_allowed_to('查看', $building)) {
			URI::redirect('error/401');
		}

		if (!$building->id) {
			URI::redirect('error/404');
		}

		$this->layout->body->primary_tabs
			->add_tab('view',[
					'url'=>$building->url(NULL,NULL,NULL,'view'),
					'title'=>I18N::T('gismon','%name',['%name'=>H($building->name)]),
				])
			->select('view');
		
		$content = V('building/view');
		$content->building = $building;
		
		$content->secondary_tabs = Widget::factory('tabs');
		
		Event::bind('gismon.building.index.tab.content', [$this, '_index_2d'], 0, '2d');
		//Event::bind('gismon.building.index.tab.content', array($this, '_index_3d'), 0, '3d');
				
		$group_root = Tag_Model::root('group');
		
		$content->group_root = $group_root;

		$content->secondary_tabs
			->set('building', $building)
			->set('class', 'secondary_tabs')
			->tab_event('door.index.tab')
			->content_event('gismon.building.index.tab.content')
			->add_tab('2d',[
					'url'=> $building->url('2d'),
					'title'=>I18N::T('gismon','平面监控'),
				])
			/*
			->add_tab('3d',array(
					'url'=> $building->url('3d'),
					'title'=>I18N::T('gismon','立体监控'),
				))*/
			->select($tab);
			
		$this->add_css('gismon:common');
		$this->layout->body->primary_tabs->content = $content;
	}

	function _index_2d($e, $tabs) {
		$building = $tabs->building;
	
		$this->add_js('gismon:blueprint', FALSE);	

		$tabs->content = V('gismon:building/view_2d', ['building' => $building]);	
	}

	function _index_3d($e, $tabs) {
		$building = $tabs->building;
		
		$this->add_js(Config::get('system.base_url').'o3djs/base.js');
		$tabs->content = V('gismon:building/view_3d', ['building' => $building]);	
	}
		
	
	function edit($id = 0, $tab = 'info') {
		$building = O('gis_building', $id);

		if (!L('ME')->is_allowed_to('修改', $building)) {
			URI::redirect('error/401');
		}

		if (!$building->id) {
			URI::redirect('error/404');
		}

			
		$content = V('building/edit');
		
		Event::bind('building.edit.content', [$this,'_edit_info'], '0', 'info');
		Event::bind('building.edit.content', [$this,'_edit_photo'], '0', 'photo');
		Event::bind('building.edit.content', [$this,'_edit_rule'], '0', 'rule');

        $this->layout->body->primary_tabs = Widget::factory('tabs');

        $this->layout->body->primary_tabs
			->add_tab('info',[
					'url'=>$building->url('info',NULL,NULL,'edit'),
					'title'=>I18N::T('gismon','基本信息'),
				]);
//			->add_tab('photo',[
//					'url'=>$building->url('photo',NULL,NULL,'edit'),
//					'title'=>I18N::T('gismon','楼宇图标'),
//				])
//            ->add_tab('rule',array(
//                    'url'=>$building->url('rule',NULL,NULL,'edit'),
//                    'title'=>I18N::T('gismon','楼宇规则'),
//                ));

        $this->layout->body->primary_tabs
				->set('building', $building)
				->tab_event('building.edit.tab')
				->content_event('building.edit.content')
				->select($tab);

		$breadcrumb = [
            [
                'url'=>'!gismon',
                'title'=>I18N::T('gismon','楼宇列表'),
            ],
			[
				'url'=>$building->url(NULL,NULL,NULL,'view'),
				'title'=>I18N::T('gismon','%name',['%name'=>H($building->name)]),
			],

			[
				'title'=>I18N::T('gismon','修改'),
			],
		];

        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumb]);
	}
	
	function _edit_info($e, $tabs) {
		$building = $tabs->building;

		$group_root = Tag_Model::root('group');

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

		if(Input::form('submit')) {
			
			$form = Form::filter(Input::form());
		
			$form->validate('name', 'not_empty', I18N::T('gismon', '楼宇名称不能为空！'));

            Event::trigger('gismon_building[edit].post_submit_validate', $form);


            if (!is_numeric($form['floors']) || $form['floors'] <= 0) {
				$form->set_error('floor', I18N::T('gismon', '楼宇楼层填写有误，请重新填写！'));
			}
			
			if($form->no_error) {
				$building->name = $form['name'];
				$building->longitude = $form['longitude'];
				$building->latitude = $form['latitude'];
				$building->floors = $form['floors'];
				$building->width = $form['width'];
				$building->height = $form['height'];

				$group = O('tag_group', $form['group_id']);
				
				$group_root->disconnect($building);
				$building->group = NULL;
				
				if ($group->root->id == $group_root->id) {
					$group_root->disconnect($building);
					$group->connect($building);
					$building->group = $group;
				}

                Event::trigger('gismon_building[edit].post_submit', $form, $building);

                if ($building->save()) {
					/*添加记录*/
					Log::add(strtr('[gismon] %user_name[%user_id] 修改了楼宇 %building_name[%building_id] 的基本信息', [
								'%user_name' => L('ME')->name,
								'%user_id' => L('ME')->id,
								'%building_name' => $building->name,
								'%building_id' => $building->id,
					]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '楼宇信息更新成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '楼宇信息更新失败!'));
				}
			}
		}
		
		$tabs->content = V('building/edit.info', ['group_root'=>$group_root, 'building'=>$building, 'form'=>$form]);
		
	}
	
	function _edit_photo($e, $tabs) {
		$building = $tabs->building;
		
		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					$building->save_icon(Image::load($file['tmp_name'], $ext));
	
					/*添加记录*/
					Log::add(strtr('[gismon] %user_name[%user_id] 修改了楼宇 %building_name[%building_id] 的图标', [
								'%user_name' => L('ME')->name,
								'%user_id' => L('ME')->id,
								'%building_name' => $building->name,
								'%building_id' => $building->id, 
					]), 'journal');				

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '楼宇图标已更新'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '楼宇图标更新失败!'));
				}
			}
			else{
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '请选择您要上传的楼宇图片。'));
			}
		}
	
		$tabs->content = V('building/edit.photo');
	}
	
	function delete($id=0) {

		$building = O('gis_building',$id);

		if (!L('ME')->is_allowed_to('删除', $building)) {
			URI::redirect('error/401');
		}

		if($building->id) {
			//删除此楼宇的所有记录
			Q("$building dc_record")->delete_all();
			$user = L('ME');
			if($building->delete()) {
		
				/*添加记录*/	
				Log::add(strtr('[gismon] %user_name[%user_id] 删除了楼宇 %building_name[%building_id] ', [
							'%user_name' => $user->name,
							'%user_id' => $user->id,
							'%building_name' => $building->name,
							'%building_id' => $building->id,
				]), 'journal');
			
				Lab::message(Lab::MESSAGE_NORMAL,I18N::T('gismon','楼宇删除成功!'));
			}
			URI::redirect('!gismon');
		}
	}
	
	function delete_photo($id=0) {

		$building = O('gis_building',$id);

		if (!L('ME')->is_allowed_to('修改', $building)) {
			URI::redirect('error/401');
		}
		
		$building->delete_icon();
		
		/*添加记录*/
		Log::add(strtr('[gismon] %user_name[%user_id] 删除了楼宇 %building_name[%building_id] 的图标', [
					'%user_name' => L('ME')->name,
					'%user_id' => L('ME')->id,
					'%building_name' => $building->name,
					'%building_id' => $building->id,
		]), 'journal');
			
		URI::redirect($building->url('photo', NULL, NULL, 'edit'));
	}

	function equipment_list($id = 0) {

		$building = O('gis_building',$id);
		if (!$building->id) {
			exit(0);
		}

		if (!L('ME')->is_allowed_to('修改', 'gis_device')) {
			exit(0);
		}

		$form = Lab::form();

		$selector = 'equipment';
		
		if ($form['location']) {
			$location = Q::quote(trim($form['location']));
			$selector .="[location*=$location|location2*=$location]";
		}

		if ($form['name']) {
			$name = Q::quote(trim($form['name']));
			$selector .="[name*=$name]";
		}

		if ($form['group_id']) {
			$group = O('tag_group', $form['group_id']);
			if ($group->id && $group->root->id) {
				$selector = $group . ' ' . $selector;
			}
		}

		$start = (int) $form['st'];
		$per_page = 10;

        $selector = Event::trigger('gismon.device.extra_selector', $selector, $form) ? : $selector;
        $equipments = Q($selector);
		$pagination = Lab::pagination($equipments, $start, $per_page);
		
		$group_root = Tag_Model::root('group');

		echo V('gismon:building/equipment_list', [
			'building' => $building,
			'form' => $form,
			'group' => $group,
			'group_root' => $group_root,
			'equipments' => $equipments,
			'pagination' => $pagination,
		]);

		exit;

	}


}

class Building_AJAX_Controller extends AJAX_Controller {

	/**
	 * 从楼宇移除设备(xiaopei.li@2011.07.01)
	 *
	 * @param  id 楼宇id
	 */
	function index_remove_device_click($id = 0) {
		$me = L('ME');
		if (!$me->is_allowed_to('修改', 'gis_device')) {
			exit;
		}

		$building = O('gis_building', $id);
		if (!$building->id) {
			exit;
		}

		$form = Form::filter(Input::form());

		switch ($form['model']) {
		case 'equipment':
		case 'door':
			$object = O($form['model'], $form['id']);
			break;
		default:
			exit;
		}

		$device = O('gis_device', ['object'=>$object]);

		if (!$device->id) {
			exit;
		}

		if (JS::confirm(I18N::T('gismon', '您确定要从此层移除该设备么?'))) {
			if ($device->delete()) {
				Log::add(strtr('[gismon] %user_name[%user_id] 移除了平面监控上的仪器 %object_name[%object_id]', [
							'%user_name' => $me->name,
							'%user_id' => $me->id,
							'%object_name' => $device->object->name,
							'%object_id' => $device->object->id,
				]), 'journal');
			};
			
			JS::refresh();
		}
	}

	function index_pickup_device_click($id = 0) {
		$building = O('gis_building', $id);
		if (!$building->id) {
			exit;
		}
		
		$form = (array) Input::form();
		$floor = min(max($form['floor'], 0), $building->floors - 1);
		$rect = $form['rect'];

		switch ($form['model']) {
		case 'equipment': case 'door':
			$object = O($form['model'], $form['id']);
			break;
		default:
			exit;
		}
		
		if (!$object->id) {
			exit;
		}
		
		$device = O('gis_device', ['object'=>$object]);
		$device->object = $object;
		$device->building = $building;
		$device->floor = $floor;
		$device->x = $rect['left'] + floor($rect['width'] / 2);
		$device->y = $rect['top'] + floor($rect['height'] / 2);
		$device->save();
		
		/*添加记录*/
		Log::add(strtr('[gismon] %user_name[%user_id] 在平面监控上添加了新的仪器 %object_name[%object_id]', [
					'%user_name' => L('ME')->name,
					'%user_id' => L('ME')->id,
					'%object_name' => $device->object->name,
					'%object_id' => $device->object->id,
		]), 'journal');

		$command = (string) JS::smart()->Q->broadcast('@window', 'gismon.add_device', $device->info());
		
		JS::run($command);
	}
	
	function index_device_position_update($id = 0) {
		$building = O('gis_building', $id);
		if (!$building->id) {
			exit;
		}
		
		$form = (array) Input::form();

		$device = O('gis_device', $form['id']);
		if ($device->id) {
			$device->x = $form['x'];
			$device->y = $form['y'];
			$device->save();

			/*添加记录*/		
			Log::add(strtr('[gismon] %user_name[%user_id] 修改了仪器 %object_name[%object_id] 的位置', [
						'%user_name' => L('ME')->name,
						'%user_id' => L('ME')->id,
						'%object_name' => $device->object->name,
						'%object_id' => $device->object->id,
			]), 'journal');

			$object = $device->object;
			$command = (string) JS::smart()->Q->broadcast('@window', 'gismon.add_device', $device->info());
			
			JS::run($command);
		}

	}

	function index_add_equipment_click($id = 0) {
		$building = O('gis_building', $id);
		if (!$building->id) {
			exit;
		}

		if (!L('ME')->is_allowed_to('修改', 'gis_device')) {
			exit;
		}

		JS::dialog(V('gismon:building/add_equipment', ['building'=>$building]), ['width' => 480]);
	}
	

}
