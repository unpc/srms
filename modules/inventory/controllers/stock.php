<?php

class Stock_Controller extends Base_Controller {

	function index($id = 0, $tab = 'use') {
		$stock = O('stock', $id);
		if (!$stock->id) {
			URI::redirect('error/404');
		}

		$other_content = Event::trigger('stock.view', $stock);

		$this->add_js('cardread');
		$this->add_css('cardread');

        $content = V('stock/view', ['stock'=>$stock, 'other_content'=>$other_content]);
		$this->layout->body->primary_tabs
			->add_tab('view', [
						  'url' => $stock->url(),
						  'title' => H($stock->product_name),
						  ])
			->select('view')
			->set('content', $content);

		$content->secondarys_tabs = Widget::factory('tabs')
            ->add_tab('use', [
                'url' => $stock->url('use', NULL, NULL, 'view'),
                'title'=> I18N::T('inventory', '领用 / 归还记录')
            ])
            ->set('class', 'primary_tabs')
            ->set('stock', $stock)
            ->tab_event('stock.view.tab')
            ->content_event('stock.view.content')
            ->select($tab);

		$this->add_css('rte_container');
	}


    function _index_view_use_content($e, $tabs) {
        $tabs->content = V('use/lite', ['stock'=>$tabs->stock]);
    }

    function _index_view_revert_content($e, $tabs) {
        $tabs->content = V('revert/lite', ['stock'=>$tabs->stock]);
    }

	//添加存货
	function add() {
		$me = L('ME');

		if (!$me->is_allowed_to('添加', 'stock')) {
			URI::redirect('error/401');
		}

		if (Input::form('submit')) {
			// post
			try {

				$form = Form::filter(Input::form());
				// validation
				$form->validate('product_name', 'not_empty', I18N::T('inventory', '请填写产品名称！'));
				$form->validate('quantity', 'number(>=0)', I18N::T('inventory', '存量不能小于零！'));

				$ref_no = trim($form['ref_no']);

				if (strlen($ref_no) > (Config::get('stock.default_ref_no_length') ? : 8)) {
					 $form->set_error('ref_no', I18N::T('inventory', '自定义编号不能超过%num位！', ['%num'=>Config::get('stock.default_ref_no_length') ? : 8]));
				}
				if ($ref_no) {
					$ref_no = str_pad($ref_no,Config::get('stock.default_ref_no_length') ? : 8,'0',STR_PAD_LEFT);
				}
                if (preg_match('/[\W]/i', $form['barcode'])) {
                    $form->set_error('barcode', I18N::T('inventory', '条码不能为非数字、字母外的字符！'));
                }

                if ($form['quantity'] && $form['quantity'] > $form['summation']) {
                    $form->set_error('summation', I18N::T('inventory', '总量不应小于存量！'));
                }

				if ($form['ref_no'] && O('stock', ['ref_no'=>$ref_no])->id) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '您输入的自定义编号在系统中已存在!'));
					throw new Error_Exception;
				}

				if ( $form['expire_status'] != Stock_Model::$never_expired ) {
					if (strstr($form['expire_notice_time'], '.') || (double)$form['expire_notice_time'] < 0){
						$form->set_error('expire_notice_time', I18N::T('inventory', '过期时间只能为大于等于0的整数'));
					}
				}

                if (Module::is_installed('extra')) {
                    Event::trigger('extra.form.validate', Lab_Model::default_lab(),'stock', $form, $form['type']);
                }

				if (!$form->no_error) {
					throw new Exception;
				}

				$stock = O('stock');
				// assignment

                $stock->ref_no = $ref_no ? : NULL;
				$stock->product_name = $form['product_name'];
				$stock->catalog_no = $form['catalog_no'];
				$stock->manufacturer = $form['manufacturer'];
				$stock->vendor = $form['vendor'];
				$stock->model = $form['model'];
				$stock->spec = $form['spec'];
				$stock->unit_price = $form['unit_price'];
				$stock->quantity = $form['quantity'];
                $stock->summation = $form['summation'];
				$stock->location = $form['location'];
				$stock->barcode = strtoupper($form['barcode']);

