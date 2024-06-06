<?php
class Billing_API
{
    public static function transactions_get($e, $params, $data, $query)
    {
        $user = L('gapperUser');

        $selector = "billing_transaction";
        if ($query['departmentId$']) {
            $ids = array_map(function ($i) {
                $department = O('billing_department',  (int)$i);
                $user = L('gapperUser');
                if (!$department->id || !$user->is_allowed_to('列表收支明细', $department)) {
                    throw new Exception('Forbbiden', 403);
                }
                return (int)$department->id;
            }, explode(',', $query['departmentId$']));
            if (!count($ids)) {
                throw new Exception('departmentId cannot be empty', 404);
            }
            $selector = "billing_department[id=" . join(",", $ids) . "]<department billing_account<account " . $selector;
        } elseif ($query['accountId$']) {
            $ids = array_map(function ($i) {
                $account =  O('billing_account', (int)$i);
                $user = L('gapperUser');
                if (!$account->id || !$user->is_allowed_to('列表收支明细', $account)) {
                    throw new Exception('Forbbiden', 403);
                }
                return (int)$account->id;
            }, explode(',', $query['accountId$']));
            if (!count($ids)) {
                throw new Exception('accountId cannot be empty', 404);
            }
            $selector .= "[account_id=" . join(",", $ids) . "]";
        } elseif ($query['labId$']) {
            $ids = array_map(function ($i) {
                $lab = O('lab',  (int)$i);
                $user = L('gapperUser');
                if (!$lab->id || !$user->is_allowed_to('列表收支明细', $lab)) {
                    throw new Exception('Forbbiden', 403);
                }
                return (int)$lab->id;
            }, explode(',', $query['labId$']));
            if (!count($ids)) {
                throw new Exception('labId cannot be empty', 404);
            }
            $selector = "lab[id=" . join(",", $ids) . "] billing_account<account " . $selector;
        } else {
            throw new Exception('Bad Request', 400);
        }

        if (isset($query['startTime']) && intval($query['startTime'])) {
            $dtstart = intval($query['startTime']);
            $selector .= "[ctime>={$dtstart}]";
        }
        if (isset($query['endTime']) && intval($query['endTime'])) {
            $dtend = intval($query['endTime']);
            $selector .= "[ctime>0][ctime<={$dtend}]";
        }

        if (isset($query['type'])) {
            switch ($query['type']) {
                case 'all':
                default:
                    break;
                case 'income':
                    $selector .= "[income!=0]";
                    break;
                case 'income_local':
                    $selector .= "[income!=0][transfer=0]";
                    break;
                case 'income_transfer':
                    $selector .= "[income!=0][transfer>0]";
                    break;
                case 'outcome':
                    $selector .= "[outcome!=0]";
                    break;
                case 'outcome_local':
                    $selector .= "[outcome!=0][transfer=0][manual>0]";
                    break;
                case 'outcome_use':
                    $selector .= "[outcome!=0][transfer=0][manual=0]";
                    break;
                case 'outcome_transfer':
                    $selector .= "[outcome!=0][transfer>0]";
                    break;
            }
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(ctime D)";
        $transactions = [];
        foreach (Q("$selector") as $transaction) {
            $transactions[] = self::transaction_format($transaction);
        }
        $e->return_value = ["total" => $total, "items" => $transactions];
    }

    public static function transaction_format($transaction)
    {
        return [
            'id' => (int)$transaction->id,
            'user' => [
                'id' => (int)$transaction->user->id,
                'name' => $transaction->user->name,
            ],
            'department' => [
                'id' => (int)$transaction->account->department->id,
                'name' => $transaction->account->department->name,
            ],
            'account' => [
                'id' => (int)$transaction->account->id,
                'name' => $transaction->account->lab->name,
            ],
            'lab' => [
                'id' => (int)$transaction->account->lab->id,
                'name' => $transaction->account->lab->name,
            ],
            'income' => round($transaction->income, 2),
            'outcome' => round($transaction->outcome, 2),
            'description' => strip_tags((string)$transaction->description()),
            'ctime' => $transaction->ctime
        ];
    }

    public static function accounts_get($e, $params, $data, $query)
    {
        $user = L('gapperUser');

        $selector = "billing_account";
        if ($query['departmentId']) {
            $department = O('billing_department', $query['departmentId']);
            if (!$department->id || !$user->is_allowed_to('列表财务账号', $department)) {
                throw new Exception('Forbbiden', 403);
            }
            $selector = "{$department}<department {$selector}";
        } elseif ($query['labId$']) {
            $ids = array_map(function ($i) {
                $lab = O('lab',  (int)$i);
                $user = L('gapperUser');
                if (!$lab->id || !$user->is_allowed_to('查看财务情况', $lab)) {
                    throw new Exception('Forbbiden', 403);
                }
                return (int)$lab->id;
            }, explode(',', $query['labId$']));
            if (!count($ids)) {
                throw new Exception('labId cannot be empty', 404);
            }
            $selector = "lab[id=" . join(",", $ids) . "] {$selector}";
        } else {
            if ($user->is_allowed_to('列表财务账号', 'billing_department')) {
                throw new Exception('Forbbiden', 403);
            }
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(id D)";
        $accounts = [];
        foreach (Q("$selector") as $account) {
            $accounts[] = self::account_format($account);
        }
        $e->return_value = ["total" => $total, "items" => $accounts];
    }

    public static function stat_get($e, $params, $data, $query)
    {
        $me = L('gapperUser');
        $labs = Q("$me lab");
        foreach ($labs as $lab) {
            $billing_accounts = Q("$lab billing_account");
            $effective_accounts = $billing_accounts->find('[balance>0]');
            $owing_accounts = $billing_accounts->find('[balance<0]');
            $balance += Q("$lab billing_account")->find('[balance>0]')->sum('balance');
            $countowing += Q("$lab billing_account")->find('[balance<0]')->sum('balance');
        }
        $e->return_value = ['countowing' => abs($countowing), 'balance' => $balance];
    }

    public static function account_format($account)
    {
        return [
            'id' => (int)$account->id,
            'department' => [
                'id' => (int)$account->department->id,
                'name' => $account->department->name,
            ],
            'lab' => [
                'id' => (int)$account->lab->id,
                'name' => $account->lab->name,
            ],
            'income_remote' => round($account->income_remote, 2),
            'income_remote_confirmed' => round($account->income_remote_confirmed, 2),
            'income_local' => round($account->income_local, 2),
            'income_transfer' => round($account->income_transfer, 2),
            'outcome_remote' => round($account->outcome_remote, 2),
            'outcome_local' => round($account->outcome_local, 2),
            'outcome_transfer' => round($account->outcome_transfer, 2),
            'outcome_use' => round($account->outcome_use, 2),
            'balance' => round($account->balance, 2),
            'credit_line' => round($account->credit_line, 2),
        ];
    }

    public static function departments_get($e, $params, $data, $query)
    {
        $user = L('gapperUser');

        if ($user->is_allowed_to('列表财务账号', 'billing_department')) {
            throw new Exception('Forbbiden', 403);
        }

        $selector = "billing_department";
        if ($query['name']) {
            $name = Q::quote($query['name']);
            $selector .= "[name={$name}]";
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(id D)";
        $departments = [];
        foreach (Q("$selector") as $department) {
            $departments[] = self::department_format($department);
        }
        $e->return_value = ["total" => $total, "items" => $departments];
    }

    public static function department_format($department)
    {
        $tag = $department->group;
        $groups = $tag->id ? [$tag->id => $tag->name] : [];
        while ($tag->parent->id && $tag->parent->root->id) {
            $groups += [$tag->parent->id => $tag->parent->name];
            $tag = $tag->parent;
        }
        return [
            'id' => (int)$department->id,
            'name' => $department->name,
            'group' => $groups,
            'description' => $department->description
        ];
    }
}
