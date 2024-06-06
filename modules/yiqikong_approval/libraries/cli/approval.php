<?php

class CLI_Approval {

    public static function delete_expired_approval() {

        $now = Date::time();

	    $modules = Config::get('approval.modules');

        foreach (Q("approval[flag=approve]") as $approval) {
            if (!in_array($approval->source->name(), $modules)) {
		        continue;
	        }
            if ($approval->source->dtstart <= $now) {
                $approval->dtstart = $approval->source->dtstart;
                $approval->dtend = $approval->source->dtend;
                $approval->sample_count = $approval->source->sample_count;
                $approval->reserv_desc = $approval->source->component->description;
                $approval->project_id = $approval->source->project->id;
                $approval->flag = 'expired';
                $approval->description = H('预约记录审核过期, 系统自动删除');
                if ($approval->save()) {
                    $approval->source->component->delete();
                    Approval_Message::expired($approval);
                }
            }
        }
    }
}