				if ( $form['expire_status'] == Stock_Model::$never_expired ) {
					$stock->expire_status = Stock_Model::$never_expired;
				}
				else {
					$stock->expire_time = Date::get_day_end($form['expire_time']);
					$stock->expire_notice_time = intval($form['expire_notice_time']) * 86400;
					$stock->expire_status = Stock::get_stock_expire_status($stock);
				}
				// DONE barcode校验 (ean13最后1位是校验位，也许还需考虑其他编码格式)
				// barcode已改为code 128, 无校验位 http://en.wikipedia.org/wiki/Code_128 (xiaopei.li@2011.11.01)

                if ($form['auto_update_status'] == 'on') {
                    $stock->auto_update_status = TRUE;
                }
                else {
                    $stock->auto_update_status = FALSE;
				    $stock->status = $form['status'] ? : Stock_Model::UNKNOWN;
                }
				$stock->note = $form['note'];

                if (isset($form['type']) && $form['type'] != -1) {
                    $stock->type = $form['type'];
                }

				if ($stock->save()) {
                    $stock->auto_update_status ? Event::trigger('update_stock_status', $stock) : NULL;
					$tags = @json_decode($form['tags'], TRUE);
					if (count($tags)) {
						Tag_Model::replace_tags($stock, $tags, 'inventory', TRUE);
					}

                    if ($form['stock_merge']) {
                        //如果存在stock_merge则进行合并
                        $stock_merge = O('stock', $form['stock_merge']);
                        if (!$stock_merge->id) {
                            $form->set_error('stock_merge', I18N::T('inventory', '请从已有的存货选择！'));
                            //如果无法merge，提示错误信息并delete现已存在的stock，待用户重新添加
                            $stock->delete();
                            throw new Exception;
                        }
                        $stock->merge($stock_merge);
                    }
                    else {
                    	$stock->parent = $stock;
                    	$stock->save();
                    }

                    if (Module::is_installed('extra')) {
                        Event::trigger('extra.form.post_submit', $stock, $form);
                    }


					// 记录日志
					Log::add(strtr('[inventory] %user_name[%user_id]添加了一笔存货%stock_name[%stock_id]', [
								   '%user_name' => $me->name,
								   '%user_id' => $me->id,
								   '%stock_name' => $stock->product_name,
								   '%stock_id' => $stock->id
								   ]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货新建成功！'));
					URI::redirect($stock->url());
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货保存失败！'));
				}
			}
			catch (Exception $e) {
			}
		 }

