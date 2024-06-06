<?php

class List_Controller extends Base_Controller {

	public function index($tab='all') {

		$me = L('ME');
		if( !$me->is_allowed_to('查看', O('staff') ) ) {
			URI::redirect('error/401');
		}

		$secondary_tabs = Widget::factory('tabs');
		
		$secondary_tabs
			->add_tab('all', [
				'url'=>URI::url("!staff/list/index.all"),
				'title'=>I18N::T('staff', '全部')
			]);
		foreach(Staff_Model::$roles as $key => $role){
			$secondary_tabs
				->add_tab('role'.$key, [
								'url'=>URI::url("!staff/list/index.$key"),
								'title'=>I18N::T('staff', $role)
							]);
		}
		$secondary_tabs
			->set('class', 'secondary_tabs')
			->select('role'.$tab);


		$panel_buttons =[];
		$panel_buttons[] = [
			'url' => URI::url("!staff/list/index.$tab?type=csv"),
			'text'  => I18N::T('people', '导出CSV'),
			'extra' => 'class="button button_save "',
		];
		$panel_buttons[] = [
			'url' => URI::url("!staff/list/index.$tab?type=print"),
			'text'  => I18N::T('people', '打印'),
			'extra' => 'class="button button_print " target="_black"',
		];

		//多栏搜索
		$form_token = Input::form('form_token');

		$form = Lab::form();
		$selector = 'staff';
		$pre_selectors = [];

		//role搜索
		if( $tab != 'all'){
			$selector .= "[role=$tab]";
		}

		//positions搜索
		if( $form['position'] ){
			$selector .= "[position={$form['position']}]";
		}

		//GROUP搜索
		$group = O('tag_group', $form['group_id']);
		$group_root = Tag_Model::root('group');
		if ($group->id && $group->root->id == $group_root->id) {
			$pre_selectors['group'] = "$group user<user";
		}
		else {
			$group = NULL;
		}


		if (count($pre_selectors)>0) {
			$selector = '('.implode(',', (array) $pre_selectors).') '.$selector;
		}
		$sort = 'contract_time, id';
		$selector .= ":sort($sort)";
		$staffs = Q($selector);

		$type = strtolower(Input::form('type'));
		if($type == 'print' ){
			$this->_export_print($staffs, $form);
		}else if($type == 'csv' ){
			$this->_export_csv($staffs, $form);
		}else{
			$start = (int) $form['st'];
			$per_page = 20;
			$start = $start - ($start % $per_page);
			$pagination = Lab::pagination($staffs, $start, $per_page);

			$this->layout->body->primary_tabs->content
				= V('list',[
					'secondary_tabs'=>$secondary_tabs,
					'panel_buttons'=>$panel_buttons,
					'form' => $form,
					'pagination' => $pagination,
					'staffs' => $staffs,
					'group' => $group,
					'group_root' => $group_root,
					'positions' => Staff::get_positions()
				]);
		}


	}

	private function _export_print($staffs, $form){
		$this->layout = V('staff:staffs_print', [
			'staffs' => $staffs
		]);

	}

	public function _export_csv($staffs, $form){

		$csv = new CSV('php://output', 'w');

		$csv->write([
				I18N::T('staff', '工号'),
				I18N::T('staff', '姓名'),
				I18N::T('staff', '性别'),
				I18N::T('staff', '年龄'),
				I18N::T('staff', '籍贯'),
				I18N::T('staff', '省份证号'),
				I18N::T('staff', '学历'),
				I18N::T('staff', '学校'),
				I18N::T('staff', '专业'),
				I18N::T('staff', '联系电话'),
				I18N::T('staff', '部门'),
				I18N::T('staff', '职位'),
				I18N::T('staff', '合同开始时间'),
				I18N::T('staff', '合同结束时间'),
				I18N::T('staff', '有效期'),
		]);

		foreach ($staffs as $staff ) {

			$csv->write( [
				$staff->user->ref_no,
				$staff->user->name,
				I18N::T('staff', User_Model::$genders[$staff->user->gender] ),
				V('staff:staffs_table/data/age', ['staff'=>$staff]),
				$staff->birthplace,
				$staff->IDnumber,
				I18N::T('staff', $staff->get_education()),
				$staff->school,
				$staff->professional,
				$staff->user->phone,
				$staff->user->group->name,
				V('staff:staffs_table/data/position', ['staff'=>$staff]),
				Date::format($staff->start_time, 'Y/m/d'),
				Date::format($staff->contract, 'Y/m/d'),
				V('staff:staffs_table/data/effective_time', ['staff'=>$staff])
			]);
		}
		$csv->close();

	}
}
