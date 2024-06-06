<?php

class Sample_Form_Record
{
    public static function record_edit_view($e, $record, $form, $sections)
    {
        if (Q("{$record->user}<incharge {$record->equipment}")->total_count() > 0) {
            $sections[] = V('sample_form:record/sample_form_edit', ['record' => $record, 'form' => $form]);
        }
    }

    public static function post_form_validate($e, $equipment, $oname, $form)
    {
        if ($oname != 'use') {
            return true;
        }
        if ($form['sample_records'] && $form['sample_records'] != '[]' && $form['sample_records'] != '{}'
            && $form['sample_form_records']
        ) {
            $form->set_error('sample_form_records', I18N::T('sample_form', '使用记录不能同时关联送样记录和样品检测记录!'));
        }
        if ($form['sample_form_count'] <= 0 && $form['sample_form_records']) {
            $form->set_error('sample_form_count', I18N::T('sample_form', '请输入正确检测数量!'));
        }
        if (!$form['sample_result'] && $form['sample_form_records']) {
            $form->set_error('sample_result', I18N::T('sample_form', '请输入检测结果!'));
        }
    }

    public static function post_form_submit($e, $record, $form)
    {
        $config = Config::get('rpc.sample_form');
        $rpc = new RPC($config['url']);
        $rpc->set_header([
            "CLIENTID: {$config['client_id']}",
            "CLIENTSECRET: {$config['client_secret']}"
        ]);

        if ($record->sample_element->id
            && $form['sample_form_record_id'] != $record->sample_element->id) {
            $record->sample_element->count = 0;
            $record->sample_element->status = Sample_Element_Model::STATUS_DOING;
            $record->sample_element->result = null;
            $record->sample_element->source = null;
            $record->sample_element->save();
            try {
                $rpc->element->SetSampleElement(
                    $record->sample_element->remote_id,
                    [
                        'result' => '',
                        'status' => Sample_Element_Model::STATUS_DOING
                    ]
                );
            } catch (Exception $e) {
                return false;
            }
        }

        $sample_element = O('sample_element', $form['sample_form_record_id']);
        if (!$sample_element->id) {
            $record->sample_element = null;
            $record->save();
            return false;
        }

        try {
            $result = $rpc->element->SetSampleElement(
                $sample_element->remote_id,
                [
                    'result' => $form['sample_result'],
                    'status' => Sample_Element_Model::STATUS_DONE
                ]
            );
        } catch (Exception $e) {
            return false;
        }

        $sample_element->count = $form['sample_form_count'];
        $sample_element->status = Sample_Element_Model::STATUS_DONE;
        $sample_element->result = $form['sample_result'];
        $sample_element->source = $record;
        $sample_element->save();
        $record->sample_element = $sample_element;

        return true;
    }

    public static function object_is_locked($e, $object, $params)
    {
        if (in_array($object->sample_element->status, Sample_Element_Model::$LOCK_STATUS)) {
            $e->return_value = true;
            return false;
        }
    }

    public static function on_eq_record_before_delete($e,$record){
        $sample_element_record = O('sample_element', ['source' => $record]);
        $sample_element_record->source = null;
        $sample_element_record->save();
    }

    //传入对象$object为eq_record
    static function record_ACL($e, $me, $perm_name, $object, $options) {
        $equipment = $object->equipment;
        if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) return;

        switch ($perm_name) {
            case '锁定':
                /* 如果该仪器没有被反馈, 则不能锁定该记录 */
                if ($object->sample_element->status >= Sample_Element_Model::STATUS_REVIEW) {
                    $e->return_value = FALSE;
                    return FALSE;
                }
                break;
            default:
                return true;
        }
    }
}
