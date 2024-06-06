<?php

class Announce_Model extends Presentable_Model
{
    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;

        switch ($mode) {
            case 'index':
            default:
                $me = L('ME');
                // if($me->is_allowed_to('修改','announce')){
                //     $links['edit'] = array(
                //         'url' => NULL,
                //         'text' => I18N::T('announces', '修改'),
                //         'extra' => 'class="blue" q-event="click" q-object="edit_announce"'.
                //         ' q-static="'.H(array('id'=>$this->id)).
                //         '" q-src="'.URI::url("!announces/announce").'"',
                //             );
                // }
                if ($me->is_allowed_to('删除', 'announce')) {
                    $links['delete'] = [
                        'url'   => null,
                        'tip'   => I18N::T('announces', '删除'),
                        'text'  => '',
                        'extra' => 'class="blue" q-event="click" q-object="delete_announce"' .
                        ' q-static="' . H(['a_id' => $this->id]) .
                        '" q-src="' . URI::url("!announces/all") . '"',
                    ];
                }
                break;
        }

        Event::trigger('announce.links', $this, $links, $mode);
        return (array) $links;

    }

    public function delete()
    {
        if (parent::delete()) {
            $db  = Database::Factory();
            $sql = "DELETE FROM user_announce WHERE announce_id = " . $this->id;
            $db->query($sql);
            return true;
        } else {
            return false;
        }

    }
}
