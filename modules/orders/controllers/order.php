<?php

class Order_Helper {

	static function order_to_form($order) {
		$form = [
			'product_name' => $order->product_name,
			'manufacturer' => $order->manufacturer,
			'catalog_no' => $order->catalog_no,
			'model' => $order->model,
			'spec' => $order->spec,
			'quantity' => $order->quantity,
			'fare' => $order->fare,
			'vendor' => $order->vendor,
			'unit_price' => $order->unit_price,
			'price' => $order->price,
			'address' => $order->receive_address,
			'postcode' => $order->receive_postcode,
			'phone' => $order->receive_phone,
			'email' => $order->receive_email,
		];

		return $form;
	}

	static function stock_to_form($stock) {
		$form = [
			'product_name' => $stock->product_name,
			'manufacturer' => $stock->manufacturer,
			'catalog_no' => $stock->catalog_no,
			'model' => $stock->model,
			'spec' => $stock->spec,
			'quantity' => $stock->quantity,
			'vendor' => $stock->vendor,
			'unit_price' => $stock->unit_price,
		];

		return $form;
	}

	static function basic_form_to_order($form, $order) {

		if (!$form['source']) {
			$order->product_name = $form['product_name'];
			$order->manufacturer = $form['manufacturer'] ? : '';
			$order->catalog_no = $form['catalog_no'] ? : '';
			$order->model = $form['model'] ? : '';
			$order->spec = $form['spec'] ? : '';
            $order->order_no = $form['order_no'] ? : '';

			// requester can suggest
			Event::trigger('extra_basic_form_to_order', $order, $form);

            $order->fare = $form['fare'] ? : '';
            $order->unit_price = (float)$form['unit_price']?:'';
            $order->quantity = $form['quantity'];
            $order->price = (float)$form['price'] ?: $order->unit_price * (int)$order->quantity + $order->fare;

		}
		if (!$form['source'] || ($order->id && $order->status == Order_Model::REQUESTING)) {
			$order->quantity = (int) $form['quantity'];
		}

		//订单信息
		//收货地址不进行检索，以虚属性进行存储
		$order->receive_address = $form['address'];
        $order->receive_postcode = $form['postcode'];
        $order->receive_phone = $form['phone'];
        $order->receive_email = $form['email'];
	}

	static function grant_form_to_order($form, $order) {
		$me = L('ME');

		$amount = $order->price;

		$grant = O('grant', $form['grant']);
		if (!$grant->id) {
			$order->expense->delete();
			$order->expense = NULL;
			$order->save();
			return;
		}

		$expense = $order->expense;
		
		$old_grant = $expense->grant;
		$need_recalculate = $expense->id && $old_grant->id != $grant->id;

		$expense->pre_summary = I18N::T('orders', '%user 于 %date 订购 %order 的费用', [
			'%user' => Markup::encode_Q($order->requester),
			'%date' => Date::format($order->request_date, 'Y/m/d'),
			'%order'=>Markup::encode_Q($order)
		]);

		$expense->summary = $form['grant_summary'];
		$expense->amount = $amount;
		$expense->invoice_no = $form['grant_invoice_no'];

		$portion = O('grant_portion', $form['grant_portion']);
		$expense->user = $me;
		$expense->portion = $portion;
		$expense->grant = $portion->id ? $portion->grant : $grant;
		$expense->save();

		if ($expense->id) {

			if ($need_recalculate) $old_grant->recalculate();

			$order->expense = $expense;
			$order->save();

			/* 记录日志 */
            Log::add(strtr('[orders] %user_name[%user_id]关联了订单%order_name[%order_id]到经费%grant_name[%grant_id]', [
                '%user_name'=>$me->name,
                '%user_id'=> $me->id,
                '%order_name'=> $order->product_name,
                '%order_id'=> $order->id,
                '%grant_name'=> $expense->grant->project,
                '%grant_id'=> $expense->grant->id
            ]), 'journal');

			$tags = @json_decode($form['grant_tags'], TRUE);
			Tag_Model::replace_tags($expense, $tags, 'grant_expense', TRUE);
		}
	}

	static function order_to_stock($order) {
		$stock = O('stock', ['order'=>$order]);
		//已经存在stock，则进行编辑
		$stock->product_name = $order->product_name;
		$stock->manufacturer = $order->manufacturer;
		$stock->catalog_no = $order->catalog_no;
		$stock->model = $order->model;
		$stock->spec = $order->spec;
		$stock->vendor = $order->vendor;
		$stock->unit_price = $order->unit_price;
		$stock->order = $order;
		$stock->quantity = $order->quantity;
		$stock->summation = $order->quantity;
		$stock->note = $order->receive_note;
		return $stock;
	}

