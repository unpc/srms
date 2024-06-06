<?php

class API_Task_Info extends API_Common {
    function set_task($data = []) {
        $result = [
            'status' => 0,
            'data' => []
        ];
        if (count($data)) {
            foreach ($data as $value) {
                $task = O('capability_task', ['source_id' => $value['id']]);
                $task->source_id = $value['id'];
                $task->name = $value['name'];
                $task->dtstart = $value['dtstart'];
                $task->dtend = $value['dtend'];
                $task->datadtstart = $value['datadtstart'];
                $task->datadtend =$value['datadtend'];
                $task->status = $value['status'];
                $task->save();
                if ($task->id) {
                    foreach ($value['equipments'] as $equipment) {
                        $equipment_task = O('capability_equipment_task', ['source_id' => $equipment['id']]);
                        $equipment_task->source_id = $equipment['id'];
                        $equipment_task->name = $equipment['name'];
                        $equipment_task->group = o('tag', ["root"=>Tag_Model::root('group'), 'id'=>$equipment['group']]);
                        $equipment_task->submit_user = o('user', $equipment['submit_user']);
                        $equipment_task->submit_time = $equipment['submit_time'];
                        $equipment_task->equipment = O('equipment', $equipment['source_id']);
                        $equipment_task->process_status = $equipment['process_status'];
                        $equipment_task->capability_task = $task;
                        $equipment_task->save();
                        if ($equipment_task->id) {
                            //删除所有关联
                            Q("capability_equipment_task_user[capability_equipment_task={$equipment_task}]")->delete_all();
			foreach (Q("{$equipment_task->equipment} user.incharge ") as $ischarge) {
                      $user = O('capability_equipment_task_user');
                      $user->capability_equipment_task = $equipment_task;
                      $user->user = $ischarge;
                      $user->save();
                      //error_log('增加负责人'.$user->id);
                  }              
                foreach ($equipment['ischarge'] as $ischarge) {
                                $user = O('capability_equipment_task_user');
                                $user->capability_equipment_task = $equipment_task;
                                $user->user = o('user', $ischarge);
                                $user->save();
                            }
                        }
                    }
                    $result['data'][] = $task->source_id;
                }
            }
            return $result;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }

    function set_equipment($equipment) {
       $task = O('capability_task', ['source_id' => $equipment['task']]);
        if (!$task->id) {
            return [
                'status' => 1//数据为空
            ];
        }
	$equipment_task = O('capability_equipment_task', ['source_id' => $equipment['id']]);
        $equipment_task->source_id = $equipment['id'];
        $equipment_task->name = $equipment['name'];
        $equipment_task->group = o('tag', ["root"=>Tag_Model::root('group'), 'id'=>$equipment['group']]);
        $equipment_task->submit_user = o('user', $equipment['submit_user']);
        $equipment_task->submit_time = $equipment['submit_time'];
        $equipment_task->equipment = O('equipment', $equipment['source_id']);
        $equipment_task->process_status = $equipment['process_status'];
        $equipment_task->capability_task = O('capability_task', ['source_id' => $equipment['task']]);
        $equipment_task->save();
	//error_log($equipment_task->id."增加仪器");
        if ($equipment_task->id) {
            //删除所有关联
            Q("capability_equipment_task_user[capability_equipment_task={$equipment_task}]")->delete_all();
	  //  error_log(Q("{$equipment_task->equipment} user.incharge")->total_count()."增加仪器负责人");
            foreach (Q("{$equipment_task->equipment} user.incharge ") as $ischarge) {
                $user = O('capability_equipment_task_user');
                $user->capability_equipment_task = $equipment_task;
                $user->user = $ischarge;
                $user->save();
		error_log('增加负责人'.$user->id);
            }
	    foreach ($equipment['ischarge'] as $ischarge) {
                $user = O('capability_equipment_task_user');
                $user->capability_equipment_task = $equipment_task;
                $user->user = o('user', $ischarge);
                $user->save();
            }
            $result['data'][] = $equipment_task->id;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }

    function delete_equipment($data = []){
        $result = [
            'status' => 0,
            'data' => []
        ];
        if (isset($data['id'])) {
            $capability_equipment_task = O('capability_equipment_task', ['source_id' => $data['id']]);
            //删除所有关联
            Q("capability_equipment_task_user[capability_equipment_task={$capability_equipment_task}]")->delete_all();
            $capability_equipment_task->delete();
            $result['data'][] = $capability_equipment_task->source_id;
            return $result;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }

    function set_helper($equipment) {
        $equipment_task = O('capability_equipment_task', ['source_id' => $equipment['equipments_id']]);
        if ($equipment_task->id) {
            $user = O('capability_equipment_task_user');
            $user->capability_equipment_task = $equipment_task;
            $user->user = o('user', $equipment['user']);
            $user->save();
            $result['data'][] = $equipment_task->equipments_source_id;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }

}
