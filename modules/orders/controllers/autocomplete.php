<?php
class Autocomplete_Controller extends AJAX_Controller {

	function new_dealers_selector() {
		$company = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		if ($company) {
			$orders = Q("order[vendor*={$company}]")->to_assoc('vendor', 'vendor');
			$vendors = Q("vendor[company*={$company}]")->to_assoc('company', 'company');

			$vendors = array_diff($orders, $vendors);

			/*
			NO.TASK#312(guoping.zhang@2011.01.07)
			查询限制数量:10
			*/
			$all = count($vendors);
			if ($start == 0 && !$all) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				$vendors = array_slice($vendors, $start, 10);
				foreach ($vendors as $vendor) {
					Output::$AJAX[] = [
						'html' => (string) V('orders:autocomplete/vendor', ['vendor'=>$vendor]),
						'alt' => $vendor,
						'text' => $vendor,
					];
				}
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		}
	}

	function all_dealers_selector() {
		$company = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		if ($company) {
			$orders = Q("order[vendor*={$company}]")->to_assoc('vendor', 'vendor');
			$vendors = Q("vendor[company*={$company}]")->to_assoc('company', 'company');

			$vendors = array_merge($orders, $vendors);
			/*
			NO.TASK#312(guoping.zhang@2011.01.07)
			查询限制数量:10
			*/
			$all = count($vendors);
			if ($start == 0 && !$all) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				$vendor = array_slice($vendors, $start, 10);
				foreach ($vendors as $vendor) {
					Output::$AJAX[] = [
						'html' => (string) V('orders:autocomplete/vendor', ['vendor'=>$vendor]),
						'alt' => $vendor,
						'text' => $vendor,
					];
				}
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		}
	}

	function order() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		if($st >=5) return;
		$uuid = Lab::get('lims.site_id');
		$keyword = $s;
		if ($s) {
			$s = Q::quote($s);
			$selector = "[product_name*=$s]";
		}
		$orders = Q("order{$selector}:sort(source D):limit(10)");
		$stocks = Q("stock{$selector}:limit(10)");

		$num = 0;
		foreach($stocks as $stock) {
			if ($orders[$stock->order->id]) continue;
			$num ++;
            $data = [
				'html' => (string) V('autocomplete/order', ['order'=>$stock]),
				'alt' => $stock->id . '|' . $stock->name(),
				'text' => $stock->product_name,
				'data' => [
					'manufacturer' => $stock->manufacturer,
					'catalog_no' => $stock->catalog_no,
					'model' => $stock->model,
					'spec' => $stock->spec,
					'quantity' => $stock->quantity,
					'unit_price' => $stock->unit_price,
				],
			];

            if (Module::is_installed('vendor') && O('vendor', ['name'=> $stock->vendor])->id) {
                $data['data']['vendor'] = $stock->vendor;
                $data['data']['vendor_name'] = $stock->vendor;
            }

            Output::$AJAX[] = $data;
		}

		if ($num < 10) {
			foreach($orders as $order) {
				$source = $order->source;
				if ($source) {
					$mall_order = O('mall_order', ['order'=> $order]);
		            $product_id = $mall_order->product_id;
				}
				$num ++;
				$data = [
					'html' => (string) V('autocomplete/order', ['order'=>$order]),
					'alt' => $order->id . '|' . $order->name(),
					'text' => $order->product_name,
					'data' => [
                        'manufacturer' => $order->manufacturer,
                        'catalog_no' => $order->catalog_no,
                        'model' => $order->model,
                        'spec' =>$order->spec,
                        'quantity' => $order->quantity,
                        'unit_price' => $order->unit_price,
                        'price' => $order->price,
                        'source' => $order->source,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'address'=> $order->receive_address,
                        'postcode'=> $order->receive_postcode,
                        'phone'=> $order->receive_phone,
                        'email'=> $order->receive_email,
                        'fare'=> $order->fare,
                        'product_id' => $product_id ? : 0,
					],
				];

                if (Module::is_installed('vendor')) {
                    $vendor = O('vendor', ['name'=>$order->vendor]);
                    if ($vendor->id) {
                        $data['data']['vendor'] = $vendor->id;
                        $data['data']['vendor_name'] = $order->vendor;
                    }
                }

                Output::$AJAX[] = $data;

				if ($num == 10) break;
			}
		}
	}

	function incharge() {
		$s = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$users = Q("user[!hidden][atime][name*={$s}|name_abbr*={$s}]:limit({$start},5)");
		$users_count = $users->total_count();

		if ($start == 0 && !$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/user', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
				];
			}
//			$rest = $users->total_count() - $users_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
}
