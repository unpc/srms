<?php
use \Pheanstalk\Pheanstalk;

class Control_Lannister
{
    static function on_transaction_saved($e, $transaction) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        if ($transaction->description['module'] == 'billing') {
            $description_value = new Markup(I18N::T('billing', $transaction->description['template'], [
                '%user'=>$transaction->description['%user'],
                '%account'=>$transaction->description['%account'],
                '%from_account'=> $transaction->description['%from_account'],
            ]), FALSE);
        }
        if ($transaction->description['module'] == 'eq_charge') {
            $description_value = new Markup(I18N::T('eq_charge', $transaction->description['template'], [
                '%user'=>$transaction->description['%user'],
                '%equipment'=>$transaction->description['%equipment'],
            ]),FALSE);
        }

        $data = new ArrayIterator([
            'id' => $transaction->id,
            'source' => LAB_ID,
            "account" => $transaction->account_id,
            "user" => $transaction->user_id,
            "reference" => $transaction->reference_id,
            "status" => $transaction->status,
            "income" => $transaction->income,
            "outcome" => $transaction->outcome,
            "ctime" => $transaction->ctime,
            "mtime" => $transaction->mtime,
            "certificate" => $transaction->certificate,
            "voucher" => $transaction->voucher,
            "manual" => $transaction->manual,
            "transfer" => $transaction->transfer,
            'description' => $transaction->description,
            "description_value" => (string)$description_value,
        ]);

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "transaction",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_transaction_deleted($e, $object) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "transaction",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_account_saved($e, $account) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = new ArrayIterator([
            'id' => $account->id,
            'source' => LAB_ID,
            "department" => $account->department_id,
            "lab" => $account->lab_id,
            "income_remote" => $account->income_remote,
            "income_remote_confirmed" => $account->income_remote_confirmed,
            "income_local" => $account->income_local,
            "income_transfer" => $account->income_transfer,
            "outcome_remote" => $account->outcome_remote,
            "outcome_local" => $account->outcome_local,
            "outcome_transfer" => $account->outcome_transfer,
            "outcome_use" => $account->outcome_use,
            "balance" => $account->balance,
            "credit_line" => $account->credit_line,
            "account_source" => $account->account_source,
            "voucher" => $account->voucher,
        ]);

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "account",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_account_deleted($e, $object) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "account",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_department_saved($e, $department){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $root = Tag_Model::root('group');
        if ($department->group != $root) {
            $tag = $department->group;
            $group = $tag->id ? [$tag->name] : null ;
            while($tag->parent->id && $tag->parent->root->id){
                array_unshift($group, $tag->parent->name);
                $tag = $tag->parent;
            }
        } else {
            $department->group_id = '';
        }

        $data = new ArrayIterator([
            'id' => $department->id,
            'source' => LAB_ID,
            'name' => $department->name,
            'nickname' => $department->nickname,
            'group' => $group,
            'group_id' => $department->group_id,
            'mtime' => $department->mtime
        ]);

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "department",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_department_deleted($e, $object) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "department",
            'body' => $data
        ];
        $mq
            ->useTube('billing')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }
}