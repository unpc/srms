<?php

class Broadcast_Controller extends Base_Controller {

    function delete ($id) {
        $broadcast = O('broadcast', $id);

        if (!$broadcast->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');

        // if (!$me->is_allowed_to('删除', $material)) {
        //     URI::redirect('error/401');
        // }

        if ($broadcast->delete()) {
            Log::add(strtr('[notice] %user_name[%user_id] 删除了播单:%broadcast[%broadcast_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%broadcast'=> $broadcast->name, '%broadcast_id'=> $broadcast->id]), 'journal');
            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('notice','播单信息删除成功!'));
        }
        else {
            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('notice','播单信息删除失败!'));
        }

        URI::redirect(URI::url('!notice/play.list'));
    }

}

class Material_AJAX_Controller extends AJAX_Controller {
}
