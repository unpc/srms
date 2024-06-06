<?php

class Approval_Model extends Presentable_Model
{
    public function create()
    {
        if (!$this->source->id) {
            return;
        }
        $me = L('ME');
        //判断是仪器是否需要审核
        $ret = Event::trigger("model_approval.create", $this->source);
        if ($ret) {
            return false;
        }
        $flow = Config::get('flow.'.$this->source->name());
        $flag = (string)current(array_keys($flow));

        if($this->source->name() == 'eq_reserv' && $this->is_skip) $flag = 'approve_incharge';
        if ($this->source->name() == 'eq_sample') {
            $lab = $this->source->lab;
            if (!self::sample_flow_lab() || !$lab->sample_approval || $this->is_skip) {
                $flag = 'approve_incharge';
            }
        }

        //之前定制化内容逻辑保留
        if ($this->flag == 'approve') {
            Event::trigger("model_approval.create.once", $approval);
        }
        $this->user = $this->source->name() == 'eq_sample' ? $this->source->sender : $this->source->user ;
        $this->equipment = $this->source->equipment;
        $this->dtstart = $this->source->dtstart ? : 0;
        $this->dtend = $this->source->dtend ? : 0;
        $this->dtsubmit = $this->source->dtsubmit ? : 0;
        $this->count = $this->source->count ? : 0;
        $this->flag = $flag;
        $this->save();

        Event::trigger("model_approval.after.created", $this, 'create');
        return $this;
    }

    public function pass($auto = false)
    {
        $me = L('ME');
        $ret = Event::trigger("model_approval.pass", $this);
        if (!$ret) {
            $flow = Config::get("flow.{$this->source->name()}");
            $this->flag = $flow[$this->flag]['action']['pass']['next'];
            $this->auto = $auto ? 1 : 0;
            if ($this->save()) {
                Event::trigger("model_approval.after.pass", $this);
                return $this;
            }
        }
        return false;
    }

    public function reject($auto = false)
    {
        $me = L('ME');
        $ret = Event::trigger("model_approval.reject", $this);
        if (!$ret) {
            $flow = Config::get("flow.{$this->source->name()}");
            $this->flag = $flow[$this->flag]['action']['reject']['next'];
            $this->auto = $auto ? 1 : 0;
            if ($this->save()) {
                Event::trigger("model_approval.after.reject", $this);
                return $this;
            }
        }
        return false;
    }

    public function & links($mode = 'index')
    {
        $me = L('ME');
        $flow = Config::get("flow.{$this->source->name()}");
        $links = [];
    
        if (count((array) $flow[$this->flag]['action']) && $me->can_approval($this->flag, $this)) {
            foreach ($flow[$this->flag]['action'] as $name => $action) {
                $links[$name] = [
                    'text' => I18N::T('approval', $action['title']),
                    'extra' => 'q-object='.$name.' q-event="click" q-src="' . H(URI::url('!approval_flow/index')) .
                        '" q-static="' . H(['approval_id' => $this->id]) .
                        '" class="blue"',
                ];
            }
        }
        $links['view_approval'] = [
            'text' => I18N::T('approval', '查看审批'),
            'extra' => 'q-object="view" q-event="click" q-src="' . H(URI::url('!approval_flow/index')) .
                '" q-static="' . H(['approval_id' => $this->id]) .
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

    public static function sample_flow_lab()
    {
        return in_array('eq_sample', Config::get('approval.modules')) 
               && (Config::get('flow.eq_sample')['approve_pi'] || Config::get('flow.eq_sample')['approve_project']);
    }
}
