<?php

class Research_Model extends Presentable_Model
{
    protected $object_page = [
        'view'=>'!research/research/index.%id[.%arguments]',
        'edit'=>'!research/research/edit.%id[.%arguments]',
    ];
    function & links($mode = 'index') {
        if (!$this->id) return [];

        $links = new ArrayIterator;
        $me = L('ME');

        switch ($mode) {
            case 'view':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('research', '修改'),
                        'extra' =>'class="button button_edit"',
                    ];
                }
            break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('research', '修改'),
                        'extra' =>'class="blue"',
                    ];
                }
                if ($me->is_allowed_to('添加使用记录', $this)) {
                    $links['add_record'] = [
                        'url' => '#',
                        'text' => I18N::T('research', '添加记录'),
                        'extra' =>'class="blue view src:'.URI::url('!research/research/index.'.$this->id.'.records').' object:add_record event:click static:id=' . $this->id . '&oname=research"',
                    ];
                }
            break;
        }

        return (array) $links;
    }

    function save($overwrite = FALSE)
    {
        if(!$this->ctime) $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}
