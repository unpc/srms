<?php

class API_Sample_Approval {
    
    function authorize($clientId, $clientSecret)
    {
        $approval = Config::get('rpc.approval');
        if ($approval['client_id'] == $clientId && 
            $approval['client_secret'] == $clientSecret) {
            $_SESSION['approval.client_id'] = $clientId;
            return session_id();
        }
        
        return false;
    }
    
    private function _checkAuth()
    {
        $approval = Config::get('rpc.approval');
        if (!isset($_SESSION['approval.client_id']) || 
            $approval['client_id'] != $_SESSION['approval.client_id']) {
            throw new API_Exception('Access denied.', 401);
        }
    }
    
    public function sync_sample($data) {
        $status = [
            EQ_Sample_model::STATUS_TESTED,
            Sample_Approval_Model::STATUS_OFFICE,
            Sample_Approval_Model::STATUS_PLATFORM,
            Sample_Approval_Model::STATUS_ACCESS,
        ];
        
        if (in_array($data['status'], $status)) {
            $sample = O('eq_sample', (int)$data['id']);
//            if (!$sample->equipment->sample_approval_enable) return;//先注释掉，防止老师来回开关送样审核
            $sample->status = $data['status'];
            //TODO 对空对象的处理
            if ($data['firTrial']) $sample->fir_trial = O('user', (int)$data['firTrial']);
            if ($data['firTrial']) $sample->sec_trial = O('user', (int)$data['secTrial']);
            if ($data['status'] == EQ_Sample_model::STATUS_TESTED) $sample->is_locked = 0;
            return $sample->save();
        }
    }

    public function add_remark($data) {
        $remark = O('sample_remark');
        
        $remark->sample = O('eq_sample', $data['id']);
        $remark->content = $data['content'];
        $remark->type = $data['type'];
        $remark->time = strtotime($data['time']);
        
        return $remark->save();
    }

    public function get_attachments ($user, $id) {
        if (!Module::is_installed('nfs')) return [];

        $user = O('user', $user);
        $sample = O('sample', $id);
        $path = '';
        $path_type = 'attachments';
		$full_path = NFS::get_path($sample, $path, $path_type, TRUE);

        if (NFS::user_access($user, '列表文件', $object, ['path' => $path . '/foo', 'type' => $path_type])) {
            return NFS::file_list($full_path, $path);
        }

        return [];
    }
}
