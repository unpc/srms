<?php

class Use_Controller extends Base_Controller {

    //针对Use进行export函数
    function export() {
        $form = Input::form();
        $type = $form['type'];
        $form_token = $form['form_token'];

        $selector = $_SESSION[$form_token]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '操作超时, 请重试!'));
            URI::redirect('!inventory/use');
        }

        $valid_columns = Config::get('inventory.export_columns.stock');
        $visible_columns = Input::form('columns') ? : $_SESSION[$form_token]['columns'];

        $columns = [];

        foreach($valid_columns as $p=> $p_name) {
            if (isset($visible_columns[$p])) {
                if ($p == 'operate') {
                    $columns['use_quantity'] = '领用';
                    $columns['return_quantity'] = '归还';
                    continue;
                }
                $columns[$p] = $valid_columns[$p];
            }
        }
        $_SESSION[$form_token]['columns'] = $columns;

        if (in_array($type, ['print', 'csv'])) {
            call_user_func_array([$this, '_'. $type], [
                $selector,
                $columns,
            ]);
        }
        else {
            URI::redirect('error/401');
        }
    }

    private function _print($selector, $columns) {

        $this->layout = V('inventory:use/print', [
            'stock_uses'=> Q($selector),
            'columns'=> $columns,
        ]);

    }

    private function _csv($selector, $columns) {
        $stock_uses = Q($selector);

        $csv = new CSV('php://output', 'w');

        $csv->write(I18N::T('inventory', $columns));

        if ($stock_uses->total_count()) {
            $start = 0;
            $per_page = 100;
            while (1) {
                $pp_trans = $stock_uses->limit($start, $per_page);
                if ($pp_trans->length() == 0) break;
                foreach ($pp_trans as $t) {
                    $data = [];
                    if (array_key_exists('ctime', $columns)) {
                        $data[] = Date::format($t->ctime, 'Y/m/d H:i');
                    }
                    if (array_key_exists('stock', $columns)) {
                        $data[] = $t->stock->product_name;
                    }

                    if (array_key_exists('user', $columns)) {
                        $data[] = $t->user->name;
                    }

                    if (array_key_exists('use_quantity', $columns)) {
                        $data[] = $t->quantity > 0 ?  $t->quantity : null;
                    }

                    if (array_key_exists('return_quantity', $columns)) {
                        $data[] = $t->quantity < 0 ?  (-1)*$t->quantity : null;
                    }

                    if (array_key_exists('unit_price', $columns)) {
                        $data[] = $t->stock->unit_price;
                    }

                    if (array_key_exists('total_price', $columns)) {
                        $data[] = $t->stock->unit_price * ($t->quantity > 0 ?  $t->quantity : (-1) * $t->quantity);
                    }

                    if (array_key_exists('status', $columns) && $t->status) {
                        $data[] = Stock_Use_Model::$status[$t->status];
                    }

                    if (array_key_exists('note', $columns)) {
                        $data[] = $t->note;
                    }

                    $csv->write($data);
                }

                $start += $per_page;
            }
        }

        $csv->close();

    }

	function index() {

		if(!L('ME')->is_allowed_to('领用/归还', 'stock') && !L('ME')->is_allowed_to('代人领用/归还', 'stock')) {
			URI::redirect('error/401');
		}
		
		$this->layout->body->primary_tabs->add_tab('use', [
						  'url' => URI::url('!inventory/use'),
						  'title' => I18N::T('inventory', '领用 / 归还|:tab'),
						  ]);

		$form = Lab::form();

		// sort
        //未做sort
        //进行注释
        /*
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
        */

        $selector = 'stock_use:sort(ctime D)';
        $stock_uses = Q($selector);

		// pagination
		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);

		if($start > 0) {
			$last = floor($stock_uses->total_count() / $per_page) * $per_page;
			if ($last == $stock_uses->total_count()) {
				$last = max(0, $last - $per_page);
			}
			if ($start > $last) {
				$start = $last;
			}
			$stock_uses = $stock_uses->limit($start, $per_page);
		}
		else {
			$stock_uses = $stock_uses->limit($per_page);
		}

        $form_token = Session::temp_token('inventory_use_', 300);

        //设定Session
        //设定为Array, 便于其他数据累加
        $_SESSION[$form_token] = [
            'selector'=> $selector,
        ];

		$pagination = Widget::factory('pagination');
		$pagination->set([
             'start' => $start,
             'per_page' => $per_page,
             'total' => $stock_uses->total_count(),
         ]);

		$this->add_js('cardread');
		$this->add_css('cardread inventory:use');

		$this->layout->body->primary_tabs
            ->select('use')
            ->set('content', V('use/index', [
               'stock_uses' => $stock_uses,
               'pagination'=>$pagination,
               'form'=>$form,
               'form_token'=> $form_token,
               'sort_asc'=>$sort_asc,
               'sort_by'=>$sort_by,
           ]));
	}

	function add() {

		$form = Input::form();
		if (!$form['submit']) {
			URI::redirect('!inventory/use');
		}

		

		try {
			// TODO validation
			$stock = O('stock', $form['stock_id']);
			$me = L('ME');
			if (!$stock->id) {
				throw new Exception(I18N::T('inventory', '无相应存货'));
			}
			
			//判断是否有‘代人领用/归还’权限，有该权限则需填写用户(界面显示用户textbox)，否则用户为当前用户
			if ($me->is_allowed_to('代人领用/归还', $stock)) {
				if (empty($form['user'])) {
					throw new Exception(I18N::T('inventory', '用户不能为空!'));
				}
				else {
					$user = O('user', $form['user']);
					if (!$user->id) {
						throw new Exception(I18N::T('inventory', '用户填写有误!'));
					}
				}
			}
			else {
				$user = $me;
			}
			
			
			$type = $form['operate_type'];
			$quantity = $form['quantity'];

			if ($type == 'use') {
				if ($quantity <= 0) {
	            	throw new Exception(I18N::T('inventory', '领用数量需大于0'));
	        	}
	        	if ($quantity > $stock->quantity) {
					throw new Exception(I18N::T('inventory', '领用数量不能大于存量!'));
				}
	        }
			elseif ($type == 'return') {
				if ($quantity <= 0) {
	            	throw new Exception(I18N::T('inventory', '归还数量需大于0'));
	        	}
	        	if ($quantity + $stock->quantity > $stock->summation) {
		            throw new Exception(I18N::T('inventory', '归还数量超过最大归还量!'));
		        }

		        $quantity *= -1;
				
			}
			else {
				throw new Exception(I18N::T('inventory', '请制定操作类型'));
			}

			$stock_use = O('stock_use');
			$stock_use->stock = $stock;
			$stock_use->user = $user;
			$stock_use->quantity = $quantity;
			$stock_use->note = trim($form['note']);

			if (!$stock_use->save()) {
				throw new Exception(I18N::T('inventory', '领用添加失败'));
			}

			if ($type == 'use') {
				Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货领用成功!'));
        	}
        	elseif($type == 'return') {
				Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货归还成功!'));
        	}

            Event::trigger('update_stock_use_status', $type, $stock_use->id);

            $stock->quantity = $stock->quantity - $stock_use->quantity;
            $stock->save();
		}
		catch (Exception $e) {
			Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
		}

		URI::redirect('!inventory/use/');
	}

}

