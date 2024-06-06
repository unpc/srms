<?php

class DB_Sync_Department
{
    public static function extra_site_column($e, $form, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('billing_department')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 55,
            ];

            if ($form['dtstart_check'] && $form['dtend_check']) {
                $form['date'] = H(date('Y/m/d', $form['dtstart'])) . '~' . H(date('Y/m/d', $form['dtend']));
            } elseif ($form['dtstart_check']) {
                $form['date'] = H(date('Y/m/d', $form['dtstart'])) . '~' . I18N::T('eq_charge', '最末');
            } elseif ($form['dtend_check']) {
                $form['date'] = I18N::T('eq_charge', '最初') . '~' . H(date('Y/m/d', $form['dtend']));
            }

            $columns['date'] = [
                'invisible' => true,
                'nowrap'    => true,
                'title'     => I18N::T('db_sync', '时间'),
                'filter'    => [
                    'form'  => V('db_sync:filters/date', [
                        'dtstart_check' => $form['dtstart_check'],
                        'dtstart'       => $form['dtstart'],
                        'dtend_check'   => $form['dtend_check'],
                        'dtend'         => $form['dtend'],
                    ]),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend,dtstart_check,dtend_check',
                ],
                'nowrap'    => true,
                'weight'    => 56,
            ];
        }

        $columns['income'] = [
            'title'  => I18N::T('db_sync', '累计收入'),
            'nowrap' => true,
            'weight' => 40,
        ];
        $columns['outcome'] = [
            'title'  => I18N::T('db_sync', '累计支出'),
            'nowrap' => true,
            'weight' => 41,
        ];
        $columns['balance'] = [
            'title'  => I18N::T('db_sync', '累计余额'),
            'nowrap' => true,
            'weight' => 42,
        ];
    }

    public static function extra_site_row($e, $row, $department, $db = null, $form = [])
    {
        if (DB_SYNC::is_module_unify_manage('billing_department')) {
            $row['site'] = H(Config::get('site.map')[$department->site]) ?: '--';
        }

        $income_selector  = "({$department}<department,lab[hidden=0]) billing_account<account billing_transaction[income!=0]";
        $outcome_selector = "({$department}<department,lab[hidden=0]) billing_account<account billing_transaction[outcome!=0]";
        if ($form['dtstart_check'] && $form['dtend_check']) {
            $dtstart = Date::get_day_start($form['dtstart']);
            $dtend   = Date::get_day_end($form['dtend']);
            $income_selector .= "[ctime={$dtstart}~{$dtend}]";
            $outcome_selector .= "[ctime={$dtstart}~{$dtend}]";
        } elseif ($form['dtstart_check']) {
            $dtstart = Date::get_day_start($form['dtstart']);
            $income_selector .= "[ctime>{$dtstart}]";
            $outcome_selector .= "[ctime>{$dtstart}]";
        } elseif ($form['dtend_check']) {
            $dtend = Date::get_day_start($form['dtend']);
            $income_selector .= "[ctime<{$dtend}]";
            $outcome_selector .= "[ctime<{$dtend}]";
        }

        $income         = intval(Q($income_selector, null, $db)->sum('income'));
        $outcome        = intval(Q($outcome_selector, null, $db)->sum('outcome'));
        $row['income']  = '<span>' . $income . '</span>';
        $row['outcome'] = '<span>' . $outcome . '</span>';
        $row['balance'] = '<span>' . ($income - $outcome) . '</span>';
    }

    public static function department_search_filter_submit($e, $form, $selector, $pre_selectors)
    {
        if (DB_SYNC::is_master() && $form['site']) {
            $selector .= "[site={$form['site']}]";
            $e->return_value = $selector;
            return false;
        }

        $e->return_value = $selector;
        return true;
    }

    public static function edit_info_view($e, $form, $department)
    {
        if (DB_SYNC::is_module_unify_manage('billing_department')) {
            $e->return_value .= V('db_sync:public/site', [
                'form'   => $form,
                'object' => $department,
            ]);
        }
    }

    public static function billing_department_links_edit($e, $department, $links, $mode)
    {
        $me = L('ME');
        if ($GLOBALS['preload']['billing.single_department']) {
            if (Q("{$me}<incharge billing_department")->total_count() || $me->access('管理所有内容')) {
                // 负责人或者不中心管理员可以编辑
            } else {
                unset($links['edit']);
            }
        } else {
            if (Q("subsite[ref_no={$department->site}]<incharge {$me}")->total_count() || $me->access('管理所有内容')) {

            } else {
                unset($links['edit']);
            }
        }

    }

}
