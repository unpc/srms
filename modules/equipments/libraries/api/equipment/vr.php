<?php

class API_Equipment_VR {
   
    function getStatus($id) {
        $equipment = O('equipment', $id);
        if (!$equipment->id) return (object)[];
 
        $now = time();
        $status = [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'is_using' => $equipment->is_using,
            'is_monitoring' => $equipment->is_monitoring,
            'dtnow' => $now,
        ];
        
        $record = Q("eq_record[equipment={$equipment}][dtstart<$now]:sort(dtstart DESC, id DESC):limit(1)")->current();
        if ($record->id) {
            $status['current_user'] = [
                'id' => $record->user->id,
                'name' => $record->user->name
            ];
            $status['dtstart'] = $record->dtstart;  
			if ($record->reserv->id) {
				$status['reserv'] = [
					'dtstart' => $record->reserv->dtstart,
					'dtend' => $record->reserv->dtend
				];
			}
        }
        
        return (object)$status;
    }
}
