<?php
class Equipment_Approval_API
{

    public static function task_format($task)
    {
        $ret = [
            'id' => (int)$task->id,
            'flag' => $task->flag,
            'equipment' => [
                'id' => (int)$task->equipment->id,
                'name' => $task->equipment->name,
                'icon' => [
                    'original' => $task->equipment->icon_url($task->equipment->icon_file('real') ? 'real' : 128),
                    '32×32' => $task->equipment->icon_url('32')
                ],
            ],
            'user' => [
                'id' => (int)$task->user->id,
                'name' => $task->user->name,
            ],
            'source' => [
                'type' => $task->source->name(),
                'id' => (int)$task->source->id,
            ],
            'ctime' => (int)$task->ctime
        ];
        switch ($task->source->name()) {
            case 'eq_reserv':
                $ret['source']['dtstart'] = $task->source->dtstart ? : $task->dtstart;
                $ret['source']['dtend'] = $task->source->dtend ? : $task->dtend;
                $ret['source']['description'] = $task->source->component->description;
                break;
            case 'eq_sample':
                $ret['source']['dtsubmit'] = $task->source->dtsubmit;
                $ret['source']['count'] = $task->source->count;
                $ret['source']['description'] = $task->source->description;
                break;
        }
        return $ret;
    }
    public static function approval_tasks_get($e, $params, $data, $query)
    {
        $user = L("gapperUser");

        $convert = function ($key) {
            $ret = array_keys(array_filter(Config::get($key, []), function ($item) {
                return isset($item['action']);
            }));
            return $ret;
        };

        if ($query['state'] == 'unassigned') {
            $states = join(',', array_unique(array_merge(
                $convert('flow.eq_reserv'),
                $convert('flow.eq_sample'),
                $convert('flow.ue_training')
            )));
            // 待我审核
            $selector = "{$user}<incharge equipment approval[flag={$states}]";
        } elseif ($query['state'] == 'completed') {
            // 我已审核
            $approvedIds = Q("{$user}<auditor approved[flag=done,rejected]")->to_assoc('id', 'source_id');
            $selector = "approval[flag=done,rejected][id=" . join(",", $approvedIds) . "]";
        } else {
            // 我申请的
            $selector = "approval";
        }

        if (isset($query['type'])) {
            if (!in_array($query['type'], ['eq_reserv', 'eq_sample', 'ue_training'])) {
                throw new Exception('type not found', 404);
            }
            $selector .= "[source_name={$query['type']}]";
        }

        if (isset($query['userId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['userId$']));
            if (!count($ids)) {
                throw new Exception('userId cannot be empty', 404);
            }
            // 我申请的
            $selector .= "[user_id=" . join(",", $ids) . "]";
        }

        if (isset($query['taskId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['taskId$']));
            if (!count($ids)) {
                throw new Exception('taskId cannot be empty', 404);
            }
            $selector .= "[id=" . join(",", $ids) . "]";
        }

        error_log($selector);
        $total = Q("$selector")->total_count();

        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(ctime D)";

        $tasks = [];
        foreach (Q("$selector") as $task) {
            $tasks[] = self::task_format($task);
        }
        $e->return_value = ["total" => $total, "items" => $tasks];
    }

    public static function approval_task_complete_post($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        $task = O('approval', $params[0]);
        if (!$task->id) {
            throw new Exception('task not found', 404);
        }

        switch ($data['variables']['action']) {
            case 'pass':
                if (!$user->can_approval('done')) {
                    throw new Exception('Forbidden', 403);
                }
                $task->pass();
                break;
            case 'reject':
                if (!$user->can_approval('rejected')) {
                    throw new Exception('Forbidden', 403);
                }
                $task->reject();
                break;
        }
        $e->return_value = self::task_format($task);
    }

    public static function approval_task_get($e, $params, $data, $query)
    {
        $user = L("gapperUser");

        if (!isset($query['source'])) {
            throw new Exception('source not found', 404);
        }

        $source = explode("#", $query['source']);
        if (count($source) < 2) {
            throw new Exception('source not found', 404);
        }

        switch ($source[0]) {
            case 'reserv':
                $object = o('eq_reserv', $source[1]);
                break;
            case 'sample':
                $object = o('eq_sample', $source[1]);
                break;
            case 'log':
                $object = o('eq_record', $source[1]);
                break;
            default:
                throw new Exception('source not found', 404);
        }

        if (!$object->id) {
            throw new Exception('source not found', 404);
        }

        $task = O('approval', ['source' => $object]);
        if (!$task->id) {
            $e->return_value = ['task' => (object)[]];
            return;
        }

        $e->return_value = ['task' => self::task_format($task)];
    }

}
