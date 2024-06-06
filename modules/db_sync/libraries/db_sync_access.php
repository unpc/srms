<?php

class Db_Sync_Access
{
    public static function subsite_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '编辑':
            case '删除':
                if ($me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (
            (DB_SYNC::is_master() && Q("$user<incharge subsite")->total_count()) 
            || (DB_SYNC::is_slave() && Q("subsite[ref_no=" . LAB_ID . "]<incharge {$user}")->total_count())
        ){
            $perms['管理财务中心'] = 'on';
            $perms['添加/修改所有机构的仪器'] = 'on';
            $perms['修改所有仪器的使用设置'] = 'on';
            $perms['修改所有仪器的状态设置'] = 'on';
            $perms['修改所有仪器的用户标签'] = 'on';
            $perms['报废所有仪器'] = 'on';
            $perms['修改所有仪器的使用记录'] = 'on';
            $perms['管理所有仪器的培训记录'] = 'on';
            $perms['修改所有仪器的预约设置'] = 'on';
            $perms['为所有仪器添加预约'] = 'on';
            $perms['为所有仪器添加重复预约事件'] = 'on';
            $perms['修改所有仪器的预约'] = 'on';
            $perms['删除所有仪器的预约'] = 'on';
            $perms['修改所有仪器的送样设置'] = 'on';
            $perms['修改所有仪器的送样'] = 'on';
            $perms['修改所有仪器的计费设置'] = 'on';
            $perms['实时监控所有仪器'] = 'on';
            $perms['查看所有实验室成果'] = 'on';
            $perms['添加/修改所有实验室成果'] = 'on';
            $perms['管理所有环境监控对象'] = 'on';
            $perms['查看门禁模块'] = 'on';
            $perms['管理所有门禁'] = 'on';
            $perms['管理所有仪器的门禁'] = 'on';
            $perms['查看视频监控模块'] = 'on';
            $perms['管理视频设备'] = 'on';
            $perms['监控视频设备'] = 'on';
            $perms['查看环境监控模块'] = 'on';
            //地理监控
            $perms['添加/修改楼宇'] = 'on';
            $perms['调整GIS监控设备位置'] = 'on';
            $perms['查看仪器地图'] = 'on';
        }

        if (DB_SYNC::is_slave() && Q("subsite[ref_no=" . LAB_ID . "]<incharge {$user}")->total_count()) {
            $perms['管理公告'] = 'on';
            $perms['管理黑名单'] = 'on';
            $perms['添加/修改下属机构的仪器'] = 'on';
            $perms['查看所有仪器的使用收费情况'] = 'on';
            $perms['查看所有仪器的使用记录'] = 'on';
            //会议室
            $perms['添加/修改所有会议室'] = 'on';
            $perms['管理所有会议室的授权'] = 'on';
            $perms['管理所有会议室的预约'] = 'on';
            $perms['修改负责会议室信息'] = 'on';
            $perms['管理负责会议室的授权'] = 'on';
            $perms['管理负责会议室的预约'] = 'on';
            //大数据
            $perms['查看所有仪器的预约情况'] = 'on';
            $perms['查看所有仪器的送样情况'] = 'on';
            $perms['查看所有仪器的使用记录'] = 'on';
            $perms['查看所有仪器的故障情况'] = 'on';
            $perms['查看所有学院运行效益'] = 'on';
            $perms['查看所有仪器的使用汇总'] = 'on';
            $perms['查看所有仪器的效益统计'] = 'on';
            $perms['查看所有仪器的统计图表'] = 'on';
            $perms['查看所有机主服务绩效'] = 'on';
            $perms['查看所有课题组使用效益'] = 'on';
            $perms['管理实验室信息统计'] = 'on';
            $perms['科技部对接管理'] = 'on';
            $perms['管理科技厅上报信息'] = 'on';
            $perms['管理申报任务'] = 'on';
            $perms['填报仪器数据'] = 'on';
            $perms['审核仪器数据'] = 'on';
            $perms['查看所有仪器统计汇总信息'] = 'on';
        }
    }

    static function charge_confirm_ACL($e, $user, $action, $charge, $options)
    {
        if (DB_SYNC::is_slave()) {
            $e->return_value = false;
            return false;
        }
        return true;
    }

    static function object_ACL($e, $user, $perm, $object, $params)
    {
        if (DB_SYNC::is_master()
            && Q("$user<incharge subsite")->total_count()
            && !Q("$user<incharge subsite[ref_no={$object->site}]")->total_count()) {
            $e->return_value = false;
            return false;
        }
        return true;
    }

}