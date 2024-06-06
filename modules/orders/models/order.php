<?php

class Order_Model extends Presentable_Model {

	protected $object_page = [
		'view' => '!orders/order/index.%id[.%arguments]',
		'request' => '!orders/order/request',
		'confirm' => '!orders/order/confirm.%id[.%arguments]',
		'receive' => '!orders/order/receive.%id[.%arguments]',
		'edit' => '!orders/order/edit.%id[.%arguments]',
		'add' => '!orders/order/add',
		'reorder' => '!orders/order/reorder.%id[.%arguments]',
		'duplicate' => '!orders/order/duplicate.%id[.%arguments]',
        'qrcode'=> '!orders/qrcode/index.%id',
		];


	/*
	  xiaopei.li@2010.02.15
	  新的订单
	*/
	/*
	// 订单状态
	const REQUESTING = 1;
	const NOT_RECEIVED = 2;
	const RECEIVED = 3;
	const CANCELED = 4;

	static $order_status = array(
		self::REQUESTING => '申购中',
		self::NOT_RECEIVED => '已订出',
		self::RECEIVED => '已到货',
		self::CANCELED => '已取消',
		);

	static $receive_status = array(
		self::NOT_RECEIVED => '未到货',
		self::RECEIVED => '已到货',
		);

	static $order_status_title = array(
		self::REQUESTING => 'requesting',
		self::NOT_RECEIVED => 'not_received',
		self::RECEIVED => 'received',
		self::CANCELED => 'canceled',
		);

	// 订单动作
	const REQUEST = 1;
	const CONFIRM = 2;
	const RECEIVE = 3;
	const EDIT = 4;

	static $action_name = array(
		self::REQUEST => 'request',
		self::CONFIRM => 'confirm',
		self::RECEIVE => 'receive',
		self::EDIT	=> 'edit',
	);
	*/

	//普通状态: 待确认, 待再次确认, 待订出, 待付款, 付款中, 已付款, 待结算, 已结算, 退货中, 已取消, 拒绝退货.
	//增加独立运输状态: 未到货, 已到货
	//增加独立多方确认状态: 买方确认, 供应商确认

	const REQUESTING = 1; //申购中
	const NEED_CUSTOMER_APPROVE = 2; // 待买方确认
	const READY_TO_ORDER = 3; //待订出
	const READY_TO_TRANSFER = 4; //待付款
	const PENDING_TRANSFER = 5; //付款中
	const TRANSFERRED = 6; //已付款
	const PENDING_PAYMENT = 7; //待结算
	const PAID = 8; //已结算
	const RETURNING = 9; //退货中
	const CANCELED = 10; //已取消
	const REFUSE_TO_RETURN = 11; //拒绝退货
	const NEED_VENDOR_APPROVE = 12; //待供应商确认

	const NOT_RECEIVED = 0; //未到货
	const RECEIVED = 1; //已到货

	//订单列表页筛选
	const LABEL_REQUESTING = 1; //申购中
	const LABEL_CONFIRMED = 2; //已确认
	const LABEL_ORDERED = 3; //已订出
	const LABEL_RECEIVED = 4; //已到货
	const LABEL_RETURNING = 5; //退货中
	const LABEL_CANCELED = 6; //已取消

	static $order_status = [
		self::LABEL_REQUESTING => '申购中',
		self::LABEL_CONFIRMED => '已确认',
		self::LABEL_ORDERED => '已订出',
		self::LABEL_RECEIVED => '已到货',
		self::LABEL_CANCELED =>'已取消',
		];

	static $mall_order_status = [
		self::LABEL_REQUESTING => '申购中',
		self::LABEL_CONFIRMED => '已确认',
		self::LABEL_ORDERED => '已订出',
		self::LABEL_RECEIVED => '已到货',
		self::LABEL_RETURNING => '退货中',
		self::LABEL_CANCELED =>'已取消',
		];

	static $receive_status = [
		self::NOT_RECEIVED => '未到货',
		self::RECEIVED => '已到货',
		];

