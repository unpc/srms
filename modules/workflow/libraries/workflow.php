<?php

class Workflow
{
    public static function orm_model_saved($e, $object, $old_data, $new_data)
    {
        $modules = Config::get('workflow', []);
        if (!array_key_exists($object->name(), $modules)) {
            return TRUE;
        }

        $need_workflow = TRUE;

        $config = Config::get("workflow.{$object->name()}", []);
        $workflow_check = $config['need_workflow'];

        if ($workflow_check['callback_func']) {
            $need_workflow = call_user_func($workflow_check['callback_func'], $object);
        }

        if ($need_workflow && $workflow_check['hooks']) {
            $need_workflow = Event::trigger($workflow_check['hooks'], $object);
        }

        if ($need_workflow) {
            $trigger_workflow = Event::trigger("workflow_model.need_workflow", $object);
            $need_workflow =  $trigger_workflow != NULL ? $trigger_workflow : $need_workflow;
        }

        if (!$need_workflow)  return TRUE;
        
        // 创建新的approval, 注意要放在更改用户后..逻辑是先删再新增...
        $workflow = O('workflow', ['source' => $object]);
        if ($workflow->id) {
            $workflow->dtstart = $object->dtstart ?: ($approval->dtstart ?: 0);
            $workflow->dtend = $object->dtend ?: ($approval->dtend ?: 0);
            $workflow->save();
            return true;
        }

        $workflow->source = $object;
        $workflow->create();
    }
}
