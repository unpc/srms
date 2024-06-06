<?php

class YiQiKong_Lab{
    
	static function default_lab($equipment_id=0) {
		$lab = O('lab', Lab::get('default_yiqikong_lab_id'));

        //如果不存在lab，或者存在lab，但是没有设置owner，但是同时又有pi的相关配置，则修正该lab
		if (!$lab->id) {
            $lab->name = Config::get('yiqikong_lab.name');
            $lab->atime = Date::time();
            $lab->hidden = 1;
            $lab->save();

            $project = O('lab_project');
            $project->name = '仪器控';
            $project->lab = $lab;
            $project->type = 1;
            $project->ptype = 1;
            $project->isimport = 1;
            $project->save();

            $billing_depts = Q('billing_department');
            foreach ($billing_depts as $dept) {
                $account = O('billing_account');
                $account->lab = $lab;
                $account->department = $dept;
                $account->credit_line = 100000000;
                $account->save();
            }
            $equipment = O('equipment', ['yiqikong_id' => $equipment_id]);

            Lab::set('default_yiqikong_lab_id', $lab->id);
		}

        if (!Module::is_installed('labs') && $lab->name != Config::get('yiqikong_lab.name')) {
            $lab->name = Config::get('yiqikong_lab.name');
            $lab->save();

            $equipment = O('equipment', ['yiqikong_id' => $equipment_id]);
            $account = O('billing_account');
            $account->credit_line = 100000000;
            $account->lab = $lab;
            $account->department_id = $equipment->billing_dept_id;
            $account->save();
        }

		return $lab;
	}
}
