<?php

class EQ_Time_Counts_Model extends ORM_Model
{

    // 是否适用于该人员
    public function check_user($user)
    {
        // 当没设置适用人员的时候 代表该规则适用于所有人
        if ($this->controlall) return true;

        $users = array_keys(json_decode($this->controluser, TRUE) ?? []);
        if (in_array($user->id, $users)) return true;

        $labs = implode(',', array_keys(json_decode($this->controllab, TRUE) ?? []));
        $user_labs = Q("$user lab[id={$labs}]")->total_count();
        if ($user_labs > 0) return true;

        $groups = implode(',', array_keys(json_decode($this->controlgroup, TRUE) ?? []));
        $user_groups = Q("$user tag_group[id={$groups}]")->total_count();
        if ($user_groups > 0) return true;

        return false;
    }
}
