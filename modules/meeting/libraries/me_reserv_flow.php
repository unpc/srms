<?php

class ME_Reserv_Flow 
{
    // 预约是否需要审核
    public static function need_workflow($reserv)
    {
        if ($reserv->name() == 'me_reserv') {
            return (bool)$reserv->meeting->need_approval;
        }
        else {
            return FALSE;
        }
    }

    // 负责人是否可以审核通过
    public static function incharge_pass($reserv) 
    {
        return TRUE;
    }

    // workflow 在通过审核时做的判断处理
    public static function on_workflow_model_pass($e, $workflow)
    {
        $source = $workflow->source;
        if ($workflow->flag != 'done' || $source->name() != 'me_reserv') {
            return FALSE;
        }
    }

    // workflow 在被驳回时做的判断处理
    public static function on_workflow_model_reject($e, $workflow)
    {
        $source = $workflow->source;
        if ($workflow->flag != 'reject' || $source->name() != 'me_reserv') {
            return FALSE;
        }
    }

    // 根据审核状态给与不同的样式显示
    static function cal_component_get_color($e, $component, $calendar) {
		$parent_name = $calendar->parent->name();

        if ($parent_name == 'meeting') {
            $reserv = O('me_reserv', ['component' => $component]);
            $workflow = O('workflow', ['source' => $reserv]);
            if ($workflow->id) {
                if ($workflow->flag == 'reject') {
                    $return = 4 + 6;
                }
                else if ($workflow->flag == 'done') {
                    $return = 0;
                }
                else {
                    $return = 5 + 6;
                }
            }

            $e->return_value = $return;
		    return FALSE;
        }

		return TRUE;
	}
}