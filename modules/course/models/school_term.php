<?php

class School_Term_Model extends Presentable_Model
{
    const TYPE_SPRING_TERM = 1;
    const TYPE_AUTUMN_TERM = 2;

    static $TYPES = [
        self::TYPE_SPRING_TERM => '春季学期',
        self::TYPE_AUTUMN_TERM => '秋季学期'
    ];

    public function course_sessions () {
        $course_sessions = [];
        foreach (Q("course_session[type=$this->term]") as $course_session) {
            $course_sessions[$course_session->sesssion] = $course_session;
        }
        return $course_sessions;
    }

    public function course_session ($session) {
        return Q("course_session[term=$this->term][session=$session]")->current();
    }
    
    public function save($overwrite = false)
    {
        $dtstart = Date::get_week_start($this->dtstart);
        $weeks = [];
        $num = 1;
        while ($dtstart <= $this->dtend) {
            $week = ['num' => $num];
            $week['dtstart'] = $dtstart ;
            $dtstart = Date::next_time($dtstart, 7, 'd');
            $week['dtend'] = $dtstart - 1;
            $weeks[] = $week;
            $num++;
        }
        if ($this->weeks_md5 || md5(json_encode($weeks)) != $this->weeks_md5) {
            $this->weeks_md5 = md5(json_encode($weeks));
        } else {
            $weeks = [];
        }
        $result = parent::save($overwrite);
        if (count($weeks)) {
            Q("term_week[school_term=$this]")->delete_all();
            foreach ($weeks as $week) {
                $term_week = O("term_week");
                $term_week->school_term = $this;
                $term_week->week = $week['num'];
                $term_week->dtstart = $week['dtstart'];
                $term_week->dtend = $week['dtend'];
                $term_week->save();
            }
        }
        return $result;
    }

    // 获取当前学期
    static function current($time = 0) {
        // 春季学期、夏季学期和秋季学期
        if (!$time) $time = Date::time();
        $current = Q("school_term[dtstart~dtend=$time]")->current();
		if (!$current->id) {
            return $current;
		}
		return $current;
	}

    public function &links($mode = 'edit')
    {
        $me = L('ME');
        $links = new ArrayIterator;
        switch ($mode) {
            case 'edit':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'tip'   => I18N::T('course', '修改'),
                        'text'  => I18N::T('course', '修改'),
                        'extra' => 'q-object="edit_school_term" q-event="click" q-src="' . URI::url('!course/admin') .
                            '" q-static="' . H(['id'=>$this->id]) .
                            '" class="blue"'
                    ];
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'tip'   => I18N::T('course', '删除'),
                        'text'  => I18N::T('course', '删除'),
                        'extra' => 'q-object="delete_school_term" q-event="click" q-src="' . URI::url('!course/admin') .
                            '" q-static="' . H(['id'=>$this->id]) .
                            '" class="blue"'
                    ];
                }
                break;
        }
        return (array) $links;
    }
}
