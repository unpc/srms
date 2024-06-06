<?php
class Index_Controller extends Base_Controller {

	function index() {
		/* filter */
		$form = Lab::form(function(&$old_form, &$form) {

			if (isset($form['sort'])) {
				if ($old_form['sort'] == $form['sort']) {
					$form['sort_asc'] = !$old_form['sort_asc'];
				}
				else {
					$form['sort_asc'] = TRUE;
				}
			}

			if(!$form['grant_select'] && $old_form['grant_portion_select']) {
				unset($old_form['grant_portion_select']);
			}

			if (isset($form['date_filter'])) {
				if (!$form['dtstart_check']) {
					unset($old_form['dtstart_check']);
				}
				else {
					$form['dtstart'] = strtotime('midnight', $form['dtstart']);
				}
				if (!$form['dtend_check']) {
					unset($old_form['dtend_check']);
				}
				else {
					$form['dtend'] = strtotime('tomorrow', $form['dtend']) - 1;
				}
				unset($form['date_filter']);
			}

			if (isset($form['status_filter'])) {
				unset($old_form['status']);
			}

			if (isset($form['tag'])) {
				$tag = O('tag', $form['tag']);
				if ($tag->id) {
					$tags = (array)@json_decode($form['tags'], TRUE);
					$tags[$tag->id] = $tag->name;
					$form['tags'] = json_encode($tags);
				}
				unset($form['tag']);
			}

			if ($form['reset_field'] == 'grant') {
				unset($old_form['grant_check']);
				unset($old_form['grant_select']);
				unset($old_form['grant_portion_select']);
				unset($old_form['grant']);
			}

		});

//		$selector = 'order';
//		$pre_selector = array();
		$status_arr = [];

		$db = Database::factory();
		$sql_tail = '';
		$sql_where = [];
		$inner_join = [];
		$status_where = [];
		if($form['product_name']){
			$sql_where[] = '`t3`.`product_name` LIKE "%%'.$db->escape($form['product_name']).'%%"';
		}
		if($form['catalog_no']){
			$sql_where[] = '`t3`.`catalog_no` LIKE "%%'.$db->escape($form['catalog_no']).'%%"';
		}
		if($form['manufacturer']){
			$sql_where[] = '`t3`.`manufacturer` LIKE "%%'.$db->escape($form['manufacturer']).'%%"';
		}
		if($form['vendor']){
			$sql_where[] = '`t3`.`vendor` LIKE "%%'.$db->escape($form['vendor']).'%%"';
		}

		if (is_array($form['status']) && count($form['status'])) {
			//$deliver_status = [];
			foreach ($form['status'] as $key => $value) {
				$status_sql = '';
				$status = [];
				
				foreach (array_keys(Order_Model::$order_status_filter[$key]) as $s) {
					$status[] = $s;
				}
				$status_arr[] = I18N::T('orders',Order_Model::$order_status[$key]);
				$status_sql .= '(`t3`.`status` IN ('.implode(',', $status).'))';
				if ($key == Order_Model::LABEL_RECEIVED) {
					//$deliver_status[] = Order_Model::RECEIVED;
					$status_sql = '(`t3`.`deliver_status` = '.Order_Model::RECEIVED.' AND ' . $status_sql . ')';
				}
				elseif ($key == Order_Model::LABEL_ORDERED){
					//$deliver_status[] = Order_Model::NOT_RECEIVED;
					$status_sql = '(`t3`.`deliver_status` = '.Order_Model::NOT_RECEIVED.' AND ' . $status_sql . ')';
				}
				
				$status_where[] = $status_sql;
			}
/*
			$status_str = implode(',', array_unique($status));
			if (count($deliver_status)) {
				$deliver_status_str = implode(',', array_unique($deliver_status));
				$selector .= "[deliver_status=$deliver_status_str]";
			}
			$selector .= "[status=$status_str]";
*/
		}
		elseif (is_string($form['status'])) {
			$label = $form['status'];
			$statuses = implode(',', array_keys(Order_Model::$order_status_filter[$label]));
//			$selector .= "[status=$statuses]";
			$status_sql .= '(`t3`.`status` IN ('.$statuses.'))';
			if ($label == Order_Model::LABEL_RECEIVED) {
//				$deliver_status = Order_Model::RECEIVED;
//				$selector .= "[deliver_status=$deliver_status]";
				$status_sql = '(`t3`.`deliver_status` = '.Order_Model::RECEIVED.' AND ' . $status_sql . ')';
			}
			elseif ($label == Order_Model::LABEL_ORDERED) {
				//$deliver_status = Order_Model::NOT_RECEIVED;
				//$selector .= "[deliver_status=$deliver_status]";
				$status_sql = '(`t3`.`deliver_status` = '.Order_Model::NOT_RECEIVED.' AND ' . $status_sql . ')';
			}

			$sql_where[] = $status_sql;
			$status_arr[] = I18N::T('orders',Order_Model::$order_status[$label]);
		}

		if($form['dtstart_check']){
			$dtstart = Q::quote($form['dtstart']);
			//$selector .= "[request_date>=$dtstart]";
			$sql_where[] = '`t3`.`request_date`>='.$dtstart;
		}
        else {
            unset($form['dtstart']);
        }

		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
			//$selector .= "[request_date<=$dtend]";
			$sql_where[] = '`t3`.`request_date`<='.$dtend;
		}
        else {
            unset($form['dtend']);
        }

