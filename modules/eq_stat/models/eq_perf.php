<?php

class Eq_perf_Model extends Presentable_Model {
	
	protected $object_page = [
		'view'=>'!eq_stat/perf/index.%id[.%arguments]',
		'edit'=>'!eq_stat/perf/edit.%id[.%arguments]',
		'delete'=>'!eq_stat/perf/delete.%id[.%arguments]',
	];
	
	function & links($mode = 'index') {
		$links = new ArrayIterator;		
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'), 
					'text'  => I18N::T('eq_stat', '设置'),
					'extra' =>'class="button button_edit"',
				];
			}
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'), 
					'text'  => I18N::T('eq_stat', '设置'),
					'extra' =>'class="blue"',
				];
			}
			break;
		}

		return (array) $links;
	}
	
}
