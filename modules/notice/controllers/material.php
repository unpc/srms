<?php

class Material_Controller extends Base_Controller {

    function delete ($id) {
        $material = O('material', $id);

        if (!$material->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');

        // if (!$me->is_allowed_to('删除', $material)) {
        //     URI::redirect('error/401');
        // }

        $material_dir = $material->source_path();

        if ($material->delete()) {
            Log::add(strtr('[notice] %user_name[%user_id] 删除了获奖:%material[%material_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%material'=> $material->name, '%material_id'=> $material->id]), 'journal');
            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('notice','素材信息删除成功!'));
            File::rmdir($material_dir);
        }
        else {
            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('notice','素材信息删除失败!'));
        }

        URI::redirect(URI::url('!notice/play'));
    }

}

class Material_AJAX_Controller extends AJAX_Controller {
}
