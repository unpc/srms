<?php

class API_Eq_Charge extends API_Common
{
    const SOURCE_BILLING = 'billing';

    public static $sources = [
        'billing' => '报销管理',
    ];

    /*
     * 为财务账号充值接口
     * source: 来源，需在self::$sources定义
     * params = [
     *   [
     *     'acconut' => lims 财务账号ID
     *     'amount' => 金额
     *     'voucher' => 充值凭证号
     *   ]
     *   ...
     * ]
     */

    public function recharge($source, $params = [])
    {
        $this->_ready();
        // 来源不明，抛出异常
        if (!array_key_exists($source, self::$sources)) {
            throw new API_Exception(self::$errors[400], 400);
        }

        $acconuts = [];
        foreach ($params as $p) {
            $account = O('billing_account', $p['account']);

            // 增加兼容，因为有的时候Account的id为PI的id, 增加transaction的支持是为了确保最终数据正确
            if (!$account->id) {
                $account = O('billing_transaction', $p['transaction'])->account;
            }

            if (!$account->id || !$p['amount'] || !$p['voucher']) {
                throw new API_Exception(self::$errors[400], 400);
            }
            Event::trigger('api_eq_charge.recharge.validate', $source, $params);
            $acconuts[$p['account']] = $account;
        }

        foreach ($params as $p) {
            $transaction = O('billing_transaction', ['voucher' => $p['voucher']]);
            $transaction->account = $acconuts[$p['account']];

            if ($p['amount'] > 0) {
                $transaction->income = $p['amount'];
            } else {
                $transaction->outcome = $p['amount'];
            }
            $transaction->source = 'local';
            $transaction->voucher = $p['voucher'];
            $transaction->description = [
                'module' => 'billing',
                'template' => "报销成功, 报销单号: {$transaction->voucher}",
            ];
            Event::trigger('api_eq_charge.recharge.extra_value', $transaction, $source, $params);
            if (!$transaction->save()) {
                throw new API_Exception(self::$errors[500], 500);
            }
        }
        return true;
    }

    public function lockEqCharge($source, $charge_ids, $lock = true)
    {
        $this->_ready();
        // 来源不明或参数不全，抛出异常
        if (!array_key_exists($source, self::$sources) || !count($charge_ids)) {
            throw new API_Exception(self::$errors[400], 400);
        }
        $db = ORM_Model::db('eq_charge');
        $db->query(
            'UPDATE eq_charge SET is_locked = %d WHERE `id` IN (' . join(',', $charge_ids) . ')',
            $lock
        );
        Event::trigger('api_eq_charge.lock_eqcharge.extra', $source, $charge_ids, $lock);
        return true;
    }
}
