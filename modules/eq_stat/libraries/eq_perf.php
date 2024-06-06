<?php 

class Eq_Perf {
	
	static function eq_perf_ACL($e, $me, $perm_name, $perf, $options) {
		switch ($perm_name) {
			case '列表':
				if ($me->access('列表绩效评估')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '查看':
				if ($me->access('查看绩效评估')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '添加':
			case '修改':
			case '删除':
				if ($me->access('添加/修改绩效评估')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
		}
	}
	
    static function is_accessible($e, $name) {
        $me = L('ME');
        if ($me->access('管理所有内容')
            || Q("lab[owner={$me}]")->total_count()
            || Q("{$me}<incharge equipment")->total_count()
            || $me->access('添加/修改下属机构的仪器')
            || $me->access('添加/修改所有机构的仪器')
            || $me->access('添加/修改下属机构实验室')
            || $me->access('查看全部仪器统计信息')
            || $me->access('查看下属机构内仪器统计信息')) {
            $e->return_value = TRUE;
            return FALSE;
        }
        $e->return_value = false;
        return FALSE;
    }

	static function setup_stat() {
		Event::bind('perf.index.tab', 'Eq_Perf::_index_perf_tab');
		Event::bind('perf.index.tab.content', 'Eq_Perf::_index_perf_content', 0, 'equipments');
	}

	static function _index_perf_tab($e, $tabs) {
		$perf = $tabs->perf;
		if (L('ME')->is_allowed_to('查看', $perf)) {
			$tabs->add_tab('equipments', [
				'url'=>$perf->url('equipments'),
				'title'=>I18N::T('eq_stat', '仪器列表')
			]);
		}
	}
	
	static function _index_perf_content($e, $tabs) {
		$perf = $tabs->perf;
		if (!$perf->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('查看', $perf)) URI::redirect('error/401');
		$form = Lab::form();
		
		$cat_root = Tag_Model::root('equipment');
		$group_root = Tag_Model::root('group');
		
		$tag = $perf->collection;
		$selector = 'equipment';
		if ($tag->root->id) {
			$selector = "{$tag} equipment";
		}
		
		//生成 session token
		$form_token = Session::temp_token('eq_perf_equipments_',300);
		//将搜索条件存入session
		$_SESSION[$form_token] = $selector;
		
		$equipments = Q($selector);
		
		$pagination = Lab::pagination($equipments, (int)$form['st'], '10');
		
		$panel_buttons = new ArrayIterator;	
		
		$panel_buttons[] = [
				//'url'=>URI::url('!eq_stat/perfs/perf_equipments.'.$perf->id.'?type=csv&form_token='.$form_token),
				'tip'=>I18N::T('eq_stat','导出CSV'),
				'extra'=>'q-object="output" q-event="click" q-src="' . URI::url('!eq_stat/perfs') .
					'" q-static="' . H(['type'=>'csv','form_token'=>$form_token, 'perf_id'=>$perf->id]) .
					'" class="button button_save "',
			];
		$panel_buttons[] = [
				//'url'=>URI::url('!eq_stat/perfs/perf_equipments.'.$perf->id.'?type=print&form_token='.$form_token),
				'tip'=>I18N::T('eq_stat','打印'),
				'extra'=>'q-object="output" q-event="click" q-src="' . URI::url('!eq_stat/perfs') .
					'" q-static="' . H(['type'=>'print','form_token'=>$form_token, 'perf_id'=>$perf->id]) .
					'" class="button button_print  middle"',
				//'extra'=>'class="button button_print  middle" target="_blank"',
			];
		
		$tabs->content = V('eq_stat:perf/index', [
			'form'=>$form,
			'pagination'=>$pagination,
			'equipments'=>$equipments,
			'panel_buttons'=>$panel_buttons,
			'perf'=>$perf
		]);
	}
}
