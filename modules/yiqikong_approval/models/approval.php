<?php

class Approval_Model extends Presentable_Model {

    const RESERV_APPROVAL_NONE = 0;
    const RESERV_APPROVAL_PASS = 1;
    const RESERV_APPROVAL_REJECT = 2;

    public function create() {
        if (!$this->source->id) return;
        $me = L('ME');
        $ret = Event::trigger("{$this->source->name()}_approval.create", $this->source);
        // 如果当前预约不需要审批了，将审批记录删除
        if (!$ret) {
            $approval = O('approval', ['source' => $this->source]);
            if ($approval->id) {
                Q("approved[source=$approval]")->delete_all();
                $approval->delete();
            }
        }
        if ($ret) {
            $flow = Config::get('flow.'.$this->source->name());
            $flag = (string)current(array_keys($flow));

            $approval = O('approval', ['source' => $this->source]);
            if (!$approval->id) {
                $approval = O('approval');
            } else {
                if ($approval->flag == 'approve' && !$approval->notice) {
                    Event::trigger("{$this->source->name()}_approval.create.once", $approval);
                    $approval->notice = 1;
                }
            }

            $approval->source = $this->source;
            $approval->user = $this->source->user;
            $approval->equipment = $this->source->equipment;
            $approval->dtstart = $this->source->dtstart;
            $approval->dtend = $this->source->dtend;
            // 关联bug15476 由于表结构设计为approval存dtstart、dtend，
            // 不得不在source保存时再进入approval create
            // 如果此时业务逻辑为审批通过，approval的flag在pass变成done后，又在此处会变成approe，故加if处理
            if (!$approval->flag) $approval->flag = $flag;

            //无需审核
            $untro = O('yiqikong_approval_uncontrol',['equipment'=>$this->source->equipment,'approval_type' => 'eq_reserv']);
            if(Approval_Access::check_user($untro, $approval->user)){
                $approval->flag = 'done';
            }

            if ($approval->save()) {
                return $this;
            }
        }
        return FALSE;
    }

    public function pass() {
        $me = L('ME');
        $ret = Event::trigger("orm_approval.pass {$this->source->name()}_approval.pass", $this);
        if (!$ret) {
            $flow = Config::get('flow.'.$this->source->name());
            $this->flag = $flow[$this->flag]['action']['pass']['next'];
            if ($this->save()) {
                Event::trigger("orm_approval.pass {$this->source->name()}_approval.after.pass", $this);
                return $this;
            }
        }
        return FALSE;
    }

    public function reject() {
        $me = L('ME');
        $ret = Event::trigger("orm_approval.reject {$this->source->name()}_approval.reject", $this);
        if (!$ret) {
            $flow = Config::get('flow.'.$this->source->name());
            $this->flag = $flow[$this->flag]['action']['reject']['next'];
            if ($this->save()) {
                Event::trigger("orm_approval.reject {$this->source->name()}_approval.after.reject", $this);
                return $this;
            }
        }
        return FALSE;
    }

    public function save($overwrite = FALSE) {
        if (!$this->ctime) $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}