		if ($form['requester']){
			$requester = O('user', $form['requester']);
			if ($requester->id) {
				//$selector .= "[requester=$requester]";
				$sql_where[] = '`t3`.`requester_id`='.$form['requester'];
			}
		}

		if ($form['grant_check'] == 'unlinked') {
			//$selector .= "[expense_id=0]";
			$sql_where[] = '`t3`.`expense_id`=0';
		}
		elseif ($form['grant_check'] == 'linked') {
			if ($form['grant_portion_select']) {
				$portion = O('grant_portion', $form['grant_portion_select']);
				if ($portion->id) {
					$pids = $portion->childrens(TRUE);
					//$pre_selector[] = 'grant_expense[portion_id='.implode(',', $pids).']<expense';
					$inner_join[] = '`grant_expense`';
					$sql_where[] = '`grant_expense`.`id` = `t3`.`expense_id`';
					$sql_where[] = '`portion_id` IN ('.implode(',', $pids).')';
				}
			}
			elseif ($form['grant_select']) {
//				$pre_selector[] = 'grant_expense[grant_id='.intval($form['grant_select']).']<expense';
				$inner_join[] = '`grant_expense`';
				$sql_where[] = '`grant_expense`.`id` = `t3`.`expense_id`';
				$sql_where[] = '`grant_id`="'.$form['grant_select'].'"';
			}
		}

		if ($form['tags']) {	/* TASK#331(xiaopei.li@2011.03.14) */
			$root = Tag_Model::root('inventory'); /* TODO fix inventory tag */
			$tag_names = @json_decode($form['tags'], TRUE);
			foreach ($tag_names as $id => $name) {
				if ($id > 0) {
					$tag = O('tag', ['id'=>$id, 'root'=>$root]);
				}
				else {
					$tag = O('tag', ['name'=>$name,'root'=>$root]);
				}

				if ($tag->id) {
//					$pre_selector[] = $tag;
					$inner_join[] = '`_r_tag_order`';
					$inner_join[] = '`tag`';
					$sql_where[] = '`_r_tag_order`.`id2` = `t3`.`id`';
					$sql_where[] = '`_r_tag_order`.`id1` = `tag`.`id`';
				}
			}
		}
		$sort_sql = '';
		/* sort */
		$sort_by = $form['sort'] ?: 'product_name';
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'ASC':'DESC';
		switch($sort_by){
		/*
		case 'product_name':
			$selector .= ":sort(product_name {$sort_flag})";
			break;
		case 'manufacturer':
			$selector .= ":sort(manufacturer {$sort_flag})";
			break;
		case 'vendor':
			$selector .= ":sort(vendor {$sort_flag})";
			break;
		*/
		case 'price':
			//$selector .= ":sort(price {$sort_flag})";
			$sort_sql = ' GROUP BY `t3`.`price` ORDER BY `t3`.`price` '.$sort_flag;
			break;
		/*
		*********** loook!! **************
		case 'status':
			$selector .= ":sort(status {$sort_flag})";
			break;
		*/
		// case 'requester':
		// case 'purchase_date':
		default:
			//$selector .= ":sort(id {$sort_flag})";
			$sort_sql = ' GROUP BY `t3`.`id` ORDER BY `t3`.`id` '.$sort_flag;
			break;
		}
/*
		if (count($pre_selector) > 0){
			$selector = '('.implode(', ', $pre_selector).') ' . $selector;
		}
*/
		if(count($inner_join)) {
			$sql_tail .= ' INNER JOIN ('.implode(',', $inner_join).') ON ';
		}
		
