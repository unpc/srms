<?php

class CLI_EQ_Door {

	static function get_info() {
		$header = [
		'ID','名称','地址','负责人','控制地址','离线开门人员','关联仪器',
		];

		self::tr_echo($header);


		foreach (Q('door') as $door) {
			$equipments = Q("{$door}<asso equipment");
			$free_users = array_values($door->get_free_access_cards());
			
			$tr = [
				$door->id,
				$door->name,
				$door->location1 . ' ' . $door->location2,
				self::get_object_name($door->incharger),
				$door->in_addr . ' ' . 'lock#' . $door->lock_id . ' ' . 'detector#' . $door->detector_id,
				self::get_objects_names($free_users),
				self::get_objects_names($equipments),
				];

			self::tr_echo($tr);
			
		}
	}
    static private function td_echo() {
		$content = join(' ', func_get_args());
		// echo '"' . $content . '"' . "\t";
		echo $content . "\t";
	}

	static private function tr_echo($tr) {
		foreach ($tr as $td) {
			self::td_echo($td);
		}
		echo "\n";
	}

	static private function get_objects_names($objects) {
		$names = [];
		
		foreach ($objects as $object) {
			$names[] = self::get_object_name($object);
		}

		return join(' ', $names);
	}

	static private function get_object_name($object) {
		return $object->name . '[' . $object->id . ']';
	}
}
