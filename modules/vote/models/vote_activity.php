<?php 

 class Vote_Activity_Model extends Presentable_Model{
 
 	protected $object_page = [
		//'view' => '!happy/activites/index.%id[.%arguments]',
		'activity' => '!vote/vote/vote.%id[.%arguments]',
		'edit' => '!vote/index/edit.%id[.%arguments]',
		//'delete' => '!happy/edit/delete.%id[.%arguments]',
	];
 
 	function & links($mode='edit') {
 		$me = L('ME');
 		
		$links = new ArrayIterator;
		switch ($mode) {
		case 'edit':
			if( $me->is_allowed_to('创建', $this) ){	
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('vote', '修改'),
					'extra'=> ' class="blue"' ,
				];
			}
			break;
		}
		return (array) $links;
	}
 
 }