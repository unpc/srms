<?php

class Report_Controller extends Layout_Controller {

	function index() {
		$form = Input::form();
		$stock_id = $form['stock_id'];
		$type = $form['type'];

		if ($type == 'print') {
			$this->_print($stock_id);
		}
		elseif ($type == 'csv') {
			$this->_csv($stock_id, $form);
		}
		else {
			URI::redirect('error/401');
		}
	}

	public function stock($id = 0, $type = 'print')
	{
		$stock = O('stock', $id);
		if (!$stock->id) URI::redirect('error/404');
		$this->add_css('inventory:base inventory:common inventory:barcode');
		$this->add_js('inventory:barcode');
		$this->layout = V('inventory:report/stock/print', [
			'stock' => $stock
		]);
	}

	private function _print($stock_id){


        $valid_columns = Config::get('inventory.export_columns.stock');
		$visible_columns = Input::form('columns');


        foreach ($valid_columns as $p => $p_name) {
			if (isset($visible_columns[$p])) {
			    if ( $p == 'operate'){
                    $target['use_quantity'] = '领用';
                    $target['return_quantity'] = '归还';
                    continue;
                }
                $target[$p] = $valid_columns[$p];
           }
            
		}
		$stock_uses = Q("stock_use:sort(ctime D)[stock=$stock_id]");
		$dtend = Q("stock_use:sort(ctime DESC):limit(1)[stock=$stock_id]");
		$dtstart = Q("stock_use:sort(ctime ASC):limit(1)[stock=$stock_id]");
		$this->layout = V('inventory:report/print');
		$this->layout->stock_uses = $stock_uses;
		$this->layout->columns = $target;
		$this->layout->dtstart = $dtstart;
		$this->layout->dtend = $dtend;
	}

	private function _csv($stock_id, $form) {
		$form_token = $form['form_token'];
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $valid_columns = Config::get('inventory.export_columns.stock');
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (isset($visible_columns[$p])) {
			    if ( $p == 'operate'){
                    $target['use_quantity'] = '领用';
                    $target['return_quantity'] = '归还';
                    continue;
                }
                $target[$p] = $valid_columns[$p];
           }
            
		}

		$csv = new CSV('php://output', 'w');
		$me = L('ME');
		Log::add(strtr('[inventory] %user_name[%user_id]以CSV导出了存货列表', [
					'%user_name' => $me->name,
					'%user_id' => $me->id
					]), 'journal');

		$csv->write(I18N::T('inventory', $target));
		$stock_uses = Q("stock_use:sort(ctime D)[stock=$stock_id]");
		if ($stock_uses->total_count() > 0) {

			$start = 0;
			$per_page = 100;
			while (1) {
				$pp_trans = $stock_uses->limit($start, $per_page);
				if ($pp_trans->length() == 0) break;
				foreach ($pp_trans as $t) {
					$data = [];
					if (array_key_exists('ctime', $target)) {
						$data[] = Date::format($t->ctime, 'Y/m/d H:i');
					}
					if (array_key_exists('stock', $target)) {
						$data[] = $t->stock->product_name;
					}
					if (array_key_exists('user', $target)) {
						$data[] = $t->user->name;
					}
					if (array_key_exists('use_quantity', $target)) {
						$data[] = $t->quantity > 0 ? $t->quantity : null;
					}

					if (array_key_exists('return_quantity', $target)) {
						$data[] = $t->quantity < 0 ? (-1)*$t->quantity : null;
					}

                    if (array_key_exists('unit_price', $target)) {
                        $data[] = $t->stock->unit_price;
                    }

                    if (array_key_exists('total_price', $target)) {
                        $data[] = $t->stock->unit_price * ($t->quantity > 0 ? $t->quantity : (-1) * $t->quantity);
                    }

                    if (array_key_exists('status', $target) && $t->status) {
                        $data[] = Stock_Use_Model::$status[$t->status];
                    }

					if (array_key_exists('note', $target)) {
						$data[] = $t->note;
					}
					$csv->write($data);
				}

				$start += $per_page;
			}
		}
		$csv->close();
	}
}

class Report_AJAX_Controller extends AJAX_Controller {
	//仪器使用收费新打印功能
	
	function index_export_click() {

		$form = Input::form();
		$stock_id = $form['stock_id'];
		$columns = Config::get('inventory.export_columns.stock');
		$type = $form['type'];

		$form_token = Session::temp_token('inventory.return_use_',300);
		if ($type == 'csv') {
			$title = I18N::T('inventory', '请选择要导出CSV的列');
		}
		else {
			$title = I18N::T('inventory', '请选择要打印的列');
		}
		JS::dialog(V('inventory:report/export_form', [
						  'stock_id' => $stock_id,
						  'columns' => $columns,
						  'type' => $type,
						  'form_token'=>$form_token,
					]), [
						'title' => I18N::T('inventory', $title)
					]);
	}

}
