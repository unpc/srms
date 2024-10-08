<?php

class School_Term_Model extends Presentable_Model
{
    const TYPE_SPRING_TERM = 1;
    const TYPE_AUTUMN_TERM = 2;

    static $TYPES = [
        self::TYPE_SPRING_TERM => '春季学期',
        self::TYPE_AUTUMN_TERM => '秋季学期'
    ];

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
