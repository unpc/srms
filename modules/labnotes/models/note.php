<?php


class Note_Model extends Presentable_Model {

	protected $object_page = [
		'view'=>'!labnotes/note/index.%id',
		'edit'=>'!labnotes/note/edit.%id[.%arguments]',
		'delete'=>'!labnotes/note/delete.%id',
		'lock'=>'!labnotes/note/lock.%id[.%arguments]'
	];
	
	function & links($mode = 'index') {
		$links = new ArrayIterator;	
		$me = L('ME');
		switch ($mode) {
		case 'view':
			if (!$this->lock) {
				$links['lock'] = [
					'url' => $this->url(NULL,NULL,NULL,'lock'),
					'text'  => I18N::T('labnotes', '锁定'),
					'extra'=> 'class="button"',
					'type'=> 'lock'
				];
		
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('labnotes', '编辑'),
					'extra'=> 'class="button button_edit"',
					'type'=> 'edit'
				];

				$links['delete'] = [
					'url'=> $this->url(NULL,NULL,NULL,'delete'),
					'tip'=>I18N::T('labnotes','删除'),
					'extra'=>'class="font-button-delete" confirm="'.I18N::T('labnotes','你确定要删除吗? 删除后不可恢复!').'"',
					'type'=> 'delete'
				];
			}
			else {
				$links['unlock'] = [
					'url'=> $this->url(NULL,NULL,NULL,'lock'),
					'tip'=>I18N::T('labnotes','解锁'),
					'extra'=>'class="button"',
					'type'=> 'unlock'
				];
			}
			break;
		case 'index':
		default:
			if (!$this->lock) {
				$links['lock'] = [
					'url' => $this->url(NULL,NULL,NULL,'lock'),
					'text'  => I18N::T('labnotes', '锁定'),
					'extra'=> 'class="blue"',
					'type'=> 'lock'
				];
		
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('labnotes', '编辑'),
					'extra'=> 'class="blue"',
					'type'=> 'edit'
				];

				$links['delete'] = [
					'url'=> $this->url(NULL,NULL,NULL,'delete'),
					'tip'=>I18N::T('labnotes','删除'),
					'extra'=>'class="blue" confirm="'.I18N::T('labnotes','你确定要删除吗? 删除后不可恢复!').'"',
					'type'=> 'delete'
				];
			}
			else {
				$links['unlock'] = [
					'url'=> $this->url(NULL,NULL,NULL,'lock'),
					'tip'=>I18N::T('labnotes','解锁'),
					'extra'=>'class="blue"',
					'type'=> 'unlock'
				];
			}
		}
		return (array) $links;
	}
	
}
