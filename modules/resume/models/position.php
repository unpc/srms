<?php
class Position_Model extends Presentable_Model {
	
	protected $object_page = [
		'edit'=>'!resume/position/edit.%id[.%arguments]',
		'delete'=>'!resume/resume/delete.%id',
		'follow'=>'!resume/position/follow.%id',
		'unfollow'=>'!resume/position/unfollow.%id',
		];
	
	function & links($mode='index') {
		$links = new ArrayIterator;
		
		$me = L('ME');
		switch ($mode) {
			case 'index':
				if( $me->is_allowed_to('修改', 'position' ) ){
					$links['edit'] = [
						'url' => '#',
						'text'  => I18N::T('resume', '修改'),
						'extra'=>'class="blue" q-object="edit" q-event="click" q-static="'.H(['id'=>$this->id]).'" q-src="'.H(URI::url('!resume/position')).'"'
					];
				}
				$links = array_merge((array)$links, $me->follow_links($this, 'edit'));
				break;
		}
		return (array) $links;
	}
}
