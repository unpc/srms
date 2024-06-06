<?php

class Sample_approval {
    
    static function color($e) {
        $e->return_value = Sample_Approval_Model::$status_background_color;
        return false;
    }
    
    static function status($e) {
        $e->return_value = Sample_Approval_Model::$status;
        return false;
    }

    static function charge_status($e) {
        $e->return_value = array_merge(EQ_Sample_Model::$charge_status, Sample_Approval_Model::$charge_status);
        return false;
    }

    static function get_not_show_status()
    {
        return [
            Sample_Approval_Model::STATUS_OFFICE,
            Sample_Approval_Model::STATUS_PLATFORM,
            Sample_Approval_Model::STATUS_ACCESS,
        ];
    }
    
    static function people_extra_keys($e, $user, $info) {
        $info['is_admin'] = $user->access('管理所有内容') ? 1 : 0;
        $info['canoffice'] = $user->access('测样报告初审') ? 1 : 0;
        $info['canplatform'] = $user->access('测样报告终审') ? 1 : 0;
        $info['group_id'] = $user->group->id ? : 0;
        $e->return_value = $info;
    }
    
    static function extra_form_validate($e, $equipment, $type, $form) {
        if ($type == 'eq_sample') {
            if (Config::get('sample_approval.to_equipment') && !$equipment->sample_approval_enable) return;
            $form
                ->validate('name', 'not_empty', I18N::T('equipments', '请填写样品名称!'))
                ->validate('type', 'not_empty', I18N::T('equipments', '请填写样品类别!'))
                ->validate('code', 'not_empty', I18N::T('equipments', '请填写样品代号!'));
        }
    }
    
    static function sample_form_submit($e, $sample, $form) {
        $eqid = $sample->id ? $sample->equipment->id : $form['equipment_id'];
        $equipment = O('equipment', $eqid);
        if (Config::get('sample_approval.to_equipment') && !$equipment->sample_approval_enable) return;
        $sample->name = $form['name'];
        $sample->type = $form['type'];
        $sample->code = $form['code'];
        $sample->format = $form['format'] ? : 0;
        $sample->mode = json_encode($form['mode']);
    }
        
    static function sample_form_post_submit($e, $sample, $form) {

        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) return;

        $me = L('ME');
        $results = Q("sample_result[sample=$sample]")->to_assoc('id', 'id');
        
        if (is_array($form['result_index'])) foreach ($form['result_index'] as $key => $value) {
            if ($form['result_id'][$key]) unset($results[$form['result_id'][$key]]);
            
            $result = O('sample_result', $form['result_id'][$key]);
            $result->sample = $sample;
            $result->subname = $form['subname'][$key];
            $result->parameter = $form['parameter'][$key];
            $result->concentration = $form['concentration'][$key];
            $result->level = $form['level'][$key];
            if ($me->is_allowed_to('管理', $sample)) {
                $result->result = $form['result'][$key];
            }
            $result->save();
        }
        
        foreach ($results as $id) {
            $result = O('sample_result', $id);
            $result->delete();
        }
        