Class Use_AJAX_Controller extends AJAX_Controller {

	function index_barcode_submit() {
		$form = Input::form();
		$bc = trim($form['barcode']);
		if (!$bc) {
			return;
		}

		$bc = Q::quote($bc);
		$status = (int)Stock_Model::EXHAUSTED;
		$stocks = Q("stock[barcode=$bc][status!=$status]");
		if ($stocks->total_count() == 0) {
			JS::alert(I18N::T('inventory', '无法找到对应的存货!'));
			return;
		}

		$content = '';
		foreach ($stocks as $stock) {
			$content .= (string) V('inventory:use/form', ['stock' => $stock]);
		}

		$uniqid = trim($form['uniqid']);
		Output::$AJAX['#' . $uniqid] = $content;
	}

	function index_stock_use_return_add_click() {
		$form = Input::form();
		$stock = O('stock', $form['stock_id']);
		if (!$stock->id) {
			return;
		}

		JS::dialog(V('inventory:use/form_lite', ['stock' => $stock]), ['title'=> I18N::T('inventory', '添加领用/归还记录')]);

	}

	function index_stock_use_return_add_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		$stock = O('stock', $form['stock_id']);
		if (!$stock->id) return FALSE;

		if ($me->is_allowed_to('代人领用/归还', $stock)) {
            if (empty($form['user'])) {
                $form->set_error('user', I18N::T('inventory', '用户不能为空!'));
            }
            else {
                $user = O('user', $form['user']);
                if (!$user->id) {
                    $form->set_error('user', I18N::T('inventory', '用户填写有误!'));
                }
            }
        }
        else {
            $user = $me;
        }

		$type = $form['operate_type'];
		$quantity = $form['quantity'];
		if ($type == 'use') {
			if ($quantity <= 0) {
            	$form->set_error('quantity', I18N::T('inventory', '领用数量需大于0'));
        	}
        	if ($quantity > $stock->quantity || $quantity > $stock->summation) {
				$form->set_error('quantity', I18N::T('inventory', '领用数量不能大于存量!'));
			}

		}
		elseif ($type == 'return') {
			if ($quantity <= 0) {
            	$form->set_error('quantity', I18N::T('inventory', '归还数量需大于0'));
        	}

            //存量和余量大于总量
        	if ($quantity + $stock->quantity > $stock->summation) {
	            $form->set_error('quantity', I18N::T('inventory', '归还数量超过最大归还量!'));
	        }

	        $quantity *= -1;
		}
		else {
			return FALSE;
		}

        if ($form->no_error) {
            $stock_use = O('stock_use');
            $stock_use->user = $user;
            $stock_use->stock = $stock;
            $stock_use->note = trim($form['note']);
            $stock_use->quantity = $quantity;
            if ($stock_use->save()) {

            	if ($type == 'use') {
					Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货领用成功!'));
            	}
            	elseif($type == 'return') {
					Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货归还成功!'));
            	}

                Event::trigger('update_stock_use_status', $type, $stock_use->id);

                $stock->quantity -= $stock_use->quantity;
                $stock->save();
                Event::trigger('update_stock_status', $stock);
                JS::redirect($stock->url('return',NULL, NULL, 'view'));
            }
            else {
                Lab::MESSAGE(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货操作失败!'));
            }
        }
        else {
            JS::dialog(V('inventory:use/form_lite', ['stock'=>$stock, 'form'=>$form]));
        }
	}

    public function index_use_edit_click() {
        $form = Input::form();
        $use = O('stock_use', $form['id']);
        $me = L('ME');
        if (!$use->id || !$me->is_allowed_to('修改', $use)) return FALSE;

        $dialog_title = $use->quantity > 0 ? '修改领用记录' : '修改归还记录';

        JS::dialog(V('inventory:use/edit', ['use'=> $use]), ['title'=> I18N::T('inventory', $dialog_title)]);
    }

    public function index_use_edit_submit() {

        $form = Form::filter(Input::form());
        $use = O('stock_use', $form['id']);
        $me = L('ME');
        if (!$use->id || !$me->is_allowed_to('修改', $use)) return FALSE;
        $stock = $use->stock;

		$quantity = $form['quantity'];

        $old_quantity = $use->quantity;
		if ($old_quantity > 0) {
			if ($quantity <= 0) {
            	$form->set_error('quantity', I18N::T('inventory', '领用数量需大于0'));
        	}

            //修改前后变化值，不应超过存量
            //领用值不应超过总量值
            if ($quantity - $use->quantity > $stock->quantity || $quantity > $stock->summation) {
                $form->set_error('quantity', I18N::T('inventory', '领用数量不能大于存量!'));
            }

		}
		else {
			if ($quantity <= 0) {
            	$form->set_error('quantity', I18N::T('inventory', '归还数量需大于0'));
        	}

            //前后变化值，不应超过 总量 - 存量
            //old_quantity为负值
            if ($quantity + $old_quantity > $stock->summation - $stock->quantity || $quantity > $stock->summation) {
                $form->set_error('quantity', I18N::T('inventory', '归还数量超过最大归还量!'));
            }

	        $quantity *= -1;
		}

        if ($form->no_error) {
            $old_quantity = $use->quantity;
            $use->note = trim($form['note']);
            $use->quantity = $quantity;
            if ($use->save()) {

                if ($old_quantity > 0) {
                    Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货领用修改成功!'));
                }
                else {
                    Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('inventory', '存货归还修改成功!'));
                }

                $stock->quantity += ($old_quantity - $quantity);
                $stock->save();
                Event::trigger('update_stock_status', $stock);
                JS::refresh();
            }
            else {
                Lab::MESSAGE(Lab::MESSAGE_ERROR, I18N::T('inventory', '存货操作失败!'));
            }
        }
        else {
            $dialog_title = $old_quantity > 0 ? '修改领用记录' : '修改归还记录';
            JS::dialog(V('inventory:use/edit', ['use'=> $use, 'form'=> $form]), ['title'=> I18N::T('inventory', $dialog_title)]);
        }
    }

    public function index_use_delete_click() {
        $form = Input::form();
        $use = O('stock_use', $form['id']);
        $me = L('ME');
        if (!$use->id || !$me->is_allowed_to('删除', $use)) return FALSE;

        if (JS::confirm(I18N::T('inventory', '您确定删除吗? 请谨慎操作!'))) {
            $quantity = $use->quantity;
			//BUG 5012, the principle is the ammount of storage must smaller than the ammount of summation.
            $stock = $use->stock;
            $stock_quantity = ($stock->quantity += $quantity);
            if ($stock_quantity < 0) {
	            JS::alert(I18N::T('inventory', '存货已被领用, 不能删除该归还记录!'));
            }
            elseif ($stock_quantity > $stock->summation) {
	            JS::alert(I18N::T('inventory', '存货已归还, 不能删除该领用记录!'));
            }
            else {
            	if ($use->delete()) {
	                $stock->quantity = $stock_quantity;
	                $stock->save();
	                Event::trigger('update_stock_status', $stock);
	            }
	
	            JS::refresh();
	
            }
        }
    }

    public function index_export_click() {

        $form = Input::form();

        $form_token = $form['form_token'];

        $selector = $_SESSION[$form_token]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '操作超时, 请重试!'));
            return FALSE;
        }

        $title = ($form['type'] == 'csv') ? I18N::T('inventory', '请选择要导出Excel的列') : I18N::T('inventory', '请选择要打印的列');

        JS::dialog(V('inventory:use/export_form', [
            'form_token'=> $form_token,
            'type'=> $form['type'],
        ]), [
            'title'=> $title, 
        ]);

    }

    public function index_export_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

		if ('csv' == $type) {
			$pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('inventory', '导出等待')
            ]);
		}
    }

	function _export_csv($selector, $form, $file_name) {
		$me = L('ME');
        $valid_columns = Config::get('inventory.export_columns.stock');
        $visible_columns = Input::form('columns') ? : $_SESSION[$form_token]['columns'];

        $columns = [];

        foreach($valid_columns as $p=> $p_name) {
            if (isset($visible_columns[$p])) {
                if ($p == 'operate') {
                    $columns['use_quantity'] = '领用';
                    $columns['return_quantity'] = '归还';
                    continue;
                }
                $columns[$p] = $valid_columns[$p];
            }
        }

		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_use export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;
        return $pid;
    }
}