	static $order_status_filter = [
		self::LABEL_REQUESTING => [
				self::REQUESTING => '待确认',
				self::NEED_CUSTOMER_APPROVE => '待再次确认',
			],
		self::LABEL_CONFIRMED => [
				self::READY_TO_ORDER => '待订出',
			],
		self::LABEL_ORDERED => [
				self::NEED_VENDOR_APPROVE => '待供应商确认',
				self::READY_TO_TRANSFER => '待付款',
				self::PENDING_TRANSFER => '付款中',
				self::TRANSFERRED => '已付款',
				self::PENDING_PAYMENT => '待结算',
				self::PAID => '已结算',
			],
		self::LABEL_RECEIVED => [
				self::NEED_VENDOR_APPROVE => '待供应商确认',
				self::READY_TO_TRANSFER => '待付款',
				self::PENDING_TRANSFER => '付款中',
				self::TRANSFERRED => '已付款',
				self::PENDING_PAYMENT => '待结算',
				self::PAID => '已结算',
			],
		self::LABEL_RETURNING => [
				self::RETURNING => '退货中',
				self::REFUSE_TO_RETURN => '拒绝退货',
			],
		self::LABEL_CANCELED => [
				self::CANCELED => '已取消',
			],
		];

	//用于 span 样式
	static $order_status_title = [
		self::LABEL_REQUESTING => 'requesting',
		self::LABEL_CONFIRMED => 'confirmed',
		self::LABEL_ORDERED => 'orderd',
		self::LABEL_RECEIVED => 'received',
		self::LABEL_RETURNING => 'returning',
		self::LABEL_CANCELED => 'canceled',
		];

	const REQUEST = 1;
	const CONFIRM = 2;
	const RECEIVE = 3;
	const EDIT = 4;
	// 补增订单
	const ADD = 5;

	static $action_name = [
		self::REQUEST => 'request',
		self::CONFIRM => 'confirm',
		self::RECEIVE => 'receive',
		self::EDIT	=> 'edit',
	];

