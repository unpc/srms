<?php 

class FD_Order_Model extends Presentable_Model {
	
	protected $object_page = [
		'edit'=>'!food/fd_order/edit.%id',
		'delete'=>'!food/fd_order/delete.%id',

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
				
				//如果具有删除权限，或者这条记录属于某个用户，则可以显示删除
				
				if (L('ME')->is_allowed_to('删除', $this)) {
					$links['delete'] = [
						'url' => $this->url(NULL, NULL, NULL, 'delete'),
						'text'  => I18N::T('food', '删除'),
						'extra'=> ' class="blue" confirm="'.I18N::T('food', '您确认删除这个预订吗？').'"' ,
					];
				}
				break;
		}
		
		return (array) $links;
	}
	
}
