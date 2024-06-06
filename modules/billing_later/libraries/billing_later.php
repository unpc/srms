<?php

class Billing_Later {

    const STATUS_DRAFT = 0;
    const STATUS_PENDDING = 1;
    const STATUS_RECORD = 2;

    public static $STATUS_LABEL = [
        self::STATUS_DRAFT => '未报销',
        self::STATUS_PENDDING => '报销中',
        self::STATUS_RECORD => '已报销'
    ];

    public static $STATUS_COLOR = [
        self::STATUS_DRAFT => '#f93',
        self::STATUS_PENDDING => '#7498e0',
        self::STATUS_RECORD => '#6c9'
    ];

    static function eq_reserv_prerender_component($e, $view, $form) {

        $component = $view->component;

        if ( $component->id && L('ME')->id != $component->organizer->id ) {
            $e->return_value = $form;
            return false;
        }

        $form['grant'] = [
            'label'=>I18N::T('billing_later', '经费代码'),
            'path' => ['form' => 'billing_later:view/calendar/calendar_form/'],
            'component' => $component,
            'weight' => 100
        ];

        $form['#categories']['reserv_info']['items'][] = 'grant';

        $e->return_value = $form;
        return TRUE;
    }

    static function component_form_post_submit($e, $component, $form) {
        $object = O('eq_reserv', [ 'component' => $component ]);
        if (!$object->id) $object = O('eq_sample', [ 'component' => $component ]);

        $object->grant = isset($form['grant']) ? $form['grant'] : $object->grant;
        $object->save();
    }

    static function get_lab_grants($e, $lab) {
        $e->return_value = (array)$lab->showGrants;
        return false;
    }

    static function eq_sample_prerender_add_form($e, $form, $user) {
        $me = L('ME');
        if (Q("($user,$me) lab")->total_count()) {
            $e->return_value = V('billing_later:view/eq_sample/add', ['form' => $form, 'user' => $user]);
        }
        return false;
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $user) {
        $me = L('ME');
        if (Q("($user,$me) lab")->total_count()) {
            $e->return_value = V('billing_later:view/eq_sample/edit', ['form' => $form, 'sample' => $sample, 'user' => $user]);
        }
        return false;
    }

    static function eq_sample_form_submit($e, $sample, $form) {
        $sample->grant = isset($form['grant']) ? $form['grant'] : $sample->grant;
    }

    static function charges_table_list_columns ($e, $form, $columns, $obj) {
        $me = L('ME');

        if ($me->is_allowed_to('查看收费情况', $obj) || !$obj) {
             $columns['bl_status'] = [
                'title' => '状态',
                'nowrap' => TRUE,
                'weight' => 15,
            ];

            return TRUE;
        }
    }

    static function charges_table_list_row ($e, $row, $charge, $obj) {
        $me = L('ME');
        $db = Database::factory();

        if ($me->is_allowed_to('查看收费情况', $obj) || !$obj) {
            $opt = Config::get('rpc.servers')['billing.later'];
            $rpc = new RPC($opt['api']);
            if ($charge->bl_status) {
                $item = ['status' => $charge->bl_status];
                $row['bl_status'] = (string)V('billing_later:charges_table/data/status', ['item' => $item]);
            }
            elseif ($rpc->Item->Authorize($opt['client_id'], $opt['client_secret'])) {
                $item = $rpc->Item->GetItem($charge->transaction->id);
                $row['bl_status'] = (string)V('billing_later:charges_table/data/status', ['item' => $item]);
                if ($item['status']) {
                    $res = $db->query("UPDATE eq_charge SET bl_status = %d WHERE id = %d", $item['status'], $charge->id);
                }
            }
        }

        $e->return_value = $row;
        return TRUE;
    }

    static function eq_charge_selector_modify ($e, $selector, $form) {
        if ($form["bl_status"]!=-1 && isset($form['bl_status'])) {
            $e->return_value = $selector . "[bl_status={$form['bl_status']}]";
        }
    }
}
