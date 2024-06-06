<?php

class Subsite_Model extends Presentable_Model
{

    const UNCONNECTED = 0; // 未关联
    const CONNECTED   = 1; // 已关联

    public static $status = [
        self::UNCONNECTED => '未关联',
        self::CONNECTED   => '已关联',
    ];

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        $me    = L('ME');
        switch ($mode) {
            case 'index':
            default:
                if ($me->is_allowed_to('编辑', $this)) {
                    $links['edit'] = [
                        'url'   => null,
                        'text'  => I18N::T('db_sync', '修改'),
                        'extra' => 'class="blue" q-object="edit_subsite" q-event="click" q-src="' . H(URI::url('!db_sync/subsite')) . '" q-static="id=' . $this->id . '"',
                    ];
                }

                if ($this->status == self::UNCONNECTED) {
                    if ($me->is_allowed_to('删除', $this)) {
                        $links['delete'] = [
                            'url'   => H(URI::url('!db_sync/subsite/delete.' . $this->id)),
                            'text'  => I18N::T('db_sync', '删除'),
                            'extra' => 'class="blue" onclick="javascript:return confirm(\'确认要删除该条分站点信息吗，删除后不可恢复！\')"',
                        ];
                    }
                }
        }

        return (array) $links;
    }

}
