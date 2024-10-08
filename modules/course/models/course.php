<?php

class Course_Model extends Presentable_Model
{
    public function &links($mode = 'edit')
    {
        $links = new ArrayIterator;
        switch ($mode) {
            case 'edit':
                if (L('ME')->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'tip'   => I18N::T('course', '修改'),
                        'text'  => I18N::T('course', '修改'),
                        'extra' => 'q-object="edit" q-event="click" q-src="' . URI::url('!course/index') .
                            '" q-static="' . H(['id'=>$this->id]) .
                            '" class="blue"'
                    ];
                }
                break;
        }
        return (array) $links;
    }

}
