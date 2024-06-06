<?php

class EQ_Evaluate_User {

    static function record_links_edit($e, $record, $links, $mode) {
        $me = L('ME');
        if ($me->is_allowed_to('使用者确认', $record)) {
            $links['evaluate_user'] = [
                'url' => '#',
                'text' => I18N::T('equipments', '确认'),
                'extra' => 'class="blue" q-event="click" q-object="evaluate_user" q-static="'.H(['id' => $record->id]).'" q-src="'.URI::url('!eq_evaluate_user/index').'"'
            ];
        }

        if (MODULE_ID == 'people' && $record->evaluate_user->id) {
            $links['view_evaluate_user'] = [
                'url' => '#',
                'text' => I18N::T('equipments', '查看'),
                'extra' => 'class="blue" q-event="click" q-object="view_evaluate_user" q-static="'.H(['id' => $record->id]).'" q-src="'.URI::url('!eq_evaluate_user/index').'"'
            ];
        }
        return TRUE;
    }

    static function evaluate_user_ACL($e, $user, $perm, $object, $options) {
        if ($object->dtend <= 0) {
            $e->return_value = FALSE;
            return TRUE;
        }
        if ($object->evaluate_user->id) {
            $e->return_value = FALSE;
            return TRUE;
        }
        if ($object->equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            $e->return_value = FALSE;
            return TRUE;
        }
        if (($user->access('管理所有内容') || Equipments::user_is_eq_incharge($user, $object->equipment))) {
            $e->return_value = TRUE;
            return TRUE;
        }
    }

    static function eq_record_list_columns ($e, $form, $columns) {
        if (MODULE_ID == 'people') {
            $columns['attitude'] = [
                'title' => '用户使用态度',
                'align' => 'center',
                'nowrap' => 1,
                'weight' => 10,
            ];
            $columns['proficiency'] = [
                'title' => '熟练度',
                'align' => 'center',
                'nowrap' => 1,
                'weight' => 10,
            ];
            $columns['cleanliness'] = [
                'title' => '试验台清洁度',
                'align' => 'center',
                'nowrap' => 1,
                'weight' => 10,
            ];
            $columns['description']['weight'] = 20;
            $columns['rest']['weight'] = 20;
        }
    }

    static function eq_record_list_row($e, $row, $record) {
        if (MODULE_ID == 'people') {
            $row['attitude'] = V('eq_evaluate_user:records_table/data/attitude', ['record' => $record]);
            $row['proficiency'] = V('eq_evaluate_user:records_table/data/proficiency', ['record' => $record]);
            $row['cleanliness'] = V('eq_evaluate_user:records_table/data/cleanliness', ['record' => $record]);
        }
        return FALSE;
    }

    static function eq_record_user_before_delete($e, $record) {
        $evaluate_user = $record->evaluate_user;
        $evaluate_user->delete();
    }
}
