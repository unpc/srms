<?php

class Technical_Service_Access
{

    public static function is_accessible($e, $name)
    {
        $me = L('ME');
        $role_name = Switchrole::user_select_role();
        $e->return_value = true;
        if ($role_name == '普通用户' && !$me->access('管理所有内容')) {
            $e->return_value = false;
        }

        return FALSE;
    }

    static function user_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '管理服务分类' :
            case '管理服务项目' :
                if ($user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
            default :
                break;
        }
    }

    public static function equipment_ACL($e, $user, $perm_name, $object, $options)
    {
        switch ($perm_name) {

            case '设置服务项目':
                if ($user->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('修改负责仪器的服务项目') && Q("{$object}<incharge {$user}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('修改下属机构仪器的服务项目') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }

    static function project_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '修改' :
            case '列表' :
            case '查看' :
            case '添加' :
                if ($user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
            case '删除' :
                if (Q("service_equipment[project={$object}]")->total_count()) {
                    $e->return_value = false;
                    return false;
                }
                if ($user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                }
                return FALSE;
            default :
                break;
        }
    }

    static function service_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {

            case '查看' :
            case '预约服务' :
                $e->return_value = TRUE;
                break;
            case '导出' :
            case '添加' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务') || $user->access('管理下属机构服务')) {
                    $e->return_value = TRUE;
                }
                break;
            case '修改负责人' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = TRUE;
                }
                break;
            case '修改' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理负责服务') && Q("{$user}<incharge {$object}")->total_count()) {
                    $e->return_value = TRUE;
                }
                break;
            case '删除' :
                if (Q("service_apply[service={$object}]")->total_count()) {
                    $e->return_value = false;
                    return false;
                }
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理负责服务') && Q("{$user}<incharge {$object}")->total_count()) {
                    $e->return_value = TRUE;
                }
                break;
            default :
                break;
        }
    }

    static function apply_ACL($e, $user, $perm, $object, $options)
    {

        $service = $object->service;

        switch ($perm) {

            case '查看' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->service->group)) {
                    $e->return_value = TRUE;
                }
                if (Q("{$user}<incharge {$object->service}")->total_count()) {
                    $e->return_value = TRUE;
                }
                //服务关联机器的机主
                if (Q("service_equipment[service={$service}].equipment equipment<incharge {$user}")->total_count()) {
                    $e->return_value = TRUE;
                }
                //自己
                if ($object->user->id == $user->id) {
                    $e->return_value = TRUE;
                }
                break;
            case '修改' :
                if (!in_array($object->status, [Service_Apply_Model::STATUS_APPLY])) {
                    $e->return_value = false;
                    return false;
                }
                //自己,待审批
                if ($object->user->id == $user->id) {
                    $e->return_value = TRUE;
                }
                break;
            case '删除' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务')) {
                    $e->return_value = TRUE;
                }
                //负责的服务
                if ($user->access('管理负责服务') && Q("{$user}<incharge service")->total_count()) {
                    $e->return_value = TRUE;
                }
                if (!in_array($object->status, [Service_Apply_Model::STATUS_APPLY, Service_Apply_Model::STATUS_PASS, Service_Apply_Model::STATUS_REJECT])) {
                    $e->return_value = false;
                    return;
                }

                break;
            case '列表审批' :
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务')) {
                    $e->return_value = TRUE;
                }
                //负责的服务
                if ($user->access('管理负责服务') && Q("{$user}<incharge service")->total_count()) {
                    $e->return_value = TRUE;
                }
            case '审批' :
                if ($object->status != Service_Apply_Model::STATUS_APPLY) {
                    $e->return_value = false;
                    return false;
                }
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->service->group)) {
                    $e->return_value = TRUE;
                }
                //负责的服务
                if ($user->access('管理负责服务') && Q("{$user}<incharge {$object->service}")->total_count()) {
                    $e->return_value = TRUE;
                }
                break;
            case '下载结果' :
                if ($object->status != Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = false;
                    return false;
                }
                if ($user->access('管理所有内容') || $user->access('管理所有服务')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('管理下属机构服务') && $user->group->id && $user->group->is_itself_or_ancestor_of($object->service->group)) {
                    $e->return_value = TRUE;
                }
                //负责的服务
                if ($user->access('管理负责服务') && Q("{$user}<incharge {$object->service}")->total_count()) {
                    $e->return_value = TRUE;
                }
                //自己的
                if ($object->user->id == $user->id) {
                    $e->return_value = TRUE;
                }
                break;
            default :
                break;

        }
    }

    static function apply_record_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '结束检测任务' :
                if ($object->apply->status == Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = false;
                    return false;
                }
                if (Q("{$object->equipment}<incharge {$user}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改检测结果' :
                $isadmin = $user->access('管理所有内容') || Q("{$object->service}<incharge {$user}")->total_count();
                $iseqincharge = Q("{$object->equipment}<incharge {$user}")->total_count();
                if ($isadmin && $object->status == Service_Apply_Record_Model::STATUS_TEST && $object->apply->status == Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = true;
                    return false;
                }
                if ($object->apply->status != Service_Apply_Model::STATUS_DONE
                    && $object->status == Service_Apply_Record_Model::STATUS_TEST
                    && $iseqincharge
                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看结果':
                $isadmin = $user->access('管理所有内容') || Q("{$object->service}<incharge {$user}")->total_count();
                if ($object->status != Service_Apply_Record_Model::STATUS_TEST) {
                    $e->return_value = false;
                    return false;
                }
                if (Q("{$object->equipment}<incharge {$user}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                if ($isadmin && $object->status == Service_Apply_Record_Model::STATUS_TEST && $object->apply->status == Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default :
                break;
        }
    }

    static function apply_attachments_ACL($e, $user, $perm, $object, $options)
    {

        if ($options['type'] != 'attachments') return;

        switch ($perm) {
            case "列表文件":
            case "下载文件":
            case "上传文件":
            case "修改文件":
            case "删除文件":
                $e->return_value = true;
                break;
            default:
                return;
        }

    }

    static function apply_record_attachments_ACL($e, $user, $perm, $object, $options)
    {

        if ($options['type'] != 'attachments') return;

        switch ($perm) {
            case "上传文件":
            case "修改文件":
            case "删除文件":
                if ($user->is_allowed_to('修改检测结果', $object)) {
                    $e->return_value = true;
                    return false;
                }
                if ($object->apply->status == Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = false;
                    return false;
                }
                $e->return_value = true;
                break;
            case "列表文件":
            case "下载文件":
                $e->return_value = true;
                break;
            default:
                return;
        }

    }

    static function eq_sample_ACL($e, $user, $perm, $object, $params)
    {
        switch ($perm) {
            case '修改':
            case '删除':
                $status = Service_Apply_Record_Model::STATUS_TEST;
                $record = Q("{$object} service_apply_record[status=$status]")->current();
                if ($record->id && $record->apply->status == Service_Apply_Model::STATUS_DONE) {
                    $e->return_value = false;
                    return false;
                }
                break;
        }
    }

    /**
     * @param $e
     * @param $object
     * @return void
     * 删除预约关联的项目检测
     */
    static function delete_apply_record($e,$object)
    {
        $applyRecord = Q("service_apply_record[apply_id={$object->id}]");
        $applyRecord->delete_all();
    }

}