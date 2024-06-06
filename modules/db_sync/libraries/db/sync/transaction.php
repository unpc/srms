<?php

class DB_Sync_Transaction
{
    public static function on_transaction_saved($e, $transaction, $old_data, $new_data)
    {
        // transaction改变时，自动对account的处理
        if ($GLOBALS['preload']['billing.single_department']) {
            if (isset($new_data['site'])) {
                return;
            } else {
                $transaction->site = $transaction->site ?: $_SESSION['from_lab'] ?: Config::get('site.master')['name'];
            }
        } else {
            if (isset($new_data['site'])) {
                return;
            } else {
                $transaction->site = $transaction->account->department->site;
            }
        }

        $transaction->save();
    }

    public static function billing_transaction_links_edit($e, $transaction, $links, $mode)
    {
        $me = L('ME');
        if ($GLOBALS['preload']['billing.single_department']) {
            if (Q("{$me}<incharge {$transaction->account->department}")->total_count() || $me->access('管理所有内容')) {
                if (Db_Sync::is_slave()) {
                    if ($me->is_allowed_to('修改', $transaction)) {
                        $links['refill'] = [
                            'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                'q-object' => 'edit_transaction',
                                'q-event'  => 'click',
                                'q-static' => ['transaction_id' => $transaction->id],
                                'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/transaction', '', true),
                            ]]),
                            'text'  => I18N::T('billing', '修改'),
                            'extra' => 'class="blue view"',
                        ];
                    }
                }
            } else {
                unset($links['refill']);
            }
        }
    }
}