		if(count($sql_where)) {
			if(!count($inner_join)){
				$sql_tail .= ' WHERE ';
			}
			$sql_tail .= implode(' AND ', $sql_where);
		}

		if(count($status_where)) {
			if(!count($sql_where)) {
				$sql_tail .=' WHERE ';
			}
			else {
				$sql_tail .=' AND ';
			}
			$sql_tail .= ' ('.implode(' OR ', $status_where).')';
		}

		$count_sql_tail = $sql_tail;
		$sql_tail.= $sort_sql;
		//$orders = Q($selector);

		$sql = "SELECT `t3`.`product_name`, `t3`.`manufacturer`, `t3`.`catalog_no`, `t3`.`package`, `t3`.`model`, `t3`.`spec`, `t3`.`quantity`, `t3`.`vendor`, `t3`.`unit_price`, `t3`.`price`, `t3`.`fare`, `t3`.`order_no`, `t3`.`requester_id`, `t3`.`request_date`, `t3`.`request_note`, `t3`.`approver_id`, `t3`.`approve_date`, `t3`.`approve_note`, `t3`.`purchaser_id`, `t3`.`purchase_date`, `t3`.`purchase_note`, `t3`.`receiver_id`, `t3`.`receive_date`, `t3`.`receive_note`, `t3`.`canceler_id`, `t3`.`cancel_date`, `t3`.`cancel_note`, `t3`.`stock_id`, `t3`.`expense_id`, `t3`.`source`, `t3`.`status`, `t3`.`deliver_status`, `t3`.`ctime`, `t3`.`mtime`, `t3`.`id`, `t3`.`_extra`FROM `order` `t3`".$sql_tail;
		$count_sql = "SELECT COUNT(`t3`.id) AS total_count FROM `order` `t3`".$count_sql_tail;
		$total_count = $db->query($count_sql)->row()->total_count;
        $form_token = 'orders_form_token'. uniqid();
        $_SESSION[$form_token] = $form;

