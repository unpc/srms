<?php

class Workflow_Model extends Presentable_Model
{
    public function create()
    {
        if (!$this->source->id) {
            return;
        }
        $me = L('ME');

        $config = Config::get("workflow.{$this->source->name()}", []);
        $steps = $config['steps'];
        $flag = (string)current(array_keys($steps));

        //之前定制化内容逻辑保留
        $this->user = $this->source->user ;
        $this->dtstart = $this->source->dtstart ? : 0;
        $this->dtend = $this->source->dtend ? : 0;
        $this->flag = $flag;
        $ret = $this->save();

        // create 同时必须生成一个node来匹配
        if ($ret) {
            $node = O('workflow_node');
            $node->workflow = $this;
            $node->auditor = $this->user;
            $node->flag = $flag;
            $node->dtstart = Date::time();
            $node->save();

            Event::trigger("workflow_model.after.create", $this);
        }

        return $this;
    }

    public function pass($auto = false)
    {
        $me = L('ME');

        $config = Config::get("workflow.{$this->source->name()}", []);
        $steps = $config['steps'];
        $can_pass = true;
        $check = $steps[$this->flag]['action']['pass']['check'] ?: [];

        if ($check['callback_func']) {
            $can_pass = call_user_func($check['callback_func'], $this);
        }

        if ($can_pass && $check['hooks']) {
            $can_pass = Event::trigger($check['hooks'], $this);
        }

        $flag = $this->flag;

        if ($can_pass) {
            $this->flag = $steps[$this->flag]['action']['pass']['next'];
            if ($this->save()) {
                // 关闭上一个节点
                $prev_node = O('workflow_node', ['workflow' => $this, 'flag' => $flag]);
                $prev_node->action = 'pass';
                $prev_node->dtend = Date::time();
                $prev_node->auditor = L('ME');
                $prev_node->save();

                if ($this->flag != 'done') {
                    // 流程没有结束，则开启下一个节点
                    $node = O('workflow_node');
                    $node->workflow = $this;
                    $node->auditor = L('ME');
                    $node->flag = $this->flag;
                    $node->dtstart = Date::time();
                    $node->save();
                }

                Event::trigger("workflow_model.after.pass", $this);
                return $this;
            }
        }
        return false;
    }

    public function reject($auto = false)
    {
        $me = L('ME');
        $config = Config::get("workflow.{$this->source->name()}", []);
        $steps = $config['steps'];
        $can_reject = true;
        $check = $steps[$this->flag]['action']['reject']['check'] ?: [];

        if ($check['callback_func']) {
            $can_pass = call_user_func($check['callback_func'], $this);
        }

        if ($can_pass && $check['hooks']) {
            $can_pass = Event::trigger($check['hooks'], $this);
        }

        $flag = $this->flag;

        if ($can_reject) {
            $this->flag = $steps[$this->flag]['action']['reject']['next'];
            if ($this->save()) {
                // 关闭上一个节点
                $prev_node = O('workflow_node', ['workflow' => $this, 'flag' => $flag]);
                $prev_node->action = 'reject';
                $prev_node->dtend = Date::time();
                $prev_node->auditor = L('ME');
                $prev_node->save();

                if ($this->flag != 'reject') {
                    // 流程没有结束，则开启下一个节点
                    $node = O('workflow_node');
                    $node->workflow = $this;
                    $node->auditor = L('ME');
                    $node->flag = $this->flag;
                    $node->dtstart = Date::time();
                    $node->save();
                }
                
                Event::trigger("model_approval.after.reject", $this);
                return $this;
            }
        }
        return false;
    }

    public function & links($mode = 'index')
    {
        $me = L('ME');
        $config = Config::get("workflow.{$this->source->name()}", []);
        $steps = $config['steps'];
        $links = [];
    
        if (count((array) $steps[$this->flag]['action'])) {
            foreach ($steps[$this->flag]['action'] as $name => $action) {
                $links[$name] = [
                    'text' => I18N::T('workflow', $action['title']),
                    'extra' => 'q-object='.$name.' q-event="click" q-src="' . H(URI::url('!workflow/index')) .
                        '" q-static="' . H(['workflow_id' => $this->id]) .
                        '" class="blue"',
                ];
            }
        }
        $links['view_approval'] = [
            'text' => I18N::T('workflow', '查看审批'),
            'extra' => 'q-object="view" q-event="click" q-src="' . H(URI::url('!workflow/index')) .
                '" q-static="' . H(['workflow_id' => $this->id]) .
                '" class="blue"',
        ];
        return $links;
    }

    public function save($overwrite = false)
    {
        if (!$this->ctime) {
            $this->ctime = Date::time();
        }
        return parent::save($overwrite);
    }
}