	static function validate_form($form, $action, $status = 0) {
		//RECEIVE
/*
		if ($action == Order_Model::RECEIVE) {
			return $form;
		}
*/
		$me = L('ME');
		//获取自定义货品信息必填项
		$require = Config::get('orders.require');
		foreach ($require as $key => $value) {
			if ($value['isRequire']) {
				if ((!in_array($action, (array)$value['action'], TRUE)) ||
					(in_array($action, (array)$value['action'], TRUE) && $status == Order_Model::REQUESTING))
				{
					$form->validate($key, 'not_empty', I18N::T('orders', "请填写%name", ['%name' => $value['name']]));
				}
			}
		}
		//申购
		if($action != Order_Model::CONFIRM || ($action == Order_Model::CONFIRM && $status == Order_Model::REQUESTING)) {
			$form
				->validate('quantity', 'number(>0)', I18N::T('orders', '数量必须大于零!'))
				->validate('unit_price', 'number(>0)', I18N::T('orders', '单价必须大于零!'))
				->validate('fare', 'number(>=0)', I18N::T('orders', '运费不可小于零!'));
		}
		if ($action == Order_Model::EDIT) {

			/*BUG #4947, when edit the order information, some other information shoud be validated.
			inventory is a common module, if use 'trigger', the site which does not have order alse bind this event.
			*/
			if ( isset($form['add_to_stock']) &&  $form['add_to_stock'] == 'on' && $form['add_to_stock_mode'] == 'old') {
				$stock = O('stock', $form['stock']);
				if (!$stock->id) {
					$form->set_error('stock', I18N::T('orders', '请从已有的存货选择!'));
				}
			}
		}
        elseif ($action == Order_Model::REQUESTING) {
            Event::trigger('request_order_validate_form', $form);
        }

		// 确认、修改、补增、到货确认
		if (in_array($action, [ Order_Model::CONFIRM, Order_Model::EDIT, Order_Model::ADD, Order_Model::RECEIVE])) {
			if($action != Order_Model::CONFIRM) {
				$form
					->validate('price', 'number(>0)', I18N::T('orders', '请填写总价!'));

				if ($form['price']) {
					$form->validate('price',  'is_numeric', I18N::T('orders', '总价应为数字!'));
				}
				//确认状态下如果操作为修改 需要判断供应商必填
				if ( $action == Order_Model::EDIT && $status != Order_Model::REQUESTING ) {
					Event::trigger('confirm_order_validate_form', $form);
				}
			}
			else {
				/* 此处太紊乱了，目前Order_Model::CONFIRM为多个操作混合，为了确保Trigger运行，需要矫正状态 */
				if ( $status == Order_Model::REQUESTING ) {
					Event::trigger('confirm_order_validate_form', $form);
				}
				
			}

			

            // 补增订单 或者修改订单
            if ($action == Order_Model::ADD || $action == Order_Model::EDIT) {
            	//申购中，都得验证
	            if ($form['receive_status'] == Order_Model::RECEIVED) {
	            	$form
	                    ->validate('approver', 'not_empty', I18N::T('orders', '请填写确认人!'))
		                ->validate('approver', 'compare(>0)', I18N::T('orders', '请填写确认人!'))
	                    ->validate('approve_date', 'not_empty', I18N::T('orders', '请填写确认日期!'))
						->validate('purchaser', 'not_empty', I18N::T('orders', '请填写订购人!'))
						->validate('purchase_date', 'not_empty', I18N::T('orders', '请填写订购日期!'))
						->validate('receiver', 'not_empty', I18N::T('orders', '请填写收货人!'))
						->validate('receive_date', 'not_empty', I18N::T('orders', '请填写收货日期!'));
	            }
	            else {
	            	if($form['purchaser']) {
	            		$form
		                    ->validate('approver', 'not_empty', I18N::T('orders', '请填写确认人!'))
		                    ->validate('approver', 'compare(>0)', I18N::T('orders', '请填写确认人!'))
		                    ->validate('approve_date', 'not_empty', I18N::T('orders', '请填写确认日期!'))
							->validate('purchaser', 'not_empty', I18N::T('orders', '请填写订购人!'))
							->validate('purchase_date', 'not_empty', I18N::T('orders', '请填写订购日期!'));
	            	}
	            	elseif ($form['approver']) {
	            		$form
		                    ->validate('approver', 'not_empty', I18N::T('orders', '请填写确认人!'))
		                    ->validate('approver', 'compare(>0)', I18N::T('orders', '请填写确认人!'))
		                    ->validate('approve_date', 'not_empty', I18N::T('orders', '请填写确认日期!'));
	            	}
	            }
            }

			//grant
			$grant = O('grant', $form['grant']);
            if ($grant->id) {
                $portion = O('grant_portion', $form['grant_portion']);

                if (!$portion->id) {
                    $form->set_error('grant_portion', I18N::T('grants', '请选择关联经费!'));
                }
                elseif ($portion->grant->id != $grant->id) {
                    $form->set_error('grant_portion', I18N::T('grants', '经费选择有误!'));
                }
                else {
                    if ($portion->avail_balance < $amount) {
                        $form->set_error('grant_portion', I18N::T('grants', '经费分配不足!'));
                    }

                    $amount = (float) $form['price'];
                    $form->validate('grant_summary', 'not_empty', I18N::T('grants', '请填写支出说明!'));
                }
            }
		}

		return $form;
	}

}

class Order_Controller extends Base_Controller {

	function index($id=0, $tab=NULL) {

		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}
		// view
		$this->layout->body->primary_tabs
			->add_tab('view', [
				'url' => $order->url(),
				'title' => H($order->product_name),
			])
			->select('view')
			->set('content', V('order/view', [
				'order' => $order,
			]));
	}

}

class Order_AJAX_Controller extends AJAX_Controller {

	private $form_token;

	function index_add_stock_click($id=0) {
		if (!L('ME')->is_allowed_to('添加', 'stock')) return;
		$order = O('order', $id);
		if ($order->stock->id) return;
		JS::dialog(V('orders:order/add_stock', ['order'=>$order]), ['width'=>410, 'title'=>I18N::HT('orders', '加为存货')]);
	}

