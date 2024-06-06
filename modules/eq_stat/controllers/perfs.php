<?php

class Perfs_Controller extends Base_Controller {
	function index() {
		if (!L('ME')->is_allowed_to('列表', 'eq_perf')) URI::redirect('error/401');
		$form = Lab::form();
		$cat_root = Tag_Model::root('equipment');
		$group_root = Tag_Model::root('group');
		$selector = 'eq_perf';
		
		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
		}
		
		//时间交错查询，有交集则就列出
		if ($form['dtstart_check']) {
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[dfrom>=$dtstart | dto>$dtstart]";
		}
		if ($form['dtend_check']) {
			$dtend = Q::quote($form['dtend']);
			$selector .= "[dfrom<$dtend | dto<=$dtend]";
		}
		
		//标签查询， 选中其中一个则清空另外个缓存中的数据
		if ($form['tag']) {
			$tag = $form['tag'];
			if ($tag == 'cat') {
				$select_tag = O('tag_equipment', $form['cat_id']);
				unset($form['group_id']);
			}
			else {
				$select_tag = O('tag_group', $form['group_id']);
				unset($form['cat_id']);
			}
			if ($select_tag->root->id)  $selector .= "[collection={$select_tag}]";
		}
		
		
		$perfs = Q($selector);
		
		//分页设置，15条数据为1页
		$pagination = Lab::pagination($perfs, (int)$form['st'], 15);
		
		$content = V('perfs', [
						'cat_root'=>$cat_root,
						'group_root'=>$group_root,
						'perfs'=>$perfs,
						'pagination'=>$pagination,
						'form'=>$form	
					]);
					
		$this->layout->body->primary_tabs
			->select('perfs')
			->set('content', $content); 

	}
	
	function add() {
		if (!L('ME')->is_allowed_to('添加', 'eq_perf')) URI::redirect('error/401');
		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs
			->add_tab('add', [
							'url'=>URI::url('!eq_stat/perfs/add'),
							'title'=>I18N::T('eq_stat', '添加评估'),
						])
			->select('add');
			
		//获取仪器分类的root_tag
		$cat_root = Tag_Model::root('equipment');
		//获取组织结构的root_tag
		$group_root = Tag_Model::root('group');
		
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
			$form
				->validate('name', 'not_empty', I18N::T('eq_stat', '请输入评估名称！'))
				->validate('dfrom', 'is_numeric', I18N::T('eq_stat', '起始时间不合法。'))
				->validate('dto', 'is_numeric', I18N::T('eq_stat', '结束时间不合法。'))
				->validate('dto', 'compare(>=dfrom)', I18N::T('eq_stat', '结束时间不能在起始时间之前!'));

            $exist_perf = O('eq_perf', ['name'=>$form['name']]);
            if($exist_perf->id) {
                $form->set_error('name', I18N::T('eq_stat', '评估项目已存在!')) ;
            }
			
			if ($form->no_error) {
				$tag_type = $form['tag'];
				if ($tag_type == 'cat') {
					$tag = O('tag_equipment', $form['cat_id']);
				}
				elseif ($tag_type == 'group'){
					$tag = O('tag_group', $form['group_id']);
				}
				
				$perf = O('eq_perf');
				$perf->name = $form['name'];
				$perf->collection = $tag;
				$perf->dfrom = Date::get_day_start($form['dfrom']);
				$perf->dto = Date::get_day_end($form['dto']);
				
				if ($perf->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_stat', '绩效评估添加成功！'));
					URI::redirect('!eq_stat/perfs');
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '绩效评估添加失败！'));
				}
			}
		}
		
		$primary_tabs->content = V('add', [
									'cat_root'=>$cat_root,
									'group_root'=>$group_root,
									'form'=>$form
								]);
	}
	
	function perf_equipments($id=0) {
		$form = (array) Form::filter(Input::form());
		$perf = O('eq_perf', $id);
		if (!$perf->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('查看', $perf)) URI::redirect('error/401');
		$type = strtolower($form['type']);
		unset($form['type']);
		if ($form['form_token'] && isset($_SESSION[$form['form_token']])) {
			$selector = $_SESSION[$form['form_token']];
		}

		$valid_columns = Config::get('eq_stat.export_columns.eq_perf');
		$visible_columns = Input::form('columns');
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		
		$equipments = Q($selector);
		
		if (isset($type) && $type == 'print') {
			$this->_index_print($equipments, $perf, $valid_columns);
		}
		elseif (isset($type) && $type == 'csv') {
			$this->_index_csv($equipments, $perf, $valid_columns);
		}
	}
	
	private function _index_print($equipments, $perf, $valid_columns) {
		$this->layout = V('eq_perf_equipments_print', ['valid_columns'=>$valid_columns]);
		$this->layout->equipments = $equipments;
		$this->layout->perf = $perf;
	}
	
	private function _index_csv($equipments, $perf, $valid_columns) {
		$csv = new CSV('php://output', 'w');
		$csv->write(I18N::T('eq_stat',$valid_columns));
			
		if ($equipments->total_count() > 0) {
			$start = 0;
			$per_page = 100;
			while (1) {
				$tmp_equipments = $equipments->limit($start, $per_page);
				if ($tmp_equipments->length() == 0) break;
				foreach ($tmp_equipments as $equipment) {
					$scores = Perf::owner_score($equipment, $perf);
					$total = Perf::perf_score($equipment, $perf);
					$data = [];
					foreach ($valid_columns as $key => $value) {
						switch ($key) {
							case 'equipment':
								$data[] = $equipment->name;
								break;
							case 'score':
								$data[] = $scores['score'];
								break;
							case 'num':
								$data[] = $scores['average'];
								break;
							case 'average':
								$data[] = $scores['average'];
								break;
							case 'extra':
								$data[] = $total['total'] - $total['user_score'];
								break;
							case 'total':
								$data[] = $total['total'];
								break;

						}
					}
					$csv->write($data);
				}
				$start += $per_page;	
			}
		}
		
		$csv->close();	
	}
	
	function score_list($p_id=0, $e_id=0) {
		$perf = O('eq_perf', $p_id);
		$equipment = O('equipment', $e_id);
		if (!$perf->id || !$equipment->id) {
			URI::redirect('error/404');
		}
		if (!L('ME')->is_allowed_to('查看', $perf)) URI::redirect('error/401');
		$this->layout = V('eq_perf_equipment_list_print');
		$this->layout->equipment = $equipment;
		$this->layout->perf = $perf;
	}
}

class Perfs_Ajax_Controller extends AJAX_Controller {

		function index_output_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$perf_id = $form['perf_id'];
		$columns = Config::get('eq_stat.export_columns.eq_perf');
		
		if ($type == 'csv') {
			$title = I18N::T('eq_stat', '请选择要导出CSV的列');
		}
		else {
			$title = I18N::T('eq_stat', '请选择要打印的列');
		}
		JS::dialog(V('eq_stat:perf/export_form', [
						'type' => $type,	
						'form_token' => $form_token,
						'columns' => $columns,
						'perf_id' => $perf_id,
					]), [
						'title' => $title
					]);
	}


}
