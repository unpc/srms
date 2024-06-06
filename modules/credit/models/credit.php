<?php

// 用户信用分
class Credit_Model extends ORM_Model
{
    public function save($overwrite = false)
    {
        if (!$this->ctime) {
            $this->ctime = Date::time();
        }
        $this->mtime = Date::time();
        return parent::save($overwrite);
    }

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        $me    = L('ME');
        /*
        NO.TASK#274(guoping.zhang@2010.11.27)
        成果管理模块应用权限判断新规则
         */
        switch ($mode) {
            case 'view':
            case 'index':
            default:
                if ($me->is_allowed_to('解禁', $this)) {
                    $links['thaw'] = [
                        'url'   => null,
                        'text'  => I18N::T('achievements', '解禁'),
                        'extra' => 'class="blue" q-src="' . URI::url('!credit') . '" q-static="id=' . $this->id . '" q-event="click" q-object="thaw"',
                    ];
                }
                if ($me->is_allowed_to('查看信用记录', $this->user)) {
                    $links['view'] = [
                        'url'   => $this->user->url('credit_record'),
                        'text'  => I18N::T('credit', '查看'),
                        'extra' => 'class="blue"',
                    ];
                }
        }
        return (array) $links;
    }
}
