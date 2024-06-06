<?php 

class Food_Model extends Presentable_Model {
	
	protected $object_page = [
		'edit'=>'!food/food/edit.%id[.%arguments]',
		'delete'=>'!food/food/delete.%id',
	];
	
	function & links($mode='index') {
		$links = new ArrayIterator;
		switch ($mode) {
			case 'index':
			default:
				if (L('ME')->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('food', '编辑'),
						'extra'=> ' class="blue"' ,
					];
				}
				
				if (L('ME')->is_allowed_to('删除', $this)) {	
					$links['delete'] = [
						'url' => $this->url(NULL, NULL, NULL, 'delete'),
						'text'  => I18N::T('food', '删除'),
						'extra'=> 'class="blue" confirm="'.I18N::T('food', '您确认删除这道菜式吗？').'"' ,
					];
				}
				break;	
		}
		return (array) $links;
	}
	
}