		// set form
		$this->layout->body->primary_tabs
			->add_tab('add', [
						  'url'=>URI::url('!inventory/stock/add'),
						  'title'=>I18N::T('inventory', '添加存货'),
						  ])
			->select('add')
			->set('content', V('stock/edit.info', ['stock'=>$stock, 'form'=>$form, 'follow'=>TRUE]));
	}

	//编辑存货
	function edit($id=0, $tab='info') {

		$me = L('ME');
		if (!$me->is_allowed_to('修改', 'stock')) {
			URI::redirect('error/401');
		}

		$stock = O('stock',$id);

		Event::bind('stock.edit', [$this, '_edit_info'], 0, 'info');
		Event::bind('stock.edit', [$this, '_edit_content'], 0, 'content');
        Event::bind('stock.edit', [$this, '_edit_status'], 0, 'status');

		$content = V('stock/edit');
		$content->secondary_tabs =
			Widget::factory('tabs')
			->set('class', 'secondary_tabs')
			->set('stock', $stock);

		$content->secondary_tabs
            ->add_tab('info', [
                            'url'=>$stock->url('info', NULL, NULL, 'edit'),
						    'title'=>I18N::T('inventory', '基本信息'),
						  ])
			->add_tab('content', [
						    'url'=>$stock->url('content', NULL, NULL, 'edit'),
						    'title'=>I18N::T('inventory', '内容'),
						])
			->add_tab('status', [
						    'url'=>$stock->url('status', NULL, NULL, 'edit'),
						    'title'=>I18N::T('inventory', '状态管理'),
						]);

		$content->secondary_tabs
       		->tab_event('stock.edit.tab')
			->content_event('stock.edit')
			->select($tab);

		$this->layout->body->primary_tabs
			->add_tab('edit', [
				'*'=>[
					[
						'url'   => $stock->url(),
						'title' => H($stock->product_name),
					],
					[
						'url'   => $stock->url(NULL, NULL, NULL, 'edit'),
						'title' => I18N::HT('inventory', '修改'),
					]
				]
			])
			->select('edit')
			->set('content', $content);

		$this->add_css('rte');
		$this->add_js('rte rte.toolbar');
	}

	function _edit_info($e, $tabs) {
		$stock = $tabs->stock;

		$form = Form::filter(Input::form());

		if (!$stock->id) {
			URI::redirect('error/404');
		}

		if (Input::form('submit')) {
			// post
			try {

				$ref_no = trim($form['ref_no']);
				// validation
				$form->validate('product_name', 'not_empty', I18N::T('inventory', '请填写产品名称！'));
				$form->validate('quantity', 'number(>=0)', I18N::T('inventory', '存量不能小于零！'));
				$form->validate('summation', 'number(>=0)', I18N::T('inventory', '总量不能小于零！'));

				if ( $form['expire_status'] != Stock_Model::$never_expired ) {
					if (strstr($form['expire_notice_time'], '.') || (double)$form['expire_notice_time'] < 0){
						$form->set_error('expire_notice_time', I18N::T('inventory', '过期时间只能为大于等于0的整数'));
					}
				}
				if (strlen($ref_no) > (Config::get('stock.default_ref_no_length') ? : 8)) {
					 $form->set_error('ref_no', I18N::T('inventory', '自定义编号不能超过%num位！', ['%num'=>Config::get('stock.default_ref_no_length') ? : 8]));
				}
				if ($ref_no) {
					$ref_no = str_pad($ref_no,Config::get('stock.default_ref_no_length') ? : 8,'0',STR_PAD_LEFT);
				}

                if (preg_match('/[\W]/i', $form['barcode'])) {
                    $form->set_error('barcode', I18N::T('inventory', '条码不能为非数字、字母外的字符！'));
                }

                if ($form['quantity'] && $form['quantity'] > $form['summation']) {
                    $form->set_error('summation', I18N::T('inventory', '总量不应小于存量！'));
                }

                if ($ref_no) {
	                $new_stock = O('stock',['ref_no'=>$ref_no]);
	                if ($new_stock->id && $new_stock->id != $stock->id) {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '您输入的自定义编号在系统中已存在!'));
						throw new Error_Exception;
					}
                }

                if (Module::is_installed('extra')) {
                    Event::trigger('extra.form.validate', Lab_Model::default_lab(),'stock', $form, $form['type']);
                }

				if (!$form->no_error) {
					throw new Exception;
				}

				if ($form['stock_merge']) {
					$stock_merge = O('stock', $form['stock_merge']);
					if (!$stock_merge->id) {
						$form->set_error('stock_merge', I18N::T('inventory', '请从已有的存货选择！'));
						throw new Exception;
					}
					$stock->merge($stock_merge);
				}
				else {
					$stock->remove();
				}

				// assignment
				$stock->ref_no = $ref_no ?: NULL;
				$stock->product_name = $form['product_name'];
				$stock->catalog_no = $form['catalog_no'];
				$stock->manufacturer = $form['manufacturer'];
				$stock->vendor = $form['vendor'];
				$stock->model = $form['model'];
				$stock->spec = $form['spec'];
				$stock->barcode = strtoupper($form['barcode']);
				// DONE barcode校验 (ean13最后1位是校验位，也许还需考虑其他编码格式)
				// barcode已改为code 128, 无校验位 http://en.wikipedia.org/wiki/Code_128 (xiaopei.li@2011.11.01)

				$stock->unit_price = $form['unit_price'];
				$stock->quantity = $form['quantity'];
                $stock->summation = $form['summation'];
				$stock->location = $form['location'];

				if ( $form['expire_status'] == Stock_Model::$never_expired ) {
					$stock->expire_status = Stock_Model::$never_expired;
				}
				else {
					$stock->expire_time = Date::get_day_end($form['expire_time']);
					$stock->expire_notice_time = intval($form['expire_notice_time']) * 86400;
					$stock->expire_status = Stock::get_stock_expire_status($stock);
				}

                if ($form['auto_update_status'] == 'on') {
                    $stock->auto_update_status = TRUE;
                }
                else {
                    $stock->auto_update_status = FALSE;
				    $stock->status = $form['status'] ? : Stock_Model::UNKNOWN;
                }

				$stock->note = $form['note'];

                if (isset($form['type']) && $form['type'] != -1) {
                    $stock->type = $form['type'];
                }

				if ($stock->save()) {
                    $stock->auto_update_status ? Event::trigger('update_stock_status', $stock) : NULL;
					$tags = @json_decode($form['tags'], TRUE);
					if (count($tags)) {
						Tag_Model::replace_tags($stock, $tags, 'inventory', TRUE);
                    }
                    else {
                        Tag_Model::clean_tags($stock, 'inventory');

                    }
					// 记录日志
					$me = L('ME');
					Log::add(strtr('[inventory] %user_name[%user_id]修改了存货%stock_name[%stock_id]的基本信息', [
								   '%user_name' => $me->name,
								   '%user_id' => $me->id,
								   '%stock_name' => $stock->product_name,
								   '%stock_id' => $stock->id
								   ]), 'journal');

                    if (Module::is_installed('extra')) {
                        Event::trigger('extra.form.post_submit', $stock, $form);
                    }

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货保存成功！'));
					URI::redirect($stock->url());
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货保存失败！'));
				}
			}
			catch (Exception $e) {
			}
		}

		$tabs->content = V('stock/edit.info', ['stock'=>$stock, 'form'=>$form]);
	}

	function _edit_content($e, $tabs) {
		$stock = $tabs->stock;

		if (!$stock->id) {
			URI::redirect('error/404');
		}

		if (Input::form('submit')) {
			// post
			try {

				$form = Form::filter(Input::form());

				// validation - none

				$stock->content = Output::safe_html($form['content']);

				if ($stock->save()) {
					// 记录日志
					$me = L('ME');
					Log::add(strtr('[inventory] %user_name[%user_id]修改了存货%stock_name[%stock_id]的内容', [
								   '%user_name' => $me->name,
								   '%user_id' => $me->id,
								   '%stock_name' => $stock->product_name,
								   '%stock_id' => $stock->id
								   ]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货内容保存成功！'));
					// URI::redirect($stock->url());
					/* 修改存货内容后留在当前页面也许更合适，方便继续修改(xiaopei.li@2011.07.06) */
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货内容保存失败！'));
				}
			}
			catch (Exception $e) {
			}
		}

		$tabs->content = V('stock/edit.content', ['stock'=>$stock, 'form'=>$form]);
	}

	function _edit_status($e, $tabs) {

		$stock = $tabs->stock;
        $form = Input::form();
        if ($form['submit']) {
            $stock->percent_adequate = $form['adequate_percent'];
            $stock->percent_inadequate = $form['inadequate_percent'];
            if($stock->save()) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货状态设定保存成功！'));
            }
            else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货状态设定保存失败！'));
            }

            Event::trigger('update_stock_status', $stock);

        }

		$tabs->content = V('stock/edit.status', ['stock'=>$stock, 'form'=>$form]);
	}

  }

