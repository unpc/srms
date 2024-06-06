<?php

class Gapper_Update
{

    static public function after_remote_lab_delete($e, $localLab)
    {

        if(!$localLab->id) return;

        if (Module::is_installed('billing')) {
            foreach (Q("billing_account[lab={$localLab}]") as $account) {
                if (!Q("$account billing_transaction")->total_count()){
                    $account->delete();
                }
            }
        }
        
        Log::add(sprintf('[labs] 课题组gapper_id[%s]不存在,导致lims课题组%s[%d]变为未激活', $localLab->gapper_id ,$localLab->name, $localLab->id), 'gapper');
        $localLab->atime = 0;
        $localLab->unactive_reason = 'uno删除课题组,本地自动未激活';
        $localLab->save();
        
        foreach(Q("{$localLab} user") as $connectUser){
            $connectUser->disconnect($localLab);
            Q("{$localLab}<pi user")->total_count() ? $connectUser->disconnect($localLab,'pi') : '';
            Log::add(sprintf('[labs] 课题组gapper_id[%s]不存在,导致lims课题组%s[%d]人员[%s]关系被删除', $localLab->gapper_id ,$localLab->name, $localLab->id,$connectUser->id), 'gapper');
        }

    }

    static public function after_remote_user_delete($e, $user)
    {
        $now = time();
        if(!$user->id) return;
        $oldid = $user->gapper_id;

        $reservs = Q("eq_reserv[user={$user}][dtstart>{$now}]");
        if($reservs->total_count()){
            foreach($reservs as $reserv){
                $reserv->component->delete();
                $reserv->delete();
                Log::add(sprintf('[accounts] 用户gapper_id[%s]不存在,导致lims用户%s[%d]的预约%s-%s被删除', $oldid ,$user->name, $user->id, date('Y-m-d H:i:s',$reserv->dtstart), date('Y-m-d H:i:s',$reserv->dtend)), 'gapper');
            }
        }

        $samples = Q("eq_sample[sender={$user}][status=1,2]]");
        if($samples->total_count()){
            foreach($samples as $sample){
                $sample->delete();
                Log::add(sprintf('[accounts] 用户gapper_id[%s]不存在,导致lims用户%s[%d]的送样%s被删除', $oldid ,$user->name, $user->id,$sample->id), 'gapper');
            }
        }

        if(!Q("eq_record[user={$user}]")->total_count()
         && !Q("eq_reserv[user={$user}]")->total_count()
         && !Q("eq_sample[sender={$user}]")->total_count()
         && !Q("eq_charge[user={$user}]")->total_count()
        ){
            $user->delete();
            Log::add(sprintf('[accounts] 用户gapper_id[%s]不存在,导致lims用户%s[%d]被删除', $oldid ,$user->name, $user->id), 'gapper');
            return ;
        }

        $user->atime = 0;
        $user->gapper_id = 0;
        $user->unactive_reason = "gapper用户被删除,自动变为未激活用户";
        $user->save();
        Log::add(sprintf('[accounts] 用户gapper_id[%s]不存在且有记录,导致lims用户%s[%d]被设置为未激活', $oldid ,$user->name, $user->id), 'gapper');


    }

}
