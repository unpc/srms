<?php

class CLI_Gapper_Sync
{
    /**
     *  gapper_group sync_lab
     * gapper_group sync_group
     * gapper_role sync_role
     * gapper_role sync_permission
     * gapper_role sync_role_permission
     * gapper_user sync_user
     */
    public static function sync_all()
    {
        if (People::perm_in_uno()) {
            Upgrader::echo_success("正在同步数据.");
            CLI_Gapper_Group::sync_group();
            CLI_Gapper_Group::sync_lab();
            CLI_Gapper_Role::sync_role();
            CLI_Gapper_Role::sync_permission();
            CLI_Gapper_Role::sync_role_permission();
            CLI_Gapper_User::sync_user();
            CLI_Gapper_Address::sync_address();
            Upgrader::echo_success("同步完成." . date('Y-m-d H:i:s', time()));
        }
    }

    //同步uno课题组、人员状态，并更新本地状态
    public static function update_local_status()
    {
        if (!People::perm_in_uno()) return;
        
        //更新课题组
        Q('gapper_groups')->delete_all();
        $pg = 1;
        $pp = 100;
        $groupRoot = Gateway::getRemoteGroupRoot();
        while (true) {

            $condition = [
                'pg' => $pg,
                'pp' => $pp,
                'group_id' => $groupRoot['id'],
                'type' => 'lab'
            ];
            $groups = Gateway::getRemoteGroupDescendants($condition);
            $total = $groups['total'];

            if(empty($groups['items'])) break;

            foreach ($groups['items'] as $remote_group) {
                $lab = O('gapper_groups', ['gapper_id' => $remote_group['id']]);
                $lab->gapper_id = $remote_group['id'];
                $lab->name = $remote_group['name'];
                $lab->type = $remote_group['type'];
                $lab->sync_time = time();
                if ($lab->save()) {
                    Upgrader::echo_title("[{$lab->id}]{$lab->name}");
                }
            }
            $pg++;
        }

        if(Q("gapper_groups[type=lab]")->total_count()){
            $localLabs = Q("lab[atime][gapper_id]");
            foreach($localLabs as $localLab){
                $connect = Q("gapper_groups[type=lab][gapper_id={$localLab->gapper_id}]")->current();
                if(!$connect->id){
                    //被删除了
                    Event::trigger('gapper_groups.lab.delete',$localLab);
                }
            }
        }

        //更新人员
        Q('gapper_user')->delete_all();
        //同步到本地
        $pg = 1;
        $pp = 100;
        while (true) {
            $condition = [
                'pg' => $pg,
                'pp' => $pp,
            ];
            $gapperUsers = Gapper_User::get_remote_user($condition);
            if (!isset($gapperUsers['items'])) {
                return false;
            }
            if (!count($gapperUsers['items'])) break;
            $gapperUsers = $gapperUsers['items'];
            foreach ($gapperUsers as $gapperUser) {
                $lu = O('gapper_user', ['gapper_id' => $gapperUser['id']]);
                $lu->ref_no = $gapperUser['ref_no'];
                $lu->email = $gapperUser['email'] ?: "";
                $lu->avatar = $gapperUser['avatar'] ?: "";
                $lu->name = $gapperUser['name'] ?: "";
                $lu->gapper_id = $gapperUser['id'] ?: "";
                $lu->save();
                Upgrader::echo_title("[{$lu->id}]{$lu->name}");
            }
            $pg += 1;
        }

        //本地循环
        $selector = 'user[atime][gapper_id]';
        $start = 0;
        $step = 10;
        while (true) {
            $localUsers = Q($selector)->limit($start, $step);
            if (!count($localUsers)) break;
            foreach ($localUsers as $user) {
                $gu = O('gapper_user', ["gapper_id" => $user->gapper_id]);
                if (!$gu->id) {
                    //被删除了
                    Event::trigger('gapper_user.user.delete',$user);
                }
            }
            $start += $step;
        }

        echo 'done', "\n";
    }

    public static function push_all(){
        if (People::perm_in_uno()) {
            Upgrader::echo_success("正在推送数据至uno.");
            CLI_Gapper_Group::push_group();
            CLI_Gapper_Group::push_lab();
            // CLI_Gapper_Role::push_role();
            CLI_Gapper_User::push_user();
            Upgrader::echo_success("推送完成." . date('Y-m-d H:i:s', time()));
        }
    }
    
}
