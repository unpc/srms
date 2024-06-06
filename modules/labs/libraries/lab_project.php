<?php
class Lab_Project {
    static function eq_reserv_merge_reserv_extra($e, $source, $target) {
        //bug#13984 转需求： 仅在相同关联项目合并
        $targetReserv = O('eq_reserv', ['component'=>$target]);
        $form = L('COMPONENT_FORM');

        if ($GLOBALS['preload']['people.multi_lab']) {
            if ($form['project_lab'] != $targetReserv->project->lab->id) {
                $e->return_value = TRUE;
                return FALSE;
            }
        }

        if (!$targetReserv->equipment->merge_in_same_project) {
            $e->return_value = FALSE;
            return TRUE;
        }

        if ((!$targetReserv->project->id && !$form['project']) || $form['project'] == $targetReserv->project->id) {
            $e->return_value = FALSE;
            return TRUE;
        }
        else {
            $e->return_value = TRUE;
            return FALSE;
        }
    }
}