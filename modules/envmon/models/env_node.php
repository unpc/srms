<?php

class Env_Node_Model extends Presentable_Model {

	const ALARM_NORMAL = 0; // 正常
	const ALARM_NODATA_ABNORMAL = 1; // 无数据报警
	const ALARM_UNUSUAL_ABNORMAL = 2; // 超出范围报警

	protected $object_page = [
		'view'=>'!envmon/node/index.%id[.%arguments]',
	];
	
	function & links($mode = 'index') {
		$links = new ArrayIterator;		
		$me = L('ME');
		switch ($mode) {
		case 'index':
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => '#',
					'text'  => I18N::T('envmon', '修改'),
					'extra' => 'class=" icon-edit" q-event="click" q-object="edit_node" q-src="'. URI::url('!envmon/node'). '" q-static="'. H(['node_id'=>$this->id]). '"',
				];
			}
		}
        Event::trigger('envmon.exitra.links', $this, $links, $mode);
		return (array) $links;
	}
	
	function delete() {
		
		$sensors = Q("env_sensor[node={$this}]");

		$ret = parent::delete();
		
		if ($ret) {
			foreach ($sensors as $sensor) {
				$sensor->delete();
			}
		}
		
		return $ret;
	}
}	
