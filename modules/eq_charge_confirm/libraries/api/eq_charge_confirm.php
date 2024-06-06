<?php
class API_EQ_Charge_Confirm extends API_Common
{
    const SOURCE_BILLING = 'billing';

    public static $sources = [
        'billing' => '报销管理',
    ];

    /*
     * 收费确认批量操作接口
     * source: 来源，需在self::$sources定义
     * params = [
     *   [
     *     'acconut' => lims 财务账号ID
     *     'amount' => 金额
     *     'voucher' => 充值凭证号
     *   ]
     *   ...
     * ]
     * lock = 0 批量撤回，lock = 1 批量确认
     */
    public function confirm_status($source, $charge_ids, $confirm = false)
    {
        $this->_ready();
        // 来源不明或参数不全，抛出异常
        if (!array_key_exists($source, self::$sources) || !count($charge_ids)) {
            throw new API_Exception(self::$errors[400], 400);
        }

        $success_ids = [];
        $ids = join(',', $charge_ids);
        if (Module::is_installed('billing')) {
            // 如果不存在这个收费，直接算成功
            foreach ($charge_ids as $charge_id) {
                $c = Q("eq_charge[transaction_id={$charge_id}]")->current();
                if (!$c->id) {
                    $success_ids[] = $charge_id;
                }
            }
            $charges = Q("eq_charge[transaction_id={$ids}]");
        } else {
            // 如果不存在这个收费，直接算成功
            foreach ($charge_ids as $charge_id) {
                $c = Q("eq_charge[id={$charge_id}]")->current();
                if (!$c->id) {
                    $success_ids[] = $charge_id;
                }
            }
            $charges = Q("eq_charge[id={$ids}]");
        }
        foreach ($charges as $charge) {
            if ($confirm) {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_INCHARGE;
            } else {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                $charge->voucher = 0;
            }
            // 是否记录被报销驳回
            $newCharge = Event::trigger('eq_charge.extra_fields_save', $charge);
            $charge = $newCharge->id ? $newCharge : $charge;
            if ($charge->save()) {
                $success_ids[] = Module::is_installed('billing') ? $charge->transaction_id : $charge->id;
                Log::add(strtr('[eq_charge_confirm] %source 取消了已确认的收费记录[%charge_id]', [
                    '%source' => $source,
                    '%charge_id' => Module::is_installed('billing') ? $charge->transaction_id : $charge->id,
                ]), 'journal');
            }
        }
        return $success_ids;
    }
}