	function index_add_stock_submit($id=0) {
		$me = L('ME');
		if (!$me->is_allowed_to('添加', 'stock')) return;

		$order = O('order', $id);
		if ($order->stock->id) return;

		$form = Form::filter(Input::form());
		$type = $form['add_to_stock_mode'];
		if ($type == 'old') {
			$stock = O('stock', $form['stock']);
			if (!$stock->id) {
				$form->set_error('stock', I18N::T('orders', '请从已有的存货选择!'));
			}
		}
		// 不管怎么样，都必须生成1个与该order相似的存货
		if ($form->no_error) {
			$new_stock = O('stock');
			$new_stock->product_name = $order->product_name;
			$new_stock->manufacturer = $order->manufacturer;
			$new_stock->catalog_no = $order->catalog_no;
			$new_stock->spec = $order->spec;
			$new_stock->vendor = $order->vendor;
			$new_stock->unit_price = $order->unit_price;
			$new_stock->order = $order;
			$new_stock->quantity = $order->quantity;
            $new_stock->summation = $order->quantity;
			$new_stock->note = $order->receive_note;
			$new_stock->status = Stock_Model::UNKNOWN;
			if ($type == 'new') {
				$new_stock->barcode = strtoupper($form['barcode']);
				$new_stock->location = $form['location'];
				$new_stock->note = $form['note'];

	            if ($form['auto_update_status'] == 'on') {
	                $new_stock->auto_update_status = TRUE;
	            }
	            else {
	                $new_stock->auto_update_status = FALSE;
				    $new_stock->status = $form['status'] ? : Stock_Model::UNKNOWN;
	            }
			}
			$new_stock->creator = $me;


			if ($new_stock->save()) {
				$new_stock->auto_update_status ? Event::trigger('update_stock_status', $new_stock) : NULL;
				$order->stock = $new_stock;
				$order->save();
				// 记录日志
                Log::add(strtr('[orders] %user_name[%user_id]将订单%order_name[%order_id]加为了存货%stock_name[%stock_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=> $order->id,
                    '%stock_name'=> $new_stock->product_name,
                    '%stock_id'=> $new_stock->id
                ]), 'journal');

				if ($stock->id) {
					$new_stock->merge($stock);
                    Log::add(strtr('[orders] %user_name[%user_id]将存货%new_stock_name[%new_stock_id]移入了集合%stock_name[%stock_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%new_stock_name'=> $new_stock->product_name,
                        '%new_stock_id'=> $new_stock->id,
                        '%stock_name'=> $stock->product_name,
                        '%stock_id'=> $stock->id
                    ]), 'journal');
				}
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '存货新建成功!'));
                JS::close_dialog();
                JS::redirect($new_stock->url());
			}
            else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('orders', '存货新建失败!'));
                JS::close_dialog();
                JS::refresh();
            }
		}
		JS::dialog(V('orders:order/add_stock', ['order'=>$order, 'form'=>$form]), ['width'=>410]);
	}

	function index_cancel_order_click($id=0) {
		$order = O('order', $id);
		$me = L('ME');
		if ($order->id && $me->is_allowed_to('取消', $order) && $order->status != Order_Model::CANCELED) {
			JS::dialog(V('orders:order/cancel_form', ['oid'=>$oid]), ['width'=>'500', 'title'=>I18N::HT('orders', '请填写取消原因')]);
		}
	}


    //跳转到lims order对应的 mall order
    private function _goto_mall($order) {
        if (!$order->id) return FALSE;
        $mall_order = O('mall_order', ['order'=> $order]);
        if (!$mall_order->id) return FALSE;

        JS::redirect(URI::url('mall/go', ['source'=> $order->source, 'oid'=> $mall_order->item_id]));
    }

	function index_cancel_order_submit($id=0) {
		$order = O('order', $id);
        $me = L('ME');

		if ($order->id && $me->is_allowed_to('取消', $order) && $order->status != Order_Model::CANCELED) {
            if($order->source) {
                JS::alert(H(T('操作无法完成, 请与系统管理员联系! ')));
            }
            else {

                if(!JS::confirm(H(T('您确定要驳回该订单吗?')))) {
                    JS::close_dialog();
                    return;
                }

                $form = Input::form();
                $order->canceler = $me;
                $order->cancel_date = Date::time();
                $order->cancel_note = $form['cancel_note'];
                $order->update_status();

                if ($order->save()) {
                    /* 记录日志 */
                    Log::add(strtr('[orders] %user_name[%user_id]取消了订单%order_name[%order_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%stock_name'=> $order->product_name,
                        '%stock_id'=> $order->id
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '订单取消成功!'));
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('orders', '订单取消失败!'));
                }
            }

            JS::refresh();
		}
	}

	function index_add_expense_click($id=0) {
		$order = O('order', $id);
		$me = L('ME');

		if (!$order->id) {
			return;
		}

		JS::dialog(V('orders:order/add_expense', ['order'=>$order]), ['width'=>'380px']);
	}

	function index_remove_expense_click($id=0) {
		$me = L('ME');

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$grant = $order->expense->grant;
		if ($grant->id && $me->is_allowed_to('修改支出', $grant)) {
			$order->expense->delete();
			$order->expense = NULL;
			$order->save();
		}

		JS::refresh();
	}

	function index_add_expense_submit($id=0) {
		$me = L('ME');
		$form = Form::filter(Input::form());

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$form->validate('summary', 'not_empty', I18N::T('grants', '请填写说明!'));

		$amount = $order->price;

		$grant = O('grant', $form['grant']);

		if (!$grant->id) {
			$form->set_error('grant', I18N::T('grants', '请选择经费!'));
		}
		else {
			if (!$me->is_allowed_to('修改支出', $grant)) {
				$form->set_error('grant_id', I18N::T('grants', '您无操作此经费的权限!'));
			}
			$portion = O('grant_portion', $form['grant_portion']);
			if (!$portion->id || $portion->grant->id != $grant->id) {
				$form->set_error('grant_portion', I18N::T('grants', '经费选择有误!'));
			}
			else {
				if ($portion->avail_balance < $amount) {
					$form->set_error('grant_portion', I18N::T('grants', '经费分配不足!'));
				}
			}
		}

		$user = O('user', $form['user_id']);
		if (!$user->id) {
			$form->set_error('user_id', I18N::T('grants', '用户不存在!'));
		}

		if ($form->no_error) {
			$expense = O('grant_expense');
			$expense->pre_summary = I18N::T('orders', '%user 于 %date 订购 %order 的费用', [
				'%user' => Markup::encode_Q($order->requester),
				'%date' => Date::format($order->request_date, 'Y/m/d'),
				'%order'=>Markup::encode_Q($order)
			]);
			$expense->summary = $form['summary'];
			$expense->amount = $amount;
			$expense->invoice_no = $form['invoice_no'];

			$expense->user = $user;
			$expense->ctime = $form['date'];
			$expense->portion = $portion;
			$expense->grant = $portion->id ? $portion->grant : $grant->id;
			if ($expense->save()) {
				$order->expense = $expense;
				$order->save();

				/* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]关联了订单%order_name[%order_id]到经费%grant_name[%grant_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=> $order->id,
                    '%grant_name'=> $expense->grant->project,
                    '%grant_id'=>  $expense->grant->id
                ]), 'journal');

				$tags = @json_decode($form['tags'], TRUE);
				Tag_Model::replace_tags($expense, $tags, 'grant_expense', TRUE);
			}
			JS::refresh();
		}
		else {
			JS::dialog(V('orders:order/add_expense', [
				'order'=>$order,
				'form'=>$form,
				'grant' => $grant,
				'portion' => $portion,
			]));
		}
	}

	function index_request_order_change() {
		$form = Input::form();
		$object = O($form['object_name'], $form['object_id']);

		$autocomplete_order_items = Config::get('orders.autocomplete_order_items');

        $auto_order_data = [];
        foreach($autocomplete_order_items as $item) {
            if ($item == 'vendor') {
                if (Module::installed('vendor')) {
                    //只针对已有的vendor进行补全
                    if (!O('vendor', ['name'=> $name])->id) continue;
                }
            }
            if ($object->$item) $auto_order_data[$item] = $object->$item;
        }

		Output::$AJAX = $auto_order_data;
	}

	function index_request_click($id = 0) {
		$me = L('ME');
		if (!$me->is_allowed_to('添加申购', 'order')) {
			URI::redirect('error/401');
		}

		$order = $id ? O('order', $id) : O('order');
		if ($this->form_token) Order_Helper::basic_form_to_order((array) $_SESSION[$this->form_token], $order);
		JS::dialog(V('orders:order/request', [
			'order'=>$order,
		]), ['width'=>500, 'title'=>I18N::HT('orders', '添加申购')]);
	}

	function index_request_submit() {

		$me = L('ME');
		if (!$me->is_allowed_to('添加申购', 'order')) {
			URI::redirect('error/401');
		}

		$action = Order_Model::REQUEST;

		$order = O('order');

		$form = Input::form();

        $raw_order = O('order', $form['order_id']);
        if ($raw_order->id && $raw_order->source) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::HT('orders', '无法添加订单!'));
		}
		else {
			$form = Order_Helper::validate_form(Form::filter($form), $action);

			if (!$form->no_error) {
                JS::dialog(V('orders:order/request', [
                    'form'=>$form,
                    'order'=>$order,
                ]), ['width'=>500, 'title'=>I18N::HT('orders', '添加申购')]);
                return;
			}

			$uuid = Lab::get('lims.site_id');
			Order_Helper::basic_form_to_order($form, $order);
			$order->requester = $me;
			$order->request_date = Date::time();
			$order->request_note = $form['request_note'];
			$order->status = Order_Model::REQUESTING;
            $order->link = $form['link'];
            $order->incharges = $form['incharges'];
            $order->fare = $form['fare'];

            $order->update_status();
			// save
			if ($order->save()) {
				//更新标签
				if ($me->is_allowed_to('编辑订单标签', $order)) {
					$tags = @json_decode($form['tags'], TRUE);
					Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
				}
				/* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]添加了一笔申购%order_name[%order_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=>  $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=>  $order->id
                ]), 'journal');

				// redirect to view
				Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('orders', '订单申购已添加!'));
			}
			else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::HT('orders', '无法添加订单!'));
			}
		}

		JS::redirect('!orders/index');

	}

	function index_confirm_click($id = 0) {
		// can only confirm an existent requesting order
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('确认', $order)) {
			JS::redirect('error/401');
		}

		if ($order->source) {
			$mall_order = O('mall_order', ['order'=> $order]);
            JS::dialog(V('orders:order/mall_confirm',
                ['order'=>$order, 'mall_order'=>$mall_order]
            ), ['width'=>350, 'title'=>I18N::HT('orders', '确认订单')]);
        }
        else {
            if ($order->status != Order_Model::REQUESTING) {
                JS::redirect('error/404');
            }

            JS::dialog(V('orders:order/confirm', [
                'order'=>$order,
            ]), ['width'=>500, 'title'=>I18N::HT('orders', '确认订单')]);
        }
	}

	function index_order_click($id = 0) {

		$order = O('order', $id);
		if (!$order->id || $order->status != Order_Model::READY_TO_ORDER) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('订出', $order)) {
			JS::redirect('error/401');
		}


        JS::dialog(V('orders:order/order', [
            'order'=>$order,
        ]), ['width'=>500, 'title'=>I18N::HT('orders', '订出订单')]);
	}

	function index_order_submit($id = 0) {
		$action = Order_Model::CONFIRM;

		// can only confirm an existent requesting order
		$order = O('order', $id);
		if (!$order->id || $order->status != Order_Model::READY_TO_ORDER) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('订出', $order)) {
			JS::redirect('error/401');
		}
		$form = Form::filter(Input::form());
		$form = Order_Helper::validate_form(Form::filter(Input::form()), $action);
		$form
			->validate('address', 'not_empty', I18N::T('orders', '请填写配送地址!'))
			->validate('postcode', 'not_empty', I18N::T('orders', '请填写邮政编码!'))
			->validate('phone', 'not_empty', I18N::T('orders', '请填写联系电话!'))
			->validate('email', 'is_email', I18N::T('orders', '电子邮箱填写有误!'));
		if (!$form->no_error) {
            JS::dialog(V('orders:order/order', [
                'form'=>$form,
                'order'=>$order,
            ]), ['width'=>500, 'title'=>I18N::HT('orders', '订出订单')]);
            return;
		}
		try {
			$source = $form['source'];

			$order->receive_address = $form['address'];
            $order->receive_postcode = $form['postcode'];
            $order->receive_phone = $form['phone'];
            $order->receive_email = $form['email'];

            // 更新订购人
            if (isset($form['purchaser'])) {
                $user = O('user', $form['purchaser']);
                if (!$user->id) $user = $me;
            }
            else {
                $user = $me;
            }
            $order->purchaser = $user;
            $order->purchase_date = $form['purchase_date'] ?: Date::time();
            $order->purchase_note = $form['purchase_note'];
            $order->status = Order_Model::READY_TO_TRANSFER;//已订出

            if($me->is_allowed_to('管理订单', $order)) {
            	$order->purchase_note = $form['request_note'];
            	$order->purchase_note = $form['approve_note'];
            }

			if ($form['receive_status'] == Order_Model::RECEIVED) {
				if (isset($form['receiver'])) {
					$user = O('user', $form['receiver']);
					if (!$user->id) $user = $me;
				}
				else {
					$user = $me;
				}

				$order->receiver = $user;
				$order->receive_date = $form['receive_date'];
				$order->receive_note = $form['receive_note'];
                $order->deliver_status = Order_Model::RECEIVED;//已到货
			}
			else {
				$order->receiver = NULL;
				$order->receiver_date = 0;
				$order->receive_note = '';
                $order->deliver_status = Order_Model::NOT_RECEIVED;//已到货
			}

			if ($order->save()) {

				if ($form['add_to_stock'] == 'on') {
					$type = $form['add_to_stock_mode'];
					if ($type == 'old') {
						$stock = O('stock',$form['stock']);
						if (!$stock->id) {
							$form->set_error('stock', I18N::T('orders', '请从已有的存货选择!'));
						}
					}

					if ($form->no_error) {
						$new_stock = Order_Helper::order_to_stock($order);
						$removed = false;
						if ($type == 'new') {
							$new_stock->barcode = strtoupper($form['stock_barcode']);
							$new_stock->location = $form['stock_location'];
							$new_stock->status = $form['stock_status'];
							$new_stock->note = $form['stock_note'];
							if ($new_stock->id && $new_stock->parent_id && $new_stock->id != $new_stock->parent_id) {
								if ($new_stock->remove()) {
									$removed = true;
								}
							}
							if (!$removed) {
								$new_stock->parent = NULL;
							}
						}
						if (!$removed && $new_stock->save()) {
							$new_stock->parent = $new_stock;
							$new_stock->save();
							if ($stock->id) {
								$new_stock->merge($stock);
							}
						}
					}
					else {
						throw new Exception();
					}
				}
				else {
					$stock = O('stock', ['order'=>$order]);
					/*if the stock belongs to a clollection, before it is deleted, it should be moved out of the collection*/
					if ($stock->id && $stock->id != $stock->parent_id) {
						$stock->remove();
					}
					$stock->delete();
				}

				//更新经费
				if ($me->is_allowed_to('修改支出', $order->expense->grant)) {
					Order_Helper::grant_form_to_order($form, $order);
				}

				/* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]确认了订单%order_name[%order_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=>  $order->id
                ]), 'journal');
                $message = I18N::HT('orders', '订单已订出!');
                $new_message = Event::trigger('order_confirm_message', $form, $order->vendor);
                if ($new_message) $message = $new_message;
                Lab::message(Lab::MESSAGE_NORMAL, $message);
			}
			else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::HT('orders', '无法更新订单!'));
			}
			JS::refresh();
		}
		catch (Exception $e){
			JS::dialog(V('orders:order/order', [
				'form'=>$form,
				'order'=>$order,
			]), ['width'=>500, 'title'=>I18N::HT('orders', '订出订单')]);
		}

	}

	function index_confirm_submit($id = 0) {
		$action = Order_Model::CONFIRM;
		// can only confirm an existent requesting order
		$order = O('order', $id);
		if (!$order->id || $order->status != Order_Model::REQUESTING) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('确认', $order)) {
			JS::redirect('error/401');
		}

		try {
			$form = Input::form();
			$source = $form['source'];
			if (!$source) {
				$form = Order_Helper::validate_form(Form::filter(Input::form()), $action, $order->status);
				if (!$form->no_error) {
					throw new Exception;
				}

			}

			Order_Helper::basic_form_to_order($form, $order);

			$order->approver = $me;
			$order->approve_date = Date::time();
            $order->approve_note = $form['approve_note'];

            if($me->is_allowed_to('管理订单', $order)) {
            	$order->request_note = $form['request_note'];
            }
            $order->update_status();

            $order->link = $form['link'];
            $order->fare = $form['fare'];

			if ($order->save()) {
				//更新经费
				if ($me->is_allowed_to('修改支出', $order->expense->grant)) {
					Order_Helper::grant_form_to_order($form, $order);
				}

				//更新标签
				if ($me->is_allowed_to('编辑订单标签', $order)) {
					$tags = @json_decode($form['tags'], TRUE);
					Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
				}
				/* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]确认了订单%order_name[%order_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=>  $order->id
                ]), 'journal');
                $message = I18N::HT('orders', '订单已确认!');
                Lab::message(Lab::MESSAGE_NORMAL, $message);
			}
			else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::HT('orders', '无法更新订单!'));
			}
			JS::refresh();
		}
		catch (Exception $e){
			JS::dialog(V('orders:order/confirm', [
				'form'=>$form,
				'order'=>$order,
			]), ['width'=>500, 'title'=>I18N::HT('orders', '确认订单')]);
		}

	}

	function index_add_click() {
        $me = L('ME');
        if (!$me->is_allowed_to('修改', 'order')) {
            URI::redirect('error/401');
        }

        JS::dialog(V('orders:order/add'), [
            'width'=> 500,
            'title'=> I18N::HT('orders', '补增订单')
        ]);
    }

    //补增订单
    function index_add_submit($id = 0) {
        $action = Order_Model::ADD;
        $order = O('order');

        $me = L('ME');
        if (!$me->is_allowed_to('修改', 'order')) {
            URI::redirect('error/401');
        }

        // post
        try {
            $form = Order_Helper::validate_form(Form::filter(Input::form()), $action);
            if (!$form->no_error) {
                throw new Exception;
            }

            $order->product_name = $form['product_name'];
            $order->manufacturer = $form['manufacturer'] ? : '';
            $order->catalog_no = $form['catalog_no'] ? : '';
            $order->model = $form['model'] ? : '';
            $order->spec = $form['spec'] ? : '';
            $order->order_no = $form['order_no'] ? : '';

            // requester can suggest
            Event::trigger('extra_basic_form_to_order', $order, $form);

            $order->fare = $form['fare'];
            $order->unit_price = (float)$form['unit_price']?:'';
            $order->price = (float)$form['price'] ?: $order->unit_price * (int)$order->quantity + $order->fare;
            //订单信息
            //收货地址不进行检索，以虚属性进行存储
            $order->receive_address = $form['address'];
            $order->receive_postcode = $form['postcode'];
            $order->receive_phone = $form['phone'];
            $order->receive_email = $form['email'];
            $order->quantity = (int) $form['quantity'];
            // 更新申购人
            if (isset($form['requester'])) {
                $user = O('user', $form['requester']);
                if (!$user->id) $user = $me;
            }
            else {
                $user = $me;
            }

            $order->requester = $user;
            $order->request_date = $form['request_date'] ?: Date::time();
            $order->request_note = $form['request_note'];
            $order->link = $form['link'];
            $order->fare = (int)$form['fare'];

            // 更新确认人
            if ($form['approver']) {
                $user = O('user', $form['approver']);
                if (!$user->id) $user = $me;
                $order->approver = $user;
            }

            $order->approve_date = $form['approve_date'] ?: Date::time();
            $order->aprove_note = $form['aprove_note'];

            // 更新订购人
            if ($form['purchaser']) {
                $user = O('user', $form['purchaser']);
                if (!$user->id) $user = $me;
                $order->purchaser = $user;
            }

            $order->purchase_date = $form['purchase_date'] ?: Date::time();
            $order->purchase_note = $form['purchase_note'];

            // 更新收货人
            if ($form['receive_status'] == Order_Model::RECEIVED) {
                if (isset($form['receiver'])) {
                    $user = O('user', $form['receiver']);
                    if (!$user->id) $user = $me;
                }
                else {
                    $user = $me;
                }

                $order->receiver = $user;
                $order->receive_date = $form['receive_date'];
                $order->receive_note = $form['receive_note'];
            }
            else {
                $order->receiver = NULL;
                $order->receiver_date = 0;
                $order->receive_note = '';
            }

            $order->update_status();

            // 保存
            if ($order->save()) {

                //更新经费
                if ($me->is_allowed_to('修改支出', $order->expense->grant)) {
                    Order_Helper::grant_form_to_order($form, $order);
                }

                // 更新标签
                if ($me->is_allowed_to('编辑订单标签', $order)) {
                    $tags = @json_decode($form['tags'], TRUE);
                    Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
                }

                /* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]修改了订单%order_name[%order_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=>  $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=>  $order->id
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('orders', '订单更新成功!'));
            }
            else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::HT('orders', '无法更新订单!'));
            }

            if ($form->no_error){
                JS::refresh();
                return;
            }
        }
        catch (Exception $e){
        }


        JS::dialog(V('orders:order/add', [
            'form'=>$form,
        ]), ['width'=>500, 'title'=>I18N::HT('orders', '补增订单')]);
    }

	function index_edit_click($id = 0) {
		$action = Order_Model::EDIT;

		// can only confirm an existent requesting order
		$order = O('order', $id);
		$me = L('ME');

		if (!$me->is_allowed_to('修改', $order)) {
			URI::redirect('error/401');
		}

		JS::dialog(V('orders:order/edit', [
			'order'=>$order,
			'disable'=>'quantity',
		]), [
			'width'=>500,
			'title'=> $order->id ? I18N::HT('orders', '修改订单') : I18N::HT('orders', '补增订单')
		]);
	}

	function index_edit_submit($id = 0) {
		$action = Order_Model::EDIT;

		// can only confirm an existent requesting order
		$order = O('order', $id);
		/* TODO获取之前order的status， $old_status*/
		$old_status = $order->status;

		$me = L('ME');
		if (!$me->is_allowed_to('修改', $order)) {
			URI::redirect('error/401');
		}

		// post
		try {
			// validation
			$form = Input::form();

			if (!$form['source']) {
				$form = Order_Helper::validate_form(Form::filter(Input::form()), $action, $old_status);

				if (!$form->no_error) {
					throw new Exception;
				}
			}
			else {
				$form = Form::filter(Input::form());
			}

            //source
            if ($order->source) {
				if ($form['receive_status'] == Order_Model::NOT_RECEIVED && $order->deliver_status == Order_Model::RECEIVED) {
					//未通过审核
					JS::alert(I18N::HT('orders', '该订单已到货，不能改为未到货'));
					return FALSE;
				}
            }

            // assignment
			if (!$form['source']) {
				$order->product_name = $form['product_name'];
				$order->manufacturer = $form['manufacturer'] ? : '';
				$order->catalog_no = $form['catalog_no'] ? : '';
				$order->model = $form['model'] ? : '';
				$order->spec = $form['spec'] ? : '';
	            $order->order_no = $form['order_no'] ? : '';

				// requester can suggest
				Event::trigger('extra_basic_form_to_order', $order, $form);

				$order->unit_price = (float)$form['unit_price']?:'';
            	$order->price = (float)$form['price'] ?: $order->unit_price * (int)$order->quantity + $order->fare;
				//订单信息
				//收货地址不进行检索，以虚属性进行存储
				$order->receive_address = $form['address'];
		        $order->receive_postcode = $form['postcode'];
		        $order->receive_phone = $form['phone'];
		        $order->receive_email = $form['email'];
		        $order->quantity = (int) $form['quantity'];
		        $order->link = $form['link'];
            	$order->fare = (int)$form['fare'];

				// 更新申购人
				if (isset($form['requester'])) {
					$requester = O('user', $form['requester']);
				}

				$order->requester = $requester;
				$order->request_date = $form['request_date'] ?: Date::time();
				$order->request_note = $form['request_note'];

            	//更新确认人
				if (isset($form['approver'])) {
					$approver = O('user', $form['approver']);
				}

				$order->approver = $approver;
				$order->approve_date = $form['approve_date'] ?: Date::time();
                $order->approve_note = $form['approve_note'];


				// 更新订购人
				if (isset($form['purchaser'])) {
					$purchaser = O('user', $form['purchaser']);
				}

				$order->purchaser = $purchaser;
				$order->purchase_date = $form['purchase_date'] ?: Date::time();
				$order->purchase_note = $form['purchase_note']?:'';
				$order->update_status();

        	}
			// 更新收货人
			if ($form['receive_status'] == Order_Model::RECEIVED) {
				if (isset($form['receiver'])) {
					$user = O('user', $form['receiver']);
					if (!$user->id) $user = $me;
				}
				else {
					$user = $me;
				}

				$order->receiver = $user;
				$order->receive_date = $form['receive_date'];
				$order->receive_note = $form['receive_note']?:'';
				$order->deliver_status = Order_Model::RECEIVED;
			}
			else {
				$order->receiver = NULL;
				$order->receiver_date = 0;
				$order->receive_note = '';
				$order->deliver_status = Order_Model::NOT_RECEIVED;
			}

        	$ret = TRUE;
			if ($order->source) {
                JS::alert(I18N::HT('orders', '无法更新订单!'));
                return;
			}
			// 保存
			if ($ret && $order->save()) {
				//关联存货
				if ($form['add_to_stock'] == 'on') {
					$type = $form['add_to_stock_mode'];
					if ($type == 'old') {
						$stock = O('stock',$form['stock']);
						if (!$stock->id) {
							$form->set_error('stock', I18N::T('orders', '请从已有的存货选择!'));
						}
					}

					if ($form->no_error) {
						$new_stock = Order_Helper::order_to_stock($order);
						$removed = false;
						if ($type == 'new') {
							$new_stock->barcode = strtoupper($form['stock_barcode']);
							$new_stock->location = $form['stock_location'];
							$new_stock->status = $form['stock_status'];
							$new_stock->note = $form['stock_note'];
							if ($new_stock->id && $new_stock->parent_id && $new_stock->id != $new_stock->parent_id) {
								if ($new_stock->remove()) {
									$removed = true;
								}
							}
							if (!$removed) {
								$new_stock->parent = NULL;
							}
						}
						if (!$removed && $new_stock->save()) {
							$new_stock->parent = $new_stock;
							$new_stock->save();
							if ($stock->id) {
								$new_stock->merge($stock);
							}
						}
					}
				}
				else {
					$stock = O('stock', ['order'=>$order]);
					/*if the stock belongs to a clollection, before it is deleted, it should be moved out of the collection*/
					if ($stock->id && $stock->id != $stock->parent_id) {
						$stock->remove();
					}
					$stock->delete();
				}

				//更新经费
				if ($me->is_allowed_to('修改支出', $order->expense->grant)) {
					Order_Helper::grant_form_to_order($form, $order);
				}

				// 更新标签
				if ($me->is_allowed_to('编辑订单标签', $order)) {
					$tags = @json_decode($form['tags'], TRUE);
					Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
				}

				/* 记录日志 */
                Log::add(strtr('[orders] %user_name[%user_id]修改了订单%order_name[%order_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=>  $me->id,
                    '%order_name'=> $order->product_name,
                    '%order_id'=>  $order->id
                ]), 'journal');

				Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('orders', '订单更新成功!'));
			}
			else {
				JS::alert(I18N::HT('orders', '无法更新订单!'));
				return;
			}

			if ($form->no_error){
				JS::refresh();
				return;
			}
		}
		catch (Exception $e){
			JS::dialog(V('orders:order/edit', [
				'form'=>$form,
				'order'=>$order,
			]), ['width'=>500, 'title'=>I18N::HT('orders', '修改订单')]);
		}
	}

	function index_duplicate_click($id = 0) {
		$me = L('ME');
		// is allowed to
		if (!$me->is_allowed_to('添加申购', 'order')) {
			URI::redirect('error/401');
		}

		// can only duplicate an purchased order
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}
		if ($order->source) {
			JS::alert(I18N::T('orders','无法连接到商城完成申购, 请与课题组负责人联系!'));
			return;
		}
		$form_token = Session::temp_token();
		$_SESSION[$form_token] = Order_Helper::order_to_form($order);
		$this->form_token = $form_token;
		return $this->index_request_click($order->id);
	}

    function index_order_search_submit() {
        $order_no = Input::form('order_no');

        if ($order_no) {
            $order = O('order', $order_no);
            if ($order->id) {
                JS::redirect($order->url());
                return FALSE;
            }
            else {
                $orders = Q("order[order_no={$order_no}]:limit(1)");
                if ($orders->total_count()) {
                    JS::redirect($orders->current()->url());
                    return FALSE;
                }
            }

            JS::alert(I18N::T('orders', '您查询的订单不存在!'));
        }
        else {
            JS::alert(I18N::T('orders', '请输入您想要查询的订单编号!'));
        }
    }

    function index_receive_click($id = 0){
    	$action = Order_Model::RECEIVE;

		// can only confirm an existent requesting order
		$order = O('order', $id);
		$me = L('ME');

		if (!$me->is_allowed_to('收货', $order)) {
			URI::redirect('error/401');
		}

		JS::dialog(V('orders:order/receive', [
			'order'=>$order,
			'disable'=>'quantity',
		]), [
			'width'=>500,
			'title'=> I18N::HT('orders', '确认收货')
		]);
    }


    function index_receive_submit($id = 0) {
		$action = Order_Model::RECEIVE;
		// can only receive an not_received order
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('收货', $order)) {
			URI::redirect('error/401');
		}

		if (Input::form('submit')) {
			// post
			try {

				// validation
				$form = Order_Helper::validate_form(Form::filter(Input::form()), $action);
				if (!$form->no_error) {
					throw new Exception;
				}

				if (!$me->is_allowed_to('修改', $order)) {
					unset($form['receiver']);
				}

				//source
	        	if ($form['receive_status'] == Order_Model::NOT_RECEIVED) {
					JS::alert(I18N::HT('orders', '请选择已到货再点击提交按钮!'));
					return FALSE;
	            }

				$order->receiver = $me;
				$order->receive_date = Date::time();
				$order->receive_note = $form['receive_note'];
				$order->update_status();
				$ret = TRUE;
				if ($order->source) {
                    JS::alert(I18N::T('orders', '订单到货失败!'));
                    return;
				}
				// save
				if ($ret && $order->save()) {
					/* 记录日志 */
                    //关联存货
                    if ($form['add_to_stock'] == 'on') {
                        $stock = Order_Helper::order_to_stock($order);
                        $stock->barcode = $form['stock_barcode'];
                        $stock->location = $form['stock_location'];
                        $stock->status = $form['stock_status'];
                        $stock->note = $form['stock_note'];
                        $stock->save();
                    }
                    else {
                        $stock = O('stock', ['order'=>$order]);
                        $stock->delete();
                    }

                    //更新经费
                    if ($me->is_allowed_to('修改支出', $order->expense->grant)) {
                        Order_Helper::grant_form_to_order($form, $order);
                    }

                    // 更新标签
                    if ($me->is_allowed_to('编辑订单标签', $order)) {
                        $tags = @json_decode($form['tags'], TRUE);
                        Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
                    }

                    /* 记录日志 */
                    Log::add(strtr('[orders] %user_name[%user_id]查收了订单%order_name[%order_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%order_name'=> $order->product_name,
                        '%order_id'=> $order->id
                    ]), 'journal');

					JS::refresh();
				}
				else {
					JS::alert(I18N::T('orders', '订单到货失败!'));
					return;
				}
			}
			catch (Exception $e){
			}
		}

		JS::dialog(V('orders:order/receive', [
			'order'=>$order,
			'disable'=>'quantity',
			'form'=>$form,
		]), [
			'width'=>500,
			'title'=> I18N::HT('orders', '确认收货')
		]);
	}

	function index_from_stock_click($sid=0){
		$stock = O('stock', $sid);
		if (!$stock->id) {
			URI::redirect('error/404');
		}

		if ($stock->order->id) {
			// 根据订单填写信息
			return $this->index_duplicate_click($stock->order->id);
		}

		$me = L('ME');
		if (!$me->is_allowed_to('添加申购', 'order')) {
			URI::redirect('error/401');
		}
		$order = O('order');
		$stock_form = Order_Helper::stock_to_form($stock);

        Order_Helper::basic_form_to_order((array)$stock_form, $order);

        JS::dialog(V('orders:order/request', [
			'order'=>$order, 'showTags'=>TRUE , 'stock'=>$stock
		]), ['width'=>500, 'title'=>I18N::HT('orders', '添加申购')]);
	}

	function index_get_product_click() {
        JS::alert(T('系统错误, 请与管理员联系!'));
        Output::$AJAX['error'] = true;
        return;
    }

    function index_mall_confirm_submit($id) {
        JS::alert(H(T('确认失败!')));
        return;
    }
}
