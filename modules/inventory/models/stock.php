<?php

class Stock_Model extends Presentable_Model {

	const UNKNOWN = 1;
	const ADEQUATE = 2;
	const INADEQUATE = 3;
	const EXHAUSTED = 4;

	static $stock_status = [
		self::UNKNOWN => '不详',
		self::ADEQUATE => '充足',
		self::INADEQUATE => '紧张',
		self::EXHAUSTED => '用罄',
		];


	static $has_expired = 1;//过期
	static $not_expired = 2;//没有过期
	static $almost_expired = 3;//即将过期
	static $never_expired = 4;//永不过期

	static $expire_status = [
		1 => '已过期',
		2 => '未过期',
		3 => '即将过期',
		4 => '未设置',
	];

	protected $object_page = [
		'view' => '!inventory/stock/%id[.%arguments]',
		'edit' => '!inventory/stock/edit.%id[.%arguments]',

		// 'delete'=>'!inventory/stock/delete.%id',
		// 'auto_dealer'=>'!inventory/autocomplete/all_dealers_selector',
		// 'follow'=>'!inventory/stock/follow.%id',
		// 'unfollow'=>'!inventory/stock/unfollow.%id',
	];

	function & links($mode = 'index') {
		$links = new ArrayIterator;
		$me = L('ME');
		$stock_id = $this->id;
		switch ($mode) {
		case 'view':
			// TODO use is_allowed_to(xiaopei.li@2011.10.20)
			if ($me->is_allowed_to('领用/归还', 'stock') || $me->is_allowed_to('代人领用/归还', 'stock')) {
				$links['use_return'] = [
					'url' => URI::url('!inventory/use'),
					'text'  => I18N::T('inventory', '领用 / 归还'),
					'extra' => 'q-object="stock_use_return_add" q-event="click" q-src="' . URI::url('!inventory/use') .
					'" q-static="' . H(['stock_id' => $this->id]) .
					'" class="button button_scan"',
					];
				$links['print'] = [
						'url' => URI::url('!inventory/report/stock.'.$stock_id.'.print'),
						'text'  => I18N::T('inventory','打印'),
						'extra' => 'class="button button_print  float_right" target="_blank"',
				 		'weight' => 100,
					];
			}
			if ($me->is_allowed_to('修改', 'stock')) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('inventory', '修改存货'),
					'extra' => 'class="button button_edit"',
					];
			}
			if ($me->is_allowed_to('删除', 'stock')) {
				$links['delete'] = [
					'url' => '#',
					'text'  => I18N::T('inventory', '删除存货'),
					'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="delete_stock"
							q-static="'.HT(['sid'=>$this->id]).'"
							q-src="'.URI::url('!inventory/stock').'"',
					'weight' => 99,
					];
			}
			$links[] = Event::trigger('add_apply_button', $stock_id);
			break;
		case 'record':
				if ($me->is_allowed_to('查看','stock')) {
					$links['excel'] = [
						//'url'=>URI::url('!inventory/report/index',array('type'=>'csv', 'stock_id'=>$this->id)),
						'tip'=>I18N::T('inventory','导出CSV'),
						'extra' => 'class="button button_save "
						q-object="export" q-event="click"
						q-src="' . URI::url('!inventory/report') .'"
				 		q-static="' . H(['type'=>'csv','stock_id' => $stock_id]) .'" ',
						'weight' => 101,
						];
					$links['print'] = [
						//'url' => '!inventory/report/index',
						'text'  => I18N::T('inventory','打印'),
						'extra' => 'class="button button_print "
						q-object="export" q-event="click"
						q-src="' . URI::url('!inventory/report') .'"
				 		q-static="' . H(['type'=>'print','stock_id' => $stock_id]) .'" ',
				 		'weight' => 100,
						];
				}
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('修改', 'stock')) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('inventory', '修改'),
                    'tip'=>I18N::T('inventory', '修改'),
					'extra'=>'class="icon-edit"',
					];
				if (!$this->is_collection) {
					if ($this->parent->id != $this->id) {
						$links['remove'] = [
							'url' => '#',
							'text'  => I18N::T('inventory', '移出'),
							'extra'=> 'class="blue"
									q-event="click"
									q-object="remove_stock"
									q-static="'.HT(['sid'=>$this->id]).'"
									q-src="'.URI::url('!inventory/stock').'"',
							'weight' => 100,
						];
					}
					else {
						$links['into'] = [
							'url' => '#',
							'text'  => I18N::T('inventory', '归类'),
							'extra'=> 'class="blue"
									q-event="click"
									q-object="into_stock"
									q-static="'.HT(['sid'=>$this->id]).'"
									q-src="'.URI::url('!inventory/stock').'"',
							'weight' => 101,
						];
					}
				}
			}

		}

		Event::trigger('stock.get.links', $this, $links, $mode);
		return (array) $links;
	}

	function remove() {
		//如果是总类目 返回
		if ($this->is_collection) return FALSE;
		if ($this->id == $this->parent_id) return FALSE;
		$parent = $this->parent;
		$this->parent = $this;
		if (!$this->save()) return FALSE;

		$children = Q("stock[parent={$parent}]");
		if ($children->total_count() <= 2) {
			foreach ($children as $id => $child) {
				$child->parent = $child;
				$child->save();
			}
			$parent->delete();
		}

		return TRUE;
	}

	function delete() {
		/*if the stock will be deleted is a collection, delete it directly*/
		if ($this->is_collection) return parent::delete();
		$parent = $this->parent;
		if (!$parent->id) return parent::delete();
		if (parent::delete()) {
			if ($this->is_collection == 0 && $this->id == $parent->id) return TRUE;
			$children = Q("stock[parent={$parent}]");
			if (count($children) <= 1) {
				foreach ($children as $id => $child) {
					$child->parent = $child;
					$child->save();
				}
				$parent->delete();
			}
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	function merge($stock) {
		if (($stock->name() != $this->name()) || !$stock->id) return false;
		if ($stock->is_collection) {
			$this->parent = $stock;
			return parent::save();
		}

		$collection = O('stock');
		$collection->product_name = $stock->product_name;
		$collection->is_collection = 1;
		//collection的status为0
		$collection->status = 0;
		$collection->save();
		$collection->parent = $this->parent = $stock->parent = $collection;
		$this->save();
		$stock->save();
		$collection->save();
		return true;
	}

    function save($overwrite=FALSE) {
        if (!$this->creator->id) {
            $this->creator = L('ME');
        }

        $ret = parent::save($overwrite);

        if ($ret && !$this->parent->id) {
            $this->parent = $this;
            $this->save();
        }

        return $ret;
    }
}