        if ($form['submit'] == 'push' 
        && $me->is_allowed_to('管理', $sample) 
        && $sample->status == EQ_Sample_Model::STATUS_TESTED) {
            $form = Input::form();
            $id = $form['id'];
            $eq_sample = O('eq_sample', $id);
            
            $tags = [];
            $root = $eq_sample->equipment->group->root;
            $t = $eq_sample->equipment->group;
            while ($t->id) {
                $tags[$t->id] = $t->name;
                if ($t->id == $root->id) {
                    break;
                }
                $t = $t->parent;
            }
            
            $sample = [
                'id' => $eq_sample->id,
                'sender' => [
                    'id' => $eq_sample->sender->id,
                    'name' => $eq_sample->sender->name,
                    'organization' => $eq_sample->sender->organization,
                ],
                'operator' => [
                    'id' => $eq_sample->sender->id,
                    'name' => $eq_sample->sender->name,
                ],
                'equipment' => [
                    'id' => $eq_sample->equipment->id,
                    'name' => $eq_sample->equipment->name,
                    'ref_no' => $eq_sample->equipment->ref_no,
                ],
                'dtsubmit' => $eq_sample->dtsubmit,
                'dtstart' => $eq_sample->dtstart,
                'dtend' => $eq_sample->dtend,
                'status' => Sample_Approval_Model::STATUS_OFFICE,
                'group' => $tags,
                'count' => $eq_sample->count,
                'project' => $eq_sample->project->name,
                'name' => $eq_sample->name,
                'type' => $eq_sample->type,
                'code' => $eq_sample->code,
                'format' => $eq_sample->format,
                'mode' => json_decode($eq_sample->mode, true),
                'note' => $eq_sample->note,
            ];
            
            $results = Q("sample_result[sample=$eq_sample]");
            //TODO 考虑通用性
            if ($results) foreach ($results as $result) {
                $sample['result'][] = [
                    'id' => $result->id,
                    'subname' => $result->subname,
                    'parameter' => $result->parameter,
                    'concentration' => $result->concentration,
                    'level' => $result->level,
                    'result' => $result->result
                ];
            }

            try {
                //获取rpc配置
                $rpc_conf = Config::get('rpc.approval');
                $url = $rpc_conf['url'];
                $rpc = new RPC($url);
                if (!$rpc->Approval->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
                    throw new RPC_Exception;
                }
                
                if ($rpc->Sample->submit($sample)) {
                    $eq_sample->is_locked = true;
                    $eq_sample->status = Sample_Approval_Model::STATUS_OFFICE;
                    $eq_sample->stime = time();
                    $eq_sample->save();
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '送样审核提交成功!'));
                }
                else {
                    throw new RPC_Exception;
                }
            }
            catch(RPC_Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '送样审核提交失败!'));
            }
        }
    }
    
    static function is_accessible($e, $name) {
        $me = L('ME');
        if ($me->access('管理所有内容') ||
            $me->access('测样报告初审') ||
            $me->access('测样报告终审')) {
            $e->return_value = TRUE;
            return FALSE;
        }
        else {
            $e->return_value = FALSE;
            return FALSE;
        }
    }
   
    static function sample_links($e, $sample, $links, $mode) {

        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) return;

        $me = L('ME');
        if ($sample->status == EQ_Sample_Model::STATUS_APPLIED|| $sample->status == EQ_Sample_Model::STATUS_APPROVED) {
            $links['pdf'] = [
                'url' => '!eq_sample/approval/export.' . $sample->id,
                'text' => I18N::T('eq_sample', '任务单'),
                'extra' => 'class="blue" target="_blank"'
            ];
        }

        if ($sample->status == Sample_Approval_Model::STATUS_ACCESS) {
            $links['pdf'] = [
                'url' => '!eq_sample/approval/report.' . $sample->id,
                'text' => I18N::T('eq_sample', '报告单'),
                'extra' => 'class="blue" target="_blank"'
            ];
        }
    }
    
    static function sample_buttons($e, $sample) {
        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) return;
        $me = L('ME');
        if ($me->is_allowed_to('管理', $sample) 
        && $sample->status == EQ_Sample_Model::STATUS_TESTED) {
            $e->return_value = V('sample_approval:sample/sample_button', ['sample' => $sample]);
            return FALSE;
        }
    }
    
    static function sample_message($e, $sample) {
        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) return;
        $me = L('ME');
        if ($me->is_allowed_to('管理', $sample)) {
            $remark = Q("sample_remark[sample=$sample]:sort(time DESC)")->current();
            if ($remark->id) {
                $e->return_value = V('sample_approval:sample/remark', ['remark' => $remark]);
                return FALSE;
            }
        }
    }

    static function sample_print_custom($e, $sample, $uniqid) {
        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) {
            $e->return_value .= '';
            return;
        }
        $e->return_value .= V("sample_approval:sample/print/{$uniqid}", [
            'value' => $sample->{$uniqid}
        ]);
        return TRUE;
    }
    
    static function sample_extra_print($e, $sample) {
        if (Config::get('sample_approval.to_equipment') && !$sample->equipment->sample_approval_enable) {
            $e->return_value .= '';
            return;
        }
        $e->return_value .= V("sample_approval:sample/extra_print", [
            'results' => Q("sample_result[sample=$sample]"),
        ]);
        return TRUE;
    }

    static function eq_sample_requirement_extra_view($e, $equipment, $disabled)
    {
        $me = L('ME');
        if (Config::get('sample_approval.to_equipment')) {
            $e->return_value = V('sample_approval:sample/extra', ['equipment' => $equipment, 'disabled' => $disabled]);
        }
        return false;
    }

}