	function & links($mode = 'index') {

		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
		case 'view':
			switch ($this->status) {
			case Order_Model::REQUESTING:
				if (!$this->source && $me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改订单'),
						'extra' => 'class="button button_edit"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				if ($me->is_allowed_to('确认', $this)) {
					if(!$this->source
						|| !$this->request_confirm) {
						$links['confirm'] = [
							'url' => $this->url(NULL, NULL, NULL, 'confirm'),
							'text'  => I18N::T('orders', '确认订单'),
							'extra' => 'class="button button_view"
								q-event="click"
								q-object="confirm"
								q-src="'.H($this->url()).'"',
							];
					}

					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::NEED_CUSTOMER_APPROVE:
				if ($me->is_allowed_to('确认', $this)) {
					if(!$this->source
						|| !$this->customer_confirm) {
						$links['confirm'] = [
							'url' => $this->url(NULL, NULL, NULL, 'confirm'),
							'text'  => I18N::T('orders', '确认订单'),
							'extra' => 'class="button button_view"
								q-event="click"
								q-object="confirm"
								q-src="'.H($this->url()).'"',
							];
					}
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::NEED_VENDOR_APPROVE:
				if ($me->is_allowed_to('确认', $this)) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::READY_TO_ORDER:
				if (!$this->source && $me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改订单'),
						'extra' => 'class="button button_edit"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				if ($me->is_allowed_to('订出', $this)) {
					$links['order'] = [
						'url' => $this->url(NULL, NULL, NULL, 'order'),
						'text'  => I18N::T('orders', '订出订单'),
						'extra' => 'class="button button_view"
							q-event="click"
							q-object="order"
							q-src="'.H($this->url()).'"',
						];

					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}

				if ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="font-button-delete"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::READY_TO_TRANSFER:
				if ($me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改订单'),
						'extra' => 'class="button button_edit"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
					/*
					if (!$this->source) {
						$links['cancel'] = array(
							'url' => '#',
							'text'  => I18N::T('orders', '取消订单'),
							'extra' => 'class="font-button-delete"
								q-event="click"
								q-object="cancel_order"
								q-src="'.H($this->url()).'"',
							'weight' => 99,
							);
					}
					*/
				}

				if ($this->deliver_status == Order_Model::NOT_RECEIVED && $me->is_allowed_to('收货', $this)) {
					$links['receive'] = [
						'url' => $this->url(NULL, NULL, NULL, 'receive'),
						'text'  => I18N::T('orders', '确认收货'),
						'extra' => 'class="button button_edit"
							q-event="click"
							q-object="receive"
							q-src="'.H($this->url()).'"',
						'weight' => 50,
						];
				}

                if (!$this->source) {
                    $links['cancel'] = [
                        'url' => '#',
                        'text' => I18N::T('orders', '取消订单'),
                        'extra' => 'class="font-button-delete"
                            q-event="click"
                            q-object="cancel_order"
                            q-src="'.H($this->url()).'"',
                        'weight' => 99,
                    ];
                }

				break;
			case Order_Model::RECEIVED:
				if ($me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改订单'),
						'extra' => 'class="button button_edit"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
					if (!$this->source) {
						$links['cancel'] = [
							'url' => '#',
							'text'  => I18N::T('orders', '取消订单'),
							'extra' => 'class="font-button-delete"
								q-event="click"
								q-object="cancel_order"
								q-src="'.H($this->url()).'"',
							'weight' => 99,
							];
					}
				}
				break;
			case Order_Model::CANCELED :
				break;
			}

			if ($me->is_allowed_to('添加申购', $this)) {
				$links['duplicate'] = [
					'url' => $this->url(NULL, NULL, NULL, 'duplicate'),
					'text'  => I18N::T('orders', '再次申购'),
					'extra' => 'class="button button_refresh"
							q-event="click"
							q-object="duplicate"
							q-src="'.H($this->url()).'"',
					];
			}

			break;
		case 'index':
		default:
			switch ($this->status) {
			case Order_Model::REQUESTING:
				if (!$this->source && $me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				if ($me->is_allowed_to('确认', $this)) {
					if(!$this->source
						|| !$this->request_confirm) {
						$links['confirm'] = [
							'url' => $this->url(NULL, NULL, NULL, 'confirm'),
							'text'  => I18N::T('orders', '确认'),
							'extra' => 'class="blue"
								q-object="confirm"
								q-event="click"
								q-src="'.$this->url().'"',
							];
					}
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::NEED_CUSTOMER_APPROVE:
				if ($me->is_allowed_to('确认', $this)) {
					if(!$this->source
						|| !$this->customer_confirm ) {
						$links['confirm'] = [
							'url' => $this->url(NULL, NULL, NULL, 'confirm'),
							'text'  => I18N::T('orders', '确认'),
							'extra' => 'class="blue"
								q-object="confirm"
								q-event="click"
								q-src="'.$this->url().'"',
							];
					}
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::NEED_VENDOR_APPROVE:
				if ($me->is_allowed_to('确认', $this)) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				elseif ($me->id == $this->requester->id) {
					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '取消申购'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::READY_TO_ORDER:
				if (!$this->source && $me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				if ($me->is_allowed_to('订出', $this)) {
					$links['order'] = [
						'url' => $this->url(NULL, NULL, NULL, 'order'),
						'text'  => I18N::T('orders', '订出'),
						'extra' => 'class="blue"
							q-object="order"
							q-event="click"
							q-src="'.$this->url().'"',
						];

					$links['cancel'] = [
						'url' => '#',
						'text'  => I18N::T('orders', '驳回'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="cancel_order"
							q-src="'.H($this->url()).'"',
						'weight' => 99,
						];
				}
				break;
			case Order_Model::NOT_RECEIVED:
				if ($me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				break;
			case Order_Model::READY_TO_TRANSFER:
			case Order_Model::RECEIVED:
				if ($me->is_allowed_to('修改', $this)) {
					$links['edit'] = [
						'url' => $this->url(NULL, NULL, NULL, 'edit'),
						'text'  => I18N::T('orders', '修改'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="edit"
							q-src="'.H($this->url()).'"',
						];
				}
				if ($this->deliver_status == Order_Model::NOT_RECEIVED && $me->is_allowed_to('收货', $this)) {
					$links['receive'] = [
						'url' => $this->url(NULL, NULL, NULL, 'receive'),
						'text'  => I18N::T('orders', '确认收货'),
						'extra' => 'class="blue"
							q-event="click"
							q-object="receive"
							q-src="'.H($this->url()).'"',
						'weight' => 50,
						];
				}

                if (!$this->source && $me->access('管理订单和供应商')) {
                    $links['cancel'] = [
                        'url' => '#',
                        'text' => I18N::T('orders', '取消订单'),
                        'extra' => 'class="blue"
                            q-event="click"
                            q-object="cancel_order"
                            q-src="'.H($this->url()).'"',
                        'weight' => 99,
                    ];
                }
				break;
				break;
			case Order_Model::CANCELED:
				break;
			}
			if ($me->is_allowed_to('添加申购', $this)) {
				$links['duplicate'] = [
					'url' => $this->url(NULL, NULL, NULL, 'duplicate'),
					'text'  => I18N::T('orders', '再次申购'),
					'extra' => 'class="blue"
						q-event="click"
						q-object="duplicate"
						q-src="'.H($this->url()).'"',
				];
			}
		}

		return (array) $links;
	}

	function update_status() {

        if ($this->canceler->id) {
            $this->status  = Order_Model::CANCELED;//已取消
        }
        elseif ($this->receiver->id) {
        	$this->status = Order_Model::READY_TO_TRANSFER;//已订出
            $this->deliver_status = Order_Model::RECEIVED;//已到货
        }
        elseif ($this->purchaser->id) {
            $this->status = Order_Model::READY_TO_TRANSFER;//已订出
            $this->deliver_status = Order_Model::NOT_RECEIVED;//已到货
        }
        elseif ($this->approver->id) {
            $this->status = Order_Model::READY_TO_ORDER;//已确认
        }
        else {
            $this->status = Order_Model::REQUESTING;//申购中
        }
		return $this;
	}

	function get_label_status() {

		$status = $this->status;
		$deliver_status = $this->deliver_status;
		if ($status == self::REQUESTING || $status == self::NEED_CUSTOMER_APPROVE) {
			return self::LABEL_REQUESTING ;
		}
		elseif ($status == self::READY_TO_ORDER) {
			return self::LABEL_CONFIRMED;
		}
		elseif ($status == self::READY_TO_TRANSFER || $status == self::PENDING_TRANSFER
			|| $status == self::TRANSFERRED || $status == self::PENDING_PAYMENT || $status == self::PAID || $status == self::NEED_VENDOR_APPROVE) {
				if ($deliver_status == self::RECEIVED) {
					return self::LABEL_RECEIVED;
				}
				else {
					return self::LABEL_ORDERED;
				}
		}
		elseif ($status == self::RETURNING || $status == self::REFUSE_TO_RETURN) {
			return self::LABEL_RETURNING;
		}
		elseif ($status == self::CANCELED) {
			return self::LABEL_CANCELED;
		}
	}

	function delete() {

		$grant_expense = $this->expense;

		$ret = parent::delete();

		if ($ret && $grant_expense->id) {
			$grant_expense->delete();
		}

		return $ret;
	}

    function save($overwrite = FALSE) {

        if (!$this->status) {
            $this->status = self::REQUESTING;
        }

        return parent::save($overwrite);
    }

    function __get($key) {
        if ($key == 'name') {
			$str = strtr('%name(%id)', [
					'%name'=>$this->product_name,
					'%id'=> $this->id]);
            return $str;
        }
        else {
            return parent::__get($key);
        }
    }

    //二维码是否可显示
    function qrcode_enable() {

        $mall_order = O('mall_order', ['order'=> $this]);
        if (in_array($this->deliver_status, [self::RECEIVED, self::NOT_RECEIVED]) && !$mall_order->id) return TRUE;

        return FALSE;
    }

    function qrcode_data() {

        $data = [];

        foreach(Config::get('orders.qr') as $key => $value) {
            switch($key) {
                case 'requester' :
                case 'vendor' :
                case 'approver' :
                case 'purchaser' :
                case 'receiver' :
                case 'canceler' :
                    $data[$value] = $this->$key->name;
                    break;
                case 'stock' :
                    $data[$value] = $this->stock->product_name;
                    break;
                case 'lab_name' :
                    $data[$value] = Config::get('lab.name');
                    break;
                case 'request_date' :
                case 'purchase_date' :
                case 'receive_date' :
                case 'cancel_date' :
                    $data[$value] = Date::format($this->$key, 'Y/m/d H:i');
                    break;
                default :
                    $data[$value] = $this->$key;
            }
        }

        $_d = [];
        foreach($data as $k => $v) {
            $_d[] = "$k:$v";
        }

        return join(';', $_d);
    }

    function get_require($name) {
    	$result = '';
    	if (Config::get('orders.require')[$name]['isRequire']) {
    		$result = "* ";
    	}
    	return $result;
    }
}
