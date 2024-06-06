<?php

class Autocomplete_Controller extends AJAX_Controller {

	function user() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if ($s) {
			$s = Q::quote($s);	
			$users = Q("user[!hidden][atime][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
		}
		else {
			$users = Q("user[!hidden][atime]:limit({$start},{$n})");
		}
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

	function stock($sid = 0) {
		$name = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$selector = "stock[parent_id=@id]";

		if ($name) {
			$name = Q::quote($name);
			$selector .= "[product_name*=$name]";
		}

		if ($sid) {
			$selector .= "[id!={$sid}]";
		}

		$stocks = Q($selector)->limit($start, $n);
		$stocks_count = $stocks->total_count();

		if ($start == 0 && !$stocks_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($stocks as $stock) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/stock', ['stock'=>$stock]),
					'alt' => $stock->id,
					'text' => H($stock->product_name),
				];
			}
//			$rest = $stocks->total_count() - $stocks_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function tags($root_id) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$root = O('tag', $root_id);
		if (!$root->id) {
			return FALSE;
		}

		if ($s) {
			$s = Q::quote($s);
			$all_tags = Q("tag[root={$root}][name*={$s}]");
		}
		else {
			$all_tags = Q("tag[root={$root}]");
		}

		$tags = $all_tags->limit($start, $n);

		$all_tags_count = $all_tags->total_count();
		$tags_count = $tags->length();

		$all_tags = (array) $all_tags->to_assoc('name', 'id');
		$tags = (array) $tags->to_assoc('name', 'id');

		$rest = $all_tags_count - $tags_count;
		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag=>$reserved){
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/small_tag', ['tag'=>$tag]),
					'alt' => $tag,
					'text' => $tag,
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

	function user_selector() {

		$s = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if (!$s) return;
		$lab_id = Input::form('lab');

		$selector = 'user';
		if(!$lab_id){
			$selector .= '[!lab]';
		}
		elseif($lab_id != '*'){
			$lab = O('lab',$lab_id);
			$selector .= "[lab={$lab}]";
		}
		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量:10
		 */
		$selector .= "[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		$users = Q($selector);
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
			if ($rest > 0) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
    }

    function add_stock() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$selector = 'stock[is_collection=0]';
		if ($s) {
			$s = Q::quote($s);
			$selector .= "[product_name*={$s}]";
		}

		$stocks = Q($selector)->limit($start, $n);
		$stocks_count = $stocks->total_count();
		if ($start == 0 && !$stocks_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($stocks as $stock) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/stock', ['stock'=>$stock]),
					'alt' => $stock->id,
					'text' => $stock->product_name,
					'data' => [
						'manufacturer' => $stock->manufacturer,
						'catalog_no' => $stock->catalog_no,
						'model' => $stock->model,
						'spec' => $stock->spec,
						'vendor' => $stock->vendor,
						'quantity' => $stock->quantity,
						'unit_price' => $stock->unit_price,
						'barcode' => $stock->barcode,
						'location' => $stock->location,
					],
				];
			}
			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
}
