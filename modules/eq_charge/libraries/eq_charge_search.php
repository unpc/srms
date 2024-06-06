<?php
class EQ_Charge_Search {
	const SEARCH_CASH = 0;
	const SEARCH_SAMPLE = 1;

	const SEARCH_NONE = 0;
	const SEARCH_ADD = 1;
	const SEARCH_SUBTRACT = 2;
	const SEARCH_MULTIPY = 3;
	const SEARCH_DIVIDE = 4;

	static $search_type = [
			self::SEARCH_CASH => '收费金额',
			self::SEARCH_SAMPLE => '样品数',
		];	  

	static $search_option = [
			self::SEARCH_NONE => '--',
			self::SEARCH_ADD => '+',
			self::SEARCH_SUBTRACT => '-',
			self::SEARCH_MULTIPY => 'x',
			self::SEARCH_DIVIDE => '/',
		];

	//计算输出值
	static function get_calculate_value($searchoption, $value, $export_item_value) {
		$ret = $value;
		if($searchoption != EQ_Charge_Search::SEARCH_NONE) {
			switch($searchoption) {
				case EQ_Charge_Search::SEARCH_ADD:
					$ret = $value + $export_item_value;
					break;
				case EQ_Charge_Search::SEARCH_SUBTRACT:
					$ret = $value - $export_item_value;
					break;
				case EQ_Charge_Search::SEARCH_MULTIPY:
					$ret = $value * $export_item_value;
					break;
				case EQ_Charge_Search::SEARCH_DIVIDE:
					$ret = $value / $export_item_value;
					break;
			}
		}
		return $ret;
	}

	static function get_export_value($extraitem, $c) {
		$extra_item = explode('|', $extraitem);
		$searchtype = $extra_item[2];
		$searchoption = $extra_item[3];
		$export_item_value = $extra_item[4];	

		$ret = 0;
		if($searchtype == self::SEARCH_CASH) {
			$ret = $c->amount ? self::get_calculate_value($searchoption, $c->amount, $export_item_value) : 0; 
		}
		else {
			$s = $c->source;
			if ($s->id) {
				switch ($s->name()) {
					case 'eq_sample':
						$ret = self::get_calculate_value($searchoption, (int)max(1, $s->count), $export_item_value);
						break;
					case 'eq_record':
						$ret = self::get_calculate_value($searchoption, $s->samples, $export_item_value);
						break;
					default :
						$ret = '--';
					break;
				}
			}
			else {
				$ret = '--';
			}
		}

		return $ret;
	}
}
