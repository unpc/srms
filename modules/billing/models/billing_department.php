<?php


class Billing_Department_Model extends Presentable_Model {

	protected $object_page = [
		'view'=>'!billing/department/index.%id[.%arguments]',
		'edit'=>'!billing/department/edit.%id[.%arguments]',
		'delete'=>'!billing/department/delete.%id[.%arguments]',
	];
	
	function & links($mode='index') {
		$links = new ArrayIterator;	
		$me = L('ME');
		switch ($mode) {
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('billing', '编辑'),
					'tip' => I18N::T('billing', '编辑'),
					'extra'=> 'class="button icon-edit "',
				];
			}
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('billing', '编辑'),
					'tip' => I18N::T('billing', '编辑'),
					'extra'=> 'class="blue"',
				];
			}
        }
        
        Event::trigger('billing_department.links', $this, $links, $mode);
		return (array) $links;
	}
	
	function group_path() {

		$group_root = Tag_Model::root('group');

		$tag = $this->group;
		if ($tag->root->id != $group_root->id) return;
		
		$tag_links = [];
		$root_id = $group_root->id;
		while ($tag->id && $tag->id != $root_id) {
			$tag_links[] = $tag->name;
			$tag = $tag->parent;
		}
		$tag_links = array_reverse($tag_links);
		
		return implode(' &#187; ', $tag_links);
	}

	function add_account_for_lab($lab) {
        $account = O('billing_account');
        $account->lab = $lab;
        $account->department = $this;
        $account->save();

		return $account;
	}
	
}