		/* pagination */
		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);

		$_SESSION[$form_token]['sql'] = $sql;

		if($start > 0) {
			$last = floor($total_count / $per_page) * $per_page;
			if ($last == $total_count) {
				$last = max(0, $last - $per_page);
			}
			if ($start > $last) {
				$start = $last;
			}
			$sql .= ' LIMIT '.$start.','.$per_page;
			//$orders = $orders->limit($start, $per_page);
		}
		else {
			//$orders = $orders->limit($per_page);
			$sql .= ' LIMIT 0,'.$per_page;
		}

        $query = $db->query($sql);
        if ($query) {
            $orders = $query->rows();
        }
        else {
            $orders = Q('order:empty');
        }

		$pagination = Widget::factory('pagination');
		$pagination->set([
							 'start' => $start,
							 'per_page' => $per_page,
							 'total' => $total_count,
							 ]);

		$this->layout->body->primary_tabs
			->select('orders')
			->set('content', V('orders', [
								   'orders'=>$orders,
								   'pagination'=>$pagination,
								   'form'=>$form,
								   'status_arr'=>$status_arr,
								   'sort_asc'=>$sort_asc,
								   'sort_by'=>$sort_by,
                                   'form_token'=> $form_token
								   ]));
	}

	// 打印报表 和 导出 CSV 的选项
	function export(){
		/*
		NO.TASK#274(guoping.zhang@2010.11.25)
		应用权限判断新规则
		*/
		if(!L('ME')->is_allowed_to('导出', 'order')){
			URI::redirect('error/401');
		}

		$form = Input::form();

		$form_token = $form['form_token'];
		$old_form = (array) $_SESSION[$form_token];
		$new_form = (array) $form;
		if (isset($new_form['columns'])) {
		    unset($old_form['columns']);
		}

		$_form = $_SESSION[$form_token] = $new_form + $old_form;
		$sql = $_SESSION[$form['form_token']]['sql'];
		$db = Database::factory();
		$orders = $db->query($sql)->rows();
//      $orders = Q($_SESSION[$form['form_token']]['selector']);

        $columns = Config::get('orders.export_columns');
        foreach ($columns as $key => $value) {
            if (!array_key_exists($key, $_form['columns'])) {
                unset($columns[$key]);
            }
        }

        switch($form['type']) {
            case 'csv':
                call_user_func_array([$this, '_export_csv'], [$columns, $orders, $_form['form']]);
                break;
            case 'print':
                call_user_func_array([$this, '_export_print'], [$columns, $orders, $_form]);
                break;
            default:
                return;
        }
	}

	function _export_print($columns, $orders, $form) {


		/* 记录日志 */
		$me = L('ME');

        Log::add(strtr('[orders] %user_name[%user_id]打印了订单列表', [
            '%user_name'=> $me->name,
            '%user_id'=> $me->id
        ]), 'journal');

		$this->layout = V('orders:print', ['columns'=>$columns, 'form'=> $form, 'orders'=> $orders]);
	}

	function _export_csv($columns ,$orders, $form) {
		$form = $form['form'];

		$csv = new CSV('php://output', 'w');

		/* 记录日志 */
		$me = L('ME');
        Log::add(strtr('[orders] %user_name[%user_id]以CSV导出了订单列表', [
            '%user_name'=> $me->name,
            '%user_id'=> $me->id
        ]), 'journal');

		try {
			if ($form['product_name']) {
				$csv->write([
						I18N::T('orders', '产品名称'),
						$form['product_name'],
						]);
			}
			if ($form['manufacturer']) {
				$csv->write([
						I18N::T('orders', '供应商'),
						$form['manufacturer'],
						]);
			}
			if ($form['catalog_no']) {
				$csv->write([
						I18N::T('orders', '目录号'),
						$form['catalog_no'],
						]);
			}
			if ($form['vendor']) {
				$csv->write([
					I18N::T('orders', '供应商'),
					$form['vendor']
					]);
			}
			if ($form['requester']) {
				$user = O('user', $form['requester']);
				if ($user->id) {
					$csv->write([
						I18N::T('orders', '申购人'),
						$user->name
						]);
				}
			}
			if ($form['status']) {
				foreach ($form['status'] as $key => $value) {
					$str[] = I18N::T('orders', Order_Model::$order_status[$key]);
				}
				$csv->write([
					I18N::T('orders', '订单状态'),
					implode(', ', $str)
					]);
			}
			$tags_arr = json_decode($form['tags'], TRUE);
			if ($tags_arr) {
				$csv->write([
					I18N::T('orders', '标签'),
					implode(', ', $tags_arr)
					]);
			}
			if ($form['grant_check']) {
				if ($form['grant_check'] == 'unlinked') {
					$csv->write([
						I18N::T('orders', ''),
						I18N::T('orders', '未关联'),
						]);
				}
				elseif ($form['grant_check'] == 'linked' && $form['grant']) {
					$grant = O('grant', (int)$form['grant']);
					$grant_str = $grant->source;
					if ($form['grant_portion']) {
						$grant_portion = O('grant_portion', $form['grant_portion']);
						if ($grant_portion->id) {
							$grant_str .= '»'.$grant_portion->name;
						}
					}
					$csv->write([
						I18N::T('order', '关联经费'),
						$grant_str
						]);
				}
			}
			if ($form['dtstart_check'] && $form['dtstart']) {
				$dtstart = Date::format($form['dtstart'], 'Y/m/d');
			}
			else {
				$dtstart = I18N::T('orders', '最初');
			}
			if ($form['dtend_check'] && $form['dtend']) {
				$dtend = Date::format($form['dtend'], 'Y/m/d');
			}
			else {
				$dtend = I18N::T('orders', '最末');
			}
			$csv->write([
							I18N::T('orders', '时间范围'),
							$dtstart . ' - ' . $dtend,
							]);

			$csv->write(['']);

			$csv->write(I18N::T('orders',$columns));

			if (!count($orders)) {
				throw new Exception;
			}

			foreach ($orders as $obj) {
				$order = O('order', $obj->id);
				$data = [];
				foreach ($columns as $key => $value) {
					switch ($key) {
						case 'product_name':
							$data[] = H($order->product_name);
							break;
						case 'manufacturer':
							$data[] = H($order->manufacturer);
							break;
						case 'catalog_no':
							$data[] = $order->catalog_no;
							break;
						case 'vendor':
							$data[] = H($order->vendor);
							break;
						case 'unit_price':
							$data[] = $order->unit_price;
							break;
						case 'quantity':
							$data[] = $order->quantity;
							break;
						case 'spec':
							$data[] = H($order->spec);
							break;
						case 'price':
							$data[] = $order->price;
							break;
						case 'fare':
							$data[] = $order->fare;
							break;
                        case 'grant' :
                            $expense = $order->expense;
                            if ($expense->id) {
                                $grant = $expense->grant;
                                $portion = $expense->portion;
                                $protions = [];
                                while($portion->id) {
                                    $protions[] = $portion->name;
                                    $portion = $portion->parent;
                                }

                                $protions = array_reverse($protions);

                                array_unshift($protions, $grant->project);
                                $_data = implode(' » ', $protions);

                                if ($expense->invoice_no) {
                                    $_data = strtr("%data\n | %title: %invoice_no", [
                                        '%data'=> $_data,
                                        '%title'=> I18N::T('orders', '发票号'),
                                        '%invoice_no'=> $expense->invoice_no,
                                    ]);
                                }
                            }
                            else {
                                $_data = '--';
                            }
                            $data[] = $_data;
                            $_data = [];
                            break;
						case 'order_status':
							$label_status = $order->get_label_status();
							$data[] = I18N::T('orders', Order_Model::$order_status[$label_status]);;
							break;
						case 'requester':
							$data[] = H($order->requester->name);
							break;
						case 'request_date':
							$data[] = $order->request_date ? Date::format($order->request_date, 'Y/m/d') : '--';
							break;
						case 'purchaser':
							$data[] = H($order->purchaser->name);
							break;
						case 'purchase_date':
							$data[] = $order->purchase_date ? Date::format($order->purchase_date, 'Y/m/d') : '--';
							break;
					}
				}
				$csv->write($data);
			}
		}
		catch (Exception $e) {
			$csv->write([I18N::T('orders', '无数据')]);
		}

		$csv->close();

		exit();
	}

	//生成上传 CSV 的路径
	static function tmp_file_name($key) {
		return Config::get('system.tmp_dir').L('ME')->id.'.inventory_orders.'.$key;
	}

	//上传csv文件
	function import(){
		/*
		NO.TASK#274(guoping.zhang@2010.11.25)
		应用权限判断新规则
		*/
		if(!L('ME')->is_allowed_to('导入', 'order')){
			URI::redirect('error/401');
		}

		$file_name = self::tmp_file_name('import');

		if (Input::form('submit')) {
			//删除上次上传的文件
			if (file_exists($file_name)) {
				File::delete($file_name);
			}

			$file = Input::file('file');
			//进行文件上传
			if ($file['tmp_name']) {
					if(File::extension($file['name']) != 'csv') {
						Lab::message(LAB::MESSAGE_NORMAL , I18N::T('orders','文件类型错误, 请上传csv文件'));
					}
					else{

						File::check_path($file_name);
						if(move_uploaded_file($file['tmp_name'], $file_name)) {
							Lab::message(LAB::MESSAGE_NORMAL , I18N::T('orders','文件上传成功'));
							URI::redirect('!orders/index/import_finish');
						}else{
							Lab::message(Lab::MESSAGE_ERROR, I18N::T('orders', '文件上传失败'));
						}
					}

			}
			else{
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('orders', '请选择您要上传的文件。'));
			}
		}

		$this->layout->body->primary_tabs
			->add_tab('import', [
				'url'=>URI::url('!orders/index/import'),
				'title'=>I18N::T('orders', '导入订单'),
			])
			->select('import')
		 	->set('content', V('order/import'));
	}

	function import_finish() {

		$file_name = self::tmp_file_name('import');
		if (!file_exists($file_name)) {
			URI::redirect('!orders/index/import');
		}

		if(Input::form('submit')){
			try {

				//进行分析判断
				$form = Input::form();

				$csv_columns = $form['csv_columns'];

				//清除空列
				foreach($csv_columns as $k=>$v){
					if(!$v) unset($csv_columns[$k]);
				}

				//默认用户 若 上传的 csv 无用户或用户不是实验室的成员，则用此默认用户替代
				$default_purchaser = O('user', $form['default_purchaser']);

				$csv = new CSV($file_name, 'r');

				//if ($form['skip_first_row']) $csv->read();
				if ($form['skip_rows'] && $form['skip_rows_count']) {
					$skip_rows_count = $form['skip_rows_count']-1;
					while ($skip_rows_count) {
						$csv->read();
						$skip_rows_count--;
					}
				}

				//定义计数器，表示导入多少行
				$affected_rows = 0;

				$f2i = array_flip($csv_columns);

				/* begin validation */
				$necessary_fields = [
					'product_name' => I18N::T('orders', '请导入产品名称！'),
					'quantity' => I18N::T('orders', '请导入数量！'),
					'price' => I18N::T('orders', '请导入总价！'),
					'vendor' => I18N::T('orders', '请导入供应商！'),
					];
				$error_count = 0;

				foreach ($necessary_fields as $n_field => $n_alarm) {
					if (!isset($f2i[$n_field])) {
						Lab::message(Lab::MESSAGE_ERROR, $n_alarm);
						$error_count += 1;
					}
				}
				if (!(isset($f2i[$n_field]) || $default_purchaser->id)) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('orders', '请导入订购人或选择默认订购人！'));
					$error_count += 1;
				}

				if ($error_count) {
					throw new Exception;
				}
				/* end validation(xiaopei.li@2011.04.29) */

				while ($row = $csv->read()) {

					//从文件中读出来一条一条存入数据库
					$order = O('order');

					/* 必填项 */
					$order->product_name = $row[$f2i['product_name']];
					$order->quantity = (int)$row[$f2i['quantity']];
					$order->price = round((float) $row[$f2i['price']], 2);
					$order->vendor = $row[$f2i['vendor']];

					$order->purchaser = O('user', ['name'=>$row[$f2i['purchaser']]]);
					if (!$order->purchaser->id) $order->purchaser = $default_purchaser;

					$order->purchase_date = strtotime($row[$f2i['purchase_date']]);
					if (!$order->purchase_date) $order->purchase_date = time();

					//如果没有申购人，则申购人为订购人
					$order->requester = O('user', ['name'=>$row[$f2i['requester']]]);
					if (!$order->requester->id) $order->requester = $order->purchaser;

					$order->request_date = $row[$f2i['request_date']] ? strtotime($row[$f2i['request_date']]) : $order->purchase_date;

					/* 选填项 */
					if (isset($row[$f2i['unit_price']])) {
						$order->unit_price = round((float) $row[$f2i['unit_price']], 2);
					}
					if (isset($row[$f2i['manufacturer']])) {
						$order->manufacturer = $row[$f2i['manufacturer']];
					}
					if (isset($row[$f2i['catalog_no']])) {
						$order->catalog_no = $row[$f2i['catalog_no']];
					}
					if (isset($row[$f2i['spec']])) {
						$order->spec = $row[$f2i['spec']];
					}
					if (isset($row[$f2i['order_no']])) {
						$order->order_no = $row[$f2i['order_no']];
					}
					if (isset($row[$f2i['fare']])) {
						$order->fare = $row[$f2i['fare']];
					}

					$order->status = Order_Model::READY_TO_TRANSFER;

					//导入成功一条，就将计数器累加，依次类推
					if ($order->save()) {
						$affected_rows++;
						/* 记录日志 */
						$me = L('ME');

                        Log::add(strtr('[orders] %user_name[%user_id]导入了订单%order_name[%order_id]', [
                            '%user_name'=> $me->name,
                            '%user_id'=> $me->id,
                            '%order_name'=> $order->product_name,
                            '%order_id'=> $order->id
                        ]), 'journal');
					}
				}

				$csv->close();

				File::delete($file_name);

				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '已导入 %imported_num 条订单记录!',['%imported_num'=>$affected_rows]));

				URI::redirect('!orders/index');

			}
			catch (Exception $e) {

			}
		}


		$csv = new CSV($file_name,'r');

		$csv_rows = [];
		//显示前 5 条记录，让用户选择相对应的列
		$i=10;
		$csv_max_cols = 0;
		while ($i-- && $row = $csv->read()) {
			$csv_rows[] = $row;

			//代表导入的 CSV 中，最长的列
			if (count($row) > $csv_max_cols) $csv_max_cols = count($row);

		}
		$csv->close();

		$this->layout->body->primary_tabs
			->add_tab('match', [
						  'url'=>URI::url('!orders/index/import_finish'),
						  'title'=>I18N::T('orders', '导入订单'),
						  ])
			->select('match')
		 	->content = V('order/import_finish',['csv_rows'=>$csv_rows, 'csv_max_cols'=>$csv_max_cols, 'form' => $form]);
	}

}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_rows_count_change() {

		$form = Input::form();

		$skip_rows_count = (int)$form['skip_rows_count'];
		$uniqid = $form['uniqid'];

		$file_name = Orders_Controller::tmp_file_name('import');
		//var_dump($file_name);
		if (!file_exists($file_name)) {
			//URI::redirect('!orders/index/import');
		}

		$csv = new CSV($file_name,'r');

		if ($skip_rows_count) {
			$skip_rows_count -= 1;
			while ($skip_rows_count) {
				$csv->read();
				$skip_rows_count--;
			}
		}

		$csv_rows = [];
		//显示前 $i 条记录，让用户选择相对应的列
		$i=10;
		$csv_max_cols = 0;
		while ($i-- && $row = $csv->read()) {
			$csv_rows[] = $row;
			//代表导入的 CSV 中，最长的列
			if (count($row) > $csv_max_cols) $csv_max_cols = count($row)-1;
		}

		$csv->close();

		Output::$AJAX['#csv_content_'.$uniqid] = [
			'data'=>(string)V('order/csv_content_uniqid',['uniqid'=>$uniqid,'csv_rows'=>$csv_rows,'csv_max_cols'=>$csv_max_cols]),
			'mode'=>'replace',
		];

	}

    function index_orders_export_click() {
        $form = Input::form();
        JS::dialog(V('orders:export', [
            'type'=> $form['type'],
            'form_token'=> $form['form_token']
        ]), [
            'title'=> I18N::T('orders', '请选择导出列'
        )]);
    }
}
