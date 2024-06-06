<?php

class Happyhour_Model extends Presentable_Model {
	
	protected $object_page = [
		'view' => '!happy/activites/index.%id[.%arguments]',
		'activites' => '!happy/activites/index.%id[.%arguments]',
		'edit' => '!happy/edit/index.%id[.%arguments]',
		'delete' => '!happy/edit/delete.%id[.%arguments]',
	];
	
	
	function & links($mode='edit') {
		$links = new ArrayIterator;
		switch ($mode) {
		case 'edit':
			if( L('ME')->is_allowed_to('创建', $this) ){	
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('happyhour', '修改'),
					'extra'=> ' class="blue"' ,
				];
			}
			break;
		}
		return (array) $links;
	}
	
}
