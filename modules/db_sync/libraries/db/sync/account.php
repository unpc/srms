<?php

class DB_Sync_Account
{
    public static function billing_account_links_edit($e, $account, $links, $mode)
    {
        $me = L('ME');
        if (DB_SYNC::is_module_unify_manage('billing_account')) {
            if ($GLOBALS['preload']['billing.single_department']) {
                if (Db_Sync::is_slave()) {
                    if (Q("{$me}<incharge billing_department billing_account")->total_count() || $me->access('管理所有内容')) {
                        if ($me->is_allowed_to('充值', $account)) {
                            $links['credit_line'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'credit_line',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '信用额度'),
                                'extra' => 'class="blue view"',
                            ];
                        }

                        // 如果没有远程账号，才可以充值
                        if ($account->source == 'local' && !$account->voucher) {
                            $sources = Config::get('billing.sources');
                            if ($me->is_allowed_to('充值', $account)) {
                                $links['refill'] = [
                                    'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                        'q-object' => 'account_credit',
                                        'q-event'  => 'click',
                                        'q-static' => ['account_id' => $account->id],
                                        'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                    ]]),
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view"',
                                ];
                            } elseif (count($sources) && $account->lab->owner->id == $me->id) {
                                $links['refill'] = [
                                    'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                        'q-object' => 'refill_notif',
                                        'q-event'  => 'click',
                                        'q-static' => ['lab_id' => $account->lab->id],
                                        'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                    ]]),
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view"',
                                ];
                            }
                        }
                        // 有远程账号，且用户是当前课题组的pi，可以看到充值按钮，跳转到远程
                        elseif ($account->source != 'local' && $account->voucher && $account->lab->owner->id == $me->id) {
                            $billing_link = Config::get('billing.sources')[$account->source]['http_url'];
                            if ($billing_link) {
                                $links['refill'] = [
                                    'url'   => $billing_link,
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view" target="_blank"',
                                ];
                            }
                        }

                        if ($me->is_allowed_to('扣费', $account)) {
                            $links['deduction'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'account_deduction',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '扣费'),
                                'extra' => 'class="blue view"',
                            ];
                        }

                        if ($me->is_allowed_to('删除', $account)) {
                            $links['delete'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'delete_account',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '删除'),
                                'extra' => 'class="blue"',
                            ];
                        }
                    } else {
                        unset($links['credit_line']);
                        unset($links['refill']);
                        unset($links['deduction']);
                        unset($links['delete']);
                    }
                } else {
                    if (Q("{$me}<incharge billing_department billing_account")->total_count() || $me->access('管理所有内容')) {

                    } else {
                        unset($links['credit_line']);
                        unset($links['refill']);
                        unset($links['deduction']);
                        unset($links['delete']);
                    }
                }
            } else {
                if (
                    Q("{$me}<incharge {$account->department}")->total_count()
                    || $me->access('管理所有内容')
                    || Q("subsite[ref_no={$account->department->site}]<incharge {$me}")->total_count() // 分站管理员
                ) {
                    if (Db_Sync::is_slave()) {
                        if ($me->is_allowed_to('充值', $account)) {
                            $links['credit_line'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'credit_line',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '信用额度'),
                                'extra' => 'class="blue view"',
                            ];
                        }

                        // 如果没有远程账号，才可以充值
                        if ($account->source == 'local' && !$account->voucher) {
                            $sources = Config::get('billing.sources');
                            if ($me->is_allowed_to('充值', $account)) {
                                $links['refill'] = [
                                    'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                        'q-object' => 'account_credit',
                                        'q-event'  => 'click',
                                        'q-static' => ['account_id' => $account->id],
                                        'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                    ]]),
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view"',
                                ];
                            } elseif (count($sources) && $account->lab->owner->id == $me->id) {
                                $links['refill'] = [
                                    'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                        'q-object' => 'refill_notif',
                                        'q-event'  => 'click',
                                        'q-static' => ['lab_id' => $account->lab->id],
                                        'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                    ]]),
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view"',
                                ];
                            }
                        }
                        // 有远程账号，且用户是当前课题组的pi，可以看到充值按钮，跳转到远程
                        elseif ($account->source != 'local' && $account->voucher && $account->lab->owner->id == $me->id) {
                            $billing_link = Config::get('billing.sources')[$account->source]['http_url'];
                            if ($billing_link) {
                                $links['refill'] = [
                                    'url'   => $billing_link,
                                    'text'  => I18N::T('billing', '充值'),
                                    'extra' => 'class="blue view" target="_blank"',
                                ];
                            }
                        }

                        if ($me->is_allowed_to('扣费', $account)) {
                            $links['deduction'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'account_deduction',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '扣费'),
                                'extra' => 'class="blue view"',
                            ];
                        }

                        if ($me->is_allowed_to('删除', $account)) {
                            $links['delete'] = [
                                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                    'q-object' => 'delete_account',
                                    'q-event'  => 'click',
                                    'q-static' => ['account_id' => $account->id],
                                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!billing/account', '', true),
                                ]]),
                                'text'  => I18N::T('billing', '删除'),
                                'extra' => 'class="blue"',
                            ];
                        }
                    }
                } else {
                    unset($links['credit_line']);
                    unset($links['refill']);
                    unset($links['deduction']);
                    unset($links['delete']);
                }
            }
        }
    }
}
