<?php

class History_Controller extends Base_Controller {

	function index() {
		$me = L('ME');
		$form = Lab::form(); 
		$start = $form['start'] ? $form['start'] : 0;
		$sort_by = $form['sort'] ? $form['sort'] : 'ctime';
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		$selector = "happy_reply[replyer={$me}]";
		
		//if ($me->is_allowed_to('创建', 'happyhour')) $selector = "happy_reply";
		
		switch ($sort_by) {
		case 'replyer':
			$selector .= ":sort(replyer_id $sort_flag)";
			break;
		case 'content':
			$selector .= ":sort(content $sort_flag)";
			break;
		case 'stock':
			$selector .= ":sort(stock $sort_flag)";
			break;
		case 'ctime':
			$selector .= ":sort(ctime $sort_flag)";
			break;
		default:
			$selector .= ":sort(ctime D)";
			break;
		}
		$happyreplys = Q("$selector");

		$start = (int) $form['st'];
		$per_page = 25;
		$start = $start - ($start % $per_page);
		$pagination = Lab::pagination($happyreplys, $start, $per_page);
		$this->add_css('preview');
		$this->add_js('preview');

		$type = strtolower(Input::form('type'));	
		$export_types = ['print', 'csv'];
							
		if (in_array($type, $export_types)) {
			call_user_func([$this, '_export_'.$type], $happyreplys);
		}
		else {
		$panel_buttons = new ArrayIterator;
			if ($me->is_allowed_to('创建', 'happyhour')) {
				$panel_buttons[] = [
					'url' => URI::url('!happy/history/index?type=csv'),
					'text'  => I18N::T('happy', '导出CSV'),
					'extra' => 'class="button button_save "',
					];
			
				$panel_buttons[] = [
					'url' => URI::url('!happy/history/index?type=print'),
					'text'  => I18N::T('happy', '打印'),
					'extra' => 'class="button button_print " target="_blank"',
					];
			
			}
		
			$this->layout->body->primary_tabs
				->select('history')
				->content = V('happy:history',[
								'pagination' => $pagination,
								'st' => $start,
								'happyreplys' => $happyreplys,
								'next_start' => $next_start,
								'sort_asc' => $sort_asc,
								'sort_by' => $sort_by,
								'panel_buttons' => $panel_buttons,
					]);
		}
	}
	
	
		function _export_csv($happyreplys) {
			$csv = new CSV('php://output', 'w');
			/* 记录日志 */
			$me = L('ME');
			$log = sprintf('[happy] %s[%d]以CSV导出了成员列表',
						   $me->name, $me->id);
			Log::add($log, 'journal');
		
			$csv->write([
							I18N::T('happy', '用户'),
							I18N::T('happy', '选购单'),
							I18N::T('happy', '购买数'),
							I18N::T('happy', '发布时间'),
							]);

			if ($happyreplys->total_count() > 0) {
				foreach ($happyreplys as $happyreply) {
					$csv->write( [
									 $happyreply->replyer->name,
									 $happyreply->content,
									 $happyreply->stock,
									 Date::format($happyreply->ctime),
									 ]);
				}
			}
			$csv->close();
		}
	
	function _export_print($happyreplys) {
		$properties_to_print = [
			'name' => '用户',
			'content' => '选购物品',
			'stock' => '数量',
			'time' => '发布时间',
			];

		$this->layout = V('history_print', [
							  'happyreplys' => $happyreplys,
							  'properties_to_print' => $properties_to_print,
							  ]);
		/* 记录日志 */
		$me = L('ME');
		$log = sprintf('[happy] %s[%d]打印了历史记录',
					   $me->name, $me->id);
		Log::add($log, 'journal');
		
	}
	
}

