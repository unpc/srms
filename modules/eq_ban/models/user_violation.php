<?php

class User_Violation_Model extends Presentable_Model
{

    protected $object_page = [
        'view' => '!people/profile/index.%id[.%arguments]',
    ];

    public function save($overwrite = false)
    {
        $this->total_count = $this->eq_miss_count +
            $this->eq_leave_early_count +
            $this->eq_overtime_count +
            $this->eq_late_count +
            $this->eq_violate_count;

        $root_id = $this->user->group->root->id;
        $group = $this->user->group;
        $user = $this->user;
        
        $max_allowed_violate_times = Lab::get('equipment.max_allowed_violate_times', Config::get('equipment.max_allowed_violate_times'), $group->name, TRUE);

        if(!$max_allowed_violate_times){
            $max_allowed_violate_times = Lab::get('equipment.max_allowed_violate_times', Config::get('equipment.max_allowed_violate_times'), 0);
        }

        if($max_allowed_violate_times > 0 && $this->eq_violate_count >= $max_allowed_violate_times){
			$banned = O('eq_banned', ['user'=>$user,
                'object_id'=>0]);
			$banned->user = $user;
			$banned->reason = I18N::T('eq_ban', '使用设备违规行为超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
        }

        $max_allowed_total_count_times = Lab::get('equipment.max_allowed_total_count_times', Config::get('equipment.max_allowed_total_count_times'), $group->name, TRUE);

        if(!$max_allowed_total_count_times){
            $max_allowed_total_count_times = Lab::get('equipment.max_allowed_total_count_times', Config::get('equipment.max_allowed_total_count_times'), 0);
        }

        if($max_allowed_total_count_times > 0 && $this->total_count >= $max_allowed_total_count_times){
			$banned = O('eq_banned', ['user'=>$user,
                'object_id'=>0]);
			$banned->user = $user;
			$banned->reason = I18N::T('eq_ban', '使用设备违规总次数超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
        }
        return parent::save($overwrite);
    }
}
