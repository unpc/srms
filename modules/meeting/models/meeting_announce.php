<?php

class Meeting_Announce_Model extends Presentable_Model
{
    public function &links($mode = 'index')
    {
        switch ($mode) {
            case 'index':
            default:
                $me = L('ME');
                if ($me->is_allowed_to('修改公告', $this->meeting)) {
                    $links['edit'] = [
                        'url'   => null,
                        'tip'  => I18N::T('meeting', '修改'),
                        'text'  => '',
                        'extra' => 'class="icon-edit" q-event="click" q-object="edit_announce"' .
                        ' q-static="' . H(['a_id' => $this->id]) .
                        '" q-src="' . URI::url("!meeting/announce") . '"',
                    ];
                }
                if ($me->is_allowed_to('删除公告', $this->meeting)) {
                    $links['delete'] = [
                        'url'   => null,
                        'tip'  => I18N::T('meeting', '删除'),
                        'text'  => '',
                        'extra' => 'class="icon-trash" q-event="click" q-object="delete_announce"' .
                        ' q-static="' . H(['a_id' => $this->id]) .
                        '" q-src="' . URI::url("!meeting/announce") . '"',
                    ];
                }
                break;
        }
        return (array) $links;

    }

    public function delete()
    {
        if ($this->id) {
            foreach (Q("$this<read user") as $user) {
                $user->disconnect($this, 'read');
            }
        }
        return parent::delete();
    }
}