class Stock_AJAX_Controller extends AJAX_Controller {

	function index_delete_stock_click() {

		$form = Input::form();
		$sid = $form['sid'];
		$stock = O('stock', $sid);
		$me = L('ME');

		if (!$me->is_allowed_to('删除', $stock)) {
			return;
		}

		if (JS::confirm(I18N::T('inventory', '你确定要删除吗?请谨慎操作!'))){
			/*
			 *  TODO 如果存在了领用记录，则该存货不可被删除，但是领用记录不能删除，所以只要存在了领用记录， 该存货就一辈子不可能删除了。
			 */
			if (Q("stock_use[stock={$stock}]")->total_count() > 0) {
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('inventory','该存货有领用记录，不可删除!'));
				JS::redirect($stock->url());
			}
			else {
				$stock_attachments_dir_path = NFS::get_path($stock, '', 'attachments', TRUE);
				if ($stock->delete()) {
					File::rmdir($stock_attachments_dir_path);
					Lab::message(LAB::MESSAGE_NORMAL,I18N::T('inventory','存货删除成功!'));
					Log::add(strtr('[inventory] %user_name[%user_id]删除了存货%stock_name[%stock_id]', [
								   '%user_name' => $me->name,
								   '%user_id' => $me->id,
								   '%stock_name' => $stock->product_name,
								   '%stock_id' => $stock->id
								   ]), 'journal');

					JS::redirect(URI::url('!inventory/index'));
				}
				else {
					JS::alert(I18N::T('inventory', '存货删除失败!'));
				}
			}
		}

	}

	function index_edit_stock_content_click() {

		$form = Form::filter(Input::form());
		$sid = $form['sid'];
		$stock = O('stock', $sid);
		$me = L('ME');

		if (!$me->is_allowed_to('修改', $stock)) {
			return;
		}

		/*
		  IMAGINING
		  点编辑后内容替换为form，form提交后替换为新内容，而不通过dialog和refresh
		  refer to lablife
		*/
		JS::dialog(V('inventory:stock/edit_content', ['stock'=>$stock]));
	}

	function index_edit_stock_content_submit() {
		$form = Form::filter(Input::form());
		$sid = $form['sid'];
		$stock = O('stock', $sid);
		$me = L('ME');

		$stock->content = $form['stock_content'];
		$stock->save();

		JS::refresh();
	}

	function index_into_stock_click() {
		$form = Form::filter(Input::form());
		$stock = O('stock', $form['sid']);
		if (!L('ME')->is_allowed_to('修改', $stock) || $stock->is_collection) return;
		JS::dialog(V('inventory:stock/into_stock', ['stock'=>$stock]), ['width'=>'370px']);
	}

	function index_into_stock_submit() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$new_stock = O('stock', $form['sid']);
		if (!$me->is_allowed_to('修改', $new_stock) || $new_stock->is_collection) return;

		$stock = O('stock', $form['stock']);

		if ($stock->id) {
			$new_stock->merge($stock);
			// 记录日志
			Log::add(strtr('[inventory] %user_name[%user_id]将存货%new_stock_name[%new_stock_id]移入了集合%stock_name[%stock_id]', [
						   '%user_name' => $me->name,
						   '%user_id' => $me->id,
						   '%new_stock_name' => $new_stock->product_name,
						   '%new_stock_id' => $new_stock->id,
						   '%stock_name' => $stock->product_name,
						   '%stock_id' => $stock->id
						   ]), 'journal');
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货归类成功！'));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货归类失败！'));
		}

		JS::close_dialog();
		JS::redirect(URI::url('!inventory/index'));
	}

	function index_remove_stock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$stock = O('stock', $form['sid']);
		if (!$me->is_allowed_to('修改', $stock) || $stock->is_collection) return;
		$collection = $stock->parent;
		if (!JS::confirm(I18N::T('inventory', '请谨慎操作！您确认将该存货从集合中移出吗？'))) return;
		$stock->remove();
		// 记录日志
		Log::add(strtr('[inventory] %user_name[%user_id]将存货%new_stock_name[%new_stock_id]移出了集合%stock_name[%stock_id]', [
					   '%user_name' => $me->name,
					   '%user_id' => $me->id,
					   '%new_stock_name' => $stock->product_name,
					   '%new_stock_id' => $stock->id,
					   '%stock_name' => $collection->product_name,
					   '%stock_id' => $collection->id
					   ]), 'journal');


		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货成功从集合中移出！'));
		JS::redirect(URI::url('!inventory/index'));
	}

	function index_change_stock_name_blur() {
		$me = L('ME');
		if (!$me->is_allowed_to('修改', 'stock')) return;
		$form = Form::filter(Input::form());
		$collection = O('stock', $form['collection']);
		$name = $form['product_name'] ? $form['product_name'] : $collection->product_name;
		$uniqid = $form['selector'];

		$collection->product_name = $name;
		$collection->save();
		// 记录日志
		Log::add(strtr('[inventory] %user_name[%user_id]修改了存货集合%stock_name[%stock_id]的名称', [
					   '%user_name' => $me->name,
					   '%user_id' => $me->id,
					   '%stock_name' => $collection->product_name,
					   '%stock_id' => $collection->id
					   ]), 'journal');

		Output::$AJAX[$uniqid] = $collection->product_name;
    }

    function index_stock_type_change() {
        $form = Input::form();

        if ($form['category'] == -1) return FALSE;

        Output::$AJAX['view'] = (string) V('inventory:stock/edit.info.extra', [
            'category'=> $form['category'],
            'form'=> $form['form'],
            'stock'=> O('stock', $form['sid']),
        ]);
    }

}
